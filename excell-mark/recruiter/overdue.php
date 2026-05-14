<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$recruiterId = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'advance') {
    $appId = $_POST['app_id'];
    $stmt = $pdo->prepare("UPDATE applications SET stage = 'Reviewed', reviewed_at = NOW(), is_overdue = 0 WHERE id = ? AND recruiter_id = ?");
    if ($stmt->execute([$appId, $recruiterId])) {
        $success = "Applicant advanced to Reviewed and removed from Overdue.";
    }
}

$stmt = $pdo->prepare("SELECT a.*, u.full_name as applicant_name, jp.title as job_title FROM applications a JOIN users u ON a.applicant_id = u.id JOIN job_posts jp ON a.job_post_id = jp.id WHERE a.recruiter_id = ? AND a.stage = 'Pending' AND a.is_overdue = 1 ORDER BY a.applied_at ASC");
$stmt->execute([$recruiterId]);
$overdueApps = $stmt->fetchAll();

$pageTitle = "Overdue Alerts";
include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav"><div class="page-title">Overdue Applications</div><div class="top-nav-actions"></div></div>
    <div class="content-area">

    <?php if ($success): ?><div class="flash-message flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card" style="margin-bottom: 2rem; background: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.3);">
        <p style="color: var(--status-high);"><i class="fa-solid fa-triangle-exclamation"></i> These applications have been pending for over 3 days.</p>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Job Applied</th>
                        <th>Applied Date</th>
                        <th>Days Pending</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($overdueApps) === 0): ?>
                        <tr><td colspan="5">No overdue applications. Great job!</td></tr>
                    <?php endif; ?>
                    <?php foreach ($overdueApps as $app): ?>
                    <?php $daysPending = floor((time() - strtotime($app['applied_at'])) / (60 * 60 * 24)); ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app['applicant_name']) ?></strong></td>
                        <td><?= htmlspecialchars($app['job_title']) ?></td>
                        <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                        <td style="color: var(--status-high); font-weight: bold;"><?= $daysPending ?> days</td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="advance">
                                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                <button type="submit" class="btn btn-outline" style="padding: 0.2rem 0.6rem; font-size: 0.8rem;">Advance</button>
                            </form>
                            <a href="thread.php?app_id=<?= $app['id'] ?>" class="btn btn-primary" style="padding: 0.2rem 0.6rem; font-size: 0.8rem;">Follow Up</a>
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
