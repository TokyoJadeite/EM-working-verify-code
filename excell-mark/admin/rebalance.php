<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reassign_job') {
    $jobId = $_POST['job_post_id'];
    $newRecruiterId = $_POST['new_recruiter_id'];
    
    // Log previous state
    $stmt = $pdo->prepare("SELECT assigned_recruiter_id FROM job_posts WHERE id = ?");
    $stmt->execute([$jobId]);
    $oldRecruiterId = $stmt->fetchColumn();
    
    // Update Job Post
    $pdo->prepare("UPDATE job_posts SET assigned_recruiter_id = ? WHERE id = ?")->execute([$newRecruiterId, $jobId]);
    
    // Update pending applications related to this job post
    $pdo->prepare("UPDATE applications SET recruiter_id = ? WHERE job_post_id = ? AND stage IN ('Pending', 'Reviewed', 'Shortlisted')")->execute([$newRecruiterId, $jobId]);
    
    // Log Rebalance
    if ($newRecruiterId) {
        $pdo->prepare("INSERT INTO workload_log (recruiter_id, job_post_id, assigned_by) VALUES (?, ?, ?)")->execute([$newRecruiterId, $jobId, $_SESSION['user_id']]);
        
        // Notify new recruiter
        $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'assignment')")->execute([$newRecruiterId, "A job post was reassigned to you by the Admin."]);
    }
    
    $success = "Job post and its pending applications have been reassigned.";
}

// Calculate workload (active posts + pending apps)
$recruiters = $pdo->query("SELECT id, full_name FROM users WHERE role = 'recruiter'")->fetchAll();
$workloadData = [];

foreach ($recruiters as $r) {
    $activePosts = $pdo->prepare("SELECT COUNT(*) FROM job_posts WHERE assigned_recruiter_id = ? AND is_active = 1");
    $activePosts->execute([$r['id']]);
    $postCount = $activePosts->fetchColumn();
    
    $pendingApps = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE recruiter_id = ? AND stage = 'Pending'");
    $pendingApps->execute([$r['id']]);
    $appCount = $pendingApps->fetchColumn();
    
    $totalLoad = $postCount + $appCount;
    
    $status = 'Low';
    if ($totalLoad >= 6 && $totalLoad <= 15) $status = 'Moderate';
    if ($totalLoad > 15) $status = 'High';
    
    $workloadData[] = [
        'id' => $r['id'],
        'name' => $r['full_name'],
        'posts' => $postCount,
        'apps' => $appCount,
        'status' => $status
    ];
}

// Get active job posts for reassignment
$jobPosts = $pdo->query("SELECT jp.id, jp.title, jp.assigned_recruiter_id, u.full_name as current_recruiter FROM job_posts jp LEFT JOIN users u ON jp.assigned_recruiter_id = u.id WHERE jp.is_active = 1")->fetchAll();

$pageTitle = "Workload Rebalancing";
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
    

    <?php if ($success): ?><div class="flash-message flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="dashboard-grid" style="grid-template-columns: 2fr 1fr;">
        <div class="card">
            <div class="card-header">
                <h3>Recruiter Workload Map</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Recruiter</th>
                            <th>Active Posts</th>
                            <th>Pending Apps</th>
                            <th>Heat Indicator</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workloadData as $w): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                            <td><?= $w['posts'] ?></td>
                            <td><?= $w['apps'] ?></td>
                            <td>
                                <?php if ($w['status'] === 'Low'): ?>
                                    <span class="badge badge-low">Low</span>
                                <?php elseif ($w['status'] === 'Moderate'): ?>
                                    <span class="badge badge-mod">Moderate</span>
                                <?php else: ?>
                                    <span class="badge badge-high">High</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Reassign Job Post</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reassign_job">
                <div class="form-group">
                    <label>Select Job Post</label>
                    <select name="job_post_id" class="form-control" required>
                        <option value="">-- Choose Job --</option>
                        <?php foreach ($jobPosts as $jp): ?>
                            <option value="<?= $jp['id'] ?>">
                                <?= htmlspecialchars($jp['title']) ?> (Currently: <?= htmlspecialchars($jp['current_recruiter'] ?? 'Unassigned') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assign to Recruiter</label>
                    <select name="new_recruiter_id" class="form-control" required>
                        <option value="">-- Choose Recruiter --</option>
                        <?php foreach ($recruiters as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-admin" style="width: 100%;">Execute Transfer</button>
            </form>
    </div>
</div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
