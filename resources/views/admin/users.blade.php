@extends('admin.layouts.app')

@section('title', 'User Management - OpenShelf Admin')
@section('page_title', 'User Management')

@push('styles')
<style>
    .page-header, .filters-bar, .filter-tabs, .action-buttons, .pagination-wrap { display:flex; }
    .page-header, .filters-bar { justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
    .page-header { margin-bottom:2rem; }
    .header-title h1 { margin:0 0 0.4rem; font-size:2.25rem; letter-spacing:-1px; }
    .header-title p { margin:0; color:var(--text-muted); }
    .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem; margin-bottom:2rem; }
    .stat-card, .filters-bar, .table-container {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
    }
    .stat-card { padding:1.5rem; text-align:center; }
    .stat-value { font-size:2.5rem; font-weight:850; }
    .stat-label { color:var(--text-muted); font-size:0.8rem; font-weight:800; text-transform:uppercase; }
    .filters-bar { padding:1.25rem; margin-bottom:1.5rem; }
    .filter-tabs { gap:0.75rem; flex-wrap:wrap; }
    .filter-tab {
        padding:0.7rem 1.2rem; border-radius:12px; text-decoration:none; background:var(--bg-body); border:1px solid var(--border); color:var(--text-muted); font-weight:700;
    }
    .filter-tab.active { background:#2C3E50; color:white; border-color:#2C3E50; }
    .search-box { position:relative; flex-grow:1; max-width:400px; }
    .search-box i { position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); }
    .search-box input {
        width:100%; padding:0.85rem 1rem 0.85rem 2.6rem; border-radius:12px; border:1px solid var(--border); background:var(--surface); color:var(--text-main); font:inherit;
    }
    .table-container { overflow-x:auto; }
    .users-table { width:100%; border-collapse:collapse; min-width:950px; }
    .users-table th, .users-table td { padding:1.25rem; border-bottom:1px solid var(--border); text-align:left; }
    .users-table th { font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); letter-spacing:1px; }
    .user-info { display:flex; align-items:center; gap:1rem; }
    .user-avatar {
        width:46px; height:46px; border-radius:14px; overflow:hidden; flex-shrink:0;
        background:linear-gradient(135deg, #2C3E50, #4C9F8A);
        display:flex; align-items:center; justify-content:center;
    }
    .user-avatar img {
        width:100%; height:100%; object-fit:cover; display:block;
    }
    .user-name { font-weight:700; }
    .user-email { color:var(--text-muted); font-size:0.85rem; }
    .action-buttons { gap:0.5rem; }
    .action-btn {
        width:40px; height:40px; border:none; border-radius:12px; color:white; display:inline-flex; align-items:center; justify-content:center; cursor:pointer;
    }
    .action-btn.approve { background:#4C9F8A; }
    .action-btn.reject { background:#f59e0b; }
    .action-btn.delete { background:#ef4444; }
    .action-btn.view { background:#2C3E50; text-decoration:none; }
    .status-active { background: rgba(76, 159, 138, 0.15); color: #4C9F8A; }
    .status-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .status-rejected { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
    .pagination-wrap { justify-content:center; margin-top:1.5rem; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <div class="header-title">
            <h1>User Management</h1>
            <p>Manage and moderate user accounts with precision</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-value" style="color:#2C3E50;">{{ $totalUsersCount }}</div><div class="stat-label">Total Users</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#10b981;">{{ $approvedUsersCount }}</div><div class="stat-label">Active Users</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#f59e0b;">{{ $pendingUsersCount }}</div><div class="stat-label">Pending Review</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#ef4444;">{{ $rejectedUsersCount }}</div><div class="stat-label">Rejected</div></div>
    </div>

    <div class="filters-bar">
        <div class="filter-tabs">
            <a href="{{ route('admin.users.index', ['status' => 'all', 'search' => $search]) }}" class="filter-tab {{ $status === 'all' ? 'active' : '' }}">All Users</a>
            <a href="{{ route('admin.users.index', ['status' => 'pending', 'search' => $search]) }}" class="filter-tab {{ $status === 'pending' ? 'active' : '' }}">Pending ({{ $pendingUsersCount }})</a>
            <a href="{{ route('admin.users.index', ['status' => 'approved', 'search' => $search]) }}" class="filter-tab {{ $status === 'approved' ? 'active' : '' }}">Approved ({{ $approvedUsersCount }})</a>
            <a href="{{ route('admin.users.index', ['status' => 'rejected', 'search' => $search]) }}" class="filter-tab {{ $status === 'rejected' ? 'active' : '' }}">Rejected ({{ $rejectedUsersCount }})</a>
        </div>
        <form method="GET" class="search-box" id="searchForm">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search by name, email or phone..." value="{{ $search }}">
            <input type="hidden" name="status" value="{{ $status }}">
        </form>
    </div>

    <div id="bulkBar" class="bulk-bar hidden">
        <span id="selectedCount">0 selected</span>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <button class="btn-admin btn-admin-primary" type="button" onclick="bulkApprove()">Approve Selected</button>
            <button class="btn-admin" type="button" style="background:#f59e0b;color:white;" onclick="showBulkRejectModal()">Reject Selected</button>
            <button class="btn-admin" type="button" style="background:#ef4444;color:white;" onclick="bulkDelete()">Delete Selected</button>
        </div>
    </div>

    <div class="table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="selectAll"></th>
                    <th>User</th>
                    <th>Contact</th>
                    <th>Department</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $isPending = ! $user->verified && $user->status !== 'rejected';
                        $isApproved = $user->verified && $user->status === 'active';
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="user-checkbox" value="{{ $user->id }}"></td>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <img src="{{ $user->profile_image_url }}" alt="{{ $user->name ?? 'User' }}" loading="lazy">
                                </div>
                                <div>
                                    <div class="user-name">{{ $user->name ?? 'Unknown' }}</div>
                                    <div class="user-email">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:0.85rem;">
                            <div><i class="fas fa-phone" style="width:20px;color:#4C9F8A;"></i> {{ $user->phone ?: 'N/A' }}</div>
                            <div style="margin-top:0.25rem;"><i class="fas fa-door-open" style="width:20px;color:#4C9F8A;"></i> {{ $user->room_number ?: 'N/A' }}</div>
                        </td>
                        <td style="font-size:0.85rem;">
                            {{ $user->department ?: 'N/A' }}
                            <div style="font-size:0.72rem;color:var(--text-muted);">Session: {{ $user->session ?: 'N/A' }}</div>
                        </td>
                        <td>{{ optional($user->created_at)->format('M j, Y') }}</td>
                        <td>
                            <span class="status-badge {{ $isApproved ? 'status-active' : ($isPending ? 'status-pending' : 'status-rejected') }}">
                                {{ $isApproved ? 'Active' : ($isPending ? 'Pending' : 'Rejected') }}
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                @if ($isPending)
                                    <button class="action-btn approve" type="button" onclick="approveUser('{{ $user->id }}')"><i class="fas fa-check"></i></button>
                                    <button class="action-btn reject" type="button" onclick="showRejectModal('{{ $user->id }}')"><i class="fas fa-times"></i></button>
                                @endif
                                <button class="action-btn delete" type="button" onclick="deleteUser('{{ $user->id }}', @js($user->name ?? 'Unknown'))"><i class="fas fa-trash"></i></button>
                                <a class="action-btn view" href="{{ route('profile', ['id' => $user->id]) }}" target="_blank"><i class="fas fa-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">
        {{ $users->onEachSide(1)->links() }}
    </div>

    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3 style="margin:0;"><i class="fas fa-times-circle" style="color:#f59e0b;"></i> Reject User</h3></div>
            <form method="POST" action="{{ route('admin.users.index', request()->query()) }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="user_id" id="rejectUserId">
                    <label for="rejection_reason" style="display:block;margin-bottom:0.5rem;font-weight:600;">Reason for Rejection</label>
                    <textarea id="rejection_reason" name="rejection_reason" class="form-control-admin" rows="4" required placeholder="Please provide a reason..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('rejectModal')">Cancel</button>
                    <button type="submit" class="btn-admin" style="background:#f59e0b;color:white;">Reject User</button>
                </div>
            </form>
        </div>
    </div>

    <div id="bulkRejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3 style="margin:0;"><i class="fas fa-times-circle" style="color:#f59e0b;"></i> Bulk Reject Users</h3></div>
            <form method="POST" action="{{ route('admin.users.index', request()->query()) }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="action" value="bulk_reject">
                    <div id="bulkUserIds"></div>
                    <label for="bulk_rejection_reason" style="display:block;margin-bottom:0.5rem;font-weight:600;">Rejection Reason</label>
                    <textarea id="bulk_rejection_reason" name="bulk_rejection_reason" class="form-control-admin" rows="4" required placeholder="Please provide a reason..."></textarea>
                    <p style="margin-top:1rem;color:var(--text-muted);">This will reject <span id="bulkCount"></span> selected user(s).</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('bulkRejectModal')">Cancel</button>
                    <button type="submit" class="btn-admin" style="background:#f59e0b;color:white;">Reject Selected</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let selectedUsers = new Set();

    function postAction(html) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = @js(route('admin.users.index', request()->query()));
        form.innerHTML = `@csrf${html}`;
        document.body.appendChild(form);
        form.submit();
    }

    function approveUser(userId) {
        if (!confirm('Approve this user?')) return;
        postAction(`<input type="hidden" name="action" value="approve"><input type="hidden" name="user_id" value="${userId}">`);
    }

    function showRejectModal(userId) {
        document.getElementById('rejectUserId').value = userId;
        document.getElementById('rejectModal').classList.add('active');
    }

    function deleteUser(userId, userName) {
        if (!confirm(`Delete user "${userName}"? This action cannot be undone.`)) return;
        postAction(`<input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="${userId}">`);
    }

    function toggleAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.user-checkbox').forEach((checkbox) => {
            checkbox.checked = checked;
            if (checked) selectedUsers.add(checkbox.value);
            else selectedUsers.delete(checkbox.value);
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        document.querySelectorAll('.user-checkbox').forEach((checkbox) => {
            if (checkbox.checked) selectedUsers.add(checkbox.value);
            else selectedUsers.delete(checkbox.value);
        });

        document.getElementById('selectedCount').textContent = `${selectedUsers.size} selected`;
        document.getElementById('bulkBar').classList.toggle('hidden', selectedUsers.size === 0);
    }

    function bulkApprove() {
        if (selectedUsers.size === 0 || !confirm(`Approve ${selectedUsers.size} selected user(s)?`)) return;
        postAction('<input type="hidden" name="action" value="bulk_approve">' + [...selectedUsers].map((id) => `<input type="hidden" name="user_ids[]" value="${id}">`).join(''));
    }

    function showBulkRejectModal() {
        if (selectedUsers.size === 0) return;
        document.getElementById('bulkCount').textContent = selectedUsers.size;
        document.getElementById('bulkUserIds').innerHTML = [...selectedUsers].map((id) => `<input type="hidden" name="user_ids[]" value="${id}">`).join('');
        document.getElementById('bulkRejectModal').classList.add('active');
    }

    function bulkDelete() {
        if (selectedUsers.size === 0 || !confirm(`Delete ${selectedUsers.size} selected user(s)? This action cannot be undone.`)) return;
        postAction('<input type="hidden" name="action" value="bulk_delete">' + [...selectedUsers].map((id) => `<input type="hidden" name="user_ids[]" value="${id}">`).join(''));
    }

    document.getElementById('selectAll')?.addEventListener('change', toggleAll);
    document.querySelectorAll('.user-checkbox').forEach((checkbox) => checkbox.addEventListener('change', updateSelectedCount));

    let searchTimeout;
    document.querySelector('.search-box input')?.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => document.getElementById('searchForm').submit(), 400);
    });

    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) event.target.classList.remove('active');
    });
</script>
@endpush
