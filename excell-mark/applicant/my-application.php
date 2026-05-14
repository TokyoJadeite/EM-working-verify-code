<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('applicant');

$applicantId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT a.*, jp.title as job_title, u.full_name as recruiter_name 
    FROM applications a 
    JOIN job_posts jp ON a.job_post_id = jp.id 
    LEFT JOIN users u ON a.recruiter_id = u.id 
    WHERE a.applicant_id = ? 
    ORDER BY a.applied_at DESC
");
$stmt->execute([$applicantId]);
$applications = $stmt->fetchAll();

$pageTitle = "My Applications";
include '../includes/header.php';
include '../includes/nav-applicant.php';
?>

<div class="main-content">
    <div class="top-header">
        <h1>My Applications</h1>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Applied On</th>
                        <th>Assigned Recruiter</th>
                        <th>Current Stage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($applications) === 0): ?>
                        <tr><td colspan="4">You have not applied to any jobs yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app['job_title']) ?></strong></td>
                        <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                        <td><?= htmlspecialchars($app['recruiter_name'] ?? 'Unassigned') ?></td>
                        <td>
                            <?php 
                                $badge = 'badge-mod';
                                if($app['stage'] === 'Reviewed') $badge = 'badge-admin';
                                if($app['stage'] === 'Shortlisted') $badge = 'badge-recruiter';
                                if($app['stage'] === 'Hired') $badge = 'badge-low';
                            ?>
                            <span class="badge <?= $badge ?>"><?= $app['stage'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
