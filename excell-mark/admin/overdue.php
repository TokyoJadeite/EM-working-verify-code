<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

// Detect overdue applications globally
$thresholdDays = 3;
$pdo->exec("UPDATE applications SET is_overdue = 1 WHERE stage = 'Pending' AND applied_at < NOW() - INTERVAL $thresholdDays DAY");

// Handle escalation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'escalate' && !empty($_POST['app_id'])) {
        $appId = $_POST['app_id'];
        // Update flag and notify assigned recruiter
        $pdo->prepare("UPDATE applications SET is_flagged = 1 WHERE id = ?")->execute([$appId]);
        
        $stmt = $pdo->prepare("SELECT recruiter_id, applicant_id FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $appData = $stmt->fetch();
        
        if ($appData && $appData['recruiter_id']) {
            $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'alert')")
                ->execute([$appData['recruiter_id'], "Admin escalated an overdue application ID #{$appId}"]);
        }
    }
}

// Fetch overdue
$stmt = $pdo->query("
    SELECT a.*, u1.full_name as applicant_name, jp.title as job_title, u2.full_name as recruiter_name
    FROM applications a
    JOIN users u1 ON a.applicant_id = u1.id
    JOIN job_posts jp ON a.job_post_id = jp.id
    LEFT JOIN users u2 ON a.recruiter_id = u2.id
    WHERE a.is_overdue = 1 AND a.stage = 'Pending'
    ORDER BY a.applied_at ASC
");
$overdueApps = $stmt->fetchAll();

$pageTitle = "Overdue Monitor";
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
    
    
    <div class="card" style="margin-bottom: 2rem; background: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.3);">
        <p style="color: var(--status-high);"><i class="fa-solid fa-triangle-exclamation"></i> Applications stuck in <strong>Pending</strong> stage for more than 3 days are flagged here.</p>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Job Applied</th>
                        <th>Assigned To</th>
                        <th>Applied Date</th>
                        <th>Days Pending</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($overdueApps) === 0): ?>
                        <tr><td colspan="6">No overdue applications. Great job!</td></tr>
                    <?php endif; ?>
                    <?php foreach ($overdueApps as $app): ?>
                    <?php 
                        $daysPending = floor((time() - strtotime($app['applied_at'])) / (60 * 60 * 24));
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app['applicant_name']) ?></strong></td>
                        <td><?= htmlspecialchars($app['job_title']) ?></td>
                        <td><?= htmlspecialchars($app['recruiter_name'] ?? 'Unassigned') ?></td>
                        <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                        <td style="color: var(--status-high); font-weight: bold;"><?= $daysPending ?> days</td>
                        <td>
                            <?php if ($app['is_flagged']): ?>
                                <span class="badge badge-high">Escalated</span>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="escalate">
                                    <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                    <button type="submit" class="btn btn-outline" style="color: var(--status-high); border-color: var(--status-high); padding: 0.2rem 0.6rem; font-size: 0.8rem;">Escalate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
