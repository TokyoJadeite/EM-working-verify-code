<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$_recId = $_SESSION['user_id'];
$_recName = $_SESSION['full_name'];
$_recInitials = strtoupper(substr($_recName, 0, 1) . (strpos($_recName, ' ') ? substr($_recName, strpos($_recName, ' ') + 1, 1) : ''));

// Dynamic badge counts
$_pendingCount = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN job_posts jp ON a.job_post_id = jp.id WHERE jp.assigned_recruiter_id = ? AND a.stage = 'Pending'");
$_pendingCount->execute([$_recId]);
$_pendingBadge = $_pendingCount->fetchColumn();

$_overdueRecCount = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN job_posts jp ON a.job_post_id = jp.id WHERE jp.assigned_recruiter_id = ? AND a.stage IN ('Pending','Reviewed') AND DATEDIFF(NOW(), a.applied_at) > 3");
$_overdueRecCount->execute([$_recId]);
$_overdueBadge = $_overdueRecCount->fetchColumn();

$_myPostsCount = $pdo->prepare("SELECT COUNT(*) FROM job_posts WHERE assigned_recruiter_id = ?");
$_myPostsCount->execute([$_recId]);
$_postsBadge = $_myPostsCount->fetchColumn();

$_unreadRecMsgs = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$_unreadRecMsgs->execute([$_recId]);
$_msgsBadge = $_unreadRecMsgs->fetchColumn();
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <span class="logo-sub">RECRUITMENT SYSTEM</span>
        <div class="logo-text">EXCELL<span style="color: var(--color-recruiter);">MARK</span></div>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">My Workspace</div>
            <a href="dashboard.php" class="nav-item <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-table-columns"></i> My Dashboard</span>
            </a>
            <a href="pending.php" class="nav-item <?= $currentPage == 'pending.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-inbox"></i> Pending Review</span>
                <?php if($_pendingBadge > 0): ?><span class="nav-badge"><?= $_pendingBadge ?></span><?php endif; ?>
            </a>
            <a href="reviewed.php" class="nav-item <?= $currentPage == 'reviewed.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-magnifying-glass"></i> Reviewed</span>
            </a>
            <a href="shortlisted.php" class="nav-item <?= $currentPage == 'shortlisted.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-star"></i> Shortlisted</span>
            </a>
            <a href="hired.php" class="nav-item <?= $currentPage == 'hired.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-check"></i> Hired</span>
            </a>
            <?php if($_overdueBadge > 0): ?>
            <a href="overdue.php" class="nav-item <?= $currentPage == 'overdue.php' ? 'active' : '' ?>" style="color: var(--status-danger);">
                <span><i class="fa-solid fa-play"></i> Overdue Flagged</span>
                <span class="nav-badge danger"><?= $_overdueBadge ?></span>
            </a>
            <?php endif; ?>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Job Posts</div>
            <a href="my-posts.php" class="nav-item <?= $currentPage == 'my-posts.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-file-invoice"></i> Job Post Overview</span>
                <?php if($_postsBadge > 0): ?><span class="nav-badge"><?= $_postsBadge ?></span><?php endif; ?>
            </a>
            <a href="create-job-post.php" class="nav-item <?= $currentPage == 'create-job-post.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-plus"></i> Create Job Post</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Communication</div>
            <a href="messages.php" class="nav-item <?= in_array($currentPage, ['messages.php', 'thread.php']) ? 'active' : '' ?>">
                <span><i class="fa-solid fa-comments"></i> Messages</span>
                <?php if($_msgsBadge > 0): ?><div style="width: 6px; height: 6px; background: var(--status-danger); border-radius: 50%;"></div><?php endif; ?>
            </a>
            <a href="documents.php" class="nav-item <?= $currentPage == 'documents.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-paperclip"></i> Documents</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Performance</div>
            <a href="quota.php" class="nav-item <?= $currentPage == 'quota.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-bullseye"></i> Quota Progress</span>
            </a>
            <a href="my-funnel.php" class="nav-item <?= $currentPage == 'my-funnel.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-filter"></i> Hiring Funnel</span>
            </a>
        </div>
        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="settings.php" class="nav-item <?= $currentPage == 'settings.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-gear"></i> Settings</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <div class="avatar" style="background: var(--color-recruiter);"><?= $_recInitials ?></div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($_recName) ?></span>
            <span class="user-role">Recruiter</span>
        </div>
        <a href="/excell-mark/logout.php" style="margin-left: auto; color: var(--text-muted);"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</aside>
