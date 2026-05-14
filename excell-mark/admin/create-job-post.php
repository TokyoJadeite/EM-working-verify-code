<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $slots = (int)$_POST['slots'];
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $recruiter_id = !empty($_POST['recruiter_id']) ? $_POST['recruiter_id'] : null;

    if (empty($title) || empty($slots)) {
        $error = "Title and slots are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO job_posts (title, description, slots, deadline, assigned_recruiter_id, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $slots, $deadline, $recruiter_id, $_SESSION['user_id']])) {
            $success = "Job post created successfully.";
            
            // Notification to recruiter if assigned
            if ($recruiter_id) {
                $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'assignment')");
                $notif->execute([$recruiter_id, "You have been assigned a new job post: $title"]);
            }
            
            // Notify all applicants about new job opportunity
            $applicants = $pdo->query("SELECT id FROM users WHERE role = 'applicant'");
            $notifApplicant = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'new_job')");
            foreach ($applicants as $a) {
                $notifApplicant->execute([$a['id'], "New job opportunity: $title — Apply now!"]);
            }
        } else {
            $error = "Failed to create job post.";
        }
    }
}

// Fetch recruiters for dropdown
$recruiters = $pdo->query("SELECT id, full_name FROM users WHERE role = 'recruiter'")->fetchAll();

$pageTitle = "Create Job Post";
include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-admin.php'; ?>
    <div class="top-nav">
        <div class="page-title">
            <?= $pageTitle ?? 'Admin' ?>
        </div>
    </div>
    <div class="content-area">
    

    <?php if ($error): ?><div class="flash-message flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="flash-message flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card" style="max-width: 800px;">
        <form method="POST">
            <div class="form-group">
                <label>Job Title *</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Job Description</label>
                <textarea name="description" class="form-control" rows="6"></textarea>
            </div>
            
            <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Available Slots *</label>
                    <input type="number" name="slots" class="form-control" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Deadline (Optional)</label>
                    <input type="date" name="deadline" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label>Assign to Recruiter</label>
                <select name="recruiter_id" class="form-control">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($recruiters as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-admin" style="margin-top: 1rem;">Create Post</button>
        </form>
    </div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
