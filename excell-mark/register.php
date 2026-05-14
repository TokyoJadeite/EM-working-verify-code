<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';

if (isLoggedIn()) {
    header("Location: /excell-mark/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact_number'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($fullName) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email is already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, contact_number) VALUES (?, ?, ?, 'applicant', ?)");
            if ($stmt->execute([$fullName, $email, $hash, $contact])) {
                header("Location: /excell-mark/index.php?registered=1");
                exit;
            } else {
                $error = "An error occurred during registration.";
            }
        }
    }
}
$pageTitle = "Create Account | ExcellMark";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="/excell-mark/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Password strength meter */
        .password-wrapper { position: relative; }
        .password-wrapper .toggle-pass {
            position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
            color: var(--border-light); cursor: pointer; transition: color 0.2s;
        }
        .password-wrapper .toggle-pass:hover { color: var(--text-primary); }

        .strength-meter { height: 3px; background: var(--border-color); border-radius: 2px; margin-top: 0.5rem; overflow: hidden; }
        .strength-fill { height: 100%; width: 0; border-radius: 2px; transition: width 0.4s ease, background 0.4s ease; }
        .strength-text { font-size: 0.7rem; margin-top: 0.25rem; color: var(--text-muted); }

        /* Two-column row for smaller fields */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        /* Animated entrance */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .register-form { animation: fadeSlideUp 0.5s ease forwards; }

        /* Register button style */
        .btn-register {
            width: 100%;
            padding: 0.85rem;
            font-size: 0.95rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--accent-blue), #6366f1);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-register:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
        }
        .btn-register:active { transform: translateY(0); }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-split { flex-direction: column; }
            .auth-left { display: none; }
            .auth-right { padding: 2rem 1.5rem; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="auth-split">
    <div class="auth-left">
        <div class="brand-sub">// RECRUITMENT SYSTEM</div>
        <h1 class="brand-title">EXCELL<span>MARK</span></h1>
        <p class="brand-desc">End-to-end hiring intelligence — from application to offer letter, in one unified workspace.</p>
        <div style="margin-top: auto; display: flex; align-items: center; gap: 0.5rem;">
            <div style="width: 8px; height: 8px; background: var(--color-applicant); border-radius: 50; box-shadow: 0 0 8px var(--color-applicant);"></div>
            <span style="color: var(--text-muted); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase;">Applicant Registration</span>
        </div>
    </div>
    
    <div class="auth-right">
        <div style="max-width: 440px; width: 100%; margin: 0 auto;">
            <div class="auth-tabs">
                <div class="auth-tab" onclick="window.location.href='index.php'" style="cursor: pointer;">Sign In</div>
                <div class="auth-tab active">Create Account</div>
            </div>
            
            <?php if ($error): ?>
                <div class="flash-message flash-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="register-form" id="registerForm">
                <div class="form-group">
                    <label for="full_name">Full Name <span style="color: var(--status-danger);">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Juan Dela Cruz" required
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address <span style="color: var(--status-danger);">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="you@email.com" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" class="form-control" placeholder="09XX XXX XXXX"
                               value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span style="color: var(--status-danger);">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                        <i class="fa-regular fa-eye-slash toggle-pass" onclick="togglePassword('password', this)"></i>
                    </div>
                    <div class="strength-meter"><div class="strength-fill" id="strengthFill"></div></div>
                    <div class="strength-text" id="strengthText"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span style="color: var(--status-danger);">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter your password" required minlength="6">
                        <i class="fa-regular fa-eye-slash toggle-pass" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                    <div id="matchMessage" style="font-size: 0.7rem; margin-top: 0.25rem;"></div>
                </div>
                
                <button type="submit" class="btn btn-register" style="margin-top: 0.5rem;">
                    <i class="fa-solid fa-user-plus" style="margin-right: 0.5rem;"></i> Create Account
                </button>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <p style="color: var(--text-secondary); font-size: 0.85rem;">
                        Already have an account? <a href="index.php" style="color: var(--accent-blue-hover); font-weight: 500;">Sign In</a>
                    </p>
                </div>
            </form>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <span style="font-size: 0.7rem; color: var(--text-muted);">Registering as</span>
                <span class="nav-badge" style="border-radius: 99px;">
                    <div style="width: 6px; height: 6px; background: var(--color-applicant); border-radius: 50%; display: inline-block; margin-right: 4px; box-shadow: 0 0 5px var(--color-applicant);"></div>
                    APPLICANT
                </span>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    function togglePassword(fieldId, icon) {
        const field = document.getElementById(fieldId);
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }

    // Password strength meter
    document.getElementById('password').addEventListener('input', function() {
        const val = this.value;
        const fill = document.getElementById('strengthFill');
        const text = document.getElementById('strengthText');
        let score = 0;
        if (val.length >= 6) score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { w: '0%', c: 'transparent', t: '' },
            { w: '20%', c: '#ef4444', t: 'Very weak' },
            { w: '40%', c: '#f59e0b', t: 'Weak' },
            { w: '60%', c: '#eab308', t: 'Fair' },
            { w: '80%', c: '#10b981', t: 'Strong' },
            { w: '100%', c: '#059669', t: 'Very strong' },
        ];
        const l = val.length === 0 ? levels[0] : levels[score];
        fill.style.width = l.w;
        fill.style.background = l.c;
        text.textContent = l.t;
        text.style.color = l.c;

        // Also check match
        checkMatch();
    });

    // Password match checker
    document.getElementById('confirm_password').addEventListener('input', checkMatch);
    function checkMatch() {
        const pw = document.getElementById('password').value;
        const cpw = document.getElementById('confirm_password').value;
        const msg = document.getElementById('matchMessage');
        if (cpw.length === 0) { msg.textContent = ''; return; }
        if (pw === cpw) {
            msg.textContent = '✓ Passwords match';
            msg.style.color = '#10b981';
        } else {
            msg.textContent = '✗ Passwords do not match';
            msg.style.color = '#ef4444';
        }
    }
</script>
</body>
</html>
