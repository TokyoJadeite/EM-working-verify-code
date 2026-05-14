<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $role = $_POST['role'] ?? 'recruiter';

        // Validate role
        if (!in_array($role, ['recruiter', 'admin'])) {
            $error = "Invalid role selected.";
        } elseif (empty($fullName) || empty($email)) {
            $error = "Name and email are required.";
        } else {
            $password = password_hash('password', PASSWORD_DEFAULT); // Default password

            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already exists.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, contact_number) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$fullName, $email, $password, $role, $contact]);
                $roleLabel = ucfirst($role);
                $success = "{$roleLabel} account created successfully with default password 'password'.";
            }
        }
    }

    if ($_POST['action'] === 'delete' && isset($_POST['user_id'])) {
        $targetId = (int)$_POST['user_id'];
        // Prevent deleting self
        if ($targetId === (int)$_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('recruiter', 'admin')");
            $stmt->execute([$targetId]);
            $success = "Account removed successfully.";
        }
    }

    if ($_POST['action'] === 'reset_password' && isset($_POST['user_id'])) {
        $targetId = (int)$_POST['user_id'];
        $defaultHash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role IN ('recruiter', 'admin')");
        $stmt->execute([$defaultHash, $targetId]);
        $success = "Password has been reset to the default 'password'.";
    }
}

// View filter
$viewRole = $_GET['view'] ?? 'all';

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query based on filter
$whereClause = "WHERE role IN ('recruiter', 'admin')";
$params = [];
if ($viewRole === 'recruiter') {
    $whereClause = "WHERE role = 'recruiter'";
} elseif ($viewRole === 'admin') {
    $whereClause = "WHERE role = 'admin'";
}

