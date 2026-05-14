<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$recruiters = $pdo->query("SELECT id, full_name FROM users WHERE role = 'recruiter'")->fetchAll();

$funnelData = [];
$stages = ['Pending', 'Reviewed', 'Shortlisted', 'Hired'];

foreach ($recruiters as $r) {
    $counts = [];
    foreach ($stages as $s) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE recruiter_id = ? AND stage = ?");
        $stmt->execute([$r['id'], $s]);
        $counts[] = $stmt->fetchColumn();
    }
    $funnelData[] = [
        'name' => $r['full_name'],
        'data' => $counts
    ];
}

$pageTitle = "Hiring Funnel View";
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
        <?php foreach ($funnelData as $index => $fd): ?>
        <div class="card">
            <div class="card-header">
                <h3><?= htmlspecialchars($fd['name']) ?></h3>
            </div>
            <canvas id="funnelChart<?= $index ?>" height="150"></canvas>
        </div>
        <?php endforeach; ?>
    </div>

    </div>
</div>

<?php ob_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stages = <?= json_encode($stages) ?>;
    const funnelData = <?= json_encode($funnelData) ?>;
    
    funnelData.forEach((fd, i) => {
        const ctx = document.getElementById('funnelChart' + i).getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: stages,
                datasets: [{
                    label: 'Applicants',
                    data: fd.data,
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.6)',
                        'rgba(79, 114, 255, 0.6)',
                        'rgba(167, 139, 250, 0.6)',
                        'rgba(16, 185, 129, 0.6)'
                    ],
                    borderColor: [
                        '#f59e0b',
                        '#4f72ff',
                        '#a78bfa',
                        '#10b981'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#252a38' }, ticks: { color: '#9ca3af', stepSize: 1 } },
                    x: { grid: { color: 'transparent' }, ticks: { color: '#9ca3af' } }
                },
                plugins: { legend: { display: false } }
            }
        });
    });
});
</script>
<?php
$extraJS = ob_get_clean();
include '../includes/footer.php';
?>

