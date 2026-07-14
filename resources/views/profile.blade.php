@extends('layouts.app')

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
