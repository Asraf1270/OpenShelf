@extends('layouts.app')

@push('styles')
<style>
/* =========================================
   ANNOUNCEMENTS PAGE — Mobile-First Design
   ========================================= */

.ann-page {
    min-height: 60vh;
    padding-bottom: 5rem;
}

/* ── Hero Banner ── */
.ann-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5d7a 45%, #4c9f8a 100%);
    padding: 2rem 1rem 2.5rem;
    position: relative;
    overflow: hidden;
}
.ann-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.ann-hero-inner {
    max-width: 860px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}
.ann-hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    color: rgba(255,255,255,0.9);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    padding: 0.3rem 0.85rem;
    border-radius: 99px;
    margin-bottom: 0.85rem;
}
.ann-hero h1 {
    font-size: 1.65rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    line-height: 1.25;
}
.ann-hero p {
    color: rgba(255,255,255,0.82);
    font-size: 0.9rem;
    margin: 0;
    max-width: 480px;
    line-height: 1.6;
}

/* ── Main body area ── */
.ann-body {
    max-width: 860px;
    margin: 0 auto;
    padding: 1.5rem 1rem 0;
}

/* ── Back link ── */
.ann-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary, #64748b);
    text-decoration: none;
    padding: 0.5rem 0.9rem;
    border-radius: 10px;
    border: 1px solid var(--border, #e2e8f0);
    background: var(--surface-solid, #fff);
    margin-bottom: 1.25rem;
    transition: all 0.2s ease;
}
.ann-back-btn:hover {
    background: var(--surface-hover, #f1f5f9);
    color: var(--text, #0f172a);
    border-color: var(--primary, #2c3e50);
}

/* ── Priority badge ── */
.ann-priority {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    padding: 0.25rem 0.65rem;
    border-radius: 99px;
    flex-shrink: 0;
}
.ann-priority.info    { background: rgba(59,130,246,0.12); color: #1d4ed8; }
.ann-priority.primary { background: rgba(59,130,246,0.12); color: #1d4ed8; }
.ann-priority.success { background: rgba(16,185,129,0.12); color: #065f46; }
.ann-priority.warning { background: rgba(245,158,11,0.12); color: #92400e; }
.ann-priority.danger  { background: rgba(239,68,68,0.12);  color: #991b1b; }

/* ── New badge ── */
.ann-new-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 0.2rem 0.55rem;
    border-radius: 99px;
    background: linear-gradient(135deg, #4c9f8a, #2d5d7a);
    color: #fff;
    flex-shrink: 0;
}

/* ── Single announcement detail card ── */
.ann-detail-card {
    background: var(--surface-solid, #fff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 28px rgba(15,23,42,0.06);
}
.ann-detail-header {
    padding: 1.4rem 1.4rem 1rem;
    border-bottom: 1px solid var(--border, #e2e8f0);
}
.ann-detail-header h2 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text, #0f172a);
    margin: 0 0 0.75rem;
    line-height: 1.4;
}
.ann-detail-meta {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
}
.ann-detail-date {
    font-size: 0.8rem;
    color: var(--text-muted, #94a3b8);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.ann-detail-body {
    padding: 1.4rem;
    font-size: 0.95rem;
    line-height: 1.85;
    color: var(--text-secondary, #475569);
}

/* ── List card ── */
.ann-list-card {
    background: var(--surface-solid, #fff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 18px;
    padding: 1.1rem 1.25rem;
    box-shadow: 0 4px 14px rgba(15,23,42,0.04);
    transition: box-shadow 0.25s ease, transform 0.2s ease;
    text-decoration: none;
    display: block;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.ann-list-card:hover {
    box-shadow: 0 10px 28px rgba(15,23,42,0.1);
    transform: translateY(-2px);
}
.ann-list-card.unread {
    border-left: 4px solid #4c9f8a;
}
.ann-list-card.danger-card {
    border-left: 4px solid #ef4444;
}
.ann-list-card.warning-card {
    border-left: 4px solid #f59e0b;
}
.ann-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.65rem;
}
.ann-card-title {
    font-size: 0.975rem;
    font-weight: 700;
    color: var(--text, #0f172a);
    line-height: 1.45;
    flex: 1;
    min-width: 0;
    text-decoration: none;
}
.ann-card-title:hover { color: #4c9f8a; }
.ann-card-preview {
    font-size: 0.875rem;
    color: var(--text-secondary, #64748b);
    line-height: 1.65;
    margin-bottom: 0.8rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.ann-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.ann-card-date {
    font-size: 0.775rem;
    color: var(--text-muted, #94a3b8);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.ann-card-badges {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
}
.ann-read-more {
    font-size: 0.8rem;
    font-weight: 600;
    color: #4c9f8a;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    text-decoration: none;
    transition: gap 0.2s ease;
}
.ann-read-more:hover { gap: 0.4rem; }

/* ── Empty state ── */
.ann-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--surface-solid, #fff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 24px;
    box-shadow: 0 4px 18px rgba(15,23,42,0.05);
}
.ann-empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.25rem;
    background: linear-gradient(135deg, rgba(76,159,138,0.12), rgba(45,93,122,0.12));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #4c9f8a;
}
.ann-empty h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text, #0f172a);
    margin: 0 0 0.5rem;
}
.ann-empty p {
    font-size: 0.9rem;
    color: var(--text-muted, #94a3b8);
    margin: 0;
    max-width: 300px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

/* ── Section header ── */
.ann-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    gap: 0.5rem;
}
.ann-section-title {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-muted, #94a3b8);
}
.ann-count-pill {
    font-size: 0.72rem;
    font-weight: 700;
    background: var(--surface-hover, #f1f5f9);
    color: var(--text-secondary, #64748b);
    padding: 0.2rem 0.6rem;
    border-radius: 99px;
}

/* ── Cards list spacing ── */
.ann-list {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

/* ── Responsive ── */
@media (min-width: 640px) {
    .ann-hero {
        padding: 3rem 2rem 3.5rem;
    }
    .ann-hero h1 {
        font-size: 2rem;
    }
    .ann-hero p {
        font-size: 1rem;
    }
    .ann-body {
        padding: 2rem 1.5rem 0;
    }
    .ann-detail-header {
        padding: 1.75rem 2rem 1.25rem;
    }
    .ann-detail-body {
        padding: 1.75rem 2rem;
    }
    .ann-list-card {
        padding: 1.25rem 1.5rem;
    }
    .ann-card-title {
        font-size: 1rem;
    }
    .ann-card-preview {
        -webkit-line-clamp: 3;
    }
}

@media (min-width: 1024px) {
    .ann-hero {
        padding: 3.5rem 2rem 4rem;
    }
    .ann-hero h1 {
        font-size: 2.25rem;
    }
    .ann-detail-header h2 {
        font-size: 1.45rem;
    }
    .ann-detail-body {
        font-size: 1rem;
    }
}

/* ── Dark Mode ── */
[data-theme="dark"] .ann-detail-card,
[data-theme="dark"] .ann-list-card,
[data-theme="dark"] .ann-empty {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 4px 18px rgba(0,0,0,0.25);
}
[data-theme="dark"] .ann-list-card:hover {
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
}
[data-theme="dark"] .ann-detail-header { border-bottom-color: #334155; }
[data-theme="dark"] .ann-detail-header h2,
[data-theme="dark"] .ann-card-title,
[data-theme="dark"] .ann-empty h3 { color: #f8fafc; }
[data-theme="dark"] .ann-detail-body,
[data-theme="dark"] .ann-card-preview { color: #94a3b8; }
[data-theme="dark"] .ann-card-date,
[data-theme="dark"] .ann-detail-date,
[data-theme="dark"] .ann-empty p { color: #64748b; }
[data-theme="dark"] .ann-back-btn {
    background: #1e293b;
    border-color: #334155;
    color: #94a3b8;
}
[data-theme="dark"] .ann-back-btn:hover {
    background: #334155;
    color: #f8fafc;
}
[data-theme="dark"] .ann-count-pill {
    background: #334155;
    color: #94a3b8;
}
[data-theme="dark"] .ann-priority.info,
[data-theme="dark"] .ann-priority.primary { background: rgba(59,130,246,0.2); color: #93c5fd; }
[data-theme="dark"] .ann-priority.success { background: rgba(16,185,129,0.2); color: #6ee7b7; }
[data-theme="dark"] .ann-priority.warning { background: rgba(245,158,11,0.2); color: #fcd34d; }
[data-theme="dark"] .ann-priority.danger  { background: rgba(239,68,68,0.2);  color: #fca5a5; }
[data-theme="dark"] .ann-empty-icon { background: linear-gradient(135deg, rgba(76,159,138,0.2), rgba(45,93,122,0.2)); }
[data-theme="dark"] .ann-section-title { color: #64748b; }
</style>
@endpush

@section('content')
<div class="ann-page">

    {{-- ── Hero ── --}}
    <div class="ann-hero">
        <div class="ann-hero-inner">
            <div class="ann-hero-pill">
                <i class="fas fa-bullhorn"></i>
                Announcements
            </div>
            <h1>
                <i class="fas fa-bell"></i>
                What's New
            </h1>
            <p>Stay up to date with important updates, features, and news from the OpenShelf team.</p>
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="ann-body">

        @if ($selectedAnnouncement)
            {{-- ── Single Detail View ── --}}
            <a href="{{ route('announcements.index') }}" class="ann-back-btn">
                <i class="fas fa-arrow-left"></i>
                All Announcements
            </a>

            <div class="ann-detail-card">
                <div class="ann-detail-header">
                    <h2>{{ $selectedAnnouncement->title }}</h2>
                    <div class="ann-detail-meta">
                        <span class="ann-priority {{ $selectedAnnouncement->priority_badge }}">
                            @if($selectedAnnouncement->priority === 'danger')
                                <i class="fas fa-exclamation-circle"></i>
                            @elseif($selectedAnnouncement->priority === 'warning')
                                <i class="fas fa-exclamation-triangle"></i>
                            @elseif($selectedAnnouncement->priority === 'success')
                                <i class="fas fa-check-circle"></i>
                            @else
                                <i class="fas fa-info-circle"></i>
                            @endif
                            {{ $selectedAnnouncement->priority_label }}
                        </span>
                        <span class="ann-detail-date">
                            <i class="far fa-calendar-alt"></i>
                            {{ $selectedAnnouncement->created_at?->format('F j, Y') }}
                        </span>
                    </div>
                </div>
                <div class="ann-detail-body">
                    {!! nl2br(e($selectedAnnouncement->content)) !!}
                </div>
            </div>

        @else
            {{-- ── Announcements List ── --}}
            @if ($activeAnnouncements->isEmpty())
                <div class="ann-empty">
                    <div class="ann-empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h3>No Announcements Yet</h3>
                    <p>Check back later for important updates from the OpenShelf team.</p>
                </div>
            @else
                @php
                    $unreadCount = $activeAnnouncements->filter(fn($a) => !in_array($a->id, $readIds, true))->count();
                @endphp
                <div class="ann-section-header">
                    <span class="ann-section-title">All Updates</span>
                    @if($unreadCount > 0)
                        <span class="ann-count-pill">{{ $unreadCount }} unread</span>
                    @endif
                </div>

                <div class="ann-list">
                    @foreach ($activeAnnouncements as $announcement)
                        @php
                            $isRead = in_array($announcement->id, $readIds, true);
                            $cardClass = '';
                            if (!$isRead) {
                                $cardClass = $announcement->priority === 'danger' ? 'unread danger-card'
                                    : ($announcement->priority === 'warning' ? 'unread warning-card' : 'unread');
                            }
                        @endphp
                        <a href="{{ route('announcements.index', ['id' => $announcement->id]) }}"
                           class="ann-list-card {{ $cardClass }}">
                            <div class="ann-card-top">
                                <div class="ann-card-title">{{ $announcement->title }}</div>
                                <div class="ann-card-badges">
                                    <span class="ann-priority {{ $announcement->priority_badge }}">
                                        @if($announcement->priority === 'danger')
                                            <i class="fas fa-exclamation-circle"></i>
                                        @elseif($announcement->priority === 'warning')
                                            <i class="fas fa-exclamation-triangle"></i>
                                        @elseif($announcement->priority === 'success')
                                            <i class="fas fa-check-circle"></i>
                                        @else
                                            <i class="fas fa-info-circle"></i>
                                        @endif
                                        {{ $announcement->priority_label }}
                                    </span>
                                    @if(!$isRead)
                                        <span class="ann-new-badge">
                                            <i class="fas fa-circle" style="font-size:0.45rem;"></i>
                                            New
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="ann-card-preview">
                                {{ Str::limit($announcement->content, 160, '') }}
                            </div>

                            <div class="ann-card-footer">
                                <span class="ann-card-date">
                                    <i class="far fa-calendar-alt"></i>
                                    {{ $announcement->created_at?->format('M j, Y') }}
                                </span>
                                @if(strlen($announcement->content) > 160)
                                    <span class="ann-read-more">
                                        Read more <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i>
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif

    </div>
</div>
@endsection
