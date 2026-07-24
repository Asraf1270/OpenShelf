@extends('admin.layouts.app')

@section('title', 'Admin Dashboard - OpenShelf')
@section('page_title', 'Dashboard')

@push('styles')
<style>
    .dashboard-stats, .charts-row, .activity-grid, .quick-actions {
        display: grid;
        gap: 1.5rem;
    }
    .dashboard-stats { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 2rem; }
    .charts-row { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); margin-bottom: 2rem; }
    .quick-actions { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem; }
    .activity-grid { grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); }
    .welcome-banner, .stat-card, .chart-card, .action-card, .activity-feed, .category-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 24px;
    }
    .welcome-banner {
        padding: 3rem;
        margin-bottom: 2rem;
        color: white;
        background: linear-gradient(135deg, #2C3E50 0%, #34495e 50%, #4C9F8A 100%);
        position: relative;
        overflow: hidden;
    }
    .welcome-title { font-size: 2.5rem; font-weight: 800; margin: 0 0 0.75rem; }
    .welcome-text { max-width: 650px; opacity: 0.88; line-height: 1.6; }
    .date-badge {
        position: absolute; top: 2rem; right: 2rem;
        background: rgba(255,255,255,0.12); padding: 0.6rem 1rem; border-radius: 14px;
        font-size: 0.85rem; font-weight: 600;
    }
    .stat-card { padding: 1.75rem; display:flex; gap:1rem; align-items:center; }
    .stat-icon {
        width: 64px; height: 64px; border-radius: 18px;
        display:flex; align-items:center; justify-content:center; font-size:1.75rem;
    }
    .stat-value { font-size: 2rem; font-weight: 800; }
    .stat-label { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; }
    .stat-change { margin-top: 0.5rem; font-size: 0.85rem; font-weight: 700; }
    .trend-up { color: #10b981; }
    .trend-down { color: #ef4444; }
    .chart-card, .activity-feed, .category-card { padding: 1.75rem; }
    .chart-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; gap:1rem; }
    .chart-title, .section-title { font-weight: 800; letter-spacing: -0.5px; margin:0; }
    .chart-container { height: 320px; }
    .action-card { padding: 1.5rem; text-decoration:none; }
    .action-icon {
        width: 56px; height: 56px; border-radius: 16px; display:flex; align-items:center; justify-content:center;
        color:white; margin-bottom:1rem; font-size:1.25rem; background: linear-gradient(135deg, var(--primary), var(--secondary));
    }
    .action-title { font-weight: 700; }
    .action-desc { color: var(--text-muted); font-size: 0.85rem; margin-top: 0.35rem; }
    .activity-list { display:flex; flex-direction:column; gap:1rem; }
    .activity-item {
        display:flex; align-items:center; gap:1rem; padding:1rem;
        background: var(--bg-body); border-radius:16px; border:1px solid transparent;
    }
    .activity-icon {
        width: 48px; height: 48px; border-radius: 12px; display:flex; align-items:center; justify-content:center;
        font-size: 1.1rem;
    }
    .activity-content { flex:1; }
    .activity-title { font-weight:700; }
    .activity-desc, .activity-time { color: var(--text-muted); font-size:0.85rem; }
    .category-tag {
        background: var(--bg-body); border-radius: 12px; padding: 0.75rem 1rem; margin-bottom:0.75rem;
        display:flex; justify-content:space-between; align-items:center; font-weight:600;
    }
    .category-count {
        background: var(--primary); color: white; padding: 0.2rem 0.55rem; border-radius: 8px; font-size: 0.75rem;
    }
    @media (max-width: 1200px) { .activity-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) {
        .welcome-banner { padding: 2rem; }
        .welcome-title { font-size: 1.75rem; }
        .date-badge { position: static; display: inline-block; margin-bottom: 1rem; }
    }
</style>
@endpush

@section('content')
    <div class="welcome-banner">
        <div class="date-badge">
            <i class="far fa-calendar-alt"></i> {{ $todayLabel }}
        </div>
        <h1 class="welcome-title">{{ $greeting }}, {{ $admin->name }}!</h1>
        <p class="welcome-text">
            Here's what's happening with OpenShelf today. You have {{ $pendingUsers }} pending user approvals and {{ $pendingRequests }} pending requests.
        </p>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(44, 62, 80, 0.1); color: #2C3E50;"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-value">{{ number_format($totalUsers) }}</div>
                <div class="stat-label">Total Users</div>
                <div class="stat-change {{ $userGrowthPercent >= 0 ? 'trend-up' : 'trend-down' }}">
                    <i class="fas fa-arrow-{{ $userGrowthPercent >= 0 ? 'up' : 'down' }}"></i> {{ abs($userGrowthPercent) }}% from last month
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(46, 139, 87, 0.1); color: #2E8B57;"><i class="fas fa-book"></i></div>
            <div>
                <div class="stat-value">{{ number_format($totalBooks) }}</div>
                <div class="stat-label">Total Books</div>
                <div class="stat-change {{ $bookGrowthPercent >= 0 ? 'trend-up' : 'trend-down' }}">
                    <i class="fas fa-arrow-{{ $bookGrowthPercent >= 0 ? 'up' : 'down' }}"></i> {{ abs($bookGrowthPercent) }}% from last month
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(76, 159, 138, 0.1); color: #4C9F8A;"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="stat-value">{{ number_format($availableBooks) }}</div>
                <div class="stat-label">Available Books</div>
                <div class="stat-change" style="color:#4C9F8A;">
                    <i class="fas fa-percent"></i> {{ $totalBooks > 0 ? round(($availableBooks / $totalBooks) * 100) : 0 }}% of total
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(198, 93, 93, 0.1); color: #C65D5D;"><i class="fas fa-hand-holding-heart"></i></div>
            <div>
                <div class="stat-value">{{ number_format($borrowedBooks) }}</div>
                <div class="stat-label">Borrowed Books</div>
                <div class="stat-change">Currently in circulation</div>
            </div>
        </div>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(217, 119, 6, 0.1); color: #D97706;"><i class="fas fa-clock"></i></div>
            <div>
                <div class="stat-value">{{ number_format($pendingUsers) }}</div>
                <div class="stat-label">Pending Approvals</div>
                <div class="stat-change"><a href="{{ route('admin.users.index', ['status' => 'pending']) }}" style="color:#D97706;text-decoration:none;">Review now <i class="fas fa-arrow-right"></i></a></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(44, 62, 80, 0.1); color: #2C3E50;"><i class="fas fa-exchange-alt"></i></div>
            <div>
                <div class="stat-value">{{ number_format($totalRequests) }}</div>
                <div class="stat-label">Total Requests</div>
                <div class="stat-change"><span class="trend-up">{{ $approvedRequests }} approved</span> <span class="trend-down"> • {{ $rejectedRequests }} rejected</span></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(217, 119, 6, 0.1); color: #D97706;"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <div class="stat-value">{{ number_format($pendingRequests) }}</div>
                <div class="stat-label">Pending Requests</div>
                <div class="stat-change">Awaiting review</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(46, 139, 87, 0.1); color: #2E8B57;"><i class="fas fa-undo-alt"></i></div>
            <div>
                <div class="stat-value">{{ number_format($returnedRequests) }}</div>
                <div class="stat-label">Completed Returns</div>
                <div class="stat-change"><i class="fas fa-check-double"></i> Successfully completed</div>
            </div>
        </div>
    </div>

    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header"><h3 class="chart-title">User Growth</h3></div>
            <div class="chart-container"><canvas id="userGrowthChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-header"><h3 class="chart-title">Book Activity</h3></div>
            <div class="chart-container"><canvas id="bookGrowthChart"></canvas></div>
        </div>
    </div>

    <div style="margin-bottom:2rem;">
        <h2 class="section-title" style="margin-bottom:1rem;">Quick Actions</h2>
        <div class="quick-actions">
            <a href="{{ route('admin.users.index', ['status' => 'pending']) }}" class="action-card">
                <div class="action-icon"><i class="fas fa-user-check"></i></div>
                <div class="action-title">Approve Users</div>
                <div class="action-desc">{{ $pendingUsers }} pending approvals</div>
            </a>
            <a href="{{ route('admin.books.index') }}" class="action-card">
                <div class="action-icon" style="background:linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-book"></i></div>
                <div class="action-title">Manage Books</div>
                <div class="action-desc">{{ $totalBooks }} books in library</div>
            </a>
        </div>
    </div>

    <div class="activity-grid">
        <div class="activity-feed">
            <div class="chart-header"><h3 class="chart-title">Recent Activity</h3></div>
            <div class="activity-list">
                @forelse ($recentActivities as $activity)
                    <div class="activity-item">
                        <div class="activity-icon" style="background: {{ $activity['color'] }}20; color: {{ $activity['color'] }};">
                            <i class="fas {{ $activity['icon'] }}"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">{{ $activity['title'] }}</div>
                            <div class="activity-desc">{{ $activity['description'] }}</div>
                        </div>
                        <div class="activity-time">{{ optional($activity['timestamp'])->diffForHumans() ?? 'Just now' }}</div>
                    </div>
                @empty
                    <p style="color:var(--text-muted);margin:0;">No activity available yet.</p>
                @endforelse
            </div>
        </div>

        <div class="category-card">
            <div class="chart-header"><h3 class="chart-title">Top Categories</h3></div>
            @forelse ($topCategories as $category => $count)
                <div class="category-tag">
                    <span>{{ $category }}</span>
                    <span class="category-count">{{ $count }}</span>
                </div>
            @empty
                <p style="color:var(--text-muted);margin:0;">No categories data available yet.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const growthLabels = @json(array_keys($userGrowth)).map((dateValue) => {
        const date = new Date(dateValue);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });

    Chart.defaults.font.family = "'Outfit', sans-serif";
    Chart.defaults.color = '#64748b';

    new Chart(document.getElementById('userGrowthChart'), {
        type: 'line',
        data: {
            labels: growthLabels,
            datasets: [{
                label: 'New Users',
                data: @json(array_values($userGrowth)),
                borderColor: '#4C9F8A',
                backgroundColor: 'rgba(76, 159, 138, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('bookGrowthChart'), {
        type: 'bar',
        data: {
            labels: growthLabels,
            datasets: [{
                label: 'New Books',
                data: @json(array_values($bookGrowth)),
                backgroundColor: '#10b981',
                borderRadius: 12,
                barPercentage: 0.5
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
</script>
@endpush
