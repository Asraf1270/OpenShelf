@extends('layouts.app')

@push('styles')
<style>
    .add-container { max-width: 800px; margin: 0 auto; padding: var(--space-5); }
    .cover-preview { width: 150px; height: 200px; margin: 0 auto var(--space-4); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md); cursor: pointer; transition: transform var(--transition-fast); }
    .cover-preview:hover { transform: scale(1.02); }
    .cover-preview img { width: 100%; height: 100%; object-fit: cover; }
    .cover-placeholder { width: 150px; height: 200px; margin: 0 auto var(--space-4); background: var(--surface-hover); border-radius: var(--radius-lg); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: var(--space-2); color: var(--text-tertiary); cursor: pointer; transition: all var(--transition-fast); }
    .cover-placeholder:hover { background: var(--border); transform: scale(1.02); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); }
    @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } .add-container { padding: var(--space-4); } }
    .char-counter { font-size: var(--font-size-xs); color: var(--text-tertiary); text-align: right; margin-top: var(--space-1); }
    .char-counter.danger { color: var(--danger); }
    .char-counter.warning { color: var(--warning); }
    .more-info-section { margin-top: var(--space-6); padding-top: var(--space-6); border-top: 1px solid var(--border); }
    .more-info-section.hidden { display: none; }
    .toggle-more-btn {
        display: inline-flex;
        align-items: center;
        gap: var(--space-2);
        background: none;
        border: none;
        color: var(--primary);
        cursor: pointer;
        font-size: inherit;
        padding: var(--space-2) 0;
        transition: color var(--transition-fast);
    }
    .toggle-more-btn:hover { color: var(--primary-dark); }
    .toggle-more-btn i { transition: transform var(--transition-fast); }
    .toggle-more-btn.collapsed i { transform: rotate(-90deg); }
</style>
@endpush

@section('content')
<div class="container">
    <div class="add-container">
        <div style="margin-bottom: var(--space-6);">
            <h1><i class="fas fa-plus-circle" style="color: var(--primary);"></i> Add New Book</h1>
            <p class="text-muted">Share your book with the community</p>
        </div>

        @if ($success)
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Book added successfully!
                <a href="{{ route('book.show', ['id' => $addedBookId]) }}" class="btn btn-sm btn-outline ms-3">View Book</a>
            </div>
        @endif

        @error('general')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('books.store') }}" enctype="multipart/form-data" id="addForm">
            @csrf

            <div class="text-center mb-4">
                <label for="cover_image" style="cursor: pointer;">
                    <div id="coverPreview" class="cover-placeholder">
                        <i class="fas fa-cloud-upload-alt fa-2x"></i>
                        <span>Click to upload cover</span>
                    </div>
                </label>
                <input type="file" name="cover_image" id="cover_image" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="previewCover(this)">
                <div class="small text-muted">Max size: 10MB. Supported: JPG, PNG, GIF, WebP</div>
                @error('cover_image')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Book Title *</label>
                <input type="text" name="title" class="form-input" value="{{ old('title') }}" maxlength="200" required>
                @error('title')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Author Name *</label>
                <input type="text" name="author" class="form-input" value="{{ old('author') }}" maxlength="100" required>
                @error('author')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-select" required>
                        <option value="">Select category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Condition *</label>
                    <select name="condition" class="form-select" required>
                        <option value="">Select condition</option>
                        @foreach ($conditions as $key => $desc)
                            <option value="{{ $key }}" @selected(old('condition') === $key) title="{{ $desc }}">{{ $key }}</option>
                        @endforeach
                    </select>
                    @error('condition')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description <span class="text-muted">(Optional)</span></label>
                <textarea name="description" class="form-textarea" rows="6" maxlength="5000" oninput="updateCharCount(this)">{{ old('description') }}</textarea>
                <div class="char-counter" id="charCount">0/5000 characters</div>
                @error('description')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-center mt-4 mb-4">
                <button type="button" class="toggle-more-btn collapsed" id="toggleMoreBtn" onclick="toggleMoreInfo(event)">
                    <i class="fas fa-chevron-down"></i>
                    <span>Add More Information</span>
                </button>
            </div>

            <div id="moreInfoSection" class="more-info-section hidden">
                <h3 style="font-size: var(--font-size-lg); margin-bottom: var(--space-4);">
                    <i class="fas fa-info-circle" style="color: var(--primary); margin-right: var(--space-2);"></i>
                    Additional Information
                </h3>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" class="form-input" value="{{ old('isbn') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Publication Year</label>
                        <input type="text" name="publication_year" class="form-input" value="{{ old('publication_year') }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Publisher</label>
                        <input type="text" name="publisher" class="form-input" value="{{ old('publisher') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pages</label>
                        <input type="number" name="pages" class="form-input" value="{{ old('pages') }}" min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Language</label>
                    <input type="text" name="language" class="form-input" value="{{ old('language', 'Bangla') }}">
                </div>
            </div>

            <div class="d-flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary flex-grow-1">Add Book to Library</button>
                <a href="{{ route('profile') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function previewCover(input) {
        const preview = document.getElementById('coverPreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
                preview.classList.remove('cover-placeholder');
                preview.classList.add('cover-preview');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function updateCharCount(textarea) {
        const count = textarea.value.length;
        const max = 5000;
        const counter = document.getElementById('charCount');
        counter.textContent = `${count}/${max} characters`;
        if (count > max * 0.9) counter.classList.add('danger'), counter.classList.remove('warning');
        else if (count > max * 0.75) counter.classList.add('warning'), counter.classList.remove('danger');
        else counter.classList.remove('warning', 'danger');
    }

    function toggleMoreInfo(event) {
        event.preventDefault();
        const moreInfoSection = document.getElementById('moreInfoSection');
        const toggleBtn = document.getElementById('toggleMoreBtn');

        moreInfoSection.classList.toggle('hidden');
        toggleBtn.classList.toggle('collapsed');
    }

    const desc = document.querySelector('textarea[name="description"]');
    if (desc) updateCharCount(desc);
</script>
@endpush
