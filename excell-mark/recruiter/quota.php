<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$recruiterId = $_SESSION['user_id'];
$startOfWeek = date('Y-m-d', strtotime('monday this week'));

$quota = $pdo->prepare("SELECT target, hired_count FROM quotas WHERE recruiter_id = ? AND week_start = ?");
$quota->execute([$recruiterId, $startOfWeek]);
$q = $quota->fetch();
$target = $q['target'] ?? 0;
$hired = $q['hired_count'] ?? 0;
$quotaPct = $target > 0 ? min(100, round(($hired / $target) * 100)) : 0;

$pageTitle = "My Quota";
include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav"><div class="page-title">My Hiring Quota</div><div class="top-nav-actions"></div></div>
    <div class="content-area">

    <div class="dashboard-grid">
        <div class="card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 0;">
            <h3 style="margin-bottom: 2rem;">Current Week Progress</h3>
            
            <div style="position: relative; width: 200px; height: 200px; margin: 1rem auto;">
                <svg viewBox="0 0 36 36" style="width: 100%; height: 100%;">
                    <path style="stroke: var(--border-color); stroke-width: 3.8; fill: none;"
                        d="M18 2.0845
                        a 15.9155 15.9155 0 0 1 0 31.831
                        a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path style="stroke: var(--color-recruiter); stroke-width: 3.8; stroke-dasharray: <?= $quotaPct ?>, 100; fill: none; transition: stroke-dasharray 1s ease;"
                        d="M18 2.0845
                        a 15.9155 15.9155 0 0 1 0 31.831
                        a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                </svg>
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 2rem; font-weight: bold;">
                    <?= $quotaPct ?>%
                </div>
            </div>
            <p style="color: var(--text-secondary); text-align: center; font-size: 1.2rem; margin-top: 1rem;">
                <?= $hired ?> out of <?= $target ?> hires target
            </p>
            
            <?php if ($quotaPct >= 100): ?>
                <div class="badge badge-low" style="margin-top: 1rem; font-size: 1rem; padding: 0.5rem 1rem;">Goal Met! 🎉</div>
            <?php endif; ?>
        </div>
    </div>
</div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
