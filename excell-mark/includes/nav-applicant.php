<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$_userId = $_SESSION['user_id'];
$_userName = $_SESSION['full_name'];
$_initials = strtoupper(substr($_userName, 0, 1) . (strpos($_userName, ' ') ? substr($_userName, strpos($_userName, ' ') + 1, 1) : ''));

// Dynamic notification count
$_notifStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$_notifStmt->execute([$_userId]);
$_unreadNotifs = $_notifStmt->fetchColumn();

// Unread messages count
$_msgStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$_msgStmt->execute([$_userId]);
$_unreadMsgs = $_msgStmt->fetchColumn();
?>
<div style="height: 60px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; padding: 0 2rem; background: var(--bg-surface); position: sticky; top: 0; z-index: 10;">
    <div class="logo-text" style="font-size: 1.5rem; margin-right: 3rem;">EXCELL<span style="color: var(--color-applicant);">MARK</span></div>
    
    <div style="display: flex; gap: 2rem;">
        <a href="home.php" style="font-size: 0.85rem; font-weight: <?= $currentPage == 'home.php' ? '600' : '400' ?>; color: <?= $currentPage == 'home.php' ? 'var(--color-applicant)' : 'var(--text-secondary)' ?>;">Home</a>
        <a href="jobs.php" style="font-size: 0.85rem; font-weight: <?= $currentPage == 'jobs.php' ? '600' : '400' ?>; color: <?= $currentPage == 'jobs.php' ? 'var(--color-applicant)' : 'var(--text-secondary)' ?>;">Browse Jobs</a>
        <a href="my-application.php" style="font-size: 0.85rem; font-weight: <?= $currentPage == 'my-application.php' ? '600' : '400' ?>; color: <?= $currentPage == 'my-application.php' ? 'var(--color-applicant)' : 'var(--text-secondary)' ?>;">My Application</a>
        <a href="messages.php" style="font-size: 0.85rem; font-weight: <?= $currentPage == 'messages.php' ? '600' : '400' ?>; color: <?= $currentPage == 'messages.php' ? 'var(--color-applicant)' : 'var(--text-secondary)' ?>; display: flex; align-items: center; gap: 0.3rem;">Messages<?php if($_unreadMsgs > 0): ?><span style="background: var(--status-danger); color: #fff; font-size: 0.6rem; padding: 0.1rem 0.35rem; border-radius: 99px; font-weight: 600;"><?= $_unreadMsgs ?></span><?php endif; ?></a>
        <a href="documents.php" style="font-size: 0.85rem; font-weight: <?= $currentPage == 'documents.php' ? '600' : '400' ?>; color: <?= $currentPage == 'documents.php' ? 'var(--color-applicant)' : 'var(--text-secondary)' ?>;">Documents</a>
    </div>
    
    <div style="margin-left: auto; display: flex; align-items: center; gap: 1rem;">
        <a href="notifications.php" style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-surface-light); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; position: relative;">
            <i class="fa-solid fa-bell" style="color: <?= $_unreadNotifs > 0 ? 'var(--status-warning)' : 'var(--text-muted)' ?>;"></i>
            <?php if($_unreadNotifs > 0): ?>
            <div style="position: absolute; top: -4px; right: -4px; min-width: 16px; height: 16px; background: var(--status-danger); border-radius: 50%; font-size: 0.55rem; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; padding: 0 3px;"><?= $_unreadNotifs > 9 ? '9+' : $_unreadNotifs ?></div>
            <?php endif; ?>
        </a>
        <div class="avatar" style="background: rgba(167, 139, 250, 0.2); color: var(--color-admin); border: 1px solid var(--color-admin);"><?= $_initials ?></div>
        <a href="/excell-mark/logout.php" style="margin-left: 0.5rem; color: var(--text-muted);"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</div>
