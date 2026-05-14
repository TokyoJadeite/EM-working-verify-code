<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$totalRows = $pdo->query("SELECT COUNT(*) FROM job_posts")->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("
    SELECT jp.*, u.full_name as recruiter_name 
    FROM job_posts jp 
    LEFT JOIN users u ON jp.assigned_recruiter_id = u.id 
    ORDER BY jp.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$pageTitle = "Job Posts";
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
                        <th>Job Title</th>
                        <th>Slots</th>
                        <th>Deadline</th>
                        <th>Assigned Recruiter</th>
                        <th>Workload Badge</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($posts) === 0): ?>
                        <tr><td colspan="6">No job posts found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($posts as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                        <td><?= $p['slots'] ?></td>
                        <td><?= $p['deadline'] ? date('M d, Y', strtotime($p['deadline'])) : 'Open' ?></td>
                        <td><?= htmlspecialchars($p['recruiter_name'] ?? 'Unassigned') ?></td>
                        <td>
                            <?php if ($p['workload_status'] === 'Low'): ?>
                                <span class="badge badge-low">Low</span>
                            <?php elseif ($p['workload_status'] === 'Moderate'): ?>
                                <span class="badge badge-mod">Moderate</span>
                            <?php else: ?>
                                <span class="badge badge-high">High</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['is_active']): ?>
                                <span class="badge badge-low">Active</span>
                            <?php else: ?>
                                <span class="badge badge-high">Closed</span>
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
