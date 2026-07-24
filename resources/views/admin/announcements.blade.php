@extends('admin.layouts.app')

@section('title', 'Announcements - OpenShelf Admin')
@section('page_title', 'Announcements')

@push('styles')
<style>
    .announcements-page { max-width: 1200px; margin: 0 auto; }
    .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .stat-card { background: var(--surface); border-radius: 1rem; padding: 1.25rem; text-align: center; border: 1px solid var(--border); }
    .stat-number { font-size: 2rem; font-weight: 700; margin-bottom: 0.25rem; }
    .stat-label { color: var(--text-muted); font-size: 0.85rem; }
    .form-card { background: var(--surface); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--border); }
    .form-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border); }
    .form-header h2 { margin: 0; font-size: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
    .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem; }
    .form-group { margin-bottom: 1rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
    .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 0.75rem; font-size: 0.9rem; background: var(--surface); color: var(--text-main); font: inherit; }
    textarea.form-control { min-height: 150px; resize: vertical; }
    .checkbox-group { display: flex; gap: 1rem; flex-wrap: wrap; }
    .checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
    .announcement-list { display: flex; flex-direction: column; gap: 1rem; }
    .announcement-card { background: var(--surface); border-radius: 1rem; padding: 1.25rem; border: 1px solid var(--border); }
    .announcement-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.75rem; }
    .announcement-title { font-size: 1.1rem; font-weight: 600; margin-left: 0.5rem; }
    .priority-badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; border-radius: 2rem; font-size: 0.7rem; font-weight: 600; }
    .priority-info { background: rgba(59,130,246,0.1); color: #3b82f6; }
    .priority-success { background: rgba(16,185,129,0.1); color: #10b981; }
    .priority-warning { background: rgba(245,158,11,0.1); color: #f59e0b; }
    .priority-danger { background: rgba(239,68,68,0.1); color: #ef4444; }
    .announcement-content { color: var(--text-main); margin-bottom: 1rem; line-height: 1.5; white-space: pre-wrap; }
    .announcement-meta { display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.7rem; color: var(--text-muted); margin-bottom: 0.75rem; }
    .announcement-actions { display: flex; gap: 0.5rem; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 0.75rem; margin-top: 0.5rem; }
    .action-btn { padding: 0.35rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; cursor: pointer; background: none; border: none; color: var(--text-muted); text-decoration: none; }
    .action-btn:hover { background: var(--bg-body); color: var(--primary); }
    .action-btn.delete:hover { color: #ef4444; }
    .empty-state { text-align: center; padding: 3rem; background: var(--surface); border-radius: 1rem; border: 1px solid var(--border); }
    @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="announcements-page">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;margin:0;">Announcements</h1>
            <p style="color:var(--text-muted);margin:0.5rem 0 0;">Create and manage announcements sent to all users</p>
        </div>
    </div>

    <div class="stats-cards">
        <div class="stat-card"><div class="stat-number" style="color:var(--primary);">{{ $totalAnnouncements }}</div><div class="stat-label">Total Announcements</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#10b981;">{{ $activeAnnouncements }}</div><div class="stat-label">Active</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#f59e0b;">{{ $scheduledAnnouncements }}</div><div class="stat-label">Scheduled</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#ef4444;">{{ $expiredAnnouncements }}</div><div class="stat-label">Expired</div></div>
    </div>

    <div class="form-card">
        <div class="form-header">
            <h2><i class="fas fa-{{ $editingAnnouncement ? 'edit' : 'plus-circle' }}"></i> {{ $editingAnnouncement ? 'Edit Announcement' : 'Create New Announcement' }}</h2>
            @if ($editingAnnouncement)
                <a href="{{ route('admin.announcements.index') }}" class="btn-admin btn-outline">Cancel Edit</a>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.announcements.index', request()->query()) }}">
            @csrf
            <input type="hidden" name="action" value="{{ $editingAnnouncement ? 'update' : 'create' }}">
            @if ($editingAnnouncement)
                <input type="hidden" name="announcement_id" value="{{ $editingAnnouncement->id }}">
            @endif

            <div class="form-group">
                <label class="form-label"><i class="fas fa-heading"></i> Title</label>
                <input type="text" name="title" class="form-control" required maxlength="200" value="{{ old('title', $editingAnnouncement->title ?? '') }}" placeholder="Enter announcement title...">
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fas fa-align-left"></i> Content</label>
                <textarea name="content" class="form-control" rows="8" required placeholder="Write your announcement content here...">{{ old('content', $editingAnnouncement->content ?? '') }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-flag"></i> Priority</label>
                    <select name="priority" class="form-control">
                        @foreach (['info' => 'Info - General Information', 'success' => 'Success - Positive Update', 'warning' => 'Warning - Important Notice', 'danger' => 'Urgent - Critical Update'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', $editingAnnouncement->priority ?? 'info') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @unless ($editingAnnouncement)
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-users"></i> Target Audience</label>
                        <select name="target" class="form-control">
                            <option value="all" @selected(old('target', 'all') === 'all')>All Users</option>
                            <option value="active" @selected(old('target') === 'active')>Active Users Only</option>
                        </select>
                    </div>
                @endunless
            </div>

            <div class="form-row">
                @unless ($editingAnnouncement)
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar"></i> Schedule (Optional)</label>
                        <input type="datetime-local" name="schedule_date" class="form-control" value="{{ old('schedule_date') }}">
                        <small style="color:var(--text-muted);">Leave empty to send immediately</small>
                    </div>
                @endunless
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-calendar-times"></i> Expiry Date</label>
                    <input type="datetime-local" name="expiry_date" class="form-control" value="{{ old('expiry_date', $editingAnnouncement?->expires_at?->format('Y-m-d\TH:i') ?? now()->addDays(30)->format('Y-m-d\TH:i')) }}">
                </div>
            </div>

            @unless ($editingAnnouncement)
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-bell"></i> Delivery Methods</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="send_email" @checked(old('send_email', true))>
                            <i class="fas fa-envelope"></i> Send via Email
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="send_notification" @checked(old('send_notification', true))>
                            <i class="fas fa-bell"></i> Send via In-App Notification
                        </label>
                    </div>
                </div>
            @endunless

            <div style="display:flex;gap:1rem;margin-top:1rem;">
                <button type="submit" class="btn-admin btn-admin-primary">
                    <i class="fas fa-{{ $editingAnnouncement ? 'save' : 'paper-plane' }}"></i>
                    {{ $editingAnnouncement ? 'Update Announcement' : 'Create & Send' }}
                </button>
            </div>
        </form>
    </div>

    <h2 style="margin:1.5rem 0 1rem;">All Announcements</h2>

    @if ($announcements->isEmpty())
        <div class="empty-state">
            <i class="fas fa-bullhorn" style="font-size:3rem;color:#cbd5e1;margin-bottom:1rem;"></i>
            <h3>No Announcements Yet</h3>
            <p style="color:var(--text-muted);">Create your first announcement to reach all users</p>
        </div>
    @else
        <div class="announcement-list">
            @foreach ($announcements as $announcement)
                @php
                    $priorityClass = 'priority-' . ($announcement->priority ?? 'info');
                    $isExpired = $announcement->expires_at && $announcement->expires_at->isPast();
                    $isScheduled = $announcement->scheduled_for && $announcement->scheduled_for->isFuture();
                @endphp
                <div class="announcement-card">
                    <div class="announcement-header">
                        <div>
                            <span class="priority-badge {{ $priorityClass }}">{{ strtoupper($announcement->priority ?? 'INFO') }}</span>
                            <span class="announcement-title">{{ $announcement->title }}</span>
                        </div>
                        <div class="announcement-meta">
                            <span><i class="fas fa-user"></i> {{ $announcement->created_by_name }}</span>
                            <span><i class="far fa-calendar"></i> {{ optional($announcement->created_at)->format('M j, Y g:i A') }}</span>
                            @if ($isScheduled)
                                <span style="color:#f59e0b;"><i class="fas fa-clock"></i> Scheduled: {{ $announcement->scheduled_for->format('M j, Y g:i A') }}</span>
                            @endif
                            @if ($isExpired)
                                <span style="color:#ef4444;"><i class="fas fa-ban"></i> Expired</span>
                            @endif
                            <span><i class="fas fa-eye"></i> {{ $announcement->stats['views'] ?? 0 }} views</span>
                            <span><i class="fas fa-check-circle"></i> {{ $announcement->stats['read'] ?? 0 }} read</span>
                        </div>
                    </div>
                    <div class="announcement-content">{{ \Illuminate\Support\Str::limit($announcement->content, 300) }}</div>
                    <div class="announcement-actions">
                        @if ($isScheduled)
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Send this announcement now?')">
                                @csrf
                                <input type="hidden" name="action" value="send_now">
                                <input type="hidden" name="announcement_id" value="{{ $announcement->id }}">
                                <button type="submit" class="action-btn"><i class="fas fa-paper-plane"></i> Send Now</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.announcements.index', ['edit' => $announcement->id]) }}" class="action-btn"><i class="fas fa-edit"></i> Edit</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this announcement?')">
                            @csrf
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="announcement_id" value="{{ $announcement->id }}">
                            <button type="submit" class="action-btn delete"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
