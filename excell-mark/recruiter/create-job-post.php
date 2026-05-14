<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$recruiterId = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $slots = (int)($_POST['slots'] ?? 1);
    $deadline = $_POST['deadline'] ?? null;

    if (empty($title)) {
        $error = "Job title is required.";
    } elseif ($slots < 1) {
        $error = "At least 1 slot is required.";
    } else {
        $deadlineVal = !empty($deadline) ? $deadline : null;

        $stmt = $pdo->prepare("INSERT INTO job_posts (title, description, slots, deadline, assigned_recruiter_id, workload_status, is_active, created_at) VALUES (?, ?, ?, ?, ?, 'Low', 1, NOW())");
        if ($stmt->execute([$title, $description, $slots, $deadlineVal, $recruiterId])) {
            $success = "Job post created successfully!";
        } else {
            $error = "Failed to create job post. Please try again.";
        }
    }
}

$pageTitle = "Create Job Post";
include '../includes/header.php';
?>

<style>
    .create-form-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 2rem;
        max-width: 720px;
    }
    .create-form-card .form-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid var(--border-color);
    }
    .create-form-card .form-header .icon-circle {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: rgba(59, 130, 246, 0.1);
        color: var(--color-recruiter);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }
    .create-form-card .form-header h2 {
        font-size: 1.1rem; font-weight: 600; margin-bottom: 0.15rem;
    }
    .create-form-card .form-header p {
        font-size: 0.8rem; color: var(--text-muted);
    }

    .form-group textarea.form-control {
        min-height: 140px;
        resize: vertical;
    }
    .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .form-hint {
        font-size: 0.72rem;
        color: var(--text-muted);
        margin-top: 0.35rem;
    }
    .form-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--border-color);
    }
    .btn-recruiter {
        background: var(--color-recruiter);
        color: #fff;
        border: none;
    }
    .btn-recruiter:hover {
        background: #2563eb;
    }

    .char-counter {
        font-size: 0.7rem;
        color: var(--text-muted);
        text-align: right;
        margin-top: 0.25rem;
    }

    @media (max-width: 600px) {
        .form-row-2 { grid-template-columns: 1fr; }
        .create-form-card { padding: 1.25rem; }
    }
</style>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav">
        <div class="page-title">
            <a href="dashboard.php" style="color: var(--text-muted); margin-right: 0.5rem;"><i class="fa-solid fa-arrow-left"></i></a>
            Create Job Post
        </div>
        <div class="top-nav-actions">
            <a href="my-posts.php" class="btn btn-outline" style="padding: 0.4rem 0.8rem;">
                <i class="fa-solid fa-file-invoice"></i> My Posts
            </a>
        </div>
    </div>

    <div class="content-area">
        <?php if ($success): ?>
            <div class="flash-message flash-success" style="max-width: 720px; margin-bottom: 1.5rem;">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
                <a href="my-posts.php" style="margin-left: auto; color: var(--status-success); font-weight: 500; text-decoration: underline;">View My Posts →</a>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash-message flash-error" style="max-width: 720px; margin-bottom: 1.5rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="create-form-card">
            <div class="form-header">
                <div class="icon-circle"><i class="fa-solid fa-plus"></i></div>
                <div>
                    <h2>New Job Posting</h2>
                    <p>Fill in the details below to create a new job post for applicants.</p>
                </div>
            </div>

            <form method="POST" action="" id="jobPostForm">
                <div class="form-group">
                    <label for="title">Job Title <span style="color: var(--status-danger);">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Customer Service Representative" required
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="description">Job Description</label>
                    <textarea name="description" id="description" class="form-control" placeholder="Describe the job responsibilities, qualifications, and other relevant details..." maxlength="2000"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <div class="char-counter"><span id="charCount">0</span> / 2,000</div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="slots">Open Slots <span style="color: var(--status-danger);">*</span></label>
                        <input type="number" name="slots" id="slots" class="form-control" min="1" max="500" value="<?= htmlspecialchars($_POST['slots'] ?? '1') ?>" required>
                        <div class="form-hint">Number of positions available</div>
                    </div>
                    <div class="form-group">
                        <label for="deadline">Application Deadline</label>
                        <input type="date" name="deadline" id="deadline" class="form-control"
                               value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>"
                               min="<?= date('Y-m-d') ?>">
                        <div class="form-hint">Leave blank for open-ended</div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-recruiter">
                        <i class="fa-solid fa-paper-plane"></i> Publish Job Post
                    </button>
                    <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Character counter for description
    const desc = document.getElementById('description');
    const counter = document.getElementById('charCount');
    function updateCount() { counter.textContent = desc.value.length; }
    desc.addEventListener('input', updateCount);
    updateCount();
</script>

<?php include '../includes/footer.php'; ?>