$totalRows = $pdo->query("SELECT COUNT(*) FROM users {$whereClause}")->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("SELECT * FROM users {$whereClause} ORDER BY role ASC, created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$accounts = $stmt->fetchAll();

// Counts for filter badges
$recruiterCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'recruiter'")->fetchColumn();
$adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

$pageTitle = "Account Management";
include '../includes/header.php';
?>

<style>
    .role-selector-group {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 2rem;
    }
    .role-select-card {
        flex: 1;
        padding: 1rem 1.25rem;
        background: var(--bg-surface-light);
        border: 2px solid var(--border-color);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    .role-select-card:hover { border-color: var(--border-light); }
    .role-select-card.selected { border-color: var(--color-admin); background: rgba(139, 92, 246, 0.05); }
    .role-select-card.selected[data-role="recruiter"] { border-color: var(--color-recruiter); background: rgba(59, 130, 246, 0.05); }
    .role-select-card .role-icon { font-size: 1.3rem; margin-bottom: 0.4rem; }
    .role-select-card .role-name { font-size: 0.85rem; font-weight: 600; }
    .role-select-card .role-desc { font-size: 0.72rem; color: var(--text-muted); margin-top: 0.15rem; }

    .filter-pills {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .filter-pill {
        padding: 0.35rem 0.85rem;
        font-size: 0.78rem;
        font-weight: 500;
        border-radius: 99px;
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        background: transparent;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .filter-pill:hover { border-color: var(--border-light); color: var(--text-secondary); }
    .filter-pill.active { border-color: var(--color-admin); color: var(--color-admin); background: rgba(139, 92, 246, 0.08); }
    .filter-pill .pill-count {
        background: var(--bg-surface-light);
        padding: 0.05rem 0.4rem;
        border-radius: 99px;
        font-size: 0.7rem;
    }

    .account-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.85rem 0;
        border-bottom: 1px solid var(--border-color);
    }
    .account-row:last-child { border-bottom: none; }
    .account-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 600; font-size: 0.8rem; color: #fff;
        flex-shrink: 0;
    }
    .account-info { flex: 1; min-width: 0; }
    .account-name { font-size: 0.88rem; font-weight: 500; }
    .account-email { font-size: 0.75rem; color: var(--text-muted); }
    .account-meta { display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; }
    .account-actions { display: flex; gap: 0.4rem; }
    .account-actions .btn { padding: 0.25rem 0.5rem; font-size: 0.75rem; }

    .self-badge {
        font-size: 0.65rem;
        padding: 0.1rem 0.4rem;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.2);
        color: var(--color-admin);
        border-radius: 4px;
        margin-left: 0.4rem;
    }
</style>

<div class="main-wrapper">
    <?php include '../includes/nav-admin.php'; ?>
    <div class="top-nav">
        <div class="page-title">
            <?= $pageTitle ?>
        </div>
    </div>
    <div class="content-area">

    <?php if ($error): ?><div class="flash-message flash-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="flash-message flash-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="dashboard-grid" style="grid-template-columns: 1fr 2fr;">
        <!-- Create Account Card -->
        <div class="card">
            <div class="card-header">
                <h3>Create New Account</h3>
            </div>
            <form method="POST" id="createAccountForm">
                <input type="hidden" name="action" value="create">

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Account Type</label>
                    <div class="role-selector-group">
                        <div class="role-select-card selected" data-role="recruiter" onclick="selectRole(this, 'recruiter')">
                            <div class="role-icon" style="color: var(--color-recruiter);"><i class="fa-solid fa-magnifying-glass"></i></div>
                            <div class="role-name">Recruiter</div>
                            <div class="role-desc">Pipeline & hiring</div>
                        </div>
                        <div class="role-select-card" data-role="admin" onclick="selectRole(this, 'admin')">
                            <div class="role-icon" style="color: var(--color-admin);"><i class="fa-solid fa-shield"></i></div>
                            <div class="role-name">Admin</div>
                            <div class="role-desc">System access</div>
                        </div>
                    </div>
                    <input type="hidden" name="role" id="selectedRole" value="recruiter">
                </div>

                <div class="form-group">
                    <label>Full Name <span style="color: var(--status-danger);">*</span></label>
                    <input type="text" name="full_name" class="form-control" placeholder="Enter full name" required>
                </div>
                <div class="form-group">
                    <label>Email Address <span style="color: var(--status-danger);">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="user@company.com" required>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" placeholder="09XX XXX XXXX">
                </div>

                <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.15); border-radius: 6px; padding: 0.65rem 0.85rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-info-circle" style="color: var(--color-recruiter); font-size: 0.8rem;"></i>
                    <span style="font-size: 0.75rem; color: var(--text-secondary);">Default password: <strong style="color: var(--text-primary);">password</strong> — user should change on first login.</span>
                </div>

                <button type="submit" class="btn btn-admin" style="width: 100%;" id="submitBtn">
                    <i class="fa-solid fa-user-plus"></i> Create <span id="roleBtnLabel">Recruiter</span> Account
                </button>
            </form>
        </div>

        <!-- Accounts List -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>All Staff Accounts</h3>
                <span style="font-size: 0.78rem; color: var(--text-muted);"><?= $recruiterCount + $adminCount ?> total</span>
            </div>

            <div class="filter-pills">
                <a href="?view=all" class="filter-pill <?= $viewRole === 'all' ? 'active' : '' ?>">
                    All <span class="pill-count"><?= $recruiterCount + $adminCount ?></span>
                </a>
                <a href="?view=recruiter" class="filter-pill <?= $viewRole === 'recruiter' ? 'active' : '' ?>">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 0.7rem;"></i> Recruiters <span class="pill-count"><?= $recruiterCount ?></span>
                </a>
                <a href="?view=admin" class="filter-pill <?= $viewRole === 'admin' ? 'active' : '' ?>">
                    <i class="fa-solid fa-shield" style="font-size: 0.7rem;"></i> Admins <span class="pill-count"><?= $adminCount ?></span>
                </a>
            </div>

            <div>
                <?php if (count($accounts) === 0): ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                        <i class="fa-solid fa-users" style="font-size: 2rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
                        <p>No accounts found.</p>
                    </div>
                <?php endif; ?>
                <?php foreach ($accounts as $acc):
                    $initials = strtoupper(substr($acc['full_name'], 0, 1) . (strpos($acc['full_name'], ' ') ? substr($acc['full_name'], strpos($acc['full_name'], ' ') + 1, 1) : ''));
                    $isSelf = ($acc['id'] === $_SESSION['user_id']);
                    $isAdmin = ($acc['role'] === 'admin');
                    $avatarColor = $isAdmin ? 'var(--color-admin)' : 'var(--color-recruiter)';
                ?>
                <div class="account-row">
                    <div class="account-avatar" style="background: <?= $avatarColor ?>;"><?= $initials ?></div>
                    <div class="account-info">
                        <div class="account-name">
                            <?= htmlspecialchars($acc['full_name']) ?>
                            <?php if ($isSelf): ?><span class="self-badge">You</span><?php endif; ?>
                        </div>
                        <div class="account-email"><?= htmlspecialchars($acc['email']) ?></div>
                    </div>
                    <div class="account-meta">
                        <?php if ($isAdmin): ?>
                            <span class="badge badge-admin">Admin</span>
                        <?php else: ?>
                            <span class="badge badge-recruiter">Recruiter</span>
                        <?php endif; ?>
                        <span style="font-size: 0.72rem; color: var(--text-muted);"><?= date('M j, Y', strtotime($acc['created_at'])) ?></span>
                    </div>
                    <div class="account-actions">
                        <?php if (!$isSelf): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Reset this user\'s password to default?');">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?= $acc['id'] ?>">
                                <button type="submit" class="btn btn-outline" title="Reset password to default">
                                    <i class="fa-solid fa-key"></i>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this account? This action cannot be undone.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $acc['id'] ?>">
                                <button type="submit" class="btn btn-outline" title="Delete account" style="border-color: rgba(239,68,68,0.3); color: var(--status-danger);">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="font-size: 0.72rem; color: var(--text-muted); font-style: italic;">—</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div style="margin-top: 1rem; display: flex; gap: 0.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <?php for($i=1; $i<=$totalPages; $i++): ?>
                    <a href="?view=<?= $viewRole ?>&p=<?= $i ?>" class="btn <?= $i === $page ? 'btn-admin' : 'btn-outline' ?>" style="padding: 0.2rem 0.6rem;"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    </div>
</div>

<script>
function selectRole(card, role) {
    document.querySelectorAll('.role-select-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('selectedRole').value = role;
    document.getElementById('roleBtnLabel').textContent = role.charAt(0).toUpperCase() + role.slice(1);
}
</script>

<?php include '../includes/footer.php'; ?>
