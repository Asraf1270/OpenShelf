@extends('admin.layouts.app')

@section('title', 'Reports Management - OpenShelf Admin')
@section('page_title', 'Reports Management')

@push('styles')
<style>
    .rp { max-width: 1200px; margin: 0 auto; }
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
    .stat-card { background: var(--surface); padding: 1.75rem; border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); text-align: center; }
    .stat-card .sv { font-size: 2.25rem; font-weight: 850; letter-spacing: -1px; }
    .stat-card .sl { color: var(--text-muted); font-size: 0.85rem; font-weight: 600; margin-top: 0.5rem; }
    .filters-bar { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
    .filter-select {
        padding: 0.75rem 1.25rem; border: 1.5px solid var(--border); border-radius: 12px;
        background: var(--surface); color: var(--text-main); font: inherit; font-weight: 600; font-size: 0.9rem; cursor: pointer;
    }
    .rc { background: var(--surface); border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); margin-bottom: 1.25rem; overflow: hidden; }
    .rc-h { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 1rem; }
    .rc-h h3 { font-size: 1.1rem; font-weight: 750; margin: 0 0 0.4rem; }
    .rm { display: flex; gap: 1.5rem; color: var(--text-muted); font-size: 0.85rem; font-weight: 500; flex-wrap: wrap; }
    .sb { display: inline-block; padding: 0.35rem 1rem; border-radius: 2rem; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .tb { display: inline-block; padding: 0.3rem 0.85rem; border-radius: 8px; font-size: 0.78rem; font-weight: 600; background: rgba(76,159,138,0.1); color: var(--secondary); }
    .rc-b { padding: 1.5rem 2rem; }
    .rc-msg { color: var(--text-main); line-height: 1.7; margin-bottom: 1rem; white-space: pre-wrap; }
    .rc-a { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; padding: 1.25rem 2rem; border-top: 1px solid var(--border); background: rgba(0,0,0,0.01); }
    .af { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; flex: 1; }
    .af select, .af textarea {
        padding: 0.6rem 1rem; border: 1.5px solid var(--border); border-radius: 10px;
        background: var(--surface); color: var(--text-main); font: inherit; font-size: 0.85rem;
    }
    .af textarea { flex: 1; min-width: 200px; resize: none; height: 38px; }
    .ba { padding: 0.6rem 1.25rem; border: none; border-radius: 10px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; }
    .bu { background: var(--primary); color: #fff; }
    .bd { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
    .an-d { background: rgba(76,159,138,0.05); border-left: 3px solid var(--secondary); padding: 1rem 1.25rem; border-radius: 0 10px 10px 0; margin-top: 1rem; font-size: 0.9rem; }
    .empty { text-align: center; padding: 5rem 2rem; color: var(--text-muted); }
    .empty i { font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.3; }
</style>
@endpush

@section('content')
<div class="rp">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <h1 style="margin:0;font-weight:850;letter-spacing:-1.5px;">
            <i class="fas fa-flag" style="color:var(--secondary);margin-right:.5rem;"></i> Reports Management
        </h1>
    </div>

    @if ($message)
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ $message }}</div>
    @endif
    @if ($error)
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
    @endif

    <div class="stats-row">
        <div class="stat-card"><div class="sv" style="color:var(--primary);">{{ $stats['total'] }}</div><div class="sl">Total Reports</div></div>
        <div class="stat-card"><div class="sv" style="color:#f59e0b;">{{ $stats['pending'] }}</div><div class="sl">Pending</div></div>
        <div class="stat-card"><div class="sv" style="color:#10b981;">{{ $stats['resolved'] }}</div><div class="sl">Resolved</div></div>
    </div>

    <div class="filters-bar">
        <select class="filter-select" onchange="applyFilters()" id="statusFilter">
            <option value="all" @selected($statusFilter === 'all')>All Status</option>
            @foreach ($statusLabels as $value => $label)
                <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select class="filter-select" onchange="applyFilters()" id="typeFilter">
            <option value="all" @selected($typeFilter === 'all')>All Types</option>
            @foreach ($typeLabels as $value => $label)
                <option value="{{ $value }}" @selected($typeFilter === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @forelse ($reports as $report)
        @php
            $statusColor = $statusColors[$report->status] ?? '#6b7280';
        @endphp
        <div class="rc">
            <div class="rc-h">
                <div>
                    <h3>{{ $report->subject }}</h3>
                    <div class="rm">
                        <span><i class="fas fa-user"></i> {{ $report->name }}</span>
                        <span><i class="fas fa-envelope"></i> {{ $report->email }}</span>
                        <span><i class="fas fa-clock"></i> {{ $report->created_at?->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
                <div style="display:flex;gap:.75rem;align-items:center;">
                    <span class="tb">{{ $typeLabels[$report->type] ?? $report->type }}</span>
                    <span class="sb" style="background:{{ $statusColor }}20;color:{{ $statusColor }};">{{ $statusLabels[$report->status] ?? $report->status }}</span>
                </div>
            </div>
            <div class="rc-b">
                <div class="rc-msg">{{ $report->message }}</div>
                @if (!empty($report->admin_notes))
                    <div class="an-d">
                        <strong>Admin Notes</strong>
                        <p style="margin:.5rem 0 0;">{{ $report->admin_notes }}</p>
                    </div>
                @endif
                @if ($report->resolved_by)
                    <p style="color:var(--text-muted);font-size:.85rem;margin-top:1rem;">
                        <i class="fas fa-check-double"></i> Resolved by <strong>{{ $report->resolved_by }}</strong>
                        on {{ $report->resolved_at?->format('d M Y') }}
                    </p>
                @endif
            </div>
            <div class="rc-a">
                <form method="POST" action="{{ route('admin.reports-management.index') }}" class="af">
                    @csrf
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="report_id" value="{{ $report->id }}">
                    <select name="status">
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($report->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <textarea name="admin_notes" placeholder="Admin notes...">{{ $report->admin_notes }}</textarea>
                    <button type="submit" class="ba bu"><i class="fas fa-save"></i> Update</button>
                </form>
                <form method="POST" action="{{ route('admin.reports-management.index') }}" onsubmit="return confirm('Delete this report?')">
                    @csrf
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="report_id" value="{{ $report->id }}">
                    <button type="submit" class="ba bd"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
    @empty
        <div class="empty">
            <i class="fas fa-inbox"></i>
            <h3>No reports found</h3>
            <p>No reports matching your filter.</p>
        </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
    function applyFilters() {
        const status = document.getElementById('statusFilter').value;
        const type = document.getElementById('typeFilter').value;
        const params = new URLSearchParams();
        if (status !== 'all') params.set('status', status);
        if (type !== 'all') params.set('type', type);
        const query = params.toString();
        window.location.href = @json(route('admin.reports-management.index')) + (query ? '?' + query : '');
    }
</script>
@endpush
