<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

// Get aggregate scores
$results = [];
foreach (['pre', 'post'] as $type) {
    for ($i = 1; $i <= 5; $i++) {
        $stmt = $pdo->prepare("SELECT AVG(score) as mean, COUNT(score) as n FROM evaluations WHERE type = ? AND question_id = ?");
        $stmt->execute([$type, $i]);
        $data = $stmt->fetch();
        $results[$type][$i] = [
            'mean' => round($data['mean'], 2),
            'n' => $data['n']
        ];
    }
}

// Get comments
$stmt = $pdo->query("SELECT u.full_name, e.comments, e.submitted_at FROM evaluations e JOIN users u ON e.user_id = u.id WHERE e.type = 'post' AND e.comments IS NOT NULL AND e.comments != '' ORDER BY e.submitted_at DESC");
$comments = $stmt->fetchAll();

$pageTitle = "Evaluation Results";
include '../includes/header.php';
include '../includes/nav-admin.php';
?>

<div class="main-content">
    <div class="top-header">
        <h1>Evaluation Results</h1>
    </div>

    <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
        <div class="card">
            <div class="card-header">
                <h3 style="color: var(--color-admin);">Pre-Evaluation Mean Scores</h3>
            </div>
            <ul style="list-style:none;">
                <?php for($i=1; $i<=5; $i++): ?>
                    <li style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                        <span>Question <?= $i ?></span>
                        <strong><?= $results['pre'][$i]['mean'] ?> <small class="text-muted">(n=<?= $results['pre'][$i]['n'] ?>)</small></strong>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 style="color: var(--color-recruiter);">Post-Evaluation Mean Scores</h3>
            </div>
            <ul style="list-style:none;">
                <?php for($i=1; $i<=5; $i++): ?>
                    <li style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                        <span>Question <?= $i ?></span>
                        <strong><?= $results['post'][$i]['mean'] ?> <small class="text-muted">(n=<?= $results['post'][$i]['n'] ?>)</small></strong>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    </div>
    
    <div class="card" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h3>User Feedback & Comments</h3>
        </div>
        <?php if (count($comments) === 0): ?>
            <p class="text-muted">No comments provided yet.</p>
        <?php else: ?>
            <ul style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($comments as $c): ?>
                    <li style="background: var(--bg-dark); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                        <strong style="color: var(--color-admin);"><?= htmlspecialchars($c['full_name']) ?></strong> 
                        <small class="text-muted" style="float: right;"><?= date('M d, Y', strtotime($c['submitted_at'])) ?></small>
                        <p style="margin-top: 0.5rem; color: var(--text-secondary);"><?= nl2br(htmlspecialchars($c['comments'])) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
