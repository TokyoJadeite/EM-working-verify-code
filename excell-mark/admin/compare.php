<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$recruiters = $pdo->query("SELECT id, full_name FROM users WHERE role = 'recruiter'")->fetchAll();

$metrics = [];
$startOfWeek = date('Y-m-d', strtotime('monday this week'));

foreach ($recruiters as $r) {
    $rid = $r['id'];
    
    // Total Processed (any stage > pending or currently pending)
    $totalProcessed = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE recruiter_id = ?");
    $totalProcessed->execute([$rid]);
    $total = $totalProcessed->fetchColumn();
    
    // Hired
    $hired = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE recruiter_id = ? AND stage = 'Hired'");
    $hired->execute([$rid]);
    $hiredCount = $hired->fetchColumn();
    
    // Conversion Rate
    $conversionRate = $total > 0 ? round(($hiredCount / $total) * 100, 1) : 0;
    
    // Quota %
    $quota = $pdo->prepare("SELECT target FROM quotas WHERE recruiter_id = ? AND week_start = ?");
    $quota->execute([$rid, $startOfWeek]);
    $target = $quota->fetchColumn() ?: 0;
    
    $quotaPct = $target > 0 ? min(100, round(($hiredCount / $target) * 100)) : 0;
    
    // Avg Processing Time (days from applied to hired)
    $avgTime = $pdo->prepare("SELECT AVG(DATEDIFF(hired_at, applied_at)) FROM applications WHERE recruiter_id = ? AND stage = 'Hired' AND hired_at IS NOT NULL");
    $avgTime->execute([$rid]);
    $avgDays = round((float)$avgTime->fetchColumn(), 1);

    $metrics[] = [
        'name' => $r['full_name'],
        'total' => $total,
        'conversion' => $conversionRate,
        'quota_pct' => $quotaPct,
        'avg_days' => $avgDays
    ];
}

// Sort by conversion rate descending
usort($metrics, function($a, $b) {
    return $b['conversion'] <=> $a['conversion'];
});

$pageTitle = "Compare Metrics";
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
    

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Recruiter</th>
                        <th>Total Processed</th>
                        <th>Conversion Rate</th>
                        <th>Weekly Quota Progress</th>
                        <th>Avg Processing Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($metrics) === 0): ?>
                        <tr><td colspan="6">No metrics available.</td></tr>
                    <?php endif; ?>
                    <?php $rank = 1; foreach ($metrics as $m): ?>
                    <tr>
                        <td><strong>#<?= $rank++ ?></strong></td>
                        <td><?= htmlspecialchars($m['name']) ?></td>
                        <td><?= $m['total'] ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span><?= $m['conversion'] ?>%</span>
                                <div style="flex:1; background: var(--border-color); height: 6px; border-radius: 3px; max-width: 100px;">
                                    <div style="height: 100%; width: <?= $m['conversion'] ?>%; background: var(--color-admin);"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span><?= $m['quota_pct'] ?>%</span>
                                <div style="flex:1; background: var(--border-color); height: 6px; border-radius: 3px; max-width: 100px;">
                                    <div style="height: 100%; width: <?= $m['quota_pct'] ?>%; background: var(--status-low);"></div>
                                </div>
                            </div>
                        </td>
                        <td><?= $m['avg_days'] > 0 ? $m['avg_days'] . ' days' : 'N/A' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
