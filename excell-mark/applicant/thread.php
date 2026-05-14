<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('applicant');

$applicantId = $_SESSION['user_id'];
$appId = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;

if (!$appId) {
    header("Location: messages.php");
    exit;
}

// Get App Info
$stmt = $pdo->prepare("SELECT a.*, u.full_name as recruiter_name, u.id as recruiter_user_id, jp.title as job_title FROM applications a LEFT JOIN users u ON a.recruiter_id = u.id JOIN job_posts jp ON a.job_post_id = jp.id WHERE a.id = ? AND a.applicant_id = ?");
$stmt->execute([$appId, $applicantId]);
$app = $stmt->fetch();

if (!$app || !$app['recruiter_id']) {
    die("Invalid application or recruiter not yet assigned.");
}

// Mark messages as read
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE application_id = ? AND receiver_id = ?")->execute([$appId, $applicantId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message_body'])) {
    $msgBody = $_POST['message_body'];
    $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, application_id, message_body) VALUES (?, ?, ?, ?)")->execute([$applicantId, $app['recruiter_id'], $appId, $msgBody]);
    
    // Notify recruiter
    $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, 'You have a new message from an applicant.', 'message')")->execute([$app['recruiter_id']]);
    
    header("Location: thread.php?app_id=$appId");
    exit;
}

// Fetch Messages
$stmt = $pdo->prepare("SELECT * FROM messages WHERE application_id = ? ORDER BY sent_at ASC");
$stmt->execute([$appId]);
$messages = $stmt->fetchAll();

$pageTitle = "Chat - " . htmlspecialchars($app['recruiter_name']);
include '../includes/header.php';
include '../includes/nav-applicant.php';
?>
<style>
.chat-box { height: 400px; overflow-y: auto; padding: 1rem; background: var(--bg-dark); border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--border-color); }
.msg { margin-bottom: 1rem; max-width: 75%; padding: 0.8rem 1rem; border-radius: 8px; clear: both; }
.msg-sent { background: var(--color-admin); color: #fff; float: right; border-bottom-right-radius: 0; }
.msg-received { background: var(--bg-surface-hover); color: var(--text-primary); float: left; border-bottom-left-radius: 0; }
.msg-time { font-size: 0.7rem; opacity: 0.7; margin-top: 0.3rem; text-align: right; }
</style>

<div class="main-content">
    <div class="top-header">
        <h1>Conversation with <?= htmlspecialchars($app['recruiter_name']) ?></h1>
        <a href="messages.php" class="btn btn-outline">Back to Inbox</a>
    </div>
    
    <p style="color: var(--text-secondary); margin-bottom: 1rem;">Regarding: <strong><?= htmlspecialchars($app['job_title']) ?></strong></p>

    <div class="card">
        <div class="chat-box" id="chatBox">
            <?php if (count($messages) === 0): ?>
                <p style="text-align: center; color: var(--text-muted); margin-top: 2rem;">No messages yet. Send a message to start the conversation.</p>
            <?php endif; ?>
            <?php foreach ($messages as $m): ?>
                <div class="msg <?= $m['sender_id'] == $applicantId ? 'msg-sent' : 'msg-received' ?>">
                    <div><?= nl2br(htmlspecialchars($m['message_body'])) ?></div>
                    <div class="msg-time"><?= date('M d, H:i', strtotime($m['sent_at'])) ?></div>
                </div>
            <?php endforeach; ?>
            <div style="clear: both;"></div>
        </div>
        
        <form method="POST" style="display: flex; gap: 1rem;">
            <input type="text" name="message_body" class="form-control" placeholder="Type your message here..." required autocomplete="off">
            <button type="submit" class="btn btn-primary" style="white-space: nowrap;"><i class="fa-solid fa-paper-plane"></i> Send</button>
        </form>
    </div>
</div>

<script>
document.getElementById('chatBox').scrollTop = document.getElementById('chatBox').scrollHeight;
</script>

<?php include '../includes/footer.php'; ?>
