<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$startOfWeek = date('Y-m-d', strtotime('monday this week'));

$quotas = $pdo->prepare("
    SELECT q.*, u.full_name as recruiter_name 
    FROM quotas q 
    JOIN users u ON q.recruiter_id = u.id 
    WHERE q.week_start = ?
");
$quotas->execute([$startOfWeek]);
$currentQuotas = $quotas->fetchAll();

$pageTitle = "Quota Progress Tracker";
include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-admin.php'; ?>
    <div class="top-nav">
        <div class="page-title">
            <?= $pageTitle ?? 'Admin' ?>
        </div>
    </div>
    <div class="content-area">
    

    <div class="dashboard-grid">
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-header">
                <h3>Real-time Progress</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php if (count($currentQuotas) === 0): ?>
                    <p>No quotas set for this week.</p>
                <?php endif; ?>
                
                <?php foreach ($currentQuotas as $q): ?>
                <?php 
                    $pct = min(100, ($q['target'] > 0 ? ($q['hired_count'] / $q['target']) * 100 : 0));
                    $color = $pct >= 100 ? 'var(--status-low)' : ($pct >= 50 ? 'var(--color-admin)' : 'var(--status-pending)');
                ?>
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <strong><?= htmlspecialchars($q['recruiter_name']) ?></strong>
                        <span><?= $q['hired_count'] ?> / <?= $q['target'] ?> Hires (<?= round($pct) ?>%)</span>
                    </div>
                    <div style="background: var(--bg-dark); height: 20px; border-radius: 10px; overflow: hidden; border: 1px solid var(--border-color);">
                        <div style="height: 100%; width: <?= $pct ?>%; background: <?= $color ?>; transition: width 1s ease;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
    </div>
</div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
