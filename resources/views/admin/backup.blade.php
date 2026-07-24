@extends('admin.layouts.app')

@section('title', 'Backup - OpenShelf Admin')
@section('page_title', 'Backup Manager')

@push('styles')
<style>
    .backup-page { max-width: 800px; margin: 0 auto; }
    .create-card, .backup-list {
        background: var(--surface); border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow-sm);
    }
    .create-card { padding: 2.5rem; margin-bottom: 2.5rem; text-align: center; }
    .backup-list { overflow: hidden; }
    .backup-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 1.25rem 2rem; border-bottom: 1px solid var(--border);
    }
    .backup-item:last-child { border-bottom: none; }
    .backup-item:hover { background: rgba(76,159,138,0.05); }
    .backup-name { font-weight: 750; font-size: 1.05rem; }
    .backup-date { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-top: 0.25rem; }
    .backup-size { font-size: 0.8rem; color: var(--secondary); font-weight: 700; margin-top: 0.2rem; }
    .actions { display: flex; gap: 0.5rem; }
    .btn { padding: 0.45rem 1rem; border-radius: 12px; font-weight: 750; cursor: pointer; border: none; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-danger { background: #EF4444; color: white; }
</style>
@endpush

@section('content')
<div class="backup-page">
    <h1 style="margin-bottom:1.5rem;">Backup Manager</h1>

    @if ($message)
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ $message }}</div>
    @endif
    @if ($error)
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
    @endif

    <div class="create-card">
        <i class="fas fa-database" style="font-size:3.5rem;color:var(--primary);margin-bottom:1.5rem;opacity:0.8;"></i>
        <h3 style="font-weight:850;letter-spacing:-1px;">Create a New Backup</h3>
        <p style="color:var(--text-muted);margin-bottom:1.5rem;font-weight:500;">Safely archive all user data, book records, and system settings.</p>
        <form method="POST" action="{{ route('admin.backup.index') }}">
            @csrf
            <input type="hidden" name="action" value="create">
            <button type="submit" class="btn-admin btn-admin-primary"><i class="fas fa-plus"></i> Create Backup Now</button>
        </form>
    </div>

    <div class="backup-list">
        <div class="backup-item" style="background:var(--bg-body);">
            <strong style="text-transform:uppercase;font-size:0.75rem;letter-spacing:1.2px;color:var(--text-muted);">Available Backups</strong>
            <span style="text-transform:uppercase;font-size:0.75rem;letter-spacing:1.2px;color:var(--text-muted);">Actions</span>
        </div>
        @forelse ($backups as $backup)
            <div class="backup-item">
                <div>
                    <div class="backup-name">
                        {{ $backup['name'] }}
                        @if ($backup['is_auto'])
                            <span style="background:#e2e8f0;color:#64748b;font-size:0.7rem;padding:0.1rem 0.4rem;border-radius:1rem;margin-left:0.5rem;">Auto</span>
                        @endif
                    </div>
                    <div class="backup-date"><i class="far fa-calendar-alt"></i> {{ $backup['date'] }}</div>
                    <div class="backup-size"><i class="fas fa-hdd"></i> {{ $backup['size'] }} KB</div>
                </div>
                <div class="actions">
                    <form method="GET" action="{{ route('admin.backup.restore') }}" style="display:inline;" onsubmit="return confirm('Restore this backup? Current data will be overwritten.')">
                        <input type="hidden" name="name" value="{{ $backup['name'] }}">
                        <button type="submit" class="btn btn-primary">Restore</button>
                    </form>
                    <form method="POST" action="{{ route('admin.backup.index') }}" style="display:inline;" onsubmit="return confirm('Delete this backup?')">
                        @csrf
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="name" value="{{ $backup['name'] }}">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="backup-item">No backups found. Create your first backup.</div>
        @endforelse
    </div>
</div>
@endsection
