<?php
/**
 * OpenShelf Privacy & Security (Change Password) Page
 * Handles password edits inside /settings/change-password/
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/settings/change-password/';
    header('Location: /login/');
    exit;
}

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    :root {
        --pass-bg: #f8fafc;
        --pass-card-bg: rgba(255, 255, 255, 0.9);
        --pass-border: rgba(44, 62, 80, 0.12);
        --pass-active-teal: #4C9F8A;
        --pass-radius: 24px;
        --pass-inner-radius: 12px;
        --pass-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        --pass-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    :root[data-theme="dark"] {
        --pass-bg: #0f172a;
        --pass-card-bg: #1e293b;
        --pass-border: #334155;
        --pass-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .pass-page-wrapper {
        min-height: calc(100vh - 140px);
        background: var(--pass-bg);
        color: var(--text-primary);
        padding: 2.5rem 1rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        transition: var(--pass-transition);
    }

    .pass-container {
        width: 100%;
        max-width: 600px;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        animation: passEntrance 0.5s ease-out;
    }

    @keyframes passEntrance {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Header Nav (Back to Hub) */
    .pass-header-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .back-hub-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-secondary);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.92rem;
        transition: var(--pass-transition);
    }

    .back-hub-btn:hover {
        color: var(--primary);
        transform: translateX(-2px);
    }

    .pass-card {
        background: var(--pass-card-bg);
        border: 1px solid var(--pass-border);
        border-radius: var(--pass-radius);
        padding: 2.25rem;
        box-shadow: var(--pass-shadow);
        backdrop-filter: blur(10px);
        position: relative;
    }

    /* Form Fields */
    .pass-form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }

    .field-box {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .field-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .input-box-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-box-wrapper i {
        position: absolute;
        left: 1rem;
        color: var(--text-tertiary);
        pointer-events: none;
        font-size: 0.95rem;
    }

    .pass-input {
        width: 100%;
        min-height: 46px;
        background: var(--settings-input-bg, #ffffff);
        border: 1px solid var(--pass-border);
        border-radius: var(--pass-inner-radius);
        padding: 0.5rem 1rem 0.5rem 2.5rem;
        color: var(--text-primary);
        font-size: 0.92rem;
        transition: var(--pass-transition);
        outline: none;
    }

    :root[data-theme="dark"] .pass-input {
        background: #0f172a;
    }

    .pass-input:focus {
        border-color: var(--pass-active-teal);
        box-shadow: 0 0 0 3px rgba(76, 159, 138, 0.15);
    }

    .field-error {
        font-size: 0.8rem;
        color: #ef4444;
        margin-top: 0.2rem;
        display: none;
    }

    .field-error.visible {
        display: block;
        animation: shakeInput 0.3s ease;
    }

    @keyframes shakeInput {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-4px); }
        75% { transform: translateX(4px); }
    }

    .form-actions {
        margin-top: 2rem;
        display: flex;
        justify-content: flex-end;
    }

    .btn-submit {
        min-height: 48px;
        padding: 0 2rem;
        border-radius: var(--pass-inner-radius);
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        transition: var(--pass-transition);
        border: none;
        background: linear-gradient(135deg, var(--primary), var(--pass-active-teal));
        color: white;
        box-shadow: 0 4px 14px rgba(76, 159, 138, 0.2);
    }

    .btn-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(76, 159, 138, 0.3);
    }

    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }

    .spinner {
        display: none;
        width: 18px;
        height: 18px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Page Overlay loader */
    .page-loader-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.5);
        border-radius: var(--pass-radius);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 100;
        backdrop-filter: blur(1px);
    }

    :root[data-theme="dark"] .page-loader-overlay {
        background: rgba(15, 23, 42, 0.5);
    }

    .page-loader-overlay.active {
        display: flex;
    }

    /* Toast Notification */
    .pass-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: var(--text-primary);
        color: var(--pass-bg);
        padding: 1rem 1.5rem;
        border-radius: var(--pass-inner-radius);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        z-index: 2000;
        transform: translateY(100px);
        opacity: 0;
        pointer-events: none;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        font-weight: 500;
        font-size: 0.9rem;
    }

    .pass-toast.visible {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    .toast-success {
        border-left: 4px solid var(--pass-active-teal);
    }

    .toast-error {
        border-left: 4px solid #ef4444;
    }

    .toast-success i {
        color: var(--pass-active-teal);
    }

    .toast-error i {
        color: #ef4444;
    }
</style>

<div class="pass-page-wrapper">
    <div class="pass-container">
        <!-- Back Link -->
        <div class="pass-header-nav">
            <a href="/settings/" class="back-hub-btn">
                <i class="fas fa-arrow-left"></i> Back to Settings
            </a>
        </div>

        <div class="pass-card">
            <!-- Global Overlay -->
            <div class="page-loader-overlay" id="passFormLoader">
                <div class="spinner" style="display: block; width: 36px; height: 36px; border-width: 3px; border-top-color: var(--pass-active-teal);"></div>
            </div>

            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">Privacy & Security</h2>
                <p style="font-size: 0.88rem; color: var(--text-secondary);">Change your password and keep your credentials secure.</p>
            </div>

            <form id="passForm">
                <input type="hidden" name="action" value="change_password">

                <!-- Form Fields -->
                <div class="pass-form-grid">
                    <!-- Current Password -->
                    <div class="field-box">
                        <label class="field-label" for="currPassword">Current Password</label>
                        <div class="input-box-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="current_password" id="currPassword" class="pass-input" placeholder="Enter current password">
                        </div>
                        <div class="field-error" id="error-current_password"></div>
                    </div>

                    <!-- New Password -->
                    <div class="field-box">
                        <label class="field-label" for="newPassword">New Password</label>
                        <div class="input-box-wrapper">
                            <i class="fas fa-key"></i>
                            <input type="password" name="new_password" id="newPassword" class="pass-input" placeholder="Min. 6 characters">
                        </div>
                        <div class="field-error" id="error-new_password"></div>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="field-box">
                        <label class="field-label" for="confirmPassword">Confirm New Password</label>
                        <div class="input-box-wrapper">
                            <i class="fas fa-key"></i>
                            <input type="password" name="confirm_password" id="confirmPassword" class="pass-input" placeholder="Re-enter new password">
                        </div>
                        <div class="field-error" id="error-confirm_password"></div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="saveButton">
                        <i class="fas fa-shield-halved"></i> Update Password
                        <span class="spinner" id="saveSpinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast element -->
<div class="pass-toast" id="passToast">
    <i id="toastIcon" class="fas"></i>
    <span id="toastMessage">Changes saved.</span>
</div>

<script>
    let toastTimer = null;
    function showToastNotification(message, type = 'success') {
        const toast = document.getElementById('passToast');
        const icon = document.getElementById('toastIcon');
        const text = document.getElementById('toastMessage');

        if (!toast || !icon || !text) return;

        toast.className = 'pass-toast';
        icon.className = 'fas';

        if (type === 'success') {
            toast.classList.add('toast-success', 'visible');
            icon.classList.add('fa-circle-check');
        } else {
            toast.classList.add('toast-error', 'visible');
            icon.classList.add('fa-circle-xmark');
        }

        text.textContent = message;

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.remove('visible');
        }, 4000);
    }

    function clearErrors() {
        document.querySelectorAll('.field-error').forEach(err => {
            err.textContent = '';
            err.classList.remove('visible');
        });
    }

    function renderErrors(errors) {
        Object.keys(errors).forEach(field => {
            const errorElement = document.getElementById(`error-${field}`);
            if (errorElement) {
                errorElement.textContent = errors[field];
                errorElement.classList.add('visible');
            }
        });
    }

    // Form Ajax submission
    const passwordForm = document.getElementById('passForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            clearErrors();

            const saveBtn = document.getElementById('saveButton');
            const spinner = document.getElementById('saveSpinner');
            const loader = document.getElementById('passFormLoader');

            if (saveBtn) saveBtn.disabled = true;
            if (spinner) spinner.style.display = 'block';
            if (loader) loader.classList.add('active');

            const formData = new FormData(this);

            fetch('/api/settings.php', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP Connection error');
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showToastNotification(data.message || 'Password changed successfully!', 'success');
                    passwordForm.reset();
                } else if (data.errors) {
                    renderErrors(data.errors);
                    showToastNotification('Please correct form validation issues.', 'error');
                } else {
                    showToastNotification(data.message || 'Failed to update settings.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToastNotification('Failed to connect to the server. Try again.', 'error');
            })
            .finally(() => {
                if (saveBtn) saveBtn.disabled = false;
                if (spinner) spinner.style.display = 'none';
                if (loader) loader.classList.remove('active');
            });
        });
    }
</script>

<?php
require_once dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
