<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = "Trends & Workforce";
include '../includes/header.php';

// Workforce Data
$recruiters = $pdo->query("SELECT id, full_name FROM users WHERE role = 'recruiter'")->fetchAll();
$workforceLabels = [];
$workforceData = [];

foreach ($recruiters as $r) {
    $workforceLabels[] = $r['full_name'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE recruiter_id = ?");
    $stmt->execute([$r['id']]);
    $workforceData[] = $stmt->fetchColumn();
}
?>

<div class="main-wrapper">
    <?php include '../includes/nav-admin.php'; ?>
    <div class="top-nav">
        <div class="page-title">
            <?= $pageTitle ?? 'Admin' ?>
        </div>
    </div>
    <div class="content-area">
    

    <div class="dashboard-grid" style="grid-template-columns: 1fr;">
        <div class="card">
            <div class="card-header">
                <h3>Total Applicants per Recruiter (Workforce Dist)</h3>
            </div>
            <canvas id="workforceChart" height="80"></canvas>
    </div>

    </div>
</div>

<?php ob_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('workforceChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($workforceLabels) ?>,
            datasets: [{
                label: 'Applicants Assigned',
                data: <?= json_encode($workforceData) ?>,
                backgroundColor: 'rgba(167, 139, 250, 0.6)',
                borderColor: '#a78bfa',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, grid: { color: '#252a38' }, ticks: { color: '#9ca3af', stepSize: 1 } },
                x: { grid: { color: 'transparent' }, ticks: { color: '#9ca3af' } }
            }
        }
    });
});
</script>
<?php
$extraJS = ob_get_clean();
include '../includes/footer.php';
?>

