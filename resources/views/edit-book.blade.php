@extends('layouts.app')

@push('styles')
<style>
    :root {
        --primary: #2C3E50;
        --secondary: #4C9F8A;
        --accent: #3A7B6B;
        --bg: #F8F9FA;
        --surface: #ffffff;
        --border: #E2E8F0;
        --text-main: #0F172A;
        --text-muted: #5A6C7D;
        --radius: 16px;
        --shadow: 0 10px 25px rgba(44, 62, 80, 0.05);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    [data-theme="dark"] {
        --bg: #0F172A;
        --surface: #1E293B;
        --border: #334155;
        --text-main: #F8F9FA;
        --text-muted: #94A3B8;
        --shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .edit-page-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 1.5rem;
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 992px) {
        .edit-page-container {
            grid-template-columns: 1fr 320px;
        }
    }

    aside { order: 2; }

    .profile-side-card {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        text-align: center;
        border: 1px solid var(--border);
        margin-bottom: 1rem;
    }

    .mini-avatar-preview {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        margin: 0 auto 0.75rem;
        border: 3px solid var(--secondary);
        overflow: hidden;
        background: var(--bg);
    }

    .mini-avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .edit-form-card {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
    }

    .page-header {
        margin-bottom: 2rem;
        text-align: center;
    }

    .page-header h1 {
        font-size: 2rem;
        font-weight: 850;
        color: var(--text-main);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        letter-spacing: -1px;
    }

    .page-header p {
        color: var(--text-muted);
        font-size: 0.95rem;
        font-weight: 500;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        color: var(--text-main);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        transition: var(--transition);
    }

    .back-btn:hover {
        background: var(--bg);
        transform: translateX(-3px);
    }

    .cover-section {
        text-align: center;
        margin-bottom: 2rem;
        padding: 2rem;
        background: var(--bg);
        border-radius: var(--radius);
        border: 2px dashed var(--border);
        transition: var(--transition);
    }

    .cover-section:hover { border-color: var(--secondary); }

    .cover-preview {
        width: 120px;
        height: 160px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        margin: 0 auto 1rem;
        cursor: pointer;
        transition: transform 0.2s;
        border: 2px solid #fff;
    }

    .cover-preview:hover { transform: scale(1.05); }

    .cover-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cover-placeholder {
        width: 120px;
        height: 160px;
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: #6b7280;
        cursor: pointer;
        margin: 0 auto 1rem;
        transition: all 0.2s;
        border: 2px solid #d1d5db;
    }

    .cover-placeholder:hover {
        background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
        border-color: #9ca3af;
    }

    .image-hint {
        font-size: 0.75rem;
        color: #6b7280;
        text-align: center;
        margin-top: 0.5rem;
    }

    .form-group { margin-bottom: 1.5rem; }

    .form-label {
        display: block;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 0.6rem;
        font-size: 0.9rem;
    }

    .form-input,
    .form-textarea,
    .form-select {
        width: 100%;
        padding: 0.85rem;
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 1rem;
        transition: var(--transition);
        background: var(--surface);
        color: var(--text-main);
    }

    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 4px rgba(76, 159, 138, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    @media (min-width: 640px) {
        .form-row { grid-template-columns: 1fr 1fr; }
    }

    .form-error {
        color: #dc2626;
        font-size: 0.75rem;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .char-counter {
        font-size: 0.75rem;
        color: #6b7280;
        text-align: right;
        margin-top: 0.25rem;
    }

    .char-counter.warning { color: #f59e0b; }
    .char-counter.danger { color: #dc2626; }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: #fff;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(44, 62, 80, 0.2);
        filter: brightness(1.1);
    }

    .btn-outline {
        background: transparent;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .btn-outline:hover { background: #f9fafb; }

    .btn-sm { padding: 0.5rem 1rem; font-size: 0.75rem; }
    .btn-block { width: 100%; }

    .form-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 2rem;
    }

    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .alert-danger {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    #cover_image,
    #user_profile_pic {
        display: none;
    }
</style>
@endpush

@section('content')
<main class="edit-page-container">
    <div class="edit-form-card">
        <a href="{{ route('book.show', ['id' => $book->id]) }}" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Book
        </a>

        <div class="page-header">
            <h1><i class="fas fa-edit" style="color: var(--secondary);"></i> Edit Book</h1>
            <p>Update your book's information and keep your shelf fresh.</p>
        </div>

        @error('general')
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                {{ $message }}
            </div>
        @enderror

        <form method="POST" action="{{ route('books.update', ['id' => $book->id]) }}" enctype="multipart/form-data" id="editForm">
            @csrf

            <div class="cover-section">
                <label for="cover_image" style="cursor: pointer;">
                    @if ($book->cover_image)
                        <div class="cover-preview" id="coverPreview">
                            <img src="{{ $coverThumbUrl }}" alt="Book Cover" id="coverImagePreview">
                        </div>
                    @else
                        <div class="cover-placeholder" id="coverPlaceholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Upload Cover</span>
                            <span style="font-size: 0.7rem;">Click to change</span>
                        </div>
                    @endif
                </label>
                <input type="file" name="cover_image" id="cover_image" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewCover(this)">
                <div class="image-hint">
                    <i class="fas fa-info-circle"></i>
                    Max size: 10MB. Supported: JPG, PNG, GIF, WebP
                </div>
                @error('cover_image')
                    <div class="form-error text-center">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-book"></i>
                    Book Title <span class="text-danger">*</span>
                </label>
                <input type="text" name="title" class="form-input" value="{{ old('title', $book->title) }}" maxlength="200" required>
                @error('title')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user"></i>
                    Author Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="author" class="form-input" value="{{ old('author', $book->author) }}" maxlength="100" required>
                @error('author')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-tag"></i>
                        Category <span class="text-danger">*</span>
                    </label>
                    <select name="category" class="form-select" required>
                        <option value="">Select a category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('category', $book->category) === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-star"></i>
                        Condition <span class="text-danger">*</span>
                    </label>
                    <select name="condition" class="form-select" required>
                        <option value="">Select condition</option>
                        @foreach ($conditions as $key => $desc)
                            <option value="{{ $key }}" @selected(old('condition', $book->condition) === $key) title="{{ $desc }}">{{ $key }}</option>
                        @endforeach
                    </select>
                    @error('condition')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-align-left"></i>
                    Description <span class="text-danger">*</span>
                </label>
                <textarea name="description" class="form-textarea" rows="6" maxlength="5000" oninput="updateCharCount(this)">{{ old('description', $book->description) }}</textarea>
                <div class="char-counter" id="charCount">0/5000 characters</div>
                @error('description')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
                <a href="{{ route('book.show', ['id' => $book->id]) }}" class="btn btn-outline">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <aside class="edit-sidebar">
        <div class="profile-side-card">
            <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem;">Owner Identity</h3>
            <div class="mini-avatar-preview">
                <img src="{{ $avatarUrl }}" alt="Avatar" id="userAvatarPreview" onerror="this.src='{{ asset('images/avatars/default.jpg') }}'">
            </div>
            <p style="font-weight: 700; margin-bottom: 0.25rem;">{{ $user->name }}</p>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">OpenShelf Member</p>

            <button type="button" class="btn btn-outline btn-sm btn-block" onclick="document.getElementById('user_profile_pic').click()">
                <i class="fas fa-camera"></i> Change Photo
            </button>
            <input type="file" name="user_profile_pic" id="user_profile_pic" form="editForm" accept="image/*" onchange="previewUserAvatar(this)">

            @error('user_profile_pic')
                <p class="text-danger" style="font-size: 0.75rem; margin-top: 0.5rem;">{{ $message }}</p>
            @enderror
        </div>

        <div class="profile-side-card" style="text-align: left;">
            <h4 style="font-size: 0.9rem; font-weight: 800; margin-bottom: 0.75rem;">Quality Guidelines</h4>
            <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">
                <li style="margin-bottom: 0.5rem;"><i class="fas fa-check" style="color: var(--secondary); margin-right: 8px;"></i> High quality covers attract 3x more borrowers.</li>
                <li style="margin-bottom: 0.5rem;"><i class="fas fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Honest condition reports build lasting trust.</li>
                <li><i class="fas fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Accurate ISBNs help people find your book.</li>
            </ul>
        </div>
    </aside>
</main>
@endsection

@push('scripts')
<script>
    function previewUserAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('userAvatarPreview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function previewCover(input) {
        const preview = document.getElementById('coverImagePreview');
        const placeholder = document.getElementById('coverPlaceholder');

        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                if (preview) {
                    preview.src = e.target.result;
                } else {
                    const coverPreview = document.querySelector('.cover-preview');
                    if (!coverPreview) {
                        const newPreview = document.createElement('div');
                        newPreview.className = 'cover-preview';
                        newPreview.id = 'coverPreview';
                        newPreview.innerHTML = `<img src="${e.target.result}" id="coverImagePreview">`;
                        input.parentElement.insertBefore(newPreview, input);
                    } else {
                        document.getElementById('coverImagePreview').src = e.target.result;
                    }
                    if (placeholder) placeholder.style.display = 'none';
                }
            };

            reader.readAsDataURL(input.files[0]);
        }
    }

    function updateCharCount(textarea) {
        const count = textarea.value.length;
        const charCounter = document.getElementById('charCount');
        const maxLength = 5000;

        charCounter.textContent = `${count}/${maxLength} characters`;

        if (count > maxLength * 0.9) {
            charCounter.classList.add('danger');
            charCounter.classList.remove('warning');
        } else if (count > maxLength * 0.75) {
            charCounter.classList.add('warning');
            charCounter.classList.remove('danger');
        } else {
            charCounter.classList.remove('warning', 'danger');
        }
    }

    const descriptionField = document.querySelector('textarea[name="description"]');
    if (descriptionField) {
        updateCharCount(descriptionField);
    }
</script>
@endpush
