<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = "Admin Dashboard";

// Dynamic Data Fetching
// 1. Active Recruiters
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'recruiter'");
$activeRecruiters = $stmt->fetchColumn();

// 2. Hired This Week (All Time for now)
$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE stage = 'Hired'");
$hiresCount = $stmt->fetchColumn();

// 3. Open Job Posts
$stmt = $pdo->query("SELECT COUNT(*) FROM job_posts WHERE is_active = 1");
$openPosts = $stmt->fetchColumn();

// 4. Overdue Flags (Pending > 3 days)
$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE stage IN ('Pending', 'Reviewed') AND DATEDIFF(NOW(), applied_at) > 3");
$overdueFlags = $stmt->fetchColumn();

// 5. Recruiter Performance (Get all recruiters and their stats)
$stmt = $pdo->query("SELECT u.id, u.full_name, 
                     (SELECT COUNT(*) FROM applications a JOIN job_posts jp ON a.job_post_id = jp.id WHERE jp.assigned_recruiter_id = u.id AND a.stage = 'Hired') as hired_count,
                     (SELECT COUNT(*) FROM applications a JOIN job_posts jp ON a.job_post_id = jp.id WHERE jp.assigned_recruiter_id = u.id) as pipeline_count
                     FROM users u WHERE u.role = 'recruiter'");
$recruiters = $stmt->fetchAll();

// 6. Overdue Flags Details
$stmt = $pdo->query("SELECT a.id as app_id, jp.title as job, u.full_name as recruiter, DATEDIFF(NOW(), a.applied_at) as days_overdue 
                     FROM applications a 
                     JOIN job_posts jp ON a.job_post_id = jp.id 
                     JOIN users u ON jp.assigned_recruiter_id = u.id 
                     WHERE a.stage IN ('Pending', 'Reviewed') AND DATEDIFF(NOW(), a.applied_at) > 3 
                     ORDER BY days_overdue DESC LIMIT 6");
$overdueList = $stmt->fetchAll();

// 7. Funnel Metrics
$stmt = $pdo->query("SELECT stage, COUNT(*) as count FROM applications GROUP BY stage");
$funnelRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$funnel = [
    'Pending' => $funnelRaw['Pending'] ?? 0,
    'Reviewed' => $funnelRaw['Reviewed'] ?? 0,
    'Shortlisted' => $funnelRaw['Shortlisted'] ?? 0,
    'Hired' => $funnelRaw['Hired'] ?? 0
];
$maxFunnel = max(max($funnel), 1); // Avoid division by zero

include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-admin.php'; ?>
    
    <div class="top-nav">
        <div class="page-title">
            Admin Dashboard <span class="page-subtitle" style="margin-left: 1rem; border-left: 1px solid var(--border-color); padding-left: 1rem;">Live System Data</span>
        </div>
        <div class="top-nav-actions">
            <button class="btn btn-outline" style="padding: 0.4rem 0.8rem;"><i class="fa-solid fa-download"></i> Export</button>
            <?php if($overdueFlags > 0): ?>
            <button class="btn" style="background: rgba(239, 68, 68, 0.15); color: var(--status-danger); padding: 0.4rem 0.8rem;"><i class="fa-solid fa-flag"></i> <?= $overdueFlags ?> Flags</button>
            <?php endif; ?>
            <a href="create-job-post.php" class="btn btn-primary" style="padding: 0.4rem 0.8rem;"><i class="fa-solid fa-plus"></i> Create Job Post</a>
            <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-surface-light); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; position: relative;">
                <i class="fa-regular fa-bell" style="color: var(--text-muted);"></i>
                <?php if($overdueFlags > 0): ?>
                <div style="position: absolute; top: 0; right: 0; width: 8px; height: 8px; background: var(--status-danger); border-radius: 50%;"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="content-area">
        <div class="grid-top">
            <div class="dash-card stat-box primary">
                <span class="stat-label">Active Recruiters</span>
                <span class="stat-number"><?= $activeRecruiters ?></span>
                <span class="stat-trend trend-neutral">Registered in system</span>
            </div>
            <div class="dash-card stat-box success">
                <span class="stat-label">Total Hired</span>
                <span class="stat-number"><?= $hiresCount ?></span>
                <span class="stat-trend trend-up"><i class="fa-solid fa-check-double"></i> Placed candidates</span>
            </div>
            <div class="dash-card stat-box warning">
                <span class="stat-label">Open Job Posts</span>
                <span class="stat-number"><?= $openPosts ?></span>
                <span class="stat-trend trend-neutral">Currently active</span>
            </div>
            <div class="dash-card stat-box danger">
                <span class="stat-label">Overdue Flags</span>
                <span class="stat-number"><?= $overdueFlags ?></span>
                <span class="stat-trend <?= $overdueFlags > 0 ? 'trend-down' : 'trend-success' ?>"><?= $overdueFlags > 0 ? '<i class="fa-solid fa-arrow-up"></i> Requires attention' : '<i class="fa-solid fa-check"></i> All clear' ?></span>
            </div>
        </div>
        
        <div class="grid-main">
            <!-- Recruiter Performance Table -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Recruiter Performance</span>
                    <a href="recruiters.php" class="dash-card-subtitle" style="color: var(--accent-blue);">View all &rarr;</a>
                </div>
                
                <?php if (empty($recruiters)): ?>
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                    <i class="fa-solid fa-users-slash" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No active recruiters found in the system.</p>
                </div>
                <?php else: ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>RECRUITER</th>
                            <th>HIRED</th>
                            <th>PIPELINE</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recruiters as $index => $r): 
                            $colors = ['success', 'warning', 'info', 'danger'];
                            $color = $colors[$index % 4];
                            $initials = strtoupper(substr($r['full_name'], 0, 2));
                        ?>
                        <tr>
                            <td style="display: flex; align-items: center; gap: 0.75rem;">
                                <div class="avatar" style="background: var(--status-<?= $color ?>); width: 24px; height: 24px; font-size: 0.6rem;"><?= $initials ?></div>
                                <span style="font-weight: 500;"><?= htmlspecialchars($r['full_name']) ?></span>
                            </td>
                            <td style="color: var(--status-<?= $color ?>); font-weight: 600;"><?= $r['hired_count'] ?></td>
                            <td><?= $r['pipeline_count'] ?></td>
                            <td><span class="status-pill pill-<?= $color ?>"><div class="status-dot"></div> Active</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Overdue Flag Monitor -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <span class="dash-card-title"><i class="fa-solid fa-play" style="color: var(--status-danger); font-size: 0.6rem;"></i> Overdue Flag Monitor</span>
                    <?php if($overdueFlags > 0): ?><a href="overdue.php" class="dash-card-subtitle" style="color: var(--status-danger);">Resolve all</a><?php endif; ?>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php if (empty($overdueList)): ?>
                    <div style="text-align: center; padding: 2rem 1rem; color: var(--status-success);">
                        <i class="fa-solid fa-shield-check" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No overdue applications!</p>
                    </div>
                    <?php else: ?>
                        <?php foreach($overdueList as $f): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; background: rgba(255,255,255,0.02);">
                            <div>
                                <div style="font-size: 0.8rem; font-weight: 500; display: flex; align-items: center; gap: 0.4rem;">
                                    <div style="width: 4px; height: 4px; background: var(--status-danger); border-radius: 50%;"></div>
                                    App #<?= $f['app_id'] ?>
                                </div>
                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-left: 0.7rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;"><?= htmlspecialchars($f['job']) ?></div>
                            </div>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <span style="font-size: 0.6rem; padding: 0.15rem 0.3rem; border: 1px solid var(--border-color); border-radius: 4px; color: var(--text-secondary); max-width: 60px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($f['recruiter']) ?></span>
                                <span style="font-size: 0.7rem; color: var(--status-danger); font-weight: 500; width: 45px; text-align: right;">+<?= $f['days_overdue'] ?>d</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="grid-bottom">
            <!-- Quota Progress (Dynamic based on recruiters) -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Recruiter Workload</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.8rem; flex: 1; justify-content: center;">
                    <?php if (empty($recruiters)): ?>
                        <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem;">No data available</p>
                    <?php else: ?>
                        <?php foreach(array_slice($recruiters, 0, 5) as $index => $r): 
                            $colors = ['success', 'warning', 'info', 'danger', 'success'];
                            $color = $colors[$index % 5];
                            $initials = strtoupper(substr($r['full_name'], 0, 2));
                            $maxPipeline = max(array_column($recruiters, 'pipeline_count'));
                            $maxPipeline = $maxPipeline > 0 ? $maxPipeline : 1;
                            $pct = ($r['pipeline_count'] / $maxPipeline) * 100;
                        ?>
                        <div class="progress-bar-container">
                            <div class="progress-label" style="display: flex; align-items: center; gap: 0.4rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><div class="avatar" style="width: 16px; height: 16px; font-size: 0.5rem; background: var(--status-<?= $color ?>);"><?= $initials ?></div> <?= explode(' ', $r['full_name'])[0] ?></div>
                            <div class="progress-track"><div class="progress-fill" style="width: <?= $pct ?>%; background: var(--status-<?= $color ?>);"></div></div>
                            <div class="progress-value"><?= $r['pipeline_count'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Hiring Funnel -->
            <div class="dash-card" style="display: flex; flex-direction: column;">
                <div class="dash-card-header">
                    <span class="dash-card-title">Hiring Funnel</span>
                    <span class="dash-card-subtitle" style="color: var(--accent-blue);">All recruiters</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
                    <div class="progress-bar-container" style="margin:0;">
                        <div class="progress-label" style="width: 70px;">Pending</div>
                        <div class="progress-track"><div class="progress-fill" style="width: <?= ($funnel['Pending']/$maxFunnel)*100 ?>%; background: var(--border-light);"></div></div>
                        <div class="progress-value" style="font-weight: 600;"><?= $funnel['Pending'] ?></div>
                    </div>
                    <div class="progress-bar-container" style="margin:0;">
                        <div class="progress-label" style="width: 70px;">Reviewed</div>
                        <div class="progress-track"><div class="progress-fill" style="width: <?= ($funnel['Reviewed']/$maxFunnel)*100 ?>%; background: var(--status-warning);"></div></div>
                        <div class="progress-value" style="color: var(--status-warning); font-weight: 600;"><?= $funnel['Reviewed'] ?></div>
                    </div>
                    <div class="progress-bar-container" style="margin:0;">
                        <div class="progress-label" style="width: 70px;">Shortlisted</div>
                        <div class="progress-track"><div class="progress-fill" style="width: <?= ($funnel['Shortlisted']/$maxFunnel)*100 ?>%; background: var(--accent-blue);"></div></div>
                        <div class="progress-value" style="color: var(--accent-blue); font-weight: 600;"><?= $funnel['Shortlisted'] ?></div>
                    </div>
                    <div class="progress-bar-container" style="margin:0;">
                        <div class="progress-label" style="width: 70px;">Hired</div>
                        <div class="progress-track"><div class="progress-fill" style="width: <?= ($funnel['Hired']/$maxFunnel)*100 ?>%; background: var(--status-success);"></div></div>
                        <div class="progress-value" style="color: var(--status-success); font-weight: 600;"><?= $funnel['Hired'] ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Quick Actions</span>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <a href="create-job-post.php" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 8px; transition: border-color 0.2s;">
                        <div style="width: 40px; height: 40px; background: rgba(79, 70, 229, 0.1); color: var(--accent-blue); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;"><i class="fa-solid fa-plus"></i></div>
                        <div>
                            <div style="font-weight: 500; font-size: 0.9rem;">Create New Job Post</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Draft and publish an opening</div>
                        </div>
                    </a>
                    
                    <a href="recruiters.php" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 8px; transition: border-color 0.2s;">
                        <div style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); color: var(--status-success); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;"><i class="fa-solid fa-user-plus"></i></div>
                        <div>
                            <div style="font-weight: 500; font-size: 0.9rem;">Manage Recruiters</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Add or remove system users</div>
                        </div>
                    </a>
                    
                    <a href="rebalance.php" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 8px; transition: border-color 0.2s;">
                        <div style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.1); color: var(--status-warning); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;"><i class="fa-solid fa-scale-balanced"></i></div>
                        <div>
                            <div style="font-weight: 500; font-size: 0.9rem;">Workload Rebalancing</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Redistribute job assignments</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
