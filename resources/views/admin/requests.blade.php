@extends('admin.layouts.app')

@section('title', 'Request Management - OpenShelf Admin')
@section('page_title', 'Request Management')

@push('styles')
<style>
    .page-header, .filters-bar, .filter-group, .action-buttons, .pagination, .bulk-bar { display:flex; }
    .page-header, .filters-bar, .bulk-bar { justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
    .page-header { margin-bottom:2.5rem; }
    .header-title h1 { margin:0 0 0.5rem; font-size:2.25rem; letter-spacing:-1.5px; }
    .header-title p { margin:0; color:var(--text-muted); font-weight:500; }
    .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1.25rem; margin-bottom:3rem; }
    .stat-card, .filters-bar, .table-container {
        background:var(--surface); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow-sm);
    }
    .stat-card { padding:1.5rem; text-align:center; }
    .stat-value { font-size:2.25rem; font-weight:850; margin-bottom:0.25rem; }
    .stat-label { color:var(--text-muted); font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:1.2px; }
    .filters-bar { padding:1.5rem; margin-bottom:2rem; }
    .filter-group { gap:0.75rem; flex-wrap:wrap; }
    .filter-select, .date-input {
        padding:0.8rem 1.25rem; border-radius:12px; background:var(--bg-body); border:1px solid var(--border);
        font-size:0.9rem; font-weight:700; color:var(--text-muted); cursor:pointer;
    }
    .search-box { position:relative; flex-grow:1; max-width:400px; }
    .search-box i { position:absolute; left:1.25rem; top:50%; transform:translateY(-50%); color:var(--text-muted); }
    .search-box input {
        width:100%; padding:0.85rem 1rem 0.85rem 3rem; border-radius:14px; border:1px solid var(--border);
        background:var(--surface); color:var(--text-main); font:inherit;
    }
    .table-container { overflow-x:auto; margin-bottom:2rem; border-radius:24px; }
    .requests-table { width:100%; border-collapse:collapse; min-width:1000px; }
    .requests-table th, .requests-table td { padding:1.5rem; border-bottom:1px solid var(--border); text-align:left; vertical-align:middle; }
    .requests-table th { background:var(--bg-body); color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; letter-spacing:1.2px; font-weight:800; }
    .status-pending { background:rgba(245,158,11,0.15); color:#f59e0b; }
    .status-approved, .status-borrowed { background:rgba(76,159,138,0.15); color:#4C9F8A; }
    .status-rejected { background:rgba(239,68,68,0.15); color:#ef4444; }
    .status-returned { background:rgba(44,62,80,0.1); color:var(--primary); }
    .status-closed { background:var(--bg-body); color:var(--text-muted); }
    .overdue-badge {
        background:#ef4444; color:white; font-size:0.7rem; font-weight:800; padding:0.3rem 0.75rem;
        border-radius:8px; margin-left:0.5rem; display:inline-block;
    }
    .action-buttons { gap:0.4rem; flex-wrap:wrap; }
    .action-btn {
        padding:0.6rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:0.4rem;
        border:none; cursor:pointer; font-size:0.8rem; font-weight:750; color:white;
    }
    .action-btn.approve { background:#4C9F8A; }
    .action-btn.reject { background:#f59e0b; }
    .action-btn.close { background:#ef4444; }
    .action-btn.extend { background:var(--primary); }
    .action-btn.view { background:var(--text-muted); }
    .bulk-bar {
        background:#1e293b; color:white; border-radius:16px; padding:1rem 1.5rem; margin-bottom:1.5rem;
    }
    .bulk-bar.hidden { display:none; }
    .pagination { justify-content:center; gap:0.75rem; margin-top:3rem; }
    .page-link {
        padding:0.75rem 1.1rem; border:1px solid var(--border); border-radius:12px; text-decoration:none;
        color:var(--text-muted); font-size:0.9rem; font-weight:600; background:var(--surface);
    }
    .page-link.active { background:var(--primary); border-color:var(--primary); color:white; }
    .modal {
        display:none; position:fixed; inset:0; background:rgba(15,23,42,0.4); backdrop-filter:blur(8px);
        align-items:center; justify-content:center; z-index:2000; padding:1.5rem;
    }
    .modal.active { display:flex; }
    .modal-content {
        background:var(--surface); border-radius:24px; max-width:500px; width:100%;
        box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); overflow:hidden;
    }
    .modal-header, .modal-body, .modal-footer { padding:1.5rem 2rem; }
    .modal-header { border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
    .modal-footer { background:var(--bg-body); display:flex; gap:1rem; justify-content:flex-end; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <div class="header-title">
            <h1>Request Management</h1>
            <p>Monitor and process borrow requests across the platform</p>
        </div>
        <div>
            <a href="{{ route('admin.requests.index', array_merge(request()->query(), ['export' => 1])) }}" class="btn-admin btn-admin-primary">
                <i class="fas fa-download"></i> Export Requests
            </a>
        </div>
    </div>

    @if ($message)
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ $message }}</div>
    @endif
    @if ($error)
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
    @endif

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-value" style="color:var(--primary);">{{ $stats['total'] }}</div><div class="stat-label">Total Requests</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#f59e0b;">{{ $stats['pending'] }}</div><div class="stat-label">Pending Review</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#10b981;">{{ $stats['approved'] }}</div><div class="stat-label">Approved</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#ef4444;">{{ $stats['rejected'] }}</div><div class="stat-label">Rejected</div></div>
        <div class="stat-card"><div class="stat-value" style="color:var(--primary);">{{ $stats['returned'] }}</div><div class="stat-label">Returned</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#dc2626;">{{ $stats['overdue'] }}</div><div class="stat-label">Overdue</div></div>
    </div>

    <div class="filters-bar">
        <div class="filter-group">
            <select class="filter-select" id="statusFilter" onchange="applyFilter()">
                @foreach (['all' => 'All Status', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'returned' => 'Returned', 'overdue' => 'Overdue'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" class="date-input" id="fromDate" value="{{ $fromDate }}" onchange="applyFilter()">
            <input type="date" class="date-input" id="toDate" value="{{ $toDate }}" onchange="applyFilter()">
        </div>
        <form method="GET" class="search-box" id="searchForm">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search by book, borrower, owner..." value="{{ $search }}">
            <input type="hidden" name="status" id="hiddenStatus" value="{{ $status }}">
            <input type="hidden" name="from" id="hiddenFrom" value="{{ $fromDate }}">
            <input type="hidden" name="to" id="hiddenTo" value="{{ $toDate }}">
        </form>
    </div>

    <div id="bulkBar" class="bulk-bar hidden">
        <span id="selectedCount">0 selected</span>
        <div style="display:flex;gap:0.5rem;">
            <button class="btn-admin btn-admin-primary" type="button" onclick="bulkApprove()">Approve Selected</button>
            <button class="btn-admin" style="background:#f59e0b;color:white;" type="button" onclick="showBulkRejectModal()">Reject Selected</button>
        </div>
    </div>

    <div class="table-container">
        <table class="requests-table">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="selectAll" onclick="toggleAll()"></th>
                    <th>Book</th><th>Borrower</th><th>Owner</th><th>Request Date</th><th>Due Date</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $requestItem)
                    <tr>
                        <td><input type="checkbox" class="request-checkbox" value="{{ $requestItem->id }}" onchange="updateSelectedCount()"></td>
                        <td>
                            <div style="font-weight:500;">{{ $requestItem->book_title }}</div>
                            <div style="font-size:0.7rem;color:#64748b;">by {{ $requestItem->book_author ?? 'Unknown' }}</div>
                        </td>
                        <td>
                            <div>{{ $requestItem->borrower_name }}</div>
                            <div style="font-size:0.7rem;color:#64748b;">ID: {{ $requestItem->borrower_id }}</div>
                        </td>
                        <td>
                            <div>{{ $requestItem->owner_name }}</div>
                            <div style="font-size:0.7rem;color:#64748b;">ID: {{ $requestItem->owner_id }}</div>
                        </td>
                        <td style="font-size:0.85rem;">
                            {{ $requestItem->request_date?->format('M j, Y') }}
                            <div style="font-size:0.7rem;color:#64748b;">{{ $requestItem->duration_days ?? 14 }} days</div>
                        </td>
                        <td style="font-size:0.85rem;">
                            @if ($requestItem->expected_return_date)
                                <span>{{ $requestItem->expected_return_date->format('M j, Y') }}</span>
                                @if (!empty($requestItem->overdue_days))
                                    <div class="overdue-badge">{{ $requestItem->overdue_days }} days overdue</div>
                                @elseif (!empty($requestItem->days_until_due))
                                    <div style="font-size:0.65rem;color:#10b981;">{{ $requestItem->days_until_due }} days left</div>
                                @endif
                            @else
                                <span class="text-muted">Not set</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-badge status-{{ $requestItem->status }}">{{ ucfirst($requestItem->status) }}</span>
                            @if (!empty($requestItem->overdue))<span class="overdue-badge">OVERDUE</span>@endif
                        </td>
                        <td>
                            <div class="action-buttons">
                                @if ($requestItem->status === 'pending')
                                    <button class="action-btn approve" type="button" onclick="approveRequest('{{ $requestItem->id }}')"><i class="fas fa-check"></i> Approve</button>
                                    <button class="action-btn reject" type="button" onclick="showRejectModal('{{ $requestItem->id }}')"><i class="fas fa-times"></i> Reject</button>
                                @endif
                                @if (in_array($requestItem->status, ['approved', 'borrowed']))
                                    <button class="action-btn extend" type="button" onclick="showExtendModal('{{ $requestItem->id }}')"><i class="fas fa-calendar-plus"></i> Extend</button>
                                    <button class="action-btn close" type="button" onclick="showCloseModal('{{ $requestItem->id }}')"><i class="fas fa-lock"></i> Close</button>
                                @endif
                                <button class="action-btn view" type="button" onclick="viewRequest('{{ $requestItem->id }}')"><i class="fas fa-eye"></i> View</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:3rem;">
                            <i class="fas fa-exchange-alt" style="font-size:3rem;color:#cbd5e1;"></i>
                            <p style="margin-top:1rem;">No requests found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($requests->hasPages())
        <div class="pagination">
            @if ($requests->onFirstPage())
                <span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $requests->previousPageUrl() }}" class="page-link"><i class="fas fa-chevron-left"></i></a>
            @endif
            @foreach ($requests->getUrlRange(max(1, $requests->currentPage() - 2), min($requests->lastPage(), $requests->currentPage() + 2)) as $page => $url)
                <a href="{{ $url }}" class="page-link {{ $page === $requests->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach
            @if ($requests->hasMorePages())
                <a href="{{ $requests->nextPageUrl() }}" class="page-link"><i class="fas fa-chevron-right"></i></a>
            @endif
        </div>
    @endif

    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle" style="color:#f59e0b;"></i> Reject Request</h3>
                <button type="button" onclick="closeModal('rejectModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.requests.index') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" id="rejectRequestId">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Reason for Rejection</label>
                    <textarea name="rejection_reason" class="form-control-admin" rows="4" required placeholder="Please provide a reason..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-admin" style="background:#f59e0b;color:white;">Reject Request</button>
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('rejectModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="closeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-lock" style="color:#ef4444;"></i> Force Close Request</h3>
                <button type="button" onclick="closeModal('closeModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.requests.index') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="action" value="close">
                    <input type="hidden" name="request_id" id="closeRequestId">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Closing Notes</label>
                    <textarea name="close_notes" class="form-control-admin" rows="4" placeholder="Add notes about this closure..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-admin" style="background:#ef4444;color:white;">Force Close</button>
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('closeModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="extendModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-plus" style="color:var(--primary);"></i> Extend Return Date</h3>
                <button type="button" onclick="closeModal('extendModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.requests.index') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="action" value="extend">
                    <input type="hidden" name="request_id" id="extendRequestId">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Additional Days</label>
                    <input type="number" name="extend_days" class="form-control-admin" min="1" max="90" value="7" required>
                    <label style="display:block;margin:1rem 0 0.5rem;font-weight:500;">Reason (Optional)</label>
                    <textarea name="extend_reason" class="form-control-admin" rows="3" placeholder="Why is the extension needed?"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-admin" style="background:var(--primary);color:white;">Extend Return Date</button>
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('extendModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="bulkRejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle" style="color:#f59e0b;"></i> Bulk Reject Requests</h3>
                <button type="button" onclick="closeModal('bulkRejectModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.requests.index') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="action" value="bulk_reject">
                    <div id="bulkRequestIds"></div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Rejection Reason</label>
                    <textarea name="bulk_rejection_reason" class="form-control-admin" rows="4" required placeholder="Please provide a reason..."></textarea>
                    <p style="margin-top:1rem;color:#64748b;">This will reject <span id="bulkCount"></span> selected request(s).</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-admin" style="background:#f59e0b;color:white;">Reject Selected</button>
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('bulkRejectModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function applyFilter() {
        const status = document.getElementById('statusFilter').value;
        const from = document.getElementById('fromDate').value;
        const to = document.getElementById('toDate').value;
        const search = document.querySelector('input[name="search"]').value;
        window.location.href = `?status=${status}&search=${encodeURIComponent(search)}&from=${from}&to=${to}`;
    }

    function approveRequest(requestId) {
        if (!confirm('Approve this request?')) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = @json(route('admin.requests.index'));
        form.innerHTML = `@csrf<input type="hidden" name="action" value="approve"><input type="hidden" name="request_id" value="${requestId}">`;
        document.body.appendChild(form);
        form.submit();
    }

    function showRejectModal(requestId) {
        document.getElementById('rejectRequestId').value = requestId;
        document.getElementById('rejectModal').classList.add('active');
    }

    function showCloseModal(requestId) {
        document.getElementById('closeRequestId').value = requestId;
        document.getElementById('closeModal').classList.add('active');
    }

    function showExtendModal(requestId) {
        document.getElementById('extendRequestId').value = requestId;
        document.getElementById('extendModal').classList.add('active');
    }

    function viewRequest(requestId) {
        window.open(@json(route('requests.index')) + `?id=${requestId}`, '_blank');
    }

    let selectedRequests = new Set();

    function toggleAll() {
        const checkboxes = document.querySelectorAll('.request-checkbox');
        const selectAll = document.getElementById('selectAll');
        checkboxes.forEach(cb => {
            cb.checked = selectAll.checked;
            if (selectAll.checked) selectedRequests.add(cb.value);
            else selectedRequests.delete(cb.value);
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        document.querySelectorAll('.request-checkbox').forEach(cb => {
            if (cb.checked) selectedRequests.add(cb.value);
            else selectedRequests.delete(cb.value);
        });
        const count = selectedRequests.size;
        const bulkBar = document.getElementById('bulkBar');
        if (count > 0) {
            document.getElementById('selectedCount').textContent = count + ' selected';
            bulkBar.classList.remove('hidden');
        } else {
            bulkBar.classList.add('hidden');
        }
    }

    function bulkApprove() {
        if (selectedRequests.size === 0) return;
        if (!confirm(`Approve ${selectedRequests.size} selected request(s)?`)) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = @json(route('admin.requests.index'));
        let html = `@csrf<input type="hidden" name="action" value="bulk_approve">`;
        selectedRequests.forEach(id => html += `<input type="hidden" name="request_ids[]" value="${id}">`);
        form.innerHTML = html;
        document.body.appendChild(form);
        form.submit();
    }

    function showBulkRejectModal() {
        if (selectedRequests.size === 0) return;
        document.getElementById('bulkCount').textContent = selectedRequests.size;
        let html = '';
        selectedRequests.forEach(id => html += `<input type="hidden" name="request_ids[]" value="${id}">`);
        document.getElementById('bulkRequestIds').innerHTML = html;
        document.getElementById('bulkRejectModal').classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) e.target.classList.remove('active');
    });

    let searchTimeout;
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => document.getElementById('searchForm').submit(), 500);
        });
    }
</script>
@endpush
