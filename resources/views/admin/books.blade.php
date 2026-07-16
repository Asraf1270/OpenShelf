@extends('admin.layouts.app')

@section('title', 'Book Management - OpenShelf Admin')
@section('page_title', 'Book Management')

@push('styles')
<style>
    .page-header, .filters-bar, .filter-group, .book-info, .action-buttons, .pagination-wrap { display:flex; }
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
    .filter-group { gap:0.75rem; flex-wrap:wrap; }
    .filter-select, .search-box input {
        border:1px solid var(--border); border-radius:12px; padding:0.85rem 1rem; background:var(--surface); color:var(--text-main); font:inherit;
    }
    .search-box { position:relative; flex-grow:1; max-width:400px; }
    .search-box i { position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); }
    .search-box input { width:100%; padding-left:2.6rem; }
    .table-container { overflow-x:auto; }
    .books-table { width:100%; border-collapse:collapse; min-width:900px; }
    .books-table th, .books-table td { padding:1.25rem; border-bottom:1px solid var(--border); text-align:left; }
    .books-table th { font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); letter-spacing:1px; }
    .book-info { align-items:center; gap:1rem; }
    .book-cover-small { width:52px; height:72px; border-radius:10px; object-fit:cover; }
    .book-title { font-weight:700; }
    .book-author, .book-id { color:var(--text-muted); font-size:0.85rem; }
    .book-id { font-size:0.72rem; }
    .category-tag {
        display:inline-flex; padding:0.4rem 0.85rem; border-radius:10px; background:var(--bg-body); border:1px solid var(--border); font-size:0.8rem; font-weight:700;
    }
    .status-available { background: rgba(76, 159, 138, 0.15); color: #4C9F8A; }
    .status-borrowed { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .status-reserved { background: rgba(44, 62, 80, 0.1); color: #2C3E50; }
    .status-unavailable { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
    .action-buttons { gap:0.5rem; }
    .action-btn {
        width:40px; height:40px; border:none; border-radius:12px; color:white; display:inline-flex; align-items:center; justify-content:center; cursor:pointer;
    }
    .action-btn.edit { background:#2C3E50; }
    .action-btn.status { background:#f59e0b; }
    .action-btn.view { background:#4C9F8A; text-decoration:none; }
    .action-btn.delete { background:#ef4444; }
    .pagination-wrap { justify-content:center; margin-top:1.5rem; }
    .pagination-wrap nav { display:flex; gap:0.5rem; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <div class="header-title">
            <h1>Book Management</h1>
            <p>Manage and moderate all books in the library with ease</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-value" style="color:#2C3E50;">{{ $totalBooks }}</div><div class="stat-label">Total Books</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#10b981;">{{ $availableBooks }}</div><div class="stat-label">Available</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#f59e0b;">{{ $borrowedBooks }}</div><div class="stat-label">Borrowed</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#ef4444;">{{ $unavailableBooks }}</div><div class="stat-label">Unavailable</div></div>
    </div>

    <div class="filters-bar">
        <div class="filter-group">
            <select class="filter-select" id="statusFilter">
                <option value="all" @selected($status === 'all')>All Status</option>
                <option value="available" @selected($status === 'available')>Available</option>
                <option value="borrowed" @selected($status === 'borrowed')>Borrowed</option>
                <option value="reserved" @selected($status === 'reserved')>Reserved</option>
                <option value="unavailable" @selected($status === 'unavailable')>Unavailable</option>
            </select>
            <select class="filter-select" id="categoryFilter">
                <option value="all" @selected($category === 'all')>All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <form method="GET" class="search-box" id="searchForm">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search by title or author..." value="{{ $search }}">
            <input type="hidden" name="status" id="hiddenStatus" value="{{ $status }}">
            <input type="hidden" name="category" id="hiddenCategory" value="{{ $category }}">
        </form>
    </div>

    <div id="bulkBar" class="bulk-bar hidden">
        <span id="selectedCount">0 selected</span>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <select id="bulkStatusSelect" class="filter-select">
                <option value="">Change Status</option>
                <option value="available">Available</option>
                <option value="borrowed">Borrowed</option>
                <option value="reserved">Reserved</option>
                <option value="unavailable">Unavailable</option>
            </select>
            <button class="btn-admin btn-admin-primary" type="button" onclick="bulkUpdateStatus()">Apply</button>
            <button class="btn-admin" type="button" style="background:#ef4444;color:white;" onclick="bulkDelete()">Delete Selected</button>
        </div>
    </div>

    <div class="table-container">
        <table class="books-table">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="selectAll"></th>
                    <th>Book</th>
                    <th>Owner</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($books as $book)
                    @php
                        $bookStatus = $book->status ?? 'available';
                        $coverImage = $book->cover_url;
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="book-checkbox" value="{{ $book->id }}"></td>
                        <td>
                            <div class="book-info">
                                <img src="{{ $coverImage }}" class="book-cover-small" alt="{{ $book->title }}">
                                <div>
                                    <div class="book-title">{{ $book->title }}</div>
                                    <div class="book-author">{{ $book->author }}</div>
                                    <div class="book-id">ID: {{ $book->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $book->owner?->name ?? 'Unknown' }}</td>
                        <td><span class="category-tag">{{ $book->category ?: 'Uncategorized' }}</span></td>
                        <td><span class="status-badge status-{{ $bookStatus }}">{{ ucfirst($bookStatus) }}</span></td>
                        <td>{{ optional($book->created_at)->format('M j, Y') }}</td>
                        <td>
                            <div class="action-buttons">
                                <a class="action-btn edit" href="{{ route('books.edit', ['id' => $book->id]) }}" title="Edit Book"><i class="fas fa-edit"></i></a>
                                <button class="action-btn status" type="button" onclick="showStatusModal('{{ $book->id }}', @js($book->title))" title="Change Status"><i class="fas fa-sync-alt"></i></button>
                                <a class="action-btn view" href="{{ route('book.show', ['id' => $book->id]) }}" target="_blank" title="View Details"><i class="fas fa-eye"></i></a>
                                <button class="action-btn delete" type="button" onclick="showDeleteModal('{{ $book->id }}', @js($book->title))" title="Delete Book"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);">No books found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">
        {{ $books->onEachSide(1)->links() }}
    </div>

    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0;"><i class="fas fa-sync-alt" style="color:#f59e0b;"></i> Update Book Status</h3>
            </div>
            <form method="POST" action="{{ route('admin.books.index', request()->query()) }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="book_id" id="statusBookId">
                    <div style="background:var(--bg-body);padding:0.75rem;border-radius:0.75rem;margin-bottom:1rem;text-align:center;">
                        <strong id="statusBookTitle"></strong>
                    </div>
                    <select name="status" class="form-control-admin">
                        <option value="available">Available</option>
                        <option value="borrowed">Borrowed</option>
                        <option value="reserved">Reserved</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn-admin" style="background:#f59e0b;color:white;">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0;"><i class="fas fa-trash" style="color:#ef4444;"></i> Delete Book</h3>
            </div>
            <form method="POST" action="{{ route('admin.books.index', request()->query()) }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_book">
                    <input type="hidden" name="book_id" id="deleteBookId">
                    <p>Are you sure you want to delete <strong id="deleteBookTitle"></strong>?</p>
                    <p style="color:#ef4444;font-size:0.85rem;">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-admin" style="background:#ef4444;color:white;">Delete Book</button>
                </div>
            </form>
        </div>
    </div>

    <div id="bulkStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0;"><i class="fas fa-sync-alt" style="color:#f59e0b;"></i> Bulk Update Status</h3>
            </div>
            <form method="POST" action="{{ route('admin.books.index', request()->query()) }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="action" value="bulk_update_status">
                    <div id="bulkBookIds"></div>
                    <select name="bulk_status" id="bulkStatusModalSelect" class="form-control-admin">
                        <option value="available">Available</option>
                        <option value="borrowed">Borrowed</option>
                        <option value="reserved">Reserved</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                    <p style="margin-top:1rem;color:var(--text-muted);">This will update <span id="bulkCount"></span> selected book(s).</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('bulkStatusModal')">Cancel</button>
                    <button type="submit" class="btn-admin" style="background:#f59e0b;color:white;">Update Selected</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let selectedBooks = new Set();

    function applyFilter() {
        document.getElementById('hiddenStatus').value = document.getElementById('statusFilter').value;
        document.getElementById('hiddenCategory').value = document.getElementById('categoryFilter').value;
        document.getElementById('searchForm').submit();
    }

    function showStatusModal(bookId, bookTitle) {
        document.getElementById('statusBookId').value = bookId;
        document.getElementById('statusBookTitle').textContent = bookTitle;
        document.getElementById('statusModal').classList.add('active');
    }

    function showDeleteModal(bookId, bookTitle) {
        document.getElementById('deleteBookId').value = bookId;
        document.getElementById('deleteBookTitle').textContent = bookTitle;
        document.getElementById('deleteModal').classList.add('active');
    }

    function toggleAll() {
        const selectAll = document.getElementById('selectAll');
        document.querySelectorAll('.book-checkbox').forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
            if (checkbox.checked) selectedBooks.add(checkbox.value);
            else selectedBooks.delete(checkbox.value);
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        document.querySelectorAll('.book-checkbox').forEach((checkbox) => {
            if (checkbox.checked) selectedBooks.add(checkbox.value);
            else selectedBooks.delete(checkbox.value);
        });

        const bulkBar = document.getElementById('bulkBar');
        document.getElementById('selectedCount').textContent = `${selectedBooks.size} selected`;
        bulkBar.classList.toggle('hidden', selectedBooks.size === 0);
    }

    function bulkUpdateStatus() {
        const status = document.getElementById('bulkStatusSelect').value;
        if (!status || selectedBooks.size === 0) return;
        document.getElementById('bulkStatusModalSelect').value = status;
        document.getElementById('bulkCount').textContent = selectedBooks.size;
        document.getElementById('bulkBookIds').innerHTML = [...selectedBooks].map((id) => `<input type="hidden" name="book_ids[]" value="${id}">`).join('');
        document.getElementById('bulkStatusModal').classList.add('active');
    }

    function bulkDelete() {
        if (selectedBooks.size === 0 || !confirm(`Delete ${selectedBooks.size} selected book(s)? This cannot be undone.`)) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = @js(route('admin.books.index', request()->query()));
        form.innerHTML = `@csrf<input type="hidden" name="action" value="bulk_delete">` +
            [...selectedBooks].map((id) => `<input type="hidden" name="book_ids[]" value="${id}">`).join('');
        document.body.appendChild(form);
        form.submit();
    }

    document.getElementById('statusFilter')?.addEventListener('change', applyFilter);
    document.getElementById('categoryFilter')?.addEventListener('change', applyFilter);
    document.getElementById('selectAll')?.addEventListener('change', toggleAll);
    document.querySelectorAll('.book-checkbox').forEach((checkbox) => checkbox.addEventListener('change', updateSelectedCount));

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
