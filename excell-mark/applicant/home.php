<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('applicant');

$pageTitle = "Applicant Portal";
$userId = $_SESSION['user_id'];
$firstName = explode(' ', $_SESSION['full_name'])[0];

// Fetch active job posts
$stmt = $pdo->query("SELECT jp.*, 
                    (SELECT COUNT(*) FROM applications a WHERE a.job_post_id = jp.id AND a.stage = 'Hired') as hired_count,
                    (SELECT COUNT(*) FROM applications a WHERE a.job_post_id = jp.id AND a.applicant_id = $userId) as has_applied
                    FROM job_posts jp 
                    WHERE jp.is_active = 1 
                    ORDER BY jp.created_at DESC LIMIT 3");
$jobs = $stmt->fetchAll();

// Fetch latest application
$stmt = $pdo->prepare("SELECT a.*, jp.title, jp.assigned_recruiter_id, u.full_name as recruiter_name 
                       FROM applications a 
                       JOIN job_posts jp ON a.job_post_id = jp.id 
                       JOIN users u ON jp.assigned_recruiter_id = u.id 
                       WHERE a.applicant_id = ? 
                       ORDER BY a.applied_at DESC LIMIT 1");
$stmt->execute([$userId]);
$latestApp = $stmt->fetch();

// Document Status (simplified check based on actual implementation in documents table if exists, else stub)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE applicant_id = ?");
$stmt->execute([$userId]);
$docsCount = $stmt->fetchColumn() ?: 0;
$docsRequired = 3;

// Application status logic
$statusColorMap = [
    'Pending' => ['color' => 'var(--text-muted)', 'bg' => 'var(--bg-surface-light)'],
    'Reviewed' => ['color' => 'var(--status-warning)', 'bg' => 'rgba(245, 158, 11, 0.1)'],
    'Shortlisted' => ['color' => 'var(--accent-blue)', 'bg' => 'rgba(79, 70, 229, 0.1)'],
    'Hired' => ['color' => 'var(--status-success)', 'bg' => 'rgba(16, 185, 129, 0.1)']
];

include '../includes/header.php';
?>

<style>
/* Applicant Specific Styles */
.hero-banner {
    background: radial-gradient(circle at top right, rgba(79, 70, 229, 0.15) 0%, transparent 60%),
                radial-gradient(circle at bottom left, rgba(234, 179, 8, 0.05) 0%, transparent 40%);
    border-bottom: 1px solid var(--border-color);
    padding: 2.5rem 3rem;
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.hero-sub { color: var(--color-admin); font-size: 0.7rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 0.5rem; }
.hero-title { font-size: 2.5rem; font-weight: 700; line-height: 1.1; margin-bottom: 0.5rem; }
.hero-desc { color: var(--text-secondary); font-size: 0.9rem; }

.status-badge-lg { border: 1px solid var(--border-color); padding: 0.6rem 1rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; }
.status-badge-lg .dot { width: 10px; height: 10px; border-radius: 50%; }

.timeline-item { display: flex; gap: 1.5rem; margin-bottom: 1rem; position: relative; }
.timeline-item::before { content: ''; position: absolute; left: 15px; top: 30px; bottom: -1rem; width: 2px; background: var(--border-light); z-index: 1; }
.timeline-item:last-child::before { display: none; }
.timeline-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; z-index: 2; position: relative; border: 2px solid var(--bg-dark); }
.timeline-content { flex: 1; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem 1.25rem; }
.timeline-content h4 { font-size: 0.9rem; font-weight: 600; margin-bottom: 0.2rem; }
.timeline-content p { font-size: 0.8rem; color: var(--text-secondary); }
.timeline-content .date { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem; display: block; }

.timeline-item.done .timeline-icon { background: var(--status-success); color: #fff; }
.timeline-item.done .timeline-content { border-color: rgba(16, 185, 129, 0.3); }
.timeline-item.done h4 { color: var(--status-success); }
.timeline-item.active .timeline-icon { background: var(--accent-blue); color: #fff; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2); }
.timeline-item.active .timeline-content { border-color: var(--accent-blue); }
.timeline-item.active h4 { color: var(--accent-blue); }
.timeline-item.pending .timeline-icon { background: var(--bg-surface-light); color: var(--text-muted); border-color: var(--border-color); }

.section-title { font-size: 0.95rem; font-weight: 600; margin: 2rem 0 1rem 0; display: flex; align-items: center; gap: 0.5rem; }
.section-title::after { content: ''; flex: 1; height: 1px; background: var(--border-color); }

.upload-zone { border: 2px dashed var(--border-light); border-radius: 8px; padding: 2rem; text-align: center; color: var(--text-secondary); margin-bottom: 1rem; cursor: pointer; transition: all 0.2s; }
.upload-zone:hover { border-color: var(--accent-blue); background: rgba(79, 70, 229, 0.05); }

.job-card { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 0.5rem; transition: border-color 0.2s; }
.job-card:hover { border-color: var(--accent-blue); }

.right-sidebar { background: var(--bg-surface); border-left: 1px solid var(--border-color); padding: 2rem; display: flex; flex-direction: column; gap: 2rem; }
.summary-list { font-size: 0.85rem; }
.summary-list li { display: flex; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid var(--border-color); }
.summary-list li:last-child { border-bottom: none; }
.summary-list .lbl { color: var(--text-muted); }
.summary-list .val { font-weight: 500; }

.chat-bubble { background: var(--bg-surface-light); padding: 0.8rem 1rem; border-radius: 8px; font-size: 0.8rem; margin-bottom: 0.5rem; border: 1px solid var(--border-color); }
.chat-bubble.mine { background: rgba(79, 70, 229, 0.1); border-color: rgba(79, 70, 229, 0.2); color: var(--text-primary); margin-left: 2rem; border-bottom-right-radius: 0; }
.chat-bubble.theirs { margin-right: 2rem; border-bottom-left-radius: 0; }

.notif-card { display: flex; gap: 1rem; padding: 1rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 0.5rem; }
.notif-icon { width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
</style>

<div style="display: flex; flex-direction: column; min-height: 100vh; width: 100%;">
    <?php include '../includes/nav-applicant.php'; ?>
    
    <div class="hero-banner">
        <div>
            <div class="hero-sub">APPLICANT PORTAL</div>
            <div class="hero-title">Welcome back, <?= htmlspecialchars($firstName) ?>! 👋</div>
            <div class="hero-desc">Track your application, upload documents, and stay in touch with your recruiter.</div>
        </div>
        <div style="display: flex; gap: 2rem; text-align: right;">
            <div>
                <div style="font-size: 0.7rem; color: var(--text-muted); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 0.5rem;">APPLICATION STATUS</div>
                <?php if ($latestApp): 
                    $conf = $statusColorMap[$latestApp['status']];
                ?>
                    <div class="status-badge-lg" style="color: <?= $conf['color'] ?>; background: <?= $conf['bg'] ?>; border-color: <?= $conf['color'] ?>;">
                        <div class="dot" style="background: <?= $conf['color'] ?>; box-shadow: 0 0 8px <?= $conf['color'] ?>;"></div> 
                        <?= $latestApp['status'] ?>
                    </div>
                <?php else: ?>
                    <div class="status-badge-lg" style="color: var(--text-muted); background: var(--bg-surface-light);">
                        No Application
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-size: 0.7rem; color: var(--text-muted); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 0.5rem;">DOCUMENTS</div>
                <div style="font-size: 1.5rem; font-weight: 600; color: var(--color-applicant); line-height: 1;"><?= $docsCount ?> <span style="font-size: 1rem; color: var(--text-muted); font-weight: 400;">/ <?= $docsRequired ?></span></div>
                <div style="height: 4px; background: var(--bg-surface-light); border-radius: 2px; margin-top: 0.5rem; width: 60px; margin-left: auto;">
                    <div style="height: 100%; width: <?= min(100, ($docsCount/$docsRequired)*100) ?>%; background: var(--color-applicant); border-radius: 2px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div style="display: flex; flex: 1;">
        <!-- Left Content -->
        <div style="flex: 2; padding: 0 3rem 3rem 3rem;">
            <div class="section-title">Application Progress</div>
            
            <?php if (!$latestApp): ?>
                <div style="background: var(--bg-card); border: 1px dashed var(--border-light); border-radius: 8px; padding: 3rem; text-align: center;">
                    <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--text-muted); margin: 0 auto 1rem auto;"><i class="fa-solid fa-file-signature"></i></div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">You haven't applied to any jobs yet</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Browse our open positions below to get started on your journey.</p>
                    <a href="jobs.php" class="btn btn-primary">View Open Jobs</a>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <!-- Step 1 -->
                    <div class="timeline-item done">
                        <div class="timeline-icon"><i class="fa-solid fa-check"></i></div>
                        <div class="timeline-content">
                            <h4>Registered & Applied</h4>
                            <p>Application submitted for <?= htmlspecialchars($latestApp['title']) ?></p>
                            <span class="date"><?= date('F j, Y', strtotime($latestApp['applied_at'])) ?></span>
                        </div>
                    </div>
                    
                    <!-- Step 2 -->
                    <?php 
                        $isReviewed = in_array($latestApp['status'], ['Reviewed', 'Shortlisted', 'Hired']);
                        $isShortlisted = in_array($latestApp['status'], ['Shortlisted', 'Hired']);
                        $isHired = $latestApp['status'] === 'Hired';
                    ?>
                    <div class="timeline-item <?= $isReviewed ? 'done' : 'active' ?>">
                        <div class="timeline-icon"><?= $isReviewed ? '<i class="fa-solid fa-check"></i>' : '' ?></div>
                        <div class="timeline-content">
                            <h4>Under Review</h4>
                            <p>Your documents are being reviewed by your recruiter</p>
                            <?php if(!$isReviewed): ?>
                            <span class="date" style="color: var(--accent-blue);">In Progress</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="timeline-item <?= $isShortlisted ? 'done' : ($isReviewed && !$isShortlisted ? 'active' : 'pending') ?>">
                        <div class="timeline-icon"><?= $isShortlisted ? '<i class="fa-solid fa-check"></i>' : ($isReviewed ? '' : '3') ?></div>
                        <div class="timeline-content">
                            <h4>Shortlisted</h4>
                            <p>Qualified candidates moving forward</p>
                        </div>
                    </div>
                    
                    <!-- Step 4 -->
                    <div class="timeline-item <?= $isHired ? 'done' : ($isShortlisted && !$isHired ? 'active' : 'pending') ?>">
                        <div class="timeline-icon"><?= $isHired ? '<i class="fa-solid fa-check"></i>' : ($isShortlisted ? '' : '4') ?></div>
                        <div class="timeline-content">
                            <h4>Hired / Outcome</h4>
                            <p>Placement confirmed or may reapply</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="section-title">My Documents</div>
            <div class="upload-zone" onclick="window.location.href='documents.php'">
                <i class="fa-solid fa-paperclip" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">Click to Upload Documents</h4>
                <p style="font-size: 0.8rem;">Resume, Gov't ID, Certifications — PDF or Image · Max 10MB</p>
            </div>
            
            <?php if($docsCount == 0): ?>
                <div class="doc-item" style="border-color: rgba(245, 158, 11, 0.3);">
                    <div style="display: flex; align-items: center;">
                        <i class="fa-solid fa-file-pdf" style="color: var(--status-warning);"></i>
                        <div>
                            <div style="font-size: 0.9rem; font-weight: 500; color: var(--status-warning);">Missing Documents</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Required • not yet uploaded</div>
                        </div>
                    </div>
                    <span style="color: var(--status-warning); font-size: 0.8rem; font-weight: 500;"><i class="fa-solid fa-triangle-exclamation"></i> Pending</span>
                </div>
            <?php else: ?>
                <!-- Dynamically list uploaded documents here if DB implemented -->
            <?php endif; ?>

            <div class="section-title">Browse Open Positions</div>
            
            <?php if(empty($jobs)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--text-muted); background: var(--bg-surface-light); border-radius: 8px; border: 1px solid var(--border-color);">
                    <i class="fa-solid fa-briefcase" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No open positions at the moment. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach($jobs as $job): 
                    $slotsLeft = max(0, $job['target_hires'] - $job['hired_count']);
                ?>
                <div class="job-card">
                    <div style="display: flex; align-items: center; gap: 1.5rem;">
                        <div style="width: 48px; height: 48px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--accent-blue);"><i class="fa-solid fa-laptop-code"></i></div>
                        <div>
                            <h4 style="font-size: 1rem; margin-bottom: 0.4rem;"><?= htmlspecialchars($job['title']) ?></h4>
                            <div style="display: flex; gap: 0.5rem;">
                                <span class="status-pill pill-success" style="font-size: 0.6rem;">Open</span>
                                <span class="status-pill pill-info" style="font-size: 0.6rem;"><?= htmlspecialchars($job['department']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: <?= $slotsLeft > 0 ? 'var(--text-primary)' : 'var(--status-danger)' ?>; font-family: var(--font-heading); line-height: 1;"><?= $slotsLeft ?></div>
                        <div style="font-size: 0.65rem; color: var(--text-muted); margin-bottom: 0.5rem;">slots left</div>
                        
                        <?php if($job['has_applied'] > 0): ?>
                            <button class="btn btn-outline" style="padding: 0.3rem 0.8rem; border-color: var(--status-success); color: var(--status-success);">Applied <i class="fa-solid fa-check"></i></button>
                        <?php elseif($slotsLeft > 0): ?>
                            <button class="btn btn-primary" style="padding: 0.3rem 0.8rem;">Apply Now</button>
                        <?php else: ?>
                            <button class="btn btn-outline" style="padding: 0.3rem 0.8rem;" disabled>Filled</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Right Sidebar -->
        <div class="right-sidebar" style="flex: 1; max-width: 350px;">
            <div>
                <h3 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary); margin-bottom: 1rem;"><i class="fa-solid fa-clipboard-list" style="margin-right: 0.5rem;"></i> My Application</h3>
                <?php if($latestApp): ?>
                    <ul class="summary-list">
                        <li><span class="lbl">Position</span><span class="val" style="text-align: right; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($latestApp['title']) ?></span></li>
                        <li><span class="lbl">Recruiter</span><span class="val" style="text-align: right; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($latestApp['recruiter_name']) ?></span></li>
                        <li><span class="lbl">Applied</span><span class="val"><?= date('M j, Y', strtotime($latestApp['applied_at'])) ?></span></li>
                        <li><span class="lbl">Status</span><span class="val" style="color: var(--accent-blue);"><?= $latestApp['status'] ?></span></li>
                        <li><span class="lbl">Documents</span><span class="val" style="color: var(--status-warning);"><?= $docsCount ?> / <?= $docsRequired ?> Uploaded</span></li>
                    </ul>
                <?php else: ?>
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-align: center; padding: 1rem 0;">No active application</div>
                <?php endif; ?>
            </div>
            
            <?php if($latestApp): ?>
            <div>
                <h3 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary); margin-bottom: 1rem;"><i class="fa-solid fa-comment-dots" style="margin-right: 0.5rem;"></i> Message Recruiter</h3>
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; text-align: center;">
                    <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1rem;">Direct line to <?= htmlspecialchars($latestApp['recruiter_name']) ?></p>
                    <a href="messages.php" class="btn btn-outline" style="width: 100%;"><i class="fa-solid fa-paper-plane"></i> Open Chat</a>
                </div>
            </div>
            <?php endif; ?>
            
            <div>
                <h3 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary); margin-bottom: 1rem;"><i class="fa-solid fa-bell" style="margin-right: 0.5rem; color: var(--status-warning);"></i> Notifications</h3>
                <?php if($latestApp): ?>
                    <div class="notif-card">
                        <div class="notif-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--status-success);"><i class="fa-solid fa-check-double"></i></div>
                        <div>
                            <h4 style="font-size: 0.8rem; font-weight: 600;">Application Received</h4>
                            <p style="font-size: 0.7rem; color: var(--text-muted);">We are currently reviewing your profile.</p>
                        </div>
                    </div>
                    <?php if($docsCount < $docsRequired): ?>
                    <div class="notif-card" style="border-color: rgba(245, 158, 11, 0.3);">
                        <div class="notif-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--status-warning);"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div>
                            <h4 style="font-size: 0.8rem; font-weight: 600; color: var(--status-warning);">Action needed</h4>
                            <p style="font-size: 0.7rem; color: var(--text-muted);">Please upload your remaining documents.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-align: center; padding: 1rem 0;">No new notifications</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
