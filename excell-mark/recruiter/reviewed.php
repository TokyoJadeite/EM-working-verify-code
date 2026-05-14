<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$recruiterId = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'advance') {
    $appId = $_POST['app_id'];
    
    $stmt = $pdo->prepare("UPDATE applications SET stage = 'Shortlisted', shortlisted_at = NOW() WHERE id = ? AND recruiter_id = ?");
    if ($stmt->execute([$appId, $recruiterId])) {
        $success = "Applicant advanced to Shortlisted stage.";
        $appData = $pdo->prepare("SELECT applicant_id FROM applications WHERE id = ?");
        $appData->execute([$appId]);
        $data = $appData->fetch();
        if ($data) {
            $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, 'Congratulations! Your application has been Shortlisted.', 'update')")->execute([$data['applicant_id']]);
        }
    }
}

$stmt = $pdo->prepare("SELECT a.*, u.full_name as applicant_name, jp.title as job_title FROM applications a JOIN users u ON a.applicant_id = u.id JOIN job_posts jp ON a.job_post_id = jp.id WHERE a.recruiter_id = ? AND a.stage = 'Reviewed' ORDER BY a.reviewed_at DESC");
$stmt->execute([$recruiterId]);
$applicants = $stmt->fetchAll();

$pageTitle = "Reviewed Applicants";
include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav"><div class="page-title">Reviewed Applications</div><div class="top-nav-actions"></div></div>
    <div class="content-area">

    <?php if ($success): ?><div class="flash-message flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Job Applied</th>
                        <th>Reviewed Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($applicants) === 0): ?>
                        <tr><td colspan="4">No reviewed applications found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($applicants as $app): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app['applicant_name']) ?></strong></td>
                        <td><?= htmlspecialchars($app['job_title']) ?></td>
                        <td><?= date('M d, Y', strtotime($app['reviewed_at'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="advance">
                                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                <button type="submit" class="btn btn-primary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">Advance to Shortlisted</button>
                            </form>
                            <a href="messages.php" class="btn btn-outline" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;"><i class="fa-solid fa-envelope"></i> Message</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
