document.addEventListener('DOMContentLoaded', () => {
    // Flash message auto-dismiss
    const flashMessages = document.querySelectorAll('.flash-message');
    if (flashMessages.length > 0) {
        setTimeout(() => {
            flashMessages.forEach(msg => {
                msg.style.transition = 'opacity 0.4s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 400);
            });
        }, 5000);
    }

    // Form Submissions Spinner
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                // Check HTML5 validity
                if(form.checkValidity()) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner" style="display:inline-block; width:1em; height:1em; border:2px solid rgba(255,255,255,0.3); border-radius:50%; border-top-color:#fff; animation:spin 1s ease-in-out infinite;"></span> ' + originalText;
                }
            }
        });
    });

    // OTP Resend Timer
    const resendBtn = document.getElementById('resend-otp');
    if (resendBtn) {
        let timeLeft = 60;
        resendBtn.disabled = true;
        
        const updateTimer = () => {
            if (timeLeft <= 0) {
                resendBtn.disabled = false;
                resendBtn.textContent = 'Resend Code';
            } else {
                resendBtn.textContent = `Resend Code (${timeLeft}s)`;
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        };
        updateTimer();
    }
});

// Helper for file input size validation
function validateFileSize(input, maxSizeMB) {
    if (input.files && input.files[0]) {
        const fileSize = input.files[0].size / 1024 / 1024; // in MB
        if (fileSize > maxSizeMB) {
            alert(`File size exceeds ${maxSizeMB}MB. Please choose a smaller file.`);
            input.value = ''; // clear
            return false;
        }
    }
    return true;
}

// Add a keyframe animation for the spinner programmatically if not in CSS
const style = document.createElement('style');
style.innerHTML = `
    @keyframes spin { 
        to { transform: rotate(360deg); } 
    }
`;
document.head.appendChild(style);
