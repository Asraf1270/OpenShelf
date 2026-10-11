@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/add-book.css') }}">
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
                <div class="editor-shell">
                    <div class="editor-toolbar">
                        <button type="button" class="tool-btn compact" onclick="wrapSelection('**', '**')" title="Bold"><i class="fas fa-bold"></i></button>
                        <button type="button" class="tool-btn compact" onclick="wrapSelection('*', '*')" title="Italic"><i class="fas fa-italic"></i></button>
                        <button type="button" class="tool-btn compact" onclick="formatHeading(1)" title="Heading 1">H1</button>
                        <button type="button" class="tool-btn compact" onclick="formatHeading(2)" title="Heading 2">H2</button>
                        <button type="button" class="tool-btn compact" onclick="wrapSelection('> ', '')" title="Quote"><i class="fas fa-quote-right"></i></button>
                        <button type="button" class="tool-btn compact" onclick="togglePreview()" title="Preview"><i class="fas fa-eye"></i> Preview</button>
                        <button type="button" class="tool-btn compact" onclick="clearDescription()" title="Clear"><i class="fas fa-trash"></i> Clear</button>
                    </div>
                    <textarea name="description" id="bookDescription" class="form-textarea" rows="6" maxlength="5000" oninput="updateCharCount(this)">{{ old('description') }}</textarea>
                    <div class="char-counter" id="charCount">0/5000 characters</div>
                    <div class="editor-preview" id="descriptionPreview"></div>
                </div>
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
    function getDescriptionField() {
        return document.getElementById('bookDescription');
    }

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
        updateDescriptionPreview();
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function wrapSelection(before, after) {
        const textarea = getDescriptionField();
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = textarea.value.substring(start, end);
        const replacement = before + (selected || 'text') + after;

        textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + before.length, start + before.length + (selected.length || 4));
        updateCharCount(textarea);
    }

    function formatHeading(level) {
        const textarea = getDescriptionField();
        if (!textarea) return;

        const value = textarea.value;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const startOfLine = value.lastIndexOf('\n', start - 1) + 1;
        const endOfLine = value.indexOf('\n', end);
        const lineEnd = endOfLine === -1 ? value.length : endOfLine;
        const lines = value.substring(startOfLine, lineEnd).split('\n');
        const prefix = '#'.repeat(Math.max(1, Math.min(level, 6))) + ' ';

        const replacement = lines.map(line => {
            const trimmed = line.trim();
            if (trimmed === '') {
                return line;
            }

            return prefix + line.replace(/^#{1,6}\s+/, '');
        }).join('\n');

        textarea.value = value.substring(0, startOfLine) + replacement + value.substring(lineEnd);
        textarea.focus();
        textarea.setSelectionRange(startOfLine, startOfLine + replacement.length);
        updateCharCount(textarea);
    }

    function renderMarkdown(text) {
        if (!text.trim()) {
            return '<div class="empty-preview">Preview will appear here.</div>';
        }

        const prepared = escapeHtml(text)
            .replace(/(^|\n)##\s+(.*)/g, '$1<h2>$2</h2>')
            .replace(/(^|\n)#\s+(.*)/g, '$1<h1>$2</h1>')
            .replace(/(^|\n)&gt;\s+(.*)/g, '$1<blockquote>$2</blockquote>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(?!\s)([^*\n]+?)(?<!\s)\*/g, '<em>$1</em>');

        return prepared
            .split(/\n\s*\n/)
            .map(block => {
                const trimmed = block.trim();
                if (!trimmed) return '';
                if (/^<(h[12]|blockquote)/.test(trimmed)) return trimmed;
                return `<p>${trimmed.replace(/\n/g, '<br>')}</p>`;
            })
            .filter(Boolean)
            .join('');
    }

    function updateDescriptionPreview() {
        const textarea = getDescriptionField();
        const preview = document.getElementById('descriptionPreview');
        if (!textarea || !preview) return;

        preview.innerHTML = renderMarkdown(textarea.value);
    }

    function togglePreview() {
        const preview = document.getElementById('descriptionPreview');
        if (!preview) return;

        preview.classList.toggle('active');
        if (preview.classList.contains('active')) {
            updateDescriptionPreview();
        }
    }

    function clearDescription() {
        const textarea = getDescriptionField();
        if (!textarea) return;

        if (textarea.value && confirm('Clear the description?')) {
            textarea.value = '';
            updateCharCount(textarea);
        }
    }

    function toggleMoreInfo(event) {
        event.preventDefault();
        const moreInfoSection = document.getElementById('moreInfoSection');
        const toggleBtn = document.getElementById('toggleMoreBtn');

        moreInfoSection.classList.toggle('hidden');
        toggleBtn.classList.toggle('collapsed');
    }

    const desc = document.querySelector('textarea[name="description"]');
    if (desc) {
        updateCharCount(desc);
    }
</script>
@endpush
