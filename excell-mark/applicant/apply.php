<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('applicant');

$applicantId = $_SESSION['user_id'];
$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if (!$jobId) {
    header("Location: jobs.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM job_posts WHERE id = ? AND is_active = 1");
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    die("Job post not found or closed.");
}

$error = '';
$success = '';

// Check if already applied
$check = $pdo->prepare("SELECT id FROM applications WHERE applicant_id = ? AND job_post_id = ?");
$check->execute([$applicantId, $jobId]);
if ($check->fetch()) {
    $error = "You have already applied for this position.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Insert application
    $recruiterId = $job['assigned_recruiter_id']; // Can be null
    
    $insert = $pdo->prepare("INSERT INTO applications (applicant_id, job_post_id, recruiter_id) VALUES (?, ?, ?)");
    if ($insert->execute([$applicantId, $jobId, $recruiterId])) {
        $success = "Application submitted successfully!";
        
        // Notify recruiter if assigned
        if ($recruiterId) {
            $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'application')")
                ->execute([$recruiterId, "New application received for: {$job['title']}"]);
        }
    } else {
        $error = "Failed to submit application.";
    }
}

$pageTitle = "Apply - " . htmlspecialchars($job['title']);
include '../includes/header.php';
include '../includes/nav-applicant.php';
?>

<div class="main-content">
    <div class="top-header">
        <h1>Submit Application</h1>
        <a href="jobs.php" class="btn btn-outline">Back to Jobs</a>
    </div>

    <?php if ($error): ?><div class="flash-message flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="flash-message flash-success"><?= htmlspecialchars($success) ?> <a href="my-application.php" style="font-weight:bold; color:var(--status-low);">View Application Details</a></div><?php endif; ?>

    <div class="card" style="max-width: 800px;">
        <h2 style="margin-bottom: 1rem; color: var(--color-recruiter);"><?= htmlspecialchars($job['title']) ?></h2>
        <p style="white-space: pre-wrap; color: var(--text-secondary); margin-bottom: 2rem;"><?= htmlspecialchars($job['description']) ?></p>
        
        <div style="background: var(--bg-dark); padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid var(--border-color);">
            <h4 style="margin-bottom: 0.5rem;">Ready to Apply?</h4>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;">Make sure you have uploaded your latest resume and ID in the <a href="documents.php" style="color: var(--color-admin);">Documents</a> section before applying.</p>
            
            <?php if (!$error && !$success): ?>
            <form method="POST">
                <button type="submit" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.8rem 2rem;">Confirm & Submit Application</button>
            </form>
            <?php else: ?>
                <button class="btn btn-outline" disabled>Apply Now</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
