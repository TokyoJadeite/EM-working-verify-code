<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$recruiterId = $_SESSION['user_id'];
$stages = ['Pending', 'Reviewed', 'Shortlisted', 'Hired'];
$counts = [];

foreach ($stages as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE recruiter_id = ? AND stage = ?");
    $stmt->execute([$recruiterId, $s]);
    $counts[] = $stmt->fetchColumn();
}

$pageTitle = "My Funnel";
include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav"><div class="page-title">My Hiring Funnel</div><div class="top-nav-actions"></div></div>
    <div class="content-area">

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <canvas id="myFunnelChart" height="200"></canvas>
    </div>
</div>

<?php ob_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('myFunnelChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($stages) ?>,
            datasets: [{
                label: 'Applicants',
                data: <?= json_encode($counts) ?>,
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
</script>
<?php
$extraJS = ob_get_clean();
include '../includes/footer.php';
?>
