@extends('admin.layouts.app')

@section('title', 'Contact Messages - OpenShelf Admin')
@section('page_title', 'Contact Messages')

@push('styles')
<style>
    .cp { max-width: 1200px; margin: 0 auto; }
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .stat-card { background: var(--surface); padding: 1.75rem; border-radius: 16px; border: 1px solid var(--border); text-align: center; }
    .stat-card .sv { font-size: 2.25rem; font-weight: 850; }
    .stat-card .sl { color: var(--text-muted); font-size: 0.85rem; font-weight: 600; margin-top: 0.5rem; }
    .filters-bar { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
    .filter-select { padding: 0.75rem 1.25rem; border: 1.5px solid var(--border); border-radius: 12px; background: var(--surface); color: var(--text-main); font: inherit; font-weight: 600; }
    .mc { background: var(--surface); border-radius: 16px; border: 1px solid var(--border); margin-bottom: 1.25rem; overflow: hidden; }
    .mc-h { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 1rem; }
    .mc-h h3 { font-size: 1.1rem; font-weight: 750; margin: 0 0 0.4rem; }
    .mm { display: flex; gap: 1.5rem; color: var(--text-muted); font-size: 0.85rem; flex-wrap: wrap; }
    .sb { display: inline-block; padding: 0.35rem 1rem; border-radius: 2rem; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
    .mc-b { padding: 1.5rem 2rem; }
    .mc-msg { line-height: 1.7; white-space: pre-wrap; }
    .mc-a { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; padding: 1.25rem 2rem; border-top: 1px solid var(--border); }
    .af { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; flex: 1; }
    .af textarea { padding: 0.6rem 1rem; border: 1.5px solid var(--border); border-radius: 10px; background: var(--surface); color: var(--text-main); font: inherit; flex: 1; min-width: 200px; resize: none; height: 38px; }
    .ba { padding: 0.6rem 1.25rem; border: none; border-radius: 10px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none; }
    .bu { background: var(--primary); color: #fff; }
    .bm { background: rgba(59,130,246,0.1); color: #3b82f6; border: 1px solid rgba(59,130,246,0.2); }
    .bd { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
    .reply-d { background: rgba(76,159,138,0.05); border-left: 3px solid var(--secondary); padding: 1rem 1.25rem; border-radius: 0 10px 10px 0; margin-top: 1rem; font-size: 0.9rem; }
    .empty { text-align: center; padding: 5rem 2rem; color: var(--text-muted); }
    @media (max-width: 768px) { .mc-h, .mc-b, .mc-a { padding: 1.25rem; } .af { flex-direction: column; width: 100%; } .af textarea { width: 100%; } }
</style>
@endpush

@section('content')
@php
    $statusLabels = ['unread' => 'Unread', 'read' => 'Read', 'replied' => 'Replied'];
    $statusColors = ['unread' => '#f59e0b', 'read' => '#3b82f6', 'replied' => '#10b981'];
@endphp

<div class="cp">
    <div class="stats-row">
        <div class="stat-card"><div class="sv" style="color:var(--primary);">{{ $totalMsgs }}</div><div class="sl">Total Messages</div></div>
        <div class="stat-card"><div class="sv" style="color:#f59e0b;">{{ $unreadMsgs }}</div><div class="sl">Unread</div></div>
        <div class="stat-card"><div class="sv" style="color:#10b981;">{{ $repliedMsgs }}</div><div class="sl">Replied</div></div>
    </div>

    <div class="filters-bar">
        <select class="filter-select" id="statusFilter" onchange="applyFilters()">
            <option value="all" @selected($statusFilter === 'all')>All Status</option>
            <option value="unread" @selected($statusFilter === 'unread')>Unread</option>
            <option value="read" @selected($statusFilter === 'read')>Read</option>
            <option value="replied" @selected($statusFilter === 'replied')>Replied</option>
        </select>
    </div>

    @if ($messages->isEmpty())
        <div class="empty">
            <i class="fas fa-inbox" style="font-size:4rem;margin-bottom:1.5rem;opacity:0.3;"></i>
            <h3>No messages found</h3>
            <p>No contact messages matching your filter.</p>
        </div>
    @else
        @foreach ($messages as $message)
            <div class="mc">
                <div class="mc-h">
                    <div>
                        <h3>{{ $message->subject }}</h3>
                        <div class="mm">
                            <span><i class="fas fa-user"></i> {{ $message->name }}</span>
                            <span><i class="fas fa-envelope"></i> {{ $message->email }}</span>
                            <span><i class="fas fa-clock"></i> {{ optional($message->created_at)->format('d M Y, h:i A') }}</span>
                        </div>
                    </div>
                    <span class="sb" style="background:{{ ($statusColors[$message->status] ?? '#6b7280') }}20;color:{{ $statusColors[$message->status] ?? '#6b7280' }}">
                        {{ $statusLabels[$message->status] ?? $message->status }}
                    </span>
                </div>
                <div class="mc-b">
                    <div class="mc-msg">{{ $message->message }}</div>
                    @if ($message->admin_reply)
                        <div class="reply-d">
                            <strong>Admin Reply</strong>
                            <p style="margin:0.5rem 0 0;">{{ $message->admin_reply }}</p>
                            <p style="color:var(--text-muted);font-size:0.8rem;margin-top:0.5rem;">— {{ $message->replied_by }}, {{ optional($message->replied_at)->format('d M Y') }}</p>
                        </div>
                    @endif
                </div>
                <div class="mc-a">
                    <form method="POST" action="{{ route('admin.contact-messages.index', request()->query()) }}" class="af">
                        @csrf
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="msg_id" value="{{ $message->id }}">
                        <textarea name="admin_reply" placeholder="Write a reply...">{{ $message->admin_reply }}</textarea>
                        <button type="submit" class="ba bu"><i class="fas fa-paper-plane"></i> Send Reply</button>
                    </form>
                    <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: ' . $message->subject . ' — OpenShelf Support') }}" class="ba bm"><i class="fas fa-envelope"></i> Mailto</a>
                    @if ($message->status === 'unread')
                        <form method="POST">
                            @csrf
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="msg_id" value="{{ $message->id }}">
                            <button type="submit" class="ba bm"><i class="fas fa-eye"></i></button>
                        </form>
                    @endif
                    <form method="POST" onsubmit="return confirm('Delete this message?')">
                        @csrf
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="msg_id" value="{{ $message->id }}">
                        <button type="submit" class="ba bd"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection

@push('scripts')
<script>
    function applyFilters() {
        const status = document.getElementById('statusFilter').value;
        const url = new URL(@js(route('admin.contact-messages.index')));
        if (status !== 'all') url.searchParams.set('status', status);
        window.location.href = url.toString();
    }
</script>
@endpush
