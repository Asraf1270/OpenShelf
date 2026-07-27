@extends('admin.layouts.app')

@section('title', 'Review Missing Book Descriptions - OpenShelf Admin')
@section('page_title', 'Missing Book Descriptions')

@push('styles')
<style>
    .missing-desc-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }
    .desc-container {
        display: grid;
        grid-template-columns: 440px 1fr;
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    @media (max-width: 1024px) {
        .desc-container {
            grid-template-columns: 1fr;
        }
    }
    .card-admin {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }
    .card-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border);
    }
    .card-title-lg {
        font-size: 1.15rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-main);
    }
    .book-meta-grid {
        display: flex;
        gap: 1.25rem;
        align-items: flex-start;
    }
    .book-cover-img {
        width: 110px;
        height: 160px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid var(--border);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        background: var(--bg-body);
        flex-shrink: 0;
    }
    .book-details {
        flex: 1;
        min-width: 0;
    }
    .book-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1.3;
        margin-bottom: 0.35rem;
        word-break: break-word;
    }
    .book-author {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--secondary);
        margin-bottom: 0.75rem;
    }
    .tag-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: rgba(76, 159, 138, 0.12);
        color: var(--secondary);
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .meta-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .meta-pill {
        background: var(--bg-body);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.25rem 0.6rem;
        font-size: 0.78rem;
        color: var(--text-muted);
    }
    .prompt-box {
        background: rgba(15, 23, 42, 0.95);
        color: #e2e8f0;
        border-radius: 14px;
        padding: 1.25rem;
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.88rem;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        margin-top: 1rem;
    }
    .ai-btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .btn-ai-copy {
        background: linear-gradient(135deg, #4C9F8A, #2C3E50);
        color: white;
        font-weight: 700;
        border: none;
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition);
        box-shadow: 0 4px 14px rgba(76, 159, 138, 0.3);
    }
    .btn-ai-copy:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(76, 159, 138, 0.45);
    }
    .btn-chatgpt {
        background: #10a37f;
        color: white;
        font-weight: 600;
        border: none;
        padding: 0.75rem 1.1rem;
        border-radius: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        transition: var(--transition);
    }
    .btn-chatgpt:hover {
        background: #0e8e6f;
        color: white;
        transform: translateY(-2px);
    }
    .btn-gemini {
        background: linear-gradient(135deg, #4285f4, #9b51e0);
        color: white;
        font-weight: 600;
        border: none;
        padding: 0.75rem 1.1rem;
        border-radius: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        transition: var(--transition);
    }
    .btn-gemini:hover {
        opacity: 0.92;
        color: white;
        transform: translateY(-2px);
    }
    .editor-wrapper {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .editor-textarea {
        width: 100%;
        min-height: 220px;
        background: var(--surface);
        color: var(--text-main);
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 1rem;
        font-family: inherit;
        font-size: 0.95rem;
        line-height: 1.6;
        resize: vertical;
        transition: var(--transition);
    }
    .editor-textarea:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 4px rgba(76, 159, 138, 0.15);
    }
    .editor-tools {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .tool-btn {
        background: var(--bg-body);
        border: 1px solid var(--border);
        color: var(--text-main);
        padding: 0.4rem 0.75rem;
        border-radius: 8px;
        font-size: 0.8rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: var(--transition);
    }
    .tool-btn:hover {
        background: var(--surface);
        border-color: var(--secondary);
        color: var(--secondary);
    }
    .char-counter {
        font-size: 0.8rem;
        color: var(--text-muted);
        font-weight: 500;
    }
    .progress-bar-outer {
        width: 100%;
        height: 10px;
        background: var(--border);
        border-radius: 999px;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    .progress-bar-inner {
        height: 100%;
        width: var(--progress-percentage, 0%);
        background: linear-gradient(90deg, var(--secondary), #2C3E50);
        border-radius: 999px;
        transition: width 0.4s ease;
    }
    .queue-drawer {
        margin-top: 1.5rem;
    }
    .queue-list {
        display: flex;
        gap: 0.75rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        scroll-behavior: smooth;
    }
    .queue-item {
        flex: 0 0 200px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 0.75rem;
        text-decoration: none;
        color: var(--text-main);
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .queue-item:hover, .queue-item.active {
        border-color: var(--secondary);
        box-shadow: 0 4px 14px rgba(76, 159, 138, 0.15);
        background: rgba(76, 159, 138, 0.04);
    }
    .queue-thumb {
        width: 38px;
        height: 52px;
        object-fit: cover;
        border-radius: 6px;
        flex-shrink: 0;
    }
    .queue-info {
        overflow: hidden;
    }
    .queue-title {
        font-size: 0.82rem;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .queue-author {
        font-size: 0.75rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .toast-popup {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: #10b981;
        color: white;
        padding: 0.85rem 1.5rem;
        border-radius: 14px;
        font-weight: 600;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        z-index: 2000;
        pointer-events: none;
    }
    .toast-popup.show {
        opacity: 1;
        transform: translateY(0);
    }
    .preview-box {
        background: var(--bg-body);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1rem;
        margin-top: 0.75rem;
        font-size: 0.95rem;
        line-height: 1.6;
        display: none;
    }
    .preview-box.active {
        display: block;
    }
</style>
@endpush

@section('content')
<div class="missing-desc-wrapper">
    <div class="card-admin" style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="margin:0; font-size: 1.35rem; font-weight: 800;">Review Missing Book Descriptions</h2>
                <p style="margin: 0.25rem 0 0; color: var(--text-muted); font-size: 0.9rem;">
                    Efficiently generate and add summaries for books without description using AI assistance.
                </p>
            </div>
            <div style="text-align: right; min-width: 220px;">
                <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted);">
                    <span style="color: var(--secondary); font-size: 1.1rem; font-weight: 800;">{{ $totalMissing }}</span> Books Pending Review
                </div>
                <div class="progress-bar-outer">
                    <div class="progress-bar-inner" style="--progress-percentage: {{ $progressPercentage }}%;"></div>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                    {{ $completedCount }} of {{ $totalBooks }} books completed ({{ $progressPercentage }}%)
                </div>
            </div>
        </div>
    </div>

    @if(!$currentBook)
        <!-- Empty State: All Books have descriptions -->
        <div class="card-admin" style="text-align: center; padding: 4rem 2rem;">
            <div style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.5rem; margin-bottom: 1.5rem;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;">All Books Have Descriptions! 🎉</h3>
            <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem;">
                Great job! Every single book in the OpenShelf library currently has a detailed description attached.
            </p>
            <a href="{{ route('admin.books.index') }}" class="btn-admin btn-admin-primary">
                <i class="fas fa-arrow-left"></i> Back to Book Management
            </a>
        </div>
    @else
        @php
            // Construct the Bengali AI Prompt
            $promptText = "বই: {$currentBook->title}\n"
                . "লেখক: {$currentBook->author}\n"
                . "ক্যাটাগরি: " . ($currentBook->category ?: 'সাধারণ') . "\n"
                . (!empty($currentBook->publisher) ? "পাবলিশার: {$currentBook->publisher}\n" : '')
                . (!empty($currentBook->language) ? "ভাষা: {$currentBook->language}\n" : '')
                . "\n"
                . "অনুগ্রহ করে এই বইটির একটি সুন্দর, আকর্ষণীয় এবং বিস্তারিত বিবরণ (Book Summary / Description) বাংলায় লিখুন। বিবরণটি ৩-৪ প্যারাগ্রাফের মধ্যে সীমাবদ্ধ রাখুন এবং বইটির মূল বিষয়বস্তু, অনন্য বৈশিষ্ট্য ও পাঠকদের কেন এটি পড়া উচিত তা সাবলীল ভাষায় ফুটিয়ে তুলুন।";
        @endphp

        <div class="desc-container">
            <!-- Left Panel: Book Info & Prompt Generator -->
            <div class="card-admin">
                <div class="card-header-flex">
                    <div class="card-title-lg">
                        <i class="fas fa-book-open" style="color: var(--secondary);"></i>
                        <span>Target Book Details</span>
                    </div>
                    <a href="{{ route('book.show', ['id' => $currentBook->id]) }}" target="_blank" class="tool-btn" title="Preview on public site">
                        <i class="fas fa-external-link-alt"></i> View Page
                    </a>
                </div>

                <div class="book-meta-grid">
                    <img src="{{ $currentBook->detail_cover_url }}" alt="{{ $currentBook->title }}" class="book-cover-img fallback-cover">
                    <div class="book-details">
                        <div class="book-title">{{ $currentBook->title }}</div>
                        <div class="book-author"><i class="fas fa-feather-alt"></i> {{ $currentBook->author }}</div>
                        
                        @if($currentBook->category)
                            <div class="tag-badge">
                                <i class="fas fa-tag"></i> {{ $currentBook->category }}
                            </div>
                        @endif

                        <div class="meta-pills">
                            @if($currentBook->publisher)
                                <div class="meta-pill"><i class="fas fa-building"></i> {{ $currentBook->publisher }}</div>
                            @endif
                            @if($currentBook->publication_year)
                                <div class="meta-pill"><i class="fas fa-calendar-alt"></i> {{ $currentBook->publication_year }}</div>
                            @endif
                            @if($currentBook->language)
                                <div class="meta-pill"><i class="fas fa-globe"></i> {{ $currentBook->language }}</div>
                            @endif
                            @if($currentBook->isbn)
                                <div class="meta-pill"><i class="fas fa-barcode"></i> ISBN: {{ $currentBook->isbn }}</div>
                            @endif
                            @if($currentBook->owner)
                                <div class="meta-pill"><i class="fas fa-user-circle"></i> Owner: {{ $currentBook->owner->name }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border); margin: 1.5rem 0 1rem;">

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main); display: flex; align-items: center; gap: 0.4rem;">
                        <i class="fas fa-robot" style="color: #4C9F8A;"></i> Bengali AI Prompt
                    </div>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Auto-generated for ChatGPT & Gemini</span>
                </div>

                <div class="prompt-box" id="aiPromptText">{{ $promptText }}</div>

                <div class="ai-btn-group">
                    <button type="button" class="btn-ai-copy" id="btnCopyPrompt" onclick="copyAiPrompt()">
                        <i class="fas fa-copy" id="copyIcon"></i>
                        <span id="copyBtnLabel">Copy AI Prompt</span>
                    </button>
                    <a href="https://chatgpt.com/" target="_blank" rel="noopener noreferrer" class="btn-chatgpt" title="Open ChatGPT in new tab">
                        <i class="fas fa-bolt"></i> ChatGPT
                    </a>
                    <a href="https://gemini.google.com/app" target="_blank" rel="noopener noreferrer" class="btn-gemini" title="Open Gemini in new tab">
                        <i class="fas fa-sparkles"></i> Gemini
                    </a>
                </div>
            </div>

            <!-- Right Panel: Rich Textarea & Save Actions -->
            <div class="card-admin" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div class="card-header-flex">
                        <div class="card-title-lg">
                            <i class="fas fa-pen-nib" style="color: var(--secondary);"></i>
                            <span>Paste & Edit Description</span>
                        </div>
                        <div class="editor-tools">
                            <button type="button" class="tool-btn" onclick="togglePreview()">
                                <i class="fas fa-eye" id="previewIcon"></i> <span id="previewBtnLabel">Preview</span>
                            </button>
                        </div>
                    </div>

                    <form id="descriptionForm" method="POST" action="{{ route('admin.missing-descriptions.update') }}">
                        @csrf
                        <input type="hidden" name="book_id" value="{{ $currentBook->id }}">

                        <div class="editor-wrapper">
                            <div class="editor-tools">
                                <div style="display: flex; gap: 0.4rem;">
                                    <button type="button" class="tool-btn" onclick="wrapText('**', '**')" title="Bold">
                                        <i class="fas fa-bold"></i>
                                    </button>
                                    <button type="button" class="tool-btn" onclick="wrapText('*', '*')" title="Italic">
                                        <i class="fas fa-italic"></i>
                                    </button>
                                    <button type="button" class="tool-btn" onclick="formatParagraphs()" title="Format Paragraphs">
                                        <i class="fas fa-paragraph"></i> Auto-Paragraph
                                    </button>
                                    <button type="button" class="tool-btn" onclick="clearText()" title="Clear Text">
                                        <i class="fas fa-trash-alt"></i> Clear
                                    </button>
                                </div>
                                <div class="char-counter" id="charCounter">0 characters | 0 words</div>
                            </div>

                            <textarea 
                                name="description" 
                                id="descriptionTextarea" 
                                class="editor-textarea" 
                                placeholder="Paste the generated Bengali summary here... (Markdown & standard line breaks supported)" 
                                required
                                oninput="updateCounts()"
                            >{{ old('description') }}</textarea>

                            <div class="preview-box" id="previewBox">
                                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Rendered Preview</div>
                                <div id="previewContent" style="color: var(--text-main);"></div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Form Actions Footer -->
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <form method="POST" action="{{ route('admin.missing-descriptions.skip') }}" style="margin: 0;">
                        @csrf
                        <input type="hidden" name="book_id" value="{{ $currentBook->id }}">
                        <button type="submit" class="btn-admin btn-outline" title="Skip to next item without editing">
                            <i class="fas fa-forward"></i> Skip Item
                        </button>
                    </form>

                    <button type="submit" form="descriptionForm" class="btn-admin btn-admin-primary" style="padding: 0.85rem 2rem; font-size: 1rem; border-radius: 14px;">
                        <i class="fas fa-save"></i> Save & Load Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottom Queue Carousel / Quick Selection -->
        @if($pendingBooks->count() > 1)
            <div class="card-admin queue-drawer">
                <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-list-ol" style="color: var(--secondary);"></i>
                    <span>Pending Queue ({{ $pendingBooks->count() }} remaining)</span>
                </div>
                <div class="queue-list">
                    @foreach($pendingBooks as $pBook)
                        <a href="{{ route('admin.missing-descriptions.index', ['book_id' => $pBook->id]) }}" 
                           class="queue-item {{ $pBook->id === $currentBook->id ? 'active' : '' }}">
                            <img src="{{ $pBook->cover_url }}" class="queue-thumb fallback-cover">
                            <div class="queue-info">
                                <div class="queue-title">{{ $pBook->title }}</div>
                                <div class="queue-author">{{ $pBook->author }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>

<!-- Toast Alert -->
<div id="toastPopup" class="toast-popup">
    <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
    <span id="toastMessage">AI Prompt copied to clipboard!</span>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Image Fallback Handling without inline onerror attributes
        const defaultCoverUrl = "{{ asset('images/default-book-cover.jpg') }}";
        document.querySelectorAll('.fallback-cover').forEach(img => {
            img.addEventListener('error', function() {
                this.src = defaultCoverUrl;
            }, { once: true });
        });

        updateCounts();
    });

    function copyAiPrompt() {
        const promptElement = document.getElementById('aiPromptText');
        if (!promptElement) return;

        const textToCopy = promptElement.innerText || promptElement.textContent;

        navigator.clipboard.writeText(textToCopy).then(() => {
            showToast('AI Prompt copied to clipboard!');
            
            const btn = document.getElementById('btnCopyPrompt');
            const label = document.getElementById('copyBtnLabel');
            const icon = document.getElementById('copyIcon');

            if (btn && label && icon) {
                btn.style.background = '#10b981';
                label.innerText = 'Copied!';
                icon.className = 'fas fa-check';

                setTimeout(() => {
                    btn.style.background = '';
                    label.innerText = 'Copy AI Prompt';
                    icon.className = 'fas fa-copy';
                }, 2500);
            }
        }).catch(err => {
            console.error('Failed to copy: ', err);
            const textArea = document.createElement("textarea");
            textArea.value = textToCopy;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);
            showToast('AI Prompt copied to clipboard!');
        });
    }

    function showToast(msg) {
        const toast = document.getElementById('toastPopup');
        const toastMsg = document.getElementById('toastMessage');
        if (!toast) return;

        if (toastMsg) toastMsg.innerText = msg;
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function updateCounts() {
        const textarea = document.getElementById('descriptionTextarea');
        const counter = document.getElementById('charCounter');
        if (!textarea || !counter) return;

        const text = textarea.value;
        const charCount = text.length;
        const wordCount = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;

        counter.innerText = `${charCount} characters | ${wordCount} words`;

        const previewBox = document.getElementById('previewBox');
        if (previewBox && previewBox.classList.contains('active')) {
            renderPreview(text);
        }
    }

    function togglePreview() {
        const previewBox = document.getElementById('previewBox');
        const label = document.getElementById('previewBtnLabel');
        const icon = document.getElementById('previewIcon');
        const textarea = document.getElementById('descriptionTextarea');

        if (!previewBox) return;

        const isShowing = previewBox.classList.toggle('active');
        if (label) label.innerText = isShowing ? 'Hide Preview' : 'Preview';
        if (icon) icon.className = isShowing ? 'fas fa-eye-slash' : 'fas fa-eye';

        if (isShowing && textarea) {
            renderPreview(textarea.value);
        }
    }

    function renderPreview(text) {
        const previewContent = document.getElementById('previewContent');
        if (!previewContent) return;

        if (!text.trim()) {
            previewContent.innerHTML = '<em style="color: var(--text-muted);">Nothing to preview yet...</em>';
            return;
        }

        const formatted = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .split(/\n\s*\n/)
            .map(p => `<p style="margin-bottom: 0.85rem;">${p.replace(/\n/g, '<br>')}</p>`)
            .join('');

        previewContent.innerHTML = formatted;
    }

    function wrapText(before, after) {
        const textarea = document.getElementById('descriptionTextarea');
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = textarea.value.substring(start, end);
        const replacement = before + (selected || 'text') + after;

        textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + before.length, start + before.length + (selected.length || 4));
        updateCounts();
    }

    function formatParagraphs() {
        const textarea = document.getElementById('descriptionTextarea');
        if (!textarea || !textarea.value) return;

        let text = textarea.value.trim();
        text = text.replace(/\n{3,}/g, '\n\n');
        textarea.value = text;
        updateCounts();
        showToast('Paragraphs formatted!');
    }

    function clearText() {
        const textarea = document.getElementById('descriptionTextarea');
        if (!textarea) return;
        if (textarea.value && confirm('Are you sure you want to clear the description textarea?')) {
            textarea.value = '';
            updateCounts();
        }
    }
</script>
@endpush
