<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('applicant');

$applicantId = $_SESSION['user_id'];

if (isset($_GET['mark_all']) && $_GET['mark_all'] == 1) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$applicantId]);
    header("Location: notifications.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$applicantId]);
$notifications = $stmt->fetchAll();

$pageTitle = "Notifications";
include '../includes/header.php';
include '../includes/nav-applicant.php';
?>

<div class="main-content">
    <div class="top-header">
        <h1>Notifications</h1>
        <?php if (count($notifications) > 0): ?>
            <a href="?mark_all=1" class="btn btn-outline">Mark All as Read</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <?php if (count($notifications) === 0): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 2rem;">You have no notifications.</p>
        <?php else: ?>
            <ul style="list-style: none;">
                <?php foreach ($notifications as $n): ?>
                <li style="padding: 1rem; border-bottom: 1px solid var(--border-color); <?= $n['is_read'] ? 'opacity: 0.7;' : 'background: rgba(167, 139, 250, 0.05);' ?>">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <strong style="color: <?= $n['is_read'] ? 'var(--text-secondary)' : 'var(--text-primary)' ?>"><?= htmlspecialchars($n['message']) ?></strong>
                        <?php if (!$n['is_read']): ?>
                            <span class="badge badge-admin" style="font-size: 0.7rem;">New</span>
                        <?php endif; ?>
                    </div>
                    <small style="color: var(--text-muted);"><i class="fa-regular fa-clock"></i> <?= date('M d, Y g:i A', strtotime($n['created_at'])) ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php 
// Mark as read when viewing page
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$applicantId]);
include '../includes/footer.php'; 
?>
