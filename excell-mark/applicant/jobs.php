<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../ai/ai_match.php';
requireRole('applicant');

$applicantId = $_SESSION['user_id'];

// Get active job posts
$stmt = $pdo->query("SELECT * FROM job_posts WHERE is_active = 1 ORDER BY created_at DESC");
$jobPosts = $stmt->fetchAll();

// Get AI Matches
$aiScores = getAiJobMatches($applicantId, $jobPosts);

// Sort jobs by AI Match Score
usort($jobPosts, function($a, $b) use ($aiScores) {
    return $aiScores[$b['id']] <=> $aiScores[$a['id']];
});

$pageTitle = "Browse Jobs";
include '../includes/header.php';
include '../includes/nav-applicant.php';
?>

<div class="main-content">
    <div class="top-header">
        <h1>Browse Jobs</h1>
        <span class="badge badge-admin"><i class="fa-solid fa-wand-magic-sparkles"></i> AI Powered Matching</span>
    </div>

    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Jobs are sorted by relevance to your uploaded documents using our Word2Vec matching algorithm.</p>

    <div class="dashboard-grid">
        <?php if (count($jobPosts) === 0): ?>
            <p>No open job posts at the moment.</p>
        <?php endif; ?>
        
        <?php foreach ($jobPosts as $job): ?>
        <?php $score = $aiScores[$job['id']]; ?>
        <div class="card" style="display: flex; flex-direction: column; <?= $score > 80 ? 'border-color: var(--color-admin); box-shadow: 0 0 10px rgba(167, 139, 250, 0.1);' : '' ?>">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <h3 style="font-size: 1.2rem;"><?= htmlspecialchars($job['title']) ?></h3>
                
                <?php if ($score >= 90): ?>
                    <span class="badge badge-low" style="background: var(--status-low); color: #fff;"><?= $score ?>% Match</span>
                <?php elseif ($score >= 70): ?>
                    <span class="badge badge-admin"><?= $score ?>% Match</span>
                <?php else: ?>
                    <span class="badge badge-mod"><?= $score ?>% Match</span>
                <?php endif; ?>
            </div>
            
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem; flex-grow: 1;">
                <?= nl2br(htmlspecialchars(substr($job['description'], 0, 150))) ?>...
            </p>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-users"></i> <?= $job['slots'] ?> slots available</span>
                <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-primary" style="padding: 0.4rem 1rem;">Apply Now</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
