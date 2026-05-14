<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$success = '';
$error = '';

$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('sunday this week'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recruiter_id = $_POST['recruiter_id'];
    $target = (int)$_POST['target'];
    
    if (empty($recruiter_id) || $target <= 0) {
        $error = "Please select a recruiter and set a valid target.";
    } else {
        // Check if quota exists for this week
        $stmt = $pdo->prepare("SELECT id FROM quotas WHERE recruiter_id = ? AND week_start = ?");
        $stmt->execute([$recruiter_id, $startOfWeek]);
        if ($stmt->fetch()) {
            // Update
            $pdo->prepare("UPDATE quotas SET target = ? WHERE recruiter_id = ? AND week_start = ?")->execute([$target, $recruiter_id, $startOfWeek]);
            $success = "Quota updated for the week.";
        } else {
            // Insert
            $pdo->prepare("INSERT INTO quotas (recruiter_id, week_start, week_end, target) VALUES (?, ?, ?, ?)")->execute([$recruiter_id, $startOfWeek, $endOfWeek, $target]);
            $success = "New quota assigned.";
        }
    }
}

$recruiters = $pdo->query("SELECT id, full_name FROM users WHERE role = 'recruiter'")->fetchAll();

$quotas = $pdo->prepare("
    SELECT q.*, u.full_name as recruiter_name 
    FROM quotas q 
    JOIN users u ON q.recruiter_id = u.id 
    WHERE q.week_start = ?
");
$quotas->execute([$startOfWeek]);
$currentQuotas = $quotas->fetchAll();

$pageTitle = "Quota Configuration";
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
    

    <?php if ($error): ?><div class="flash-message flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="flash-message flash-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="dashboard-grid" style="grid-template-columns: 1fr 2fr;">
        <div class="card">
            <div class="card-header">
                <h3>Set Weekly Target</h3>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Recruiter</label>
                    <select name="recruiter_id" class="form-control" required>
                        <option value="">-- Select Recruiter --</option>
                        <?php foreach ($recruiters as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hiring Target (Count)</label>
                    <input type="number" name="target" class="form-control" min="1" required>
                </div>
                <button type="submit" class="btn btn-admin" style="width: 100%;">Save Quota</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Current Week Quotas</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Recruiter</th>
                            <th>Target</th>
                            <th>Hired So Far</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($currentQuotas) === 0): ?>
                            <tr><td colspan="4">No quotas set for this week.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($currentQuotas as $q): ?>
                        <?php 
                            $pct = min(100, ($q['hired_count'] / $q['target']) * 100);
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($q['recruiter_name']) ?></strong></td>
                            <td><?= $q['target'] ?></td>
                            <td><?= $q['hired_count'] ?></td>
                            <td style="width: 40%;">
                                <div style="background: var(--border-color); height: 8px; border-radius: 4px; overflow: hidden; margin-top: 5px;">
                                    <div style="height: 100%; width: <?= $pct ?>%; background: var(--color-admin);"></div>
                                </div>
                                <small style="color: var(--text-secondary);"><?= round($pct) ?>% Completed</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </div>
</div>

    </div>
</div>
<?php include '../includes/footer.php'; ?>
