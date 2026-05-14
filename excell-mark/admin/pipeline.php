<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$totalRows = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("
    SELECT a.*, u1.full_name as applicant_name, jp.title as job_title, u2.full_name as recruiter_name
    FROM applications a
    JOIN users u1 ON a.applicant_id = u1.id
    JOIN job_posts jp ON a.job_post_id = jp.id
    LEFT JOIN users u2 ON a.recruiter_id = u2.id
    ORDER BY a.applied_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$applications = $stmt->fetchAll();

$pageTitle = "Pipeline Overview";
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
                        <th>Applicant</th>
                        <th>Job Applied</th>
                        <th>Assigned To</th>
                        <th>Applied Date</th>
                        <th>Stage</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($applications) === 0): ?>
                        <tr><td colspan="6">No applications found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app['applicant_name']) ?></strong></td>
                        <td><?= htmlspecialchars($app['job_title']) ?></td>
                        <td><?= htmlspecialchars($app['recruiter_name'] ?? 'Unassigned') ?></td>
                        <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                        <td>
                            <?php 
                                $badgeClass = 'badge-low';
                                if ($app['stage'] === 'Pending') $badgeClass = 'badge-mod';
                                if ($app['stage'] === 'Reviewed') $badgeClass = 'badge-admin';
                                if ($app['stage'] === 'Shortlisted') $badgeClass = 'badge-recruiter';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $app['stage'] ?></span>
                        </td>
                        <td>
                            <?php if ($app['is_overdue']): ?>
                                <span class="badge badge-high">Overdue</span>
                            <?php else: ?>
                                <span class="badge badge-low">Normal</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?p=<?= $i ?>" class="btn <?= $i === $page ? 'btn-admin' : 'btn-outline' ?>" style="padding: 0.2rem 0.6rem;"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
