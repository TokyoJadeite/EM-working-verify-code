<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$recruiterId = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'advance') {
    $appId = $_POST['app_id'];
    
    $stmt = $pdo->prepare("UPDATE applications SET stage = 'Reviewed', reviewed_at = NOW() WHERE id = ? AND recruiter_id = ?");
    if ($stmt->execute([$appId, $recruiterId])) {
        $success = "Applicant advanced to Reviewed stage.";
        
        // Notify applicant
        $appData = $pdo->prepare("SELECT applicant_id, job_post_id FROM applications WHERE id = ?");
        $appData->execute([$appId]);
        $data = $appData->fetch();
        if ($data) {
            $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, 'Your application has been Reviewed by the recruiter.', 'update')")->execute([$data['applicant_id']]);
        }
    } else {
        $error = "Failed to advance applicant.";
    }
}

$jobFilter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

$query = "SELECT a.*, u.full_name as applicant_name, jp.title as job_title FROM applications a JOIN users u ON a.applicant_id = u.id JOIN job_posts jp ON a.job_post_id = jp.id WHERE a.recruiter_id = ? AND a.stage = 'Pending'";
$params = [$recruiterId];

if ($jobFilter) {
    $query .= " AND a.job_post_id = ?";
    $params[] = $jobFilter;
}

$query .= " ORDER BY a.applied_at ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applicants = $stmt->fetchAll();

$pageTitle = "Pending Applicants";
include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav"><div class="page-title">Pending Applications</div><div class="top-nav-actions"><?php if ($jobFilter): ?>
            <a href="pending.php" class="btn btn-outline">Clear Filter</a>
        <?php endif; ?></div></div>
    <div class="content-area">

    <?php if ($error): ?><div class="flash-message flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="flash-message flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Job Applied</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($applicants) === 0): ?>
                        <tr><td colspan="5">No pending applications found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($applicants as $app): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app['applicant_name']) ?></strong></td>
                        <td><?= htmlspecialchars($app['job_title']) ?></td>
                        <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                        <td>
                            <?php if ($app['is_overdue']): ?>
                                <span class="badge badge-high">Overdue</span>
                            <?php else: ?>
                                <span class="badge badge-mod">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="advance">
                                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                <button type="submit" class="btn btn-primary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">Advance to Reviewed</button>
                            </form>
                            <a href="documents.php?applicant_id=<?= $app['applicant_id'] ?>" class="btn btn-outline" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;"><i class="fa-solid fa-folder-open"></i> Docs</a>
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
