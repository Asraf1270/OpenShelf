@extends('admin.layouts.app')

@section('title', 'Admin Profile - OpenShelf')
@section('page_title', 'My Profile')

@push('styles')
<style>
    .profile-container { max-width: 900px; margin: 0 auto; }
    .profile-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem; }
    .profile-card { background: var(--surface); border-radius: 1.5rem; overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05)); }
    .profile-header { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 2.5rem 2rem; text-align: center; color: white; }
    .profile-avatar {
        width: 100px; height: 100px; background: rgba(255,255,255,0.2); border: 4px solid rgba(255,255,255,0.3);
        border-radius: 2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 3rem;
    }
    .profile-header h2 { margin: 0; font-size: 1.5rem; font-weight: 700; }
    .profile-header p { margin: 0.5rem 0 0; opacity: 0.8; font-size: 0.9rem; }
    .profile-body { padding: 2rem; }
    .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
    .section-title i { color: var(--primary); }
    .form-group { margin-bottom: 1.5rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control {
        width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border); border-radius: 0.75rem;
        font-size: 0.95rem; background: var(--bg-body); color: var(--text-main); font: inherit;
    }
    .form-control:focus { outline: none; border-color: var(--primary); background: var(--surface); }
    .form-control:disabled { background: var(--bg-body); cursor: not-allowed; color: var(--text-muted); }
    .btn-save {
        width: 100%; padding: 1rem; border-radius: 0.75rem; font-weight: 700; cursor: pointer; border: none;
        background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;
        display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    }
    @media (max-width: 768px) { .profile-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="profile-container">
    <form method="POST" action="{{ route('admin.profile') }}">
        @csrf
        <div class="profile-grid">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><i class="fas fa-user-shield"></i></div>
                    <h2>Account Profile</h2>
                    <p>{{ ucfirst($admin->role) }} Status</p>
                </div>
                <div class="profile-body">
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $admin->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="{{ $admin->email }}" disabled title="Email cannot be changed">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Admin Role</label>
                        <input type="text" class="form-control" value="{{ ucfirst($admin->role) }}" disabled>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-body">
                    <div class="section-title"><i class="fas fa-lock"></i> Security Settings</div>

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Required to change password">
                    </div>

                    <hr style="margin:2rem 0;border:none;border-top:1px solid var(--border);">

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimum 8 characters">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password_confirmation">Confirm New Password</label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-control" placeholder="Repeat new password">
                    </div>

                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
