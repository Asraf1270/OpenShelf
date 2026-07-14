@extends('layouts.app')

@push('styles')
<style>
    :root {
        --primary: #2C3E50;
        --secondary: #4C9F8A;
        --success: #4C9F8A;
        --warning: #f59e0b;
        --danger: #ef4444;
        --bg: #F8F9FA;
        --surface: #ffffff;
        --border: #E2E8F0;
        --text-main: #0F172A;
        --text-muted: #5A6C7D;
        --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
        --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.05);
        --radius-lg: 16px;
        --radius-xl: 20px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    :root[data-theme="dark"] {
        --bg: #0F172A;
        --surface: #1E293B;
        --border: #334155;
        --text-main: #F8F9FA;
        --text-muted: #94A3B8;
        --primary: #4C9F8A;
        --shadow-sm: 0 1px 2px rgba(0,0,0,0.2);
        --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.3);
    }

    .notifications-page {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .page-header {
        margin-bottom: 2rem;
        text-align: center;
    }

    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 850;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
        letter-spacing: -1px;
    }

    .page-header p {
        color: var(--text-muted);
        font-weight: 500;
    }

    .stats-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--surface);
        padding: 1rem 1.5rem;
        border-radius: var(--radius-lg);
        margin-bottom: 1.5rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    .unread-badge {
        background: var(--primary);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .mark-all-btn {
        background: none;
        border: none;
        color: var(--primary);
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        transition: var(--transition);
    }

    .mark-all-btn:hover {
        background: var(--primary);
        color: white;
    }

    .notification-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .notification-item {
        background: var(--surface);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 1.25rem;
        border: 1px solid var(--border);
        transition: var(--transition);
        cursor: pointer;
        position: relative;
    }

    .notification-item:hover {
        transform: translateX(4px);
        box-shadow: var(--shadow-md);
    }

    .notification-item.unread {
        background: linear-gradient(135deg, rgba(76, 159, 138, 0.08), rgba(76, 159, 138, 0.02));
        border-left: 4px solid var(--secondary);
    }

    .notification-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.2rem;
    }

    .notification-content { flex: 1; }

    .notification-title {
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 0.35rem;
        font-size: 1.05rem;
    }

    .notification-message {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
        line-height: 1.5;
    }

    .notification-time {
        font-size: 0.7rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .notification-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .action-btn {
        background: none;
        border: none;
        font-size: 0.7rem;
        color: var(--text-muted);
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        border-radius: 0.5rem;
        transition: var(--transition);
    }

    .action-btn:hover {
        background: var(--danger);
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 5rem 2rem;
        background: var(--surface);
        border-radius: var(--radius-xl);
        border: 1px solid var(--border);
    }

    .empty-state i {
        font-size: 4rem;
        color: var(--text-muted);
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
    }

    .empty-state p { color: var(--text-muted); }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    .page-btn {
        padding: 0.5rem 0.75rem;
        min-width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        transition: var(--transition);
    }

    .page-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    .page-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .page-btn.disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    @media (max-width: 640px) {
        .stats-bar {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .notification-item { padding: 0.875rem; }

        .notification-icon {
            width: 36px;
            height: 36px;
            font-size: 1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="notifications-page">
    <div class="page-header">
        <h1><i class="fas fa-bell" style="color: var(--primary);"></i> Notifications</h1>
        <p>Stay updated with your latest activities</p>
    </div>

    @if ($message)
        <div class="alert alert-success" style="background: rgba(16,185,129,0.1); color: var(--success); padding: 1rem; border-radius: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-check-circle"></i> {{ $message }}
        </div>
    @endif

    @if ($error)
        <div class="alert alert-danger" style="background: rgba(239,68,68,0.1); color: var(--danger); padding: 1rem; border-radius: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-exclamation-circle"></i> {{ $error }}
        </div>
    @endif

    <div class="stats-bar">
        <div>
            <span class="unread-badge">{{ $unreadCount }} unread</span>
            <span style="margin-left: 0.5rem; color: var(--text-muted);">{{ $total }} total</span>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.index') }}" onsubmit="return confirm('Mark all notifications as read?')">
                @csrf
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="mark-all-btn">
                    <i class="fas fa-check-double"></i> Mark all as read
                </button>
            </form>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <div class="empty-state">
            <i class="fas fa-bell-slash"></i>
            <h3>No Notifications</h3>
            <p>You're all caught up! New notifications will appear here.</p>
        </div>
    @else
        <div class="notification-list">
            @foreach ($notifications as $notification)
                @php
                    $icon = $notificationService->iconFor($notification->type);
                    $color = $notificationService->colorFor($notification->type);
                    $timeAgo = $notificationService->timeAgo($notification->created_at);
                @endphp
                <div class="notification-item {{ $notification->is_read ? '' : 'unread' }}"
                     data-id="{{ $notification->id }}"
                     data-link="{{ $notification->link ?? '#' }}">
                    <div class="notification-icon" style="background: {{ $color }}20; color: {{ $color }};">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">{{ $notification->title }}</div>
                        <div class="notification-message">{{ $notification->message }}</div>
                        <div class="notification-time">
                            <i class="far fa-clock"></i> {{ $timeAgo }}
                        </div>
                        <div class="notification-actions">
                            @if (! $notification->is_read)
                                <form method="POST" action="{{ route('notifications.index') }}" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="{{ $notification->id }}">
                                    <button type="submit" class="action-btn">
                                        <i class="fas fa-check"></i> Mark as read
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('notifications.index') }}" style="display: inline;" onsubmit="return confirm('Delete this notification?')">
                                @csrf
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="notification_id" value="{{ $notification->id }}">
                                <button type="submit" class="action-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($notifications->hasPages())
            <div class="pagination">
                @if ($notifications->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $notifications->previousPageUrl() }}" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                @endif

                @foreach ($notifications->getUrlRange(max(1, $notifications->currentPage() - 2), min($notifications->lastPage(), $notifications->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}" class="page-btn {{ $page === $notifications->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                @endforeach

                @if ($notifications->hasMorePages())
                    <a href="{{ $notifications->nextPageUrl() }}" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.notification-item').forEach(item => {
        const link = item.dataset.link;
        if (link && link !== '#') {
            item.addEventListener('click', function(e) {
                if (e.target.closest('.action-btn') || e.target.closest('form')) {
                    return;
                }

                const notificationId = this.dataset.id;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                fetch('{{ route('notifications.index') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: new URLSearchParams({
                        action: 'mark_read',
                        notification_id: notificationId,
                    }),
                }).finally(() => {
                    window.location.href = link;
                });
            });
        }
    });
</script>
@endpush
