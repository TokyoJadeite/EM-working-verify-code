<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mail.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    $redirect = $role === 'applicant' ? 'home.php' : 'dashboard.php';
    header("Location: /excell-mark/{$role}/{$redirect}");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'applicant';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($role === 'applicant') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                header("Location: /excell-mark/applicant/home.php");
                exit;
            } else {
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                
                $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], password_hash($otp, PASSWORD_DEFAULT), $expires_at]);
                
                sendOTP($email, $otp);
                
                $_SESSION['pending_user_id'] = $user['id'];
                $_SESSION['pending_email'] = $email;
                $_SESSION['pending_role'] = $user['role'];
                
                // Store OTP in session for dev display when PHPMailer is not installed
                if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $_SESSION['dev_otp'] = $otp;
                }
                
                header("Location: /excell-mark/verify.php");
                exit;
            }
        } else {
            $error = "Invalid credentials or role mismatch.";
        }
    }
}
$pageTitle = "Login | ExcellMark";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="/excell-mark/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="auth-split">
    <div class="auth-left">
        <div class="brand-sub">// RECRUITMENT SYSTEM</div>
        <h1 class="brand-title">EXCELL<span>MARK</span></h1>
        <p class="brand-desc">End-to-end hiring intelligence — from application to offer letter, in one unified workspace.</p>
        <div style="margin-top: auto; display: flex; align-items: center; gap: 0.5rem;">
            <div style="width: 8px; height: 8px; background: var(--text-muted); border-radius: 50%;"></div>
            <span style="color: var(--text-muted); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase;">Applicant Portal</span>
        </div>
    </div>
    
    <div class="auth-right">
        <div style="max-width: 400px; width: 100%; margin: 0 auto;">
            <div class="auth-tabs">
                <div class="auth-tab active">Sign In</div>
                <div class="auth-tab" onclick="window.location.href='register.php'">Create Account</div>
            </div>
            
            <?php if ($error): ?>
                <div class="flash-message flash-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['registered'])): ?>
                <div class="flash-message flash-success"><i class="fa-solid fa-circle-check"></i> Registration successful. Please login.</div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group" style="margin-bottom: 0.5rem;">
                    <label>SELECT YOUR ROLE</label>
                </div>
                
                <div class="role-selectors">
                    <label class="role-option active" onclick="document.querySelectorAll('.role-option').forEach(el=>el.classList.remove('active')); this.classList.add('active');">
                        <input type="radio" name="role" value="applicant" class="role-input" checked>
                        <div class="role-icon" style="color: var(--color-applicant);"><i class="fa-solid fa-user"></i></div>
                        <div class="role-details">
                            <h4>Applicant</h4>
                            <p>Browse openings & track your application</p>
                        </div>
                        <div class="role-radio-circle"></div>
                    </label>
                    
                    <div style="text-align: center; color: var(--border-light); font-size: 0.65rem; letter-spacing: 0.1em; margin: -0.5rem 0; display: flex; align-items: center; gap: 1rem;">
                        <hr style="flex: 1; border: none; border-top: 1px solid var(--border-color);">
                        <span><i class="fa-solid fa-lock" style="color: var(--status-warning);"></i> REQUIRES ACCESS CODE</span>
                        <hr style="flex: 1; border: none; border-top: 1px solid var(--border-color);">
                    </div>

                    <label class="role-option" onclick="document.querySelectorAll('.role-option').forEach(el=>el.classList.remove('active')); this.classList.add('active');">
                        <input type="radio" name="role" value="recruiter" class="role-input">
                        <div class="role-icon" style="color: var(--color-recruiter);"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div class="role-details">
                            <h4>Recruiter</h4>
                            <p>Manage pipeline & review candidates</p>
                        </div>
                        <div class="role-badge"><i class="fa-solid fa-key"></i> CODE REQUIRED</div>
                        <div class="role-radio-circle"></div>
                    </label>

                    <label class="role-option" onclick="document.querySelectorAll('.role-option').forEach(el=>el.classList.remove('active')); this.classList.add('active');">
                        <input type="radio" name="role" value="admin" class="role-input">
                        <div class="role-icon" style="color: var(--color-admin);"><i class="fa-solid fa-shield"></i></div>
                        <div class="role-details">
                            <h4>Admin</h4>
                            <p>System-wide oversight & configuration</p>
                        </div>
                        <div class="role-badge"><i class="fa-solid fa-key"></i> CODE REQUIRED</div>
                        <div class="role-radio-circle"></div>
                    </label>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="you@company.com" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        <i class="fa-regular fa-eye-slash" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--border-light); cursor: pointer;"></i>
                    </div>
                </div>
                
                <div style="text-align: right; margin-bottom: 1.5rem;">
                    <a href="#" style="font-size: 0.75rem; color: var(--text-muted);">Forgot your password?</a>
                </div>
                
                <button type="submit" class="btn btn-outline" style="width: 100%; padding: 0.85rem; font-size: 0.95rem; font-weight: 600; background: rgba(255,255,255,0.05);">Sign In</button>
            </form>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 3rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <span style="font-size: 0.7rem; color: var(--text-muted);">Signing in as</span>
                <span class="nav-badge" style="border-radius: 99px;">
                    <div style="width: 6px; height: 6px; background: var(--text-muted); border-radius: 50%; display: inline-block; margin-right: 4px;"></div>
                    APPLICANT
                </span>
            </div>
        </div>
    </div>
</div>

<script>
    // Update bottom badge text based on selected role
    document.querySelectorAll('.role-input').forEach(input => {
        input.addEventListener('change', function() {
            const badge = document.querySelector('.nav-badge');
            badge.innerHTML = `<div style="width: 6px; height: 6px; background: var(--text-muted); border-radius: 50%; display: inline-block; margin-right: 4px;"></div> ${this.value.toUpperCase()}`;
        });
    });
</script>
</body>
</html>
