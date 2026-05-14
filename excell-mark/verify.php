<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mail.php';

if (isLoggedIn()) {
    $redirect = $_SESSION['role'] === 'applicant' ? 'home.php' : 'dashboard.php';
    header("Location: /excell-mark/{$_SESSION['role']}/{$redirect}");
    exit;
}

if (!isset($_SESSION['pending_user_id'])) {
    header("Location: /excell-mark/index.php");
    exit;
}

$error = '';
$success = '';
$devOtp = null; // For development mode only

if (isset($_GET['resend']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Generate new OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['pending_user_id'], password_hash($otp, PASSWORD_DEFAULT), $expires_at]);
    
    $sent = sendOTP($_SESSION['pending_email'], $otp);
    
    // If PHPMailer is not installed, store OTP in session for dev display
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $_SESSION['dev_otp'] = $otp;
    }
    
    $success = "A new verification code has been sent.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otpInput = $_POST['otp_code'] ?? '';
    
    if (empty($otpInput)) {
        $error = "Please enter the 6-digit code.";
    } else {
        $userId = $_SESSION['pending_user_id'];
        $verified = false;
        
        // Method 1: Verify against database (production mode)
        // Use PHP time instead of MySQL NOW() to avoid timezone mismatches
        $currentTime = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT * FROM otp_codes WHERE user_id = ? AND used = 0 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $codeRecord = $stmt->fetch();
        
        if ($codeRecord) {
            // Check expiry using PHP time comparison
            $isExpired = strtotime($codeRecord['expires_at']) < time();
            if (!$isExpired && password_verify($otpInput, $codeRecord['otp_code'])) {
                $verified = true;
                // Mark as used
                $stmt = $pdo->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?");
                $stmt->execute([$codeRecord['id']]);
            }
        }
        
        // Method 2: Dev mode fallback — verify against session-stored OTP
        if (!$verified && !class_exists('PHPMailer\PHPMailer\PHPMailer') && isset($_SESSION['dev_otp'])) {
            if ($otpInput === $_SESSION['dev_otp']) {
                $verified = true;
                // Mark DB record as used if it exists
                if ($codeRecord) {
                    $stmt = $pdo->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?");
                    $stmt->execute([$codeRecord['id']]);
                }
            }
        }
        
        if ($verified) {
            // Finalize login
            $_SESSION['user_id'] = $userId;
            $_SESSION['role'] = $_SESSION['pending_role'];
            
            // Get full name
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $_SESSION['full_name'] = $stmt->fetchColumn();
            
            $role = $_SESSION['role'];
            
            // Clear pending & dev data
            unset($_SESSION['pending_user_id']);
            unset($_SESSION['pending_email']);
            unset($_SESSION['pending_role']);
            unset($_SESSION['dev_otp']);
            
            $redirect = $role === 'applicant' ? 'home.php' : 'dashboard.php';
            header("Location: /excell-mark/{$role}/{$redirect}");
            exit;
        } else {
            $error = "Invalid or expired code. Please try again or resend.";
        }
    }
}

// Check if we have a dev OTP to show (PHPMailer not installed)
$showDevOtp = !class_exists('PHPMailer\PHPMailer\PHPMailer') && isset($_SESSION['dev_otp']);
$devOtp = $showDevOtp ? $_SESSION['dev_otp'] : null;

