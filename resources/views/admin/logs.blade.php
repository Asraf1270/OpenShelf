@extends('admin.layouts.app')

@section('title', 'System Logs - OpenShelf Admin')
@section('page_title', 'System Logs')

@push('styles')
<style>
    .logs-page { max-width: 1200px; margin: 0 auto; }
    .tabs { display: flex; gap: 0.75rem; margin-bottom: 2rem; flex-wrap: wrap; }
    .tab-btn {
        padding: 0.75rem 1.5rem; background: var(--surface); border: 1px solid var(--border);
        border-radius: 2rem; text-decoration: none; color: var(--text-muted); font-weight: 700;
        display: flex; align-items: center; gap: 0.5rem;
    }
    .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
    .log-container {
        background: #0F172A; border-radius: 16px; padding: 1.5rem; overflow-x: auto;
        font-family: 'Fira Code', 'Courier New', monospace; border: 1px solid var(--border);
    }
    .log-entry {
        padding: 0.65rem; border-bottom: 1px solid rgba(255,255,255,0.05);
        color: #94A3B8; font-size: 0.85rem; white-space: pre-wrap; word-break: break-all;
    }
    .log-entry:hover { background: rgba(255,255,255,0.02); }
    .log-error { color: #F87171; background: rgba(248,113,113,0.05); }
    .log-warning { color: #FBBF24; }
    .actions { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
    .btn-log {
        padding: 0.65rem 1.25rem; border-radius: 12px; text-decoration: none; font-weight: 700;
        font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;
    }
    .btn-log-default { background: var(--surface); color: var(--text-main); border: 1px solid var(--border); }
    .btn-log-danger { background: #EF4444; color: white; }
</style>
@endpush

@section('content')
<div class="logs-page">
    <h1 style="margin-bottom:1.5rem;">System Logs</h1>

    @if ($message)
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ $message }}</div>
    @endif
    @if ($error)
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
    @endif

    <div class="tabs">
        <a href="{{ route('admin.logs.index', ['type' => 'admin']) }}" class="tab-btn {{ $logType === 'admin' ? 'active' : '' }}"><i class="fas fa-user-shield"></i> Admin Logs</a>
        <a href="{{ route('admin.logs.index', ['type' => 'user']) }}" class="tab-btn {{ $logType === 'user' ? 'active' : '' }}"><i class="fas fa-users"></i> User Activity</a>
        <a href="{{ route('admin.logs.index', ['type' => 'error']) }}" class="tab-btn {{ $logType === 'error' ? 'active' : '' }}"><i class="fas fa-exclamation-triangle"></i> Error Logs</a>
        <a href="{{ route('admin.logs.index', ['type' => 'mail']) }}" class="tab-btn {{ $logType === 'mail' ? 'active' : '' }}"><i class="fas fa-envelope"></i> Mail Logs</a>
    </div>

    <div class="actions">
        <a href="{{ route('admin.logs.clear', ['type' => $logType]) }}" class="btn-log btn-log-danger" onclick="return confirm('Clear all logs?')">
            <i class="fas fa-trash"></i> Clear Logs
        </a>
        <a href="{{ route('admin.logs.download', ['type' => $logType]) }}" class="btn-log btn-log-default">
            <i class="fas fa-download"></i> Download
        </a>
    </div>

    <div class="log-container">
        @forelse ($logs as $log)
            <div class="log-entry {{ str_contains($log, 'ERROR') ? 'log-error' : (str_contains($log, 'WARNING') ? 'log-warning' : '') }}">
                {{ $log }}
            </div>
        @empty
            <div class="log-entry">No logs available.</div>
        @endforelse
    </div>
</div>
@endsection
