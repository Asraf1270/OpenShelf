@extends('admin.layouts.app')

@section('title', 'Support Requests - OpenShelf Admin')
@section('page_title', 'Support Requests')

@push('styles')
<style>
    .su-wrap { max-width: 1200px; margin: 0 auto; }
    .su-card { background: var(--surface); border: 1px solid var(--border); border-radius: 24px; padding: 28px; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(15,23,42,0.04); }
    .su-table-wrap { overflow-x: auto; }
    .su-table { width: 100%; border-collapse: collapse; }
    .su-table th, .su-table td { padding: 14px 12px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: middle; }
    .su-table th { background: rgba(148,163,184,0.08); font-weight: 700; }
    .su-badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 999px; font-size: 0.85rem; font-weight: 700; }
    .su-badge-pending { background: rgba(245, 158, 11, 0.16); color: #d97706; }
    .su-badge-approved { background: rgba(16, 185, 129, 0.16); color: #10b981; }
    .su-filter-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 18px; }
    .su-filter-row input, .su-filter-row select { padding: 12px 14px; border: 1px solid var(--border); border-radius: 14px; width: 220px; background: var(--surface); color: var(--text-main); font: inherit; }
    .su-muted { color: var(--text-muted); }
    .su-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .su-header h1 { font-size: 2rem; margin: 0; }
</style>
@endpush

@section('content')
<div class="su-wrap">
    <div class="su-card">
        <div class="su-header">
            <div>
                <h1>Support Requests</h1>
                <p class="su-muted" style="margin:0.5rem 0 0;">Review submitted support requests and approve them to create transaction records.</p>
            </div>
            <a href="{{ route('admin.transactions.index') }}" class="btn-admin btn-outline">
                <i class="fas fa-dollar-sign"></i> View Transactions
            </a>
        </div>
    </div>

    <div class="su-card">
        <div class="su-filter-row">
            <form method="get" style="display:flex; gap:12px; flex-wrap:wrap;">
                <select name="status">
                    <option value="all" @selected($status === 'all')>All Status</option>
                    <option value="pending" @selected($status === 'pending')>Pending</option>
                    <option value="approved" @selected($status === 'approved')>Approved</option>
                </select>
                <input type="search" name="search" placeholder="Search user, provider, txn or invoice" value="{{ $search }}">
                <button type="submit" class="btn-admin btn-admin-primary">Filter</button>
            </form>
        </div>

        <div class="su-table-wrap">
            <table class="su-table">
                <thead>
                    <tr>
                        <th>Submitted</th>
                        <th>User</th>
                        <th>Provider</th>
                        <th>Amount</th>
                        <th>Transaction ID</th>
                        <th>Status</th>
                        <th>Invoice</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supportRequests as $request)
                        <tr>
                            <td>{{ optional($request->submitted_at ?? $request->created_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                <strong>{{ $request->user_name ?: 'Unknown' }}</strong><br>
                                <span class="su-muted">{{ $request->user_email ?: $request->user_phone ?: 'No contact' }}</span>
                            </td>
                            <td>{{ ucfirst($request->provider) }}</td>
                            <td>{{ number_format((float) $request->amount, 2) }}</td>
                            <td>{{ $request->transaction_id }}</td>
                            <td>
                                <span class="su-badge su-badge-{{ $request->status === 'approved' ? 'approved' : 'pending' }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td>{{ $request->invoice_number ?? '-' }}</td>
                            <td>
                                @if ($request->status === 'pending')
                                    <form method="post" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="support_id" value="{{ $request->id }}">
                                        <button type="submit" class="btn-admin btn-admin-primary">Approve</button>
                                    </form>
                                @else
                                    <span class="su-muted">Approved</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center; padding: 24px;">No support requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
