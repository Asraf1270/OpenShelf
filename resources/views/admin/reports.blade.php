@extends('admin.layouts.app')

@section('title', 'Reports - OpenShelf Admin')
@section('page_title', 'Reports & Analytics')

@push('styles')
<style>
    .reports-page { max-width: 1200px; margin: 0 auto; }
    .page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
    .stat-card, .chart-container, .top-books {
        background: var(--surface); border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow-sm);
    }
    .stat-card { padding: 1.75rem; text-align: center; }
    .stat-value { font-size: 2.5rem; font-weight: 850; letter-spacing: -1.5px; margin-bottom: 0.25rem; }
    .stat-label { color: var(--text-muted); font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
    .chart-container, .top-books { padding: 2rem; margin-bottom: 2rem; border-radius: 24px; }
    .book-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--border); }
    .book-item:last-child { border-bottom: none; }
    .book-title { font-weight: 700; }
    .book-count { background: var(--primary); color: white; padding: 0.35rem 0.85rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; }
    .tabs { display: flex; gap: 0.75rem; margin-bottom: 2rem; flex-wrap: wrap; }
    .tab-btn {
        padding: 0.75rem 1.5rem; background: var(--surface); border: 1px solid var(--border); border-radius: 2rem;
        text-decoration: none; color: var(--text-muted); font-weight: 700;
    }
    .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
    .export-btn {
        display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem;
        background: var(--secondary); color: white; border-radius: 2rem; text-decoration: none; font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="reports-page">
    <div class="page-head">
        <h1 style="font-size:1.75rem;font-weight:700;margin:0;">Reports & Analytics</h1>
        <a href="{{ route('admin.reports.export', ['type' => $reportType === 'overview' ? 'users' : $reportType]) }}" class="export-btn">
            <i class="fas fa-download"></i> Export Data
        </a>
    </div>

    <div class="tabs">
        @foreach (['overview' => 'Overview', 'users' => 'Users', 'books' => 'Books', 'requests' => 'Requests'] as $type => $label)
            <a href="{{ route('admin.reports.index', ['type' => $type]) }}" class="tab-btn {{ $reportType === $type ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-value" style="color:var(--primary);">{{ $summary['totalUsers'] }}</div><div class="stat-label">Total Users</div></div>
        <div class="stat-card"><div class="stat-value" style="color:var(--secondary);">{{ $summary['totalBooks'] }}</div><div class="stat-label">Total Books</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#f59e0b;">{{ $summary['totalRequests'] }}</div><div class="stat-label">Total Requests</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#ef4444;">{{ $summary['pendingUsers'] }}</div><div class="stat-label">Pending Users</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#3A7B6B;">{{ $summary['pendingRequests'] }}</div><div class="stat-label">Pending Requests</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#06b6d4;">{{ $summary['userActivity']['today'] }}</div><div class="stat-label">Active Today</div></div>
    </div>

    <div class="chart-container">
        <h3 style="margin-bottom:1rem;">User Growth (Last 6 Months)</h3>
        <canvas id="userGrowthChart"></canvas>
    </div>

    <div class="chart-container">
        <h3 style="margin-bottom:1rem;">Book Growth (Last 6 Months)</h3>
        <canvas id="bookGrowthChart"></canvas>
    </div>

    <div class="top-books">
        <h3 style="margin-bottom:1rem;">Most Borrowed Books</h3>
        @forelse ($topBooks as $book)
            <div class="book-item">
                <div class="book-title">{{ $book->title ?? $book['title'] ?? 'Untitled' }}</div>
                <div class="book-count">{{ $book->borrow_count ?? $book['borrow_count'] ?? 0 }} borrows</div>
            </div>
        @empty
            <p>No borrowing data yet.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const userGrowthData = @json(array_values($userGrowth));
    const userGrowthLabels = @json(array_keys($userGrowth));
    const bookGrowthData = @json(array_values($bookGrowth));

    new Chart(document.getElementById('userGrowthChart'), {
        type: 'line',
        data: {
            labels: userGrowthLabels,
            datasets: [{
                label: 'New Users',
                data: userGrowthData,
                borderColor: '#2C3E50',
                backgroundColor: 'rgba(44, 62, 80, 0.05)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#2C3E50'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('bookGrowthChart'), {
        type: 'bar',
        data: {
            labels: userGrowthLabels,
            datasets: [{
                label: 'New Books',
                data: bookGrowthData,
                backgroundColor: '#4C9F8A',
                borderRadius: 12,
                hoverBackgroundColor: '#3A7B6B'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
