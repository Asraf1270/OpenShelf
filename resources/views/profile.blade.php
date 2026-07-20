@extends('layouts.app')

@push('styles')
<style>
    .profile-hero {
        height: 180px;
        background: linear-gradient(135deg, #4c9f8a 0%, #2d5d7a 100%);
        border-radius: 0 0 32px 32px;
        margin-bottom: -4.5rem;
    }

    .profile-container {
        max-width: 1120px;
        margin: 0 auto;
        padding: 0 1rem 2rem;
        position: relative;
        z-index: 1;
    }

    .glass-card {
        background: rgba(255,255,255,0.95);
        border: 1px solid rgba(255,255,255,0.8);
        border-radius: 28px;
        padding: 1.5rem 1.5rem 1.8rem;
        box-shadow: 0 24px 45px rgba(15, 23, 42, 0.09);
    }

    .profile-avatar-wrapper {
        width: 128px;
        height: 128px;
        border-radius: 50%;
        padding: 4px;
        background: linear-gradient(135deg, #4c9f8a, #2d5d7a);
        box-shadow: 0 12px 30px rgba(45, 93, 122, 0.18);
        margin: 0 auto 1rem;
    }

    .profile-avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
    }

    .profile-name {
        font-size: clamp(1.4rem, 2vw, 1.9rem);
        margin-bottom: 0.3rem;
    }

    .profile-subtitle {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        background: rgba(76, 159, 138, 0.1);
        color: #2d5d7a;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }

    .profile-meta {
        display: flex;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .profile-meta .meta-item {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        color: var(--text-secondary, #64748b);
        font-size: 0.95rem;
    }

    .profile-bio {
        max-width: 760px;
        margin: 0 auto 1.2rem;
        color: var(--text-secondary, #64748b);
        line-height: 1.7;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem;
        margin-bottom: 1.25rem;
    }

    .info-card {
        background: linear-gradient(180deg, #fff, #f8fafc);
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 18px;
        padding: 0.95rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .info-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        color: #fff;
        flex-shrink: 0;
    }

    .dept-icon { background: linear-gradient(135deg, #4c9f8a, #2d5d7a); }
    .session-icon { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .hall-icon { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
    .room-icon { background: linear-gradient(135deg, #ec4899, #f472b6); }

    .info-content {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .info-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-secondary, #64748b);
        font-weight: 700;
    }

    .info-value {
        font-weight: 700;
        color: var(--text-primary, #0f172a);
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.9rem;
        margin-bottom: 1rem;
    }

    .stats-row .stat-card {
        background: linear-gradient(135deg, rgba(76, 159, 138, 0.1), rgba(45, 93, 122, 0.08));
        border: 1px solid rgba(76, 159, 138, 0.16);
        border-radius: 18px;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .stats-row .stat-count {
        font-size: 1.35rem;
        font-weight: 800;
        color: #2d5d7a;
    }

    .stats-row .stat-text {
        color: var(--text-secondary, #64748b);
        font-weight: 600;
    }

    .profile-actions-wrapper {
        margin-top: 0.4rem;
        display: flex;
        justify-content: center;
    }

    .action-buttons-row {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn-profile-action {
        border-radius: 999px;
        padding: 0.78rem 1rem;
        font-weight: 700;
    }

    .profile-tabs {
        display: flex;
        justify-content: center;
        gap: 0.7rem;
        flex-wrap: wrap;
        margin: 2rem 0 1.1rem;
    }

    .profile-tabs .tab-btn {
        border: 1px solid var(--border, #e5e7eb);
        background: #fff;
        border-radius: 999px;
        padding: 0.7rem 1rem;
        font-weight: 700;
        color: var(--text-secondary, #64748b);
    }

    .profile-tabs .tab-btn.active {
        background: linear-gradient(135deg, #4c9f8a, #2d5d7a);
        color: #fff;
        border-color: transparent;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    @media (max-width: 768px) {
        .profile-hero {
            height: 140px;
            margin-bottom: -3.25rem;
        }

        .info-grid,
        .stats-row {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="profile-hero"></div>

<div class="profile-container">
    <div class="glass-card reveal active">
        <div class="profile-avatar-wrapper">
            <img src="{{ $user->profile_image_url }}"
                 alt="{{ $user->name }}"
                 class="profile-avatar">
        </div>

        <h1 class="profile-name">{{ $user->name }}</h1>

        <div class="profile-subtitle">
            <i class="fas fa-graduation-cap"></i> {{ $user->department ?? 'N/A' }}
        </div>

        <div class="profile-meta">
            <span class="meta-item"><i class="far fa-calendar-alt"></i> Joined {{ $memberSince }}</span>
        </div>

        <div class="profile-bio">
            @if (! empty($user->bio))
                <p>{!! nl2br(e($user->bio)) !!}</p>
            @else
                <p class="no-bio"><i class="fas fa-info-circle"></i> No bio available yet.</p>
            @endif
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="info-icon dept-icon">
                    <i class="fas fa-university"></i>
                </div>
                <div class="info-content">
                    <span class="info-label">Department</span>
                    <span class="info-value">{{ $user->department ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon session-icon">
                    <i class="far fa-calendar-check"></i>
                </div>
                <div class="info-content">
                    <span class="info-label">Session</span>
                    <span class="info-value">{{ $user->session ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon hall-icon">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="info-content">
                    <span class="info-label">Hall</span>
                    <span class="info-value">{{ $user->hall_name ?: 'N/A' }}</span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon room-icon">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="info-content">
                    <span class="info-label">Room</span>
                    <span class="info-value">
                        @if ($showSensitiveInfo)
                            {{ $user->room_number ?? 'N/A' }}
                        @else
                            <span class="locked-text"><i class="fas fa-lock"></i> Private</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <span class="stat-count">{{ $stats['owned'] }}</span>
                <span class="stat-text">Owned</span>
            </div>
            <div class="stat-card">
                <span class="stat-count">{{ $stats['borrowed'] }}</span>
                <span class="stat-text">Borrowed</span>
            </div>
            <div class="stat-card">
                <span class="stat-count">{{ $stats['lent'] }}</span>
                <span class="stat-text">Lent</span>
            </div>
        </div>

        <div class="profile-actions-wrapper">
            @if ($isOwnProfile)
                <div class="action-buttons-row">
                    <a href="{{ route('settings.edit-profile') }}" class="btn btn-profile-action add-btn" style="justify-content: center;">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                    <a href="/add-book/" class="btn btn-profile-action edit-btn" style="justify-content: center;">
                        <i class="fas fa-plus-circle"></i> Add Book
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="profile-tabs" @if (! $showSensitiveInfo) style="max-width: 200px; margin: 3rem auto 2rem;" @endif>
        <button class="tab-btn active" onclick="switchTab(event, 'owned')">Books Owned</button>
        @if ($showSensitiveInfo)
            <button class="tab-btn" onclick="switchTab(event, 'borrowed')">Borrowed</button>
            <button class="tab-btn" onclick="switchTab(event, 'lent')">Lent</button>
        @endif
    </div>

    <div id="owned" class="tab-content active">
        @if ($ownedBooks->isEmpty())
            <div style="text-align: center; padding: 4rem 2rem; background: rgba(255,255,255,0.5); border-radius: var(--radius-xl);">
                <i class="fas fa-book-open" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
                <p>No owned books to show.</p>
            </div>
        @else
            <div id="desktop-view-wrapper" class="hide-on-mobile">
                <x-book-card-grid :books="$ownedBooks" :showOwner="false" />
            </div>
            <div id="mobile-view-wrapper" class="show-on-mobile">
                <x-book-card-list :books="$ownedBooks" :showOwner="false" />
            </div>
        @endif
    </div>

    <div id="borrowed" class="tab-content">
        @if (empty($borrowedBooks))
            <div style="text-align: center; padding: 4rem 2rem; background: rgba(255,255,255,0.5); border-radius: var(--radius-xl);">
                <i class="fas fa-book-reader" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
                <p>No borrowed books to show.</p>
            </div>
        @else
            <div id="desktop-view-wrapper-borrowed" class="hide-on-mobile">
                <x-book-card-grid
                    :books="$borrowedBooks"
                    :showOwner="true"
                    extraInfoKey="owner_name"
                    extraInfoLabel="Borrowed from"
                />
            </div>
            <div id="mobile-view-wrapper-borrowed" class="show-on-mobile">
                <x-book-card-list
                    :books="$borrowedBooks"
                    :showOwner="true"
                    extraInfoKey="owner_name"
                    extraInfoLabel="Borrowed from"
                />
            </div>
        @endif
    </div>

    <div id="lent" class="tab-content">
        @if (empty($lentBooks))
            <div style="text-align: center; padding: 4rem 2rem; background: rgba(255,255,255,0.5); border-radius: var(--radius-xl);">
                <i class="fas fa-hand-holding-heart" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
                <p>No books lent yet.</p>
            </div>
        @else
            <div id="desktop-view-wrapper-lent" class="hide-on-mobile">
                <x-book-card-grid
                    :books="$lentBooks"
                    :showOwner="true"
                    extraInfoKey="borrower_name"
                    extraInfoLabel="Lent to"
                />
            </div>
            <div id="mobile-view-wrapper-lent" class="show-on-mobile">
                <x-book-card-list
                    :books="$lentBooks"
                    :showOwner="true"
                    extraInfoKey="borrower_name"
                    extraInfoLabel="Lent to"
                />
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchTab(evt, tabId) {
    const tabcontents = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabcontents.length; i++) {
        tabcontents[i].classList.remove("active");
    }
    const tablinks = document.getElementsByClassName("tab-btn");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }
    document.getElementById(tabId).classList.add("active");
    evt.currentTarget.classList.add("active");
}

function copyProfileLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        alert("Profile link copied to clipboard!");
    }).catch(err => {
        console.error('Could not copy text: ', err);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const glassCard = document.querySelector('.glass-card');
    if (glassCard) {
        glassCard.classList.add('active');
    }
});
</script>
@endpush
