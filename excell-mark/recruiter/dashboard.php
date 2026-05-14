<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$pageTitle = "My Recruiter Dashboard";

$userId = $_SESSION['user_id'];

// 1. Quota
$stmt = $pdo->prepare("SELECT target FROM quotas WHERE recruiter_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$userId]);
$quotaRaw = $stmt->fetchColumn();
$quota = $quotaRaw ?: 60; // Default to 60 if not set

// 2. Fetch all pipeline applications for this recruiter
$stmt = $pdo->prepare("SELECT a.id, a.stage, a.applied_at, u.full_name as applicant_name, jp.title as job_title, DATEDIFF(NOW(), a.applied_at) as days_in_system
                       FROM applications a
                       JOIN job_posts jp ON a.job_post_id = jp.id
                       JOIN users u ON a.applicant_id = u.id
                       WHERE jp.assigned_recruiter_id = ? 
                       ORDER BY a.applied_at DESC");
$stmt->execute([$userId]);
$allApps = $stmt->fetchAll();

// Group pipeline
$pipeline = ['Pending' => [], 'Reviewed' => [], 'Shortlisted' => [], 'Hired' => []];
$funnel = ['Pending' => 0, 'Reviewed' => 0, 'Shortlisted' => 0, 'Hired' => 0];
$overdueCount = 0;

foreach ($allApps as $app) {
    $stage = $app['stage'];
    if (isset($pipeline[$stage])) {
        $pipeline[$stage][] = $app;
        $funnel[$stage]++;
    }
    if (($stage === 'Pending' || $stage === 'Reviewed') && $app['days_in_system'] > 3) {
        $overdueCount++;
    }
}

$hiredCount = $funnel['Hired'];
$remainingQuota = max(0, $quota - $hiredCount);
$quotaPct = min(100, ($hiredCount / $quota) * 100);
$maxFunnel = max(max($funnel), 1);

// Recent messages dummy query (simulate empty state for now unless we implement full messaging)
$stmt = $pdo->prepare("SELECT m.*, u.full_name as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? ORDER BY m.sent_at DESC LIMIT 5");
$stmt->execute([$userId]);
$messages = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
/* Kanban Board Styles */
.kanban-board { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; margin-top: 1rem; }
.kanban-col { flex: 1; min-width: 280px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 8px; display: flex; flex-direction: column; }
.kanban-header { padding: 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-secondary); }
.kanban-body { padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem; flex: 1; min-height: 200px; }
.kanban-card { background: var(--bg-surface-light); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; transition: border-color 0.2s; cursor: pointer; }
.kanban-card:hover { border-color: var(--accent-blue); }
.k-title { font-size: 0.85rem; font-weight: 600; margin-bottom: 0.2rem; }
.k-sub { font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.5rem; }
.k-footer { display: flex; justify-content: space-between; align-items: center; font-size: 0.7rem; color: var(--text-muted); }
.empty-col { text-align: center; color: var(--text-muted); font-size: 0.8rem; font-style: italic; opacity: 0.5; padding: 2rem 0; }
</style>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    
    <div class="top-nav">
        <div class="page-title">
            My Recruiter Dashboard <span class="page-subtitle" style="margin-left: 1rem; border-left: 1px solid var(--border-color); padding-left: 1rem;">Live System Data</span>
        </div>
        <div class="top-nav-actions">
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-right: 1rem;">My Quota: <span style="color: var(--accent-blue); font-weight: 600;"><?= $hiredCount ?></span> <span style="color: var(--text-muted);">/ <?= $quota ?></span></div>
            <a href="my-posts.php" class="btn btn-outline" style="padding: 0.4rem 0.8rem;"><i class="fa-solid fa-file-invoice"></i> Job Posts</a>
            <a href="create-job-post.php" class="btn btn-primary" style="padding: 0.4rem 0.8rem;"><i class="fa-solid fa-plus"></i> Post a Job</a>
        </div>
    </div>
    
    <div class="content-area">
        
        <?php if($overdueCount > 0): ?>
        <!-- Alert Banner -->
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div style="display: flex; gap: 1rem; align-items: center;">
                <i class="fa-solid fa-play" style="color: var(--status-danger);"></i>
                <div>
                    <h4 style="color: var(--status-danger); font-size: 0.9rem; margin-bottom: 0.2rem;"><?= $overdueCount ?> Overdue Applicants Require Action</h4>
                    <p style="color: var(--text-secondary); font-size: 0.8rem;">You have applicants waiting for review past the 3-day deadline. Take action now.</p>
                </div>
            </div>
            <a href="overdue.php" class="btn" style="background: rgba(239, 68, 68, 0.2); color: var(--text-primary); border: 1px solid rgba(239, 68, 68, 0.5); padding: 0.5rem 1rem;">View Flagged</a>
        </div>
        <?php endif; ?>
        
        <!-- Pipeline Board -->
        <div class="dash-card" style="margin-bottom: 1.5rem;">
            <div class="dash-card-header" style="margin-bottom: 0;">
                <span class="dash-card-title">Applicant Pipeline</span>
                <span class="dash-card-subtitle" style="color: var(--accent-blue);">All active jobs</span>
            </div>
            
            <div class="kanban-board">
                <!-- Pending -->
                <div class="kanban-col">
                    <div class="kanban-header">
                        <span style="color: var(--border-light);">PENDING</span>
                        <span class="nav-badge"><?= count($pipeline['Pending']) ?></span>
                    </div>
                    <div class="kanban-body">
                        <?php if(empty($pipeline['Pending'])): ?>
                            <div class="empty-col">No pending applicants</div>
                        <?php else: ?>
                            <?php foreach($pipeline['Pending'] as $app): ?>
                            <div class="kanban-card" <?= $app['days_in_system'] > 3 ? 'style="border-color: rgba(239, 68, 68, 0.3);"' : '' ?>>
                                <div class="k-title"><?= htmlspecialchars($app['applicant_name']) ?></div>
                                <div class="k-sub"><?= htmlspecialchars($app['job_title']) ?></div>
                                <div class="k-footer"><span>Applied <?= date('M j', strtotime($app['applied_at'])) ?></span> <?= $app['days_in_system'] > 3 ? '<span class="nav-badge danger">Overdue</span>' : '' ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reviewed -->
                <div class="kanban-col">
                    <div class="kanban-header">
                        <span style="color: var(--status-warning);">REVIEWED</span>
                        <span class="nav-badge"><?= count($pipeline['Reviewed']) ?></span>
                    </div>
                    <div class="kanban-body">
                        <?php if(empty($pipeline['Reviewed'])): ?>
                            <div class="empty-col">No reviewed applicants</div>
                        <?php else: ?>
                            <?php foreach($pipeline['Reviewed'] as $app): ?>
                            <div class="kanban-card" <?= $app['days_in_system'] > 3 ? 'style="border-color: rgba(239, 68, 68, 0.3);"' : '' ?>>
                                <div class="k-title"><?= htmlspecialchars($app['applicant_name']) ?></div>
                                <div class="k-sub"><?= htmlspecialchars($app['job_title']) ?></div>
                                <div class="k-footer"><span>Reviewed <?= date('M j', strtotime($app['applied_at'])) ?></span> <?= $app['days_in_system'] > 3 ? '<span class="nav-badge danger">Overdue</span>' : '' ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Shortlisted -->
                <div class="kanban-col">
                    <div class="kanban-header">
                        <span style="color: var(--accent-blue);">SHORTLISTED</span>
                        <span class="nav-badge"><?= count($pipeline['Shortlisted']) ?></span>
                    </div>
                    <div class="kanban-body">
                        <?php if(empty($pipeline['Shortlisted'])): ?>
                            <div class="empty-col">No shortlisted applicants</div>
                        <?php else: ?>
                            <?php foreach($pipeline['Shortlisted'] as $app): ?>
                            <div class="kanban-card">
                                <div class="k-title"><?= htmlspecialchars($app['applicant_name']) ?></div>
                                <div class="k-sub"><?= htmlspecialchars($app['job_title']) ?></div>
                                <div class="k-footer"><span>Shortlisted <?= date('M j', strtotime($app['applied_at'])) ?></span></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Hired -->
                <div class="kanban-col">
                    <div class="kanban-header">
                        <span style="color: var(--status-success);">HIRED</span>
                        <span class="nav-badge"><?= count($pipeline['Hired']) ?></span>
                    </div>
                    <div class="kanban-body">
                        <?php if(empty($pipeline['Hired'])): ?>
                            <div class="empty-col">No hired applicants</div>
                        <?php else: ?>
                            <?php foreach($pipeline['Hired'] as $app): ?>
                            <div class="kanban-card">
                                <div class="k-title"><?= htmlspecialchars($app['applicant_name']) ?></div>
                                <div class="k-sub"><?= htmlspecialchars($app['job_title']) ?></div>
                                <div class="k-footer"><span>Hired <?= date('M j', strtotime($app['applied_at'])) ?></span></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid-main">
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <!-- Circular Quota Progress -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <span class="dash-card-title">My Target Quota</span>
                    </div>
                    <div style="display: flex; justify-content: space-around; align-items: center; padding: 1rem 0;">
                        <div style="position: relative; width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(var(--accent-blue) <?= $quotaPct ?>%, var(--bg-surface-light) 0); display: flex; align-items: center; justify-content: center;">
                            <div style="position: absolute; width: 100px; height: 100px; background: var(--bg-card); border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <span style="font-size: 1.8rem; font-weight: 700; font-family: var(--font-heading); color: var(--text-primary); line-height: 1;"><?= floor($quotaPct) ?>%</span>
                                <span style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase;">of target</span>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; text-align: center; margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                        <div style="flex: 1;">
                            <div style="font-size: 1.2rem; font-weight: 600; color: var(--status-success);"><?= $hiredCount ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-secondary);">Hired</div>
                        </div>
                        <div style="flex: 1; border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color);">
                            <div style="font-size: 1.2rem; font-weight: 600;"><?= $quota ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-secondary);">Target</div>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 1.2rem; font-weight: 600; color: var(--status-warning);"><?= $remainingQuota ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-secondary);">Remaining</div>
                        </div>
                    </div>
                </div>
                
                <!-- Hiring Funnel Bars -->
                <div class="dash-card">
                    <div class="dash-card-header" style="margin-bottom: 0.5rem;">
                        <span class="dash-card-title">My Funnel</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.8rem; padding-top: 1rem;">
                        <div class="progress-bar-container" style="margin:0;">
                            <div class="progress-label" style="width: 70px;">Pending</div>
                            <div class="progress-track"><div class="progress-fill" style="width: <?= ($funnel['Pending']/$maxFunnel)*100 ?>%; background: var(--border-light);"></div></div>
                            <div class="progress-value" style="font-weight: 600;"><?= $funnel['Pending'] ?></div>
                        </div>
                        <div class="progress-bar-container" style="margin:0;">
                            <div class="progress-label" style="width: 70px;">Reviewed</div>
                            <div class="progress-track"><div class="progress-fill" style="width: <?= ($funnel['Reviewed']/$maxFunnel)*100 ?>%; background: var(--status-warning);"></div></div>
                            <div class="progress-value" style="font-weight: 600;"><?= $funnel['Reviewed'] ?></div>
                        </div>
                        <div class="progress-bar-container" style="margin:0;">
                            <div class="progress-label" style="width: 70px;">Shortlisted</div>
                            <div class="progress-track"><div class="progress-fill" style="width: <?= ($funnel['Shortlisted']/$maxFunnel)*100 ?>%; background: var(--accent-blue);"></div></div>
                            <div class="progress-value" style="font-weight: 600;"><?= $funnel['Shortlisted'] ?></div>
                        </div>
                        <div class="progress-bar-container" style="margin:0;">
                            <div class="progress-label" style="width: 70px;">Hired</div>
                            <div class="progress-track"><div class="progress-fill" style="width: <?= ($funnel['Hired']/$maxFunnel)*100 ?>%; background: var(--status-success);"></div></div>
                            <div class="progress-value" style="font-weight: 600;"><?= $funnel['Hired'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Applicant Messages List -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <span class="dash-card-title"><i class="fa-solid fa-comments"></i> Applicant Messages</span>
                    <?php if(!empty($messages)): ?><a href="messages.php" class="dash-card-subtitle" style="color: var(--accent-blue);">View all</a><?php endif; ?>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php if(empty($messages)): ?>
                        <div style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>Your inbox is empty.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($messages as $msg): 
                            $initials = strtoupper(substr($msg['sender_name'], 0, 2));
                        ?>
                        <div style="display: flex; gap: 1rem; align-items: center; cursor: pointer; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
                            <div class="avatar" style="background: rgba(79, 70, 229, 0.2); color: var(--accent-blue);"><?= $initials ?></div>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.2rem;">
                                    <span style="font-size: 0.85rem; font-weight: 500;"><?= htmlspecialchars($msg['sender_name']) ?></span>
                                    <span style="font-size: 0.7rem; color: var(--text-muted);"><?= date('M j, g:i A', strtotime($msg['sent_at'])) ?></span>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;"><?= htmlspecialchars($msg['message_body']) ?></div>
                            </div>
                            <?php if(!$msg['is_read']): ?>
                            <div style="width: 8px; height: 8px; background: var(--accent-blue); border-radius: 50%;"></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
