<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('recruiter');

$userId = $_SESSION['user_id'];
$success = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'profile';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');

        if (empty($fullName)) {
            $error = "Full name is required.";
            $activeTab = 'profile';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, contact_number = ? WHERE id = ?");
            $stmt->execute([$fullName, $contact, $userId]);
            $_SESSION['full_name'] = $fullName;
            $success = "Profile updated successfully.";
            $activeTab = 'profile';
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $activeTab = 'security';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = "All password fields are required.";
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $error = "Current password is incorrect.";
        } elseif (strlen($newPassword) < 6) {
            $error = "New password must be at least 6 characters.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "New passwords do not match.";
        } elseif ($currentPassword === $newPassword) {
            $error = "New password must be different from current password.";
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            $success = "Password changed successfully.";
        }
    }
}

$pageTitle = "Settings";
include '../includes/header.php';
?>

<style>
    .settings-tabs {
        display: flex;
        gap: 0;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 2rem;
    }
    .settings-tab {
        padding: 0.85rem 1.5rem;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .settings-tab:hover { color: var(--text-secondary); }
    .settings-tab.active {
        color: var(--color-recruiter);
        border-bottom-color: var(--color-recruiter);
    }

    .settings-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        max-width: 600px;
    }
    .settings-card-header {
        padding: 1.5rem 1.75rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .settings-card-header .icon-circle {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
    }
    .settings-card-header h3 { font-size: 1rem; font-weight: 600; }
    .settings-card-header p { font-size: 0.78rem; color: var(--text-muted); margin-top: 0.1rem; }
    .settings-card-body { padding: 1.75rem; }

    .settings-card-body .form-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--border-color);
    }

    .btn-recruiter-solid {
        background: var(--color-recruiter);
        color: #fff;
        border: none;
    }
    .btn-recruiter-solid:hover { background: #2563eb; }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border-color);
    }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-size: 0.8rem; color: var(--text-muted); }
    .info-value { font-size: 0.85rem; font-weight: 500; }

    .password-wrapper { position: relative; }
    .password-wrapper .toggle-pass {
        position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
        color: var(--border-light); cursor: pointer; transition: color 0.2s;
    }
    .password-wrapper .toggle-pass:hover { color: var(--text-primary); }

    .default-pw-warning {
        background: rgba(245, 158, 11, 0.08);
        border: 1px solid rgba(245, 158, 11, 0.25);
        border-radius: 8px;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    .default-pw-warning i { color: var(--status-warning); margin-top: 0.15rem; }
    .default-pw-warning p { font-size: 0.82rem; color: var(--text-secondary); line-height: 1.5; }
    .default-pw-warning strong { color: var(--status-warning); }

    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>

<div class="main-wrapper">
    <?php include '../includes/nav-recruiter.php'; ?>
    <div class="top-nav">
        <div class="page-title">
            <i class="fa-solid fa-gear" style="color: var(--text-muted); margin-right: 0.25rem;"></i> Settings
        </div>
    </div>

    <div class="content-area">
        <?php if ($success): ?>
            <div class="flash-message flash-success" style="max-width: 600px; margin-bottom: 1.5rem;">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash-message flash-error" style="max-width: 600px; margin-bottom: 1.5rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="settings-tabs" style="max-width: 600px;">
            <a href="?tab=profile" class="settings-tab <?= $activeTab === 'profile' ? 'active' : '' ?>">
                <i class="fa-solid fa-user"></i> Profile
            </a>
            <a href="?tab=security" class="settings-tab <?= $activeTab === 'security' ? 'active' : '' ?>">
                <i class="fa-solid fa-lock"></i> Security
            </a>
        </div>

        <!-- Profile Tab -->
        <div class="tab-content <?= $activeTab === 'profile' ? 'active' : '' ?>">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="icon-circle" style="background: rgba(59, 130, 246, 0.1); color: var(--color-recruiter);">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div>
                        <h3>Profile Information</h3>
                        <p>Update your personal details</p>
                    </div>
                </div>
                <div class="settings-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity: 0.6;">
                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.3rem;">
                                <i class="fa-solid fa-lock" style="font-size: 0.6rem;"></i> Email cannot be changed. Contact your admin.
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" name="contact_number" id="contact_number" class="form-control" value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>" placeholder="09XX XXX XXXX">
                        </div>

                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                            <div class="info-row">
                                <span class="info-label">Role</span>
                                <span class="badge badge-recruiter">Recruiter</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Account Created</span>
                                <span class="info-value"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-recruiter-solid">
                                <i class="fa-solid fa-floppy-disk"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div class="tab-content <?= $activeTab === 'security' ? 'active' : '' ?>">
            <?php
            // Check if user still has the default password
            $hasDefaultPassword = password_verify('password', $user['password_hash']);
            ?>
            <?php if ($hasDefaultPassword): ?>
            <div class="default-pw-warning" style="max-width: 600px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <p><strong>You are using the default password.</strong><br>Your account was created with a default password. Please change it immediately to secure your account.</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="icon-circle" style="background: rgba(245, 158, 11, 0.1); color: var(--status-warning);">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h3>Change Password</h3>
                        <p>Keep your account secure with a strong password</p>
                    </div>
                </div>
                <div class="settings-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter your current password" required>
                                <i class="fa-regular fa-eye-slash toggle-pass" onclick="togglePw('current_password', this)"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                                <i class="fa-regular fa-eye-slash toggle-pass" onclick="togglePw('new_password', this)"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter new password" required minlength="6">
                                <i class="fa-regular fa-eye-slash toggle-pass" onclick="togglePw('confirm_password', this)"></i>
                            </div>
                            <div id="matchMsg" style="font-size: 0.72rem; margin-top: 0.3rem;"></div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-recruiter-solid">
                                <i class="fa-solid fa-key"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePw(id, icon) {
    const f = document.getElementById(id);
    if (f.type === 'password') { f.type = 'text'; icon.classList.replace('fa-eye-slash','fa-eye'); }
    else { f.type = 'password'; icon.classList.replace('fa-eye','fa-eye-slash'); }
}
document.getElementById('confirm_password')?.addEventListener('input', function() {
    const pw = document.getElementById('new_password').value;
    const msg = document.getElementById('matchMsg');
    if (this.value.length === 0) { msg.textContent = ''; return; }
    if (pw === this.value) { msg.textContent = '✓ Passwords match'; msg.style.color = '#10b981'; }
    else { msg.textContent = '✗ Passwords do not match'; msg.style.color = '#ef4444'; }
});
</script>

<?php include '../includes/footer.php'; ?>
