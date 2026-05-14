<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$recruiterId = $_SESSION['user_id'];

// Get unique applications this recruiter has messages for, or assigned applicants
$stmt = $pdo->prepare("
    SELECT a.id as app_id, u.full_name as applicant_name, jp.title as job_title,
    (SELECT message_body FROM messages WHERE application_id = a.id ORDER BY sent_at DESC LIMIT 1) as last_msg,
    (SELECT sent_at FROM messages WHERE application_id = a.id ORDER BY sent_at DESC LIMIT 1) as last_sent,
    (SELECT COUNT(*) FROM messages WHERE application_id = a.id AND receiver_id = ? AND is_read = 0) as unread
    FROM applications a
    JOIN users u ON a.applicant_id = u.id
    JOIN job_posts jp ON a.job_post_id = jp.id
    WHERE a.recruiter_id = ?
    ORDER BY last_sent DESC
");
$stmt->execute([$recruiterId, $recruiterId]);
$threads = $stmt->fetchAll();

$pageTitle = "Messages";
include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav"><div class="page-title">Messages</div><div class="top-nav-actions"></div></div>
    <div class="content-area">

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Applicant (Job)</th>
                        <th>Latest Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($threads) === 0): ?>
                        <tr><td colspan="5">No conversations yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($threads as $t): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($t['applicant_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($t['job_title']) ?></small></td>
                        <td><?= htmlspecialchars($t['last_msg'] ?? 'No messages yet') ?></td>
                        <td><?= $t['last_sent'] ? date('M d, H:i', strtotime($t['last_sent'])) : '-' ?></td>
                        <td>
                            <?php if ($t['unread'] > 0): ?>
                                <span class="badge badge-high"><?= $t['unread'] ?> New</span>
                            <?php else: ?>
                                <span class="badge badge-low">Read</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="thread.php?app_id=<?= $t['app_id'] ?>" class="btn btn-outline" style="padding: 0.2rem 0.6rem;">Open Chat</a>
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
