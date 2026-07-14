@extends('layouts.app')

@section('content')
<div class="settings-hub-wrapper">
    <div class="settings-hub-container">
        <div class="user-summary-card">
            <img src="{{ $user->profile_image_url }}" alt="Avatar" class="user-summary-avatar">
            <div class="user-summary-details">
                <div class="user-summary-name">{{ $user->name }}</div>
                <div class="user-summary-email">
                    <i class="fas fa-envelope"></i> {{ $user->email }}
                </div>
                <div class="user-summary-badges">
                    <span class="user-summary-badge">
                        <i class="fas fa-graduation-cap"></i> {{ $user->department ?? 'No Department' }}
                    </span>
                    @if ($user->hall_name !== 'N/A')
                        <span class="user-summary-badge">
                            <i class="fas fa-hotel"></i> {{ $user->hall_name }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <nav class="hub-menu-list">
            <a href="{{ route('settings.edit-profile') }}" class="hub-menu-item">
                <div class="hub-menu-left">
                    <div class="hub-icon-container">
                        <i class="fas fa-user-gear"></i>
                    </div>
                    <div class="hub-title-box">
                        <span class="hub-menu-title">Account Management</span>
                        <span class="hub-menu-desc">Update your name, contact details, hall residency, and bio</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right hub-menu-arrow"></i>
            </a>

            <a href="{{ route('settings.change-password') }}" class="hub-menu-item">
                <div class="hub-menu-left">
                    <div class="hub-icon-container">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div class="hub-title-box">
                        <span class="hub-menu-title">Privacy & Security</span>
                        <span class="hub-menu-desc">Update password credentials and keep your account safe</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right hub-menu-arrow"></i>
            </a>

            <a href="/settings/help/" class="hub-menu-item">
                <div class="hub-menu-left">
                    <div class="hub-icon-container">
                        <i class="fas fa-circle-question"></i>
                    </div>
                    <div class="hub-title-box">
                        <span class="hub-menu-title">Help & Support</span>
                        <span class="hub-menu-desc">Read FAQs, borrowing rules, guidelines, and contact support</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right hub-menu-arrow"></i>
            </a>
        </nav>

        <div class="hub-theme-card">
            <div class="theme-card-header">
                <i class="fas fa-palette"></i>
                <span class="theme-card-title">Display & Appearance</span>
            </div>

            <div class="theme-toggle-row">
                <button class="theme-toggle-btn" id="hubThemeLight" type="button" onclick="toggleHubTheme('light')">
                    <i class="fas fa-sun"></i> Light Theme
                </button>
                <button class="theme-toggle-btn" id="hubThemeDark" type="button" onclick="toggleHubTheme('dark')">
                    <i class="fas fa-moon"></i> Dark Theme
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme') || 'light';
        updateHubThemeUI(savedTheme);
    });

    function toggleHubTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        }
        updateHubThemeUI(theme);
    }

    function updateHubThemeUI(theme) {
        const lightBtn = document.getElementById('hubThemeLight');
        const darkBtn = document.getElementById('hubThemeDark');

        if (!lightBtn || !darkBtn) return;

        lightBtn.classList.remove('active');
        darkBtn.classList.remove('active');

        if (theme === 'dark') {
            darkBtn.classList.add('active');
        } else {
            lightBtn.classList.add('active');
        }
    }
</script>
@endpush
