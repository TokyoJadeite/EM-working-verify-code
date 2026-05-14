<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$recruiterId = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM job_posts WHERE assigned_recruiter_id = ?");
$stmtCount->execute([$recruiterId]);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("SELECT * FROM job_posts WHERE assigned_recruiter_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $recruiterId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$pageTitle = "My Job Posts";
include '../includes/header.php';
?>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav"><div class="page-title">My Assigned Job Posts</div><div class="top-nav-actions"></div></div>
    <div class="content-area">

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Slots</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($posts) === 0): ?>
                        <tr><td colspan="5">No job posts assigned to you.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($posts as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                        <td><?= $p['slots'] ?></td>
                        <td><?= $p['deadline'] ? date('M d, Y', strtotime($p['deadline'])) : 'Open' ?></td>
                        <td>
                            <?php if ($p['is_active']): ?>
                                <span class="badge badge-low">Active</span>
                            <?php else: ?>
                                <span class="badge badge-high">Closed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="pending.php?job_id=<?= $p['id'] ?>" class="btn btn-outline" style="padding: 0.2rem 0.6rem; font-size: 0.8rem;">View Pipeline</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?p=<?= $i ?>" class="btn <?= $i === $page ? 'btn-recruiter' : 'btn-outline' ?>" style="padding: 0.2rem 0.6rem;"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
