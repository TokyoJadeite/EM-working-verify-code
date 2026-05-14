<?php
$currentPage = basename($_SERVER['PHP_SELF']);
// Dynamic badge counts
$_navRecruiterCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('recruiter', 'admin')")->fetchColumn();
$_navJobPostCount = $pdo->query("SELECT COUNT(*) FROM job_posts")->fetchColumn();
$_navOverdueCount = $pdo->query("SELECT COUNT(*) FROM applications WHERE stage IN ('Pending','Reviewed') AND DATEDIFF(NOW(), applied_at) > 3")->fetchColumn();
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <span class="logo-sub">RECRUITMENT SYSTEM</span>
        <div class="logo-text">EXCELL<span style="color: var(--text-muted);">MARK</span></div>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Overview</div>
            <a href="dashboard.php" class="nav-item <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-layer-group"></i> Dashboard</span>
            </a>
            <a href="trends.php" class="nav-item <?= $currentPage == 'trends.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-chart-line"></i> Analytics</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <a href="recruiters.php" class="nav-item <?= $currentPage == 'recruiters.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-users-viewfinder"></i> Staff Accounts</span>
                <span class="nav-badge"><?= $_navRecruiterCount ?></span>
            </a>
            <a href="quotas.php" class="nav-item <?= $currentPage == 'quotas.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-bullseye"></i> Quota Config</span>
            </a>
            <a href="rebalance.php" class="nav-item <?= $currentPage == 'rebalance.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-scale-unbalanced"></i> Workload Rebalancing</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Job Posts</div>
            <a href="job-posts.php" class="nav-item <?= $currentPage == 'job-posts.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-file-invoice"></i> Job Post Overview</span>
                <span class="nav-badge"><?= $_navJobPostCount ?></span>
            </a>
            <a href="create-job-post.php" class="nav-item <?= $currentPage == 'create-job-post.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-plus"></i> Create Job Post</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Pipeline</div>
            <a href="pipeline.php" class="nav-item <?= $currentPage == 'pipeline.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-route"></i> Pipeline Overview</span>
            </a>
            <a href="overdue.php" class="nav-item <?= $currentPage == 'overdue.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-flag"></i> Overdue Flags</span>
                <?php if($_navOverdueCount > 0): ?><span class="nav-badge danger"><?= $_navOverdueCount ?></span><?php endif; ?>
            </a>
            <a href="funnel.php" class="nav-item <?= $currentPage == 'funnel.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-filter"></i> Hiring Funnel</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Reports</div>
            <a href="reports.php" class="nav-item <?= $currentPage == 'reports.php' ? 'active' : '' ?>">
                <span><i class="fa-solid fa-download"></i> Export Reports</span>
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
        <div class="avatar" style="background: var(--accent-blue);">AR</div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <span class="user-role">System Administrator</span>
        </div>
        <a href="/excell-mark/logout.php" style="margin-left: auto; color: var(--text-muted);"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</aside>
