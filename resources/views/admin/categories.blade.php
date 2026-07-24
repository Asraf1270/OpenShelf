@extends('admin.layouts.app')

@section('title', 'Categories - OpenShelf Admin')
@section('page_title', 'Book Categories')

@push('styles')
<style>
    .categories-page { max-width: 800px; margin: 0 auto; }
    .page-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
    .add-form { background: var(--surface); padding: 2rem; border-radius: 16px; margin-bottom: 2.5rem; border: 1px solid var(--border); }
    .add-form h3 { margin: 0 0 1rem; }
    .add-form-row { display: flex; gap: 1rem; margin-top: 1rem; }
    .category-list { background: var(--surface); border-radius: 16px; overflow: hidden; border: 1px solid var(--border); }
    .category-item { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 2rem; border-bottom: 1px solid var(--border); }
    .category-item:last-child { border-bottom: none; }
    .category-item:hover { background: rgba(76,159,138,0.05); }
    .category-name { font-weight: 750; font-size: 1.1rem; }
    .category-count { color: var(--text-muted); font-size: 0.85rem; margin-left: 0.5rem; font-weight: 600; }
    .actions { display: flex; gap: 0.75rem; }
    .btn-icon {
        width: 38px; height: 38px; border-radius: 10px; background: var(--bg-body); border: 1px solid var(--border);
        cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-muted);
    }
    .btn-icon:hover { background: var(--primary); color: white; border-color: var(--primary); }
    .btn-icon.delete:hover { background: #ef4444; border-color: #ef4444; }
</style>
@endpush

@section('content')
<div class="categories-page">
    <div class="page-actions">
        <h1 style="margin:0;font-weight:850;letter-spacing:-1px;">Book Categories</h1>
        <form method="POST" action="{{ route('admin.categories.index') }}">
            @csrf
            <input type="hidden" name="action" value="collect">
            <button type="submit" class="btn-admin btn-admin-primary"><i class="fas fa-sync"></i> Collect Categories</button>
        </form>
    </div>

    <div class="add-form">
        <h3>Add New Category</h3>
        <form method="POST" action="{{ route('admin.categories.index') }}" class="add-form-row">
            @csrf
            <input type="hidden" name="action" value="add">
            <input type="text" name="name" class="form-control-admin" style="flex:1;" placeholder="Category name" required>
            <button type="submit" class="btn-admin btn-admin-primary">Add Category</button>
        </form>
    </div>

    <div class="category-list">
        @forelse ($categories as $category)
            <div class="category-item">
                <div>
                    <span class="category-name">{{ $category->name }}</span>
                    <span class="category-count">({{ $category->count }} books)</span>
                </div>
                <div class="actions">
                    <button class="btn-icon" type="button" onclick="editCategory({{ $category->id }}, @js($category->name))">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this category?')">
                        @csrf
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="{{ $category->id }}">
                        <button type="submit" class="btn-icon delete"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        @empty
            <div style="padding:3rem;text-align:center;color:var(--text-muted);">No categories found.</div>
        @endforelse
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin:0;">Edit Category</h3>
        </div>
        <form method="POST" action="{{ route('admin.categories.index') }}">
            @csrf
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <input type="text" name="name" id="editName" class="form-control-admin">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-admin btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn-admin btn-admin-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editCategory(id, name) {
        document.getElementById('editId').value = id;
        document.getElementById('editName').value = name;
        document.getElementById('editModal').classList.add('active');
    }
</script>
@endpush
