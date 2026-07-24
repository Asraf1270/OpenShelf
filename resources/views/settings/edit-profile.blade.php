@extends('layouts.app')

@push('styles')
<style>
    :root {
        --edit-bg: #f8fafc;
        --edit-card-bg: rgba(255, 255, 255, 0.9);
        --edit-border: rgba(44, 62, 80, 0.12);
        --edit-active-teal: #4C9F8A;
        --edit-radius: 24px;
        --edit-inner-radius: 12px;
        --edit-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        --edit-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    :root[data-theme="dark"] {
        --edit-bg: #0f172a;
        --edit-card-bg: #1e293b;
        --edit-border: #334155;
        --edit-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .edit-page-wrapper {
        min-height: calc(100vh - 140px);
        background: var(--edit-bg);
        color: var(--text-primary);
        padding: 2.5rem 1rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        transition: var(--edit-transition);
    }

    .edit-container {
        width: 100%;
        max-width: 760px;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        animation: editEntrance 0.5s ease-out;
    }

    @keyframes editEntrance {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .edit-header-nav {
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
        transition: var(--edit-transition);
    }

    .back-hub-btn:hover {
        color: var(--primary);
        transform: translateX(-2px);
    }

    .edit-card {
        background: var(--edit-card-bg);
        border: 1px solid var(--edit-border);
        border-radius: var(--edit-radius);
        padding: 2.25rem;
        box-shadow: var(--edit-shadow);
        backdrop-filter: blur(10px);
        position: relative;
    }

    .avatar-upload-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 2.25rem;
    }

    .avatar-preview-box {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        position: relative;
        overflow: hidden;
        border: 4px solid var(--edit-border);
        cursor: pointer;
        box-shadow: 0 10px 25px rgba(0,0,0,0.06);
        transition: var(--edit-transition);
    }

    .avatar-preview-box:hover {
        transform: scale(1.02);
        border-color: var(--edit-active-teal);
    }

    .avatar-preview-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-hover-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: var(--edit-transition);
        font-size: 0.78rem;
        font-weight: 600;
        gap: 4px;
    }

    .avatar-preview-box:hover .avatar-hover-overlay { opacity: 1; }

    .avatar-specs {
        font-size: 0.78rem;
        color: var(--text-secondary);
        margin-top: 0.6rem;
    }

    .edit-form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }

    @media (min-width: 580px) {
        .edit-form-grid { grid-template-columns: 1fr 1fr; }
        .full-width-field { grid-column: span 2; }
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

    .edit-input {
        width: 100%;
        min-height: 46px;
        background: var(--settings-input-bg, #ffffff);
        border: 1px solid var(--edit-border);
        border-radius: var(--edit-inner-radius);
        padding: 0.5rem 1rem 0.5rem 2.5rem;
        color: var(--text-primary);
        font-size: 0.92rem;
        transition: var(--edit-transition);
        outline: none;
    }

    :root[data-theme="dark"] .edit-input { background: #0f172a; }

    .edit-input:focus {
        border-color: var(--edit-active-teal);
        box-shadow: 0 0 0 3px rgba(76, 159, 138, 0.15);
    }

    .edit-input:disabled {
        background: var(--surface-hover);
        color: var(--text-tertiary);
        cursor: not-allowed;
    }

    textarea.edit-input {
        padding: 0.75rem 1rem;
        min-height: 100px;
        resize: vertical;
    }

    .edit-select {
        appearance: none;
        padding-right: 2.5rem;
        cursor: pointer;
    }

    .input-box-wrapper.select-wrapper::after {
        content: '\f078';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: 1rem;
        color: var(--text-tertiary);
        pointer-events: none;
        font-size: 0.8rem;
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
        border-radius: var(--edit-inner-radius);
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        transition: var(--edit-transition);
        border: none;
        background: linear-gradient(135deg, var(--primary), var(--edit-active-teal));
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

    @keyframes spin { to { transform: rotate(360deg); } }

    .page-loader-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.5);
        border-radius: var(--edit-radius);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 100;
        backdrop-filter: blur(1px);
    }

    :root[data-theme="dark"] .page-loader-overlay {
        background: rgba(15, 23, 42, 0.5);
    }

    .page-loader-overlay.active { display: flex; }

    .edit-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: var(--text-primary);
        color: var(--edit-bg);
        padding: 1rem 1.5rem;
        border-radius: var(--edit-inner-radius);
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

    .edit-toast.visible {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    .toast-success { border-left: 4px solid var(--edit-active-teal); }
    .toast-error { border-left: 4px solid #ef4444; }
    .toast-success i { color: var(--edit-active-teal); }
    .toast-error i { color: #ef4444; }
</style>
@endpush

@section('content')
<div class="edit-page-wrapper">
    <div class="edit-container">
        <div class="edit-header-nav">
            <a href="{{ route('settings') }}" class="back-hub-btn">
                <i class="fas fa-arrow-left"></i> Back to Settings
            </a>
        </div>

        <div class="edit-card">
            <div class="page-loader-overlay" id="editFormLoader">
                <div class="spinner" style="display: block; width: 36px; height: 36px; border-width: 3px; border-top-color: var(--edit-active-teal);"></div>
            </div>

            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">Account Management</h2>
                <p style="font-size: 0.88rem; color: var(--text-secondary);">Update your personal profile, department details, and residency hall information.</p>
            </div>

            <form id="editProfileForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">

                <div class="avatar-upload-section">
                    <div class="avatar-preview-box" onclick="document.getElementById('avatarFileInput').click()">
                        <img src="{{ $user->profile_image_url }}" alt="Profile Photo" id="avatarPreviewImage">
                        <div class="avatar-hover-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Upload Photo</span>
                        </div>
                    </div>
                    <input type="file" name="profile_image" id="avatarFileInput" accept="image/*" style="display: none;" onchange="previewSelectedAvatar(this)">
                    <span class="avatar-specs">Square recommended. WebP, JPG, PNG or GIF (Max 5MB)</span>
                    <div class="field-error" id="error-profile_image"></div>
                </div>

                <div class="edit-form-grid">
                    <div class="field-box">
                        <label class="field-label" for="editName">Full Name</label>
                        <div class="input-box-wrapper">
                            <i class="fas fa-signature"></i>
                            <input type="text" name="name" id="editName" class="edit-input" placeholder="Enter full name" value="{{ $user->name }}">
                        </div>
                        <div class="field-error" id="error-name"></div>
                    </div>

                    <div class="field-box">
                        <label class="field-label" for="editEmail">Email Address (Locked)</label>
                        <div class="input-box-wrapper">
                            <i class="fas fa-envelope-open-text"></i>
                            <input type="email" id="editEmail" class="edit-input" value="{{ $user->email }}" disabled>
                        </div>
                        <small style="font-size: 0.75rem; color: var(--text-tertiary);"><i class="fas fa-lock"></i> Email updates are disabled for security.</small>
                    </div>

                    <div class="field-box">
                        <label class="field-label" for="editPhone">Phone Number</label>
                        <div class="input-box-wrapper">
                            <i class="fas fa-phone-flip"></i>
                            <input type="tel" name="phone" id="editPhone" class="edit-input" placeholder="01XXXXXXXXX" value="{{ $user->phone }}">
                        </div>
                        <div class="field-error" id="error-phone"></div>
                    </div>

                    <div class="field-box">
                        <label class="field-label" for="editDept">Department</label>
                        <div class="input-box-wrapper">
                            <i class="fas fa-graduation-cap"></i>
                            <input type="text" name="department" id="editDept" class="edit-input" placeholder="e.g. Computer Science" value="{{ $user->department }}">
                        </div>
                        <div class="field-error" id="error-department"></div>
                    </div>

                    <div class="field-box">
                        <label class="field-label" for="editSession">Session</label>
                        <div class="input-box-wrapper">
                            <i class="fas fa-calendar-days"></i>
                            <input type="text" name="session" id="editSession" class="edit-input" placeholder="e.g. 2020-21" value="{{ $user->session }}">
                        </div>
                        <div class="field-error" id="error-session"></div>
                    </div>

                    <div class="field-box">
                        <label class="field-label" for="editHall">Residential Hall</label>
                        <div class="input-box-wrapper select-wrapper">
                            <i class="fas fa-hotel"></i>
                            <select name="hall" id="editHall" class="edit-input edit-select">
                                <option value="">Select Hall</option>
                                @foreach ($halls as $value => $label)
                                    <option value="{{ $value }}" @selected($user->hall === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field-error" id="error-hall"></div>
                    </div>

                    <div class="field-box">
                        <label class="field-label" for="editRoom">Room Number</label>
                        <div class="input-box-wrapper">
                            <i class="fas fa-door-open"></i>
                            <input type="text" name="room_number" id="editRoom" class="edit-input" placeholder="e.g. 214" value="{{ $user->room_number }}">
                        </div>
                        <div class="field-error" id="error-room_number"></div>
                    </div>

                    <div class="field-box full-width-field">
                        <label class="field-label" for="editBio">Bio / Description</label>
                        <textarea name="bio" id="editBio" class="edit-input" placeholder="Introduce yourself to the OpenShelf community...">{{ $user->bio }}</textarea>
                        <div class="field-error" id="error-bio"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="saveButton">
                        <i class="fas fa-floppy-disk"></i> Save Details
                        <span class="spinner" id="saveSpinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="edit-toast" id="editToast">
    <i id="toastIcon" class="fas"></i>
    <span id="toastMessage">Changes saved.</span>
</div>
@endsection

@push('scripts')
<script>
    function previewSelectedAvatar(input) {
        const file = input.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                showToastNotification('File size exceeds the 5MB limit.', 'error');
                input.value = '';
                return;
            }

            const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowed.includes(file.type)) {
                showToastNotification('Only JPG, PNG, GIF, and WebP images are allowed.', 'error');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreviewImage').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }

    let toastTimer = null;
    function showToastNotification(message, type = 'success') {
        const toast = document.getElementById('editToast');
        const icon = document.getElementById('toastIcon');
        const text = document.getElementById('toastMessage');

        if (!toast || !icon || !text) return;

        toast.className = 'edit-toast';
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
            const messages = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
            if (errorElement) {
                errorElement.textContent = messages[0];
                errorElement.classList.add('visible');
            }
        });
    }

    const profileForm = document.getElementById('editProfileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            clearErrors();

            const saveBtn = document.getElementById('saveButton');
            const spinner = document.getElementById('saveSpinner');
            const loader = document.getElementById('editFormLoader');

            if (saveBtn) saveBtn.disabled = true;
            if (spinner) spinner.style.display = 'block';
            if (loader) loader.classList.add('active');

            const formData = new FormData(this);

            fetch('{{ route('settings.edit-profile.update') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP Connection error');
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showToastNotification(data.message || 'Profile settings updated!', 'success');
                    if (data.profile_pic_url) {
                        const headerAvatar = document.querySelector('.user-avatar');
                        const mobileAvatar = document.querySelector('.mobile-avatar');
                        if (headerAvatar) headerAvatar.src = data.profile_pic_url;
                        if (mobileAvatar) mobileAvatar.src = data.profile_pic_url;
                    }
                    const newName = document.getElementById('editName').value;
                    const userNameEl = document.querySelector('.user-name');
                    const mobileNameEl = document.querySelector('.mobile-user-name');
                    if (userNameEl) userNameEl.textContent = newName;
                    if (mobileNameEl) mobileNameEl.textContent = newName;
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
@endpush
