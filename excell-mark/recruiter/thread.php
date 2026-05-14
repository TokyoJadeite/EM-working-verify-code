<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$recruiterId = $_SESSION['user_id'];
$appId = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;

if (!$appId) {
    header("Location: messages.php");
    exit;
}

// Get App Info
$stmt = $pdo->prepare("SELECT a.*, u.full_name as applicant_name, u.id as applicant_id, jp.title as job_title FROM applications a JOIN users u ON a.applicant_id = u.id JOIN job_posts jp ON a.job_post_id = jp.id WHERE a.id = ? AND a.recruiter_id = ?");
$stmt->execute([$appId, $recruiterId]);
$app = $stmt->fetch();

if (!$app) {
    die("Invalid application or permission denied.");
}

// Mark messages as read
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE application_id = ? AND receiver_id = ?")->execute([$appId, $recruiterId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message_body'])) {
    $msgBody = $_POST['message_body'];
    $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, application_id, message_body) VALUES (?, ?, ?, ?)")->execute([$recruiterId, $app['applicant_id'], $appId, $msgBody]);
    
    // Notify applicant
    $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, 'You have a new message from your recruiter.', 'message')")->execute([$app['applicant_id']]);
    
    header("Location: thread.php?app_id=$appId");
    exit;
}

// Fetch Messages
$stmt = $pdo->prepare("SELECT * FROM messages WHERE application_id = ? ORDER BY sent_at ASC");
$stmt->execute([$appId]);
$messages = $stmt->fetchAll();

$pageTitle = "Chat - " . htmlspecialchars($app['applicant_name']);
include '../includes/header.php';
?>
<style>
.chat-box { height: 400px; overflow-y: auto; padding: 1rem; background: var(--bg-dark); border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--border-color); }
.msg { margin-bottom: 1rem; max-width: 75%; padding: 0.8rem 1rem; border-radius: 8px; clear: both; }
.msg-sent { background: var(--color-recruiter); color: #fff; float: right; border-bottom-right-radius: 0; }
.msg-received { background: var(--bg-surface-hover); color: var(--text-primary); float: left; border-bottom-left-radius: 0; }
.msg-time { font-size: 0.7rem; opacity: 0.7; margin-top: 0.3rem; text-align: right; }
</style>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav"><div class="page-title">Conversation with <?= htmlspecialchars($app['applicant_name']) ?></div><div class="top-nav-actions"><a href="messages.php" class="btn btn-outline">Back to Inbox</a></div></div>
    <div class="content-area">
    
    <p style="color: var(--text-secondary); margin-bottom: 1rem;">Regarding: <strong><?= htmlspecialchars($app['job_title']) ?></strong></p>

    <div class="card">
        <div class="chat-box" id="chatBox">
            <?php if (count($messages) === 0): ?>
                <p style="text-align: center; color: var(--text-muted); margin-top: 2rem;">No messages yet. Send a message to start the conversation.</p>
            <?php endif; ?>
            <?php foreach ($messages as $m): ?>
                <div class="msg <?= $m['sender_id'] == $recruiterId ? 'msg-sent' : 'msg-received' ?>">
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
// Auto scroll to bottom
document.getElementById('chatBox').scrollTop = document.getElementById('chatBox').scrollHeight;
</script>

<?php include '../includes/footer.php'; ?>