$pageTitle = "Verify Login | ExcellMark";
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
        .otp-container {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        .otp-input {
            width: 48px;
            height: 56px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            font-family: var(--font-heading);
            background: var(--bg-surface-light);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            transition: all 0.2s;
            outline: none;
        }
        .otp-input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        .otp-input.filled {
            border-color: var(--accent-blue);
            background: rgba(79, 70, 229, 0.05);
        }

        .dev-banner {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.25);
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .dev-banner .dev-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--status-warning);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .dev-banner .dev-code {
            font-size: 2rem;
            font-family: var(--font-heading);
            letter-spacing: 0.35em;
            color: var(--text-primary);
            font-weight: 600;
        }
        .dev-banner .dev-note {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 0.4rem;
        }

        .timer-text {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 1rem;
            text-align: center;
        }
        .timer-text .timer-count {
            color: var(--accent-blue);
            font-weight: 600;
            font-family: var(--font-heading);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .verify-card {
            animation: fadeIn 0.4s ease forwards;
        }
    </style>
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: radial-gradient(circle at center, var(--bg-surface) 0%, var(--bg-dark) 100%);">

<div class="verify-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 3rem; width: 100%; max-width: 440px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
    <div style="text-align: center; margin-bottom: 2rem;">
        <div style="width: 64px; height: 64px; background: rgba(79, 70, 229, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--accent-blue); margin: 0 auto 1.5rem auto;">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Security Verification</h2>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">We've sent a 6-digit code to <strong style="color: var(--text-primary);"><?= htmlspecialchars($_SESSION['pending_email']) ?></strong></p>
    </div>
    
    <?php if ($error): ?>
        <div class="flash-message flash-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="flash-message flash-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($devOtp): ?>
    <div class="dev-banner">
        <div class="dev-label"><i class="fa-solid fa-code"></i> Development Mode — Your OTP Code</div>
        <div class="dev-code"><?= $devOtp ?></div>
        <div class="dev-note">PHPMailer not installed — code shown here instead of sent via email</div>
    </div>
    <?php endif; ?>

    <form method="POST" action="verify.php" id="otpForm">
        <div class="form-group" style="margin-bottom: 0.5rem;">
            <label style="text-align: center; display: block; margin-bottom: 0.75rem;">Enter 6-Digit Code</label>
            <div class="otp-container">
                <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="\d" data-index="0" autofocus>
                <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="\d" data-index="1">
                <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="\d" data-index="2">
                <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="\d" data-index="3">
                <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="\d" data-index="4">
                <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="\d" data-index="5">
            </div>
            <!-- Hidden input for form submission -->
            <input type="hidden" name="otp_code" id="otpHidden">
        </div>

        <div class="timer-text">
            Code expires in <span class="timer-count" id="countdown">5:00</span>
        </div>
        
        <button type="submit" class="btn btn-primary" id="verifyBtn" style="width: 100%; margin-top: 1.25rem; padding: 0.85rem; font-size: 0.95rem;" disabled>Verify & Login</button>
    </form>
    
    <div style="text-align: center; margin-top: 1.5rem; display: flex; gap: 0.75rem; justify-content: center;">
        <a href="?resend=1" class="btn btn-outline" style="font-size: 0.82rem; padding: 0.4rem 1rem; border-radius: 99px;"><i class="fa-solid fa-rotate-right"></i> Resend Code</a>
        <a href="index.php" class="btn btn-outline" style="font-size: 0.82rem; padding: 0.4rem 1rem; border-radius: 99px; color: var(--text-muted);"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
    </div>
</div>

<script>
    // OTP Input handling — auto-advance, backspace, paste support
    const inputs = document.querySelectorAll('.otp-input');
    const hidden = document.getElementById('otpHidden');
    const btn = document.getElementById('verifyBtn');

    function updateHidden() {
        let code = '';
        inputs.forEach(i => code += i.value);
        hidden.value = code;
        btn.disabled = code.length !== 6;
        
        // Visual feedback
        inputs.forEach(i => {
            i.classList.toggle('filled', i.value.length > 0);
        });
    }

    inputs.forEach((input, idx) => {
        input.addEventListener('input', function(e) {
            // Only allow digits
            this.value = this.value.replace(/\D/g, '');
            if (this.value && idx < 5) {
                inputs[idx + 1].focus();
            }
            updateHidden();
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) {
                inputs[idx - 1].focus();
                inputs[idx - 1].value = '';
                updateHidden();
            }
            // Allow Enter to submit when all 6 filled
            if (e.key === 'Enter') {
                updateHidden();
                if (hidden.value.length === 6) {
                    document.getElementById('otpForm').submit();
                }
            }
        });

        // Handle paste
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (paste.length > 0) {
                paste.split('').forEach((char, i) => {
                    if (inputs[i]) inputs[i].value = char;
                });
                const focusIdx = Math.min(paste.length, 5);
                inputs[focusIdx].focus();
                updateHidden();
            }
        });

        // Select content on focus
        input.addEventListener('focus', function() {
            this.select();
        });
    });

    // Countdown timer (5 minutes)
    let seconds = 300;
    const countdownEl = document.getElementById('countdown');
    const timer = setInterval(() => {
        seconds--;
        if (seconds <= 0) {
            clearInterval(timer);
            countdownEl.textContent = 'Expired';
            countdownEl.style.color = '#ef4444';
            btn.disabled = true;
            return;
        }
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        countdownEl.textContent = `${m}:${s.toString().padStart(2, '0')}`;
        if (seconds <= 60) countdownEl.style.color = '#ef4444';
    }, 1000);
</script>

</body>
</html>
