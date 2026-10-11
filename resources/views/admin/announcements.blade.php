@extends('admin.layouts.app')

@section('title', 'Announcements - OpenShelf Admin')
@section('page_title', 'Announcements')

@push('styles')
<style>
    .announcements-page { max-width: 1200px; margin: 0 auto; }
    .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .stat-card { background: var(--surface); border-radius: 1rem; padding: 1.25rem; text-align: center; border: 1px solid var(--border); }
    .stat-number { font-size: 2rem; font-weight: 700; margin-bottom: 0.25rem; }
    .stat-label { color: var(--text-muted); font-size: 0.85rem; }
    .form-card { background: var(--surface); border-radius: 1.5rem; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--border); }
    .form-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border); }
    .form-header h2 { margin: 0; font-size: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
    .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem; }
    .form-group { margin-bottom: 1rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
    .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 0.75rem; font-size: 0.9rem; background: var(--surface); color: var(--text-main); font: inherit; }
    textarea.form-control { min-height: 150px; resize: vertical; }
    .editor-wrapper { display: flex; flex-direction: column; gap: 0.75rem; }
    .toolbar-row { display: flex; flex-wrap: wrap; gap: 0.4rem; }
    .tool-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.7rem;
        border: 1px solid var(--border);
        background: var(--bg-body);
        color: var(--text-main);
        border-radius: 0.65rem;
        font-size: 0.8rem;
        cursor: pointer;
        transition: var(--transition);
    }
    .tool-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--surface); }
    .tool-btn.compact { padding: 0.45rem 0.55rem; }
    .tool-divider { width: 1px; height: 26px; background: var(--border); align-self: center; margin: 0 0.15rem; }
    .editor-note { font-size: 0.8rem; color: var(--text-muted); margin-top: -0.25rem; }
    .preview-box {
        background: var(--bg-body);
        border: 1px solid var(--border);
        border-radius: 0.9rem;
        padding: 1rem;
        display: none;
        line-height: 1.7;
    }
    .preview-box.active { display: block; }
    .preview-box h1, .preview-box h2, .preview-box h3, .preview-box h4, .preview-box h5, .preview-box h6 { margin: 0.8rem 0 0.5rem; line-height: 1.3; }
    .preview-box p, .preview-box ul, .preview-box ol, .preview-box blockquote, .preview-box pre { margin: 0 0 0.8rem; }
    .preview-box ul, .preview-box ol { padding-left: 1.3rem; }
    .preview-box blockquote { border-left: 4px solid var(--primary); padding-left: 0.85rem; color: var(--text-muted); font-style: italic; }
    .preview-box a { color: var(--primary); text-decoration: underline; }
    .preview-box code { background: rgba(15,23,42,0.08); padding: 0.12rem 0.3rem; border-radius: 0.4rem; }
    .preview-box pre { overflow-x: auto; background: rgba(15,23,42,0.05); padding: 0.9rem 1rem; border-radius: 0.75rem; }
    .checkbox-group { display: flex; gap: 1rem; flex-wrap: wrap; }
    .checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
    .announcement-list { display: flex; flex-direction: column; gap: 1rem; }
    .announcement-card { background: var(--surface); border-radius: 1rem; padding: 1.25rem; border: 1px solid var(--border); }
    .announcement-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.75rem; }
    .announcement-title { font-size: 1.1rem; font-weight: 600; margin-left: 0.5rem; }
    .priority-badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; border-radius: 2rem; font-size: 0.7rem; font-weight: 600; }
    .priority-info { background: rgba(59,130,246,0.1); color: #3b82f6; }
    .priority-success { background: rgba(16,185,129,0.1); color: #10b981; }
    .priority-warning { background: rgba(245,158,11,0.1); color: #f59e0b; }
    .priority-danger { background: rgba(239,68,68,0.1); color: #ef4444; }
    .announcement-content { color: var(--text-main); margin-bottom: 1rem; line-height: 1.5; white-space: pre-wrap; }
    .announcement-meta { display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.7rem; color: var(--text-muted); margin-bottom: 0.75rem; }
    .announcement-actions { display: flex; gap: 0.5rem; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 0.75rem; margin-top: 0.5rem; }
    .action-btn { padding: 0.35rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; cursor: pointer; background: none; border: none; color: var(--text-muted); text-decoration: none; }
    .action-btn:hover { background: var(--bg-body); color: var(--primary); }
    .action-btn.delete:hover { color: #ef4444; }
    .empty-state { text-align: center; padding: 3rem; background: var(--surface); border-radius: 1rem; border: 1px solid var(--border); }
    @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="announcements-page">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;margin:0;">Announcements</h1>
            <p style="color:var(--text-muted);margin:0.5rem 0 0;">Create and manage announcements sent to all users</p>
        </div>
    </div>

    <div class="stats-cards">
        <div class="stat-card"><div class="stat-number" style="color:var(--primary);">{{ $totalAnnouncements }}</div><div class="stat-label">Total Announcements</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#10b981;">{{ $activeAnnouncements }}</div><div class="stat-label">Active</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#f59e0b;">{{ $scheduledAnnouncements }}</div><div class="stat-label">Scheduled</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#ef4444;">{{ $expiredAnnouncements }}</div><div class="stat-label">Expired</div></div>
    </div>

    <div class="form-card">
        <div class="form-header">
            <h2><i class="fas fa-{{ $editingAnnouncement ? 'edit' : 'plus-circle' }}"></i> {{ $editingAnnouncement ? 'Edit Announcement' : 'Create New Announcement' }}</h2>
            @if ($editingAnnouncement)
                <a href="{{ route('admin.announcements.index') }}" class="btn-admin btn-outline">Cancel Edit</a>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.announcements.index', request()->query()) }}">
            @csrf
            <input type="hidden" name="action" value="{{ $editingAnnouncement ? 'update' : 'create' }}">
            @if ($editingAnnouncement)
                <input type="hidden" name="announcement_id" value="{{ $editingAnnouncement->id }}">
            @endif

            <div class="form-group">
                <label class="form-label"><i class="fas fa-heading"></i> Title</label>
                <input type="text" name="title" class="form-control" required maxlength="200" value="{{ old('title', $editingAnnouncement->title ?? '') }}" placeholder="Enter announcement title...">
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fas fa-align-left"></i> Content</label>
                <div class="editor-wrapper">
                    <div class="toolbar-row">
                        <button type="button" class="tool-btn compact" onclick="wrapSelection('**', '**')" title="Bold"><i class="fas fa-bold"></i></button>
                        <button type="button" class="tool-btn compact" onclick="wrapSelection('*', '*')" title="Italic"><i class="fas fa-italic"></i></button>
                        <button type="button" class="tool-btn compact" onclick="wrapSelection('<u>', '</u>')" title="Underline"><i class="fas fa-underline"></i></button>
                        <div class="tool-divider"></div>
                        <button type="button" class="tool-btn compact" onclick="formatHeading(1)" title="Heading 1">H1</button>
                        <button type="button" class="tool-btn compact" onclick="formatHeading(2)" title="Heading 2">H2</button>
                        <button type="button" class="tool-btn compact" onclick="toggleList('- ')" title="Bullet list"><i class="fas fa-list-ul"></i></button>
                        <button type="button" class="tool-btn compact" onclick="toggleList('1. ')" title="Numbered list"><i class="fas fa-list-ol"></i></button>
                        <button type="button" class="tool-btn compact" onclick="wrapSelection('> ', '')" title="Quote"><i class="fas fa-quote-right"></i></button>
                        <button type="button" class="tool-btn compact" onclick="insertLink()" title="Link"><i class="fas fa-link"></i></button>
                        <button type="button" class="tool-btn compact" onclick="wrapSelection('`', '`')" title="Code"><i class="fas fa-code"></i></button>
                        <button type="button" class="tool-btn compact" onclick="togglePreview()" title="Toggle preview"><i class="fas fa-eye"></i> Preview</button>
                        <button type="button" class="tool-btn compact" onclick="clearContent()" title="Clear"><i class="fas fa-trash"></i> Clear</button>
                    </div>
                    <textarea name="content" id="announcementContent" class="form-control" rows="8" required placeholder="Write your announcement in Markdown...">{{ old('content', $editingAnnouncement->content ?? '') }}</textarea>
                    <div class="editor-note">Markdown is supported. Use the buttons above to insert formatting.</div>
                    <div class="preview-box" id="announcementPreview"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-flag"></i> Priority</label>
                    <select name="priority" class="form-control">
                        @foreach (['info' => 'Info - General Information', 'success' => 'Success - Positive Update', 'warning' => 'Warning - Important Notice', 'danger' => 'Urgent - Critical Update'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', $editingAnnouncement->priority ?? 'info') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @unless ($editingAnnouncement)
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-users"></i> Target Audience</label>
                        <select name="target" class="form-control">
                            <option value="all" @selected(old('target', 'all') === 'all')>All Users</option>
                            <option value="active" @selected(old('target') === 'active')>Active Users Only</option>
                        </select>
                    </div>
                @endunless
            </div>

            <div class="form-row">
                @unless ($editingAnnouncement)
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar"></i> Schedule (Optional)</label>
                        <input type="datetime-local" name="schedule_date" class="form-control" value="{{ old('schedule_date') }}">
                        <small style="color:var(--text-muted);">Leave empty to send immediately</small>
                    </div>
                @endunless
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-calendar-times"></i> Expiry Date</label>
                    <input type="datetime-local" name="expiry_date" class="form-control" value="{{ old('expiry_date', $editingAnnouncement?->expires_at?->format('Y-m-d\TH:i') ?? now()->addDays(30)->format('Y-m-d\TH:i')) }}">
                </div>
            </div>

            @unless ($editingAnnouncement)
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-bell"></i> Delivery Methods</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="send_email" @checked(old('send_email', true))>
                            <i class="fas fa-envelope"></i> Send via Email
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="send_notification" @checked(old('send_notification', true))>
                            <i class="fas fa-bell"></i> Send via In-App Notification
                        </label>
                    </div>
                </div>
            @endunless

            <div style="display:flex;gap:1rem;margin-top:1rem;">
                <button type="submit" class="btn-admin btn-admin-primary">
                    <i class="fas fa-{{ $editingAnnouncement ? 'save' : 'paper-plane' }}"></i>
                    {{ $editingAnnouncement ? 'Update Announcement' : 'Create & Send' }}
                </button>
            </div>
        </form>
    </div>

    <h2 style="margin:1.5rem 0 1rem;">All Announcements</h2>

    @if ($announcements->isEmpty())
        <div class="empty-state">
            <i class="fas fa-bullhorn" style="font-size:3rem;color:#cbd5e1;margin-bottom:1rem;"></i>
            <h3>No Announcements Yet</h3>
            <p style="color:var(--text-muted);">Create your first announcement to reach all users</p>
        </div>
    @else
        <div class="announcement-list">
            @foreach ($announcements as $announcement)
                @php
                    $priorityClass = 'priority-' . ($announcement->priority ?? 'info');
                    $isExpired = $announcement->expires_at && $announcement->expires_at->isPast();
                    $isScheduled = $announcement->scheduled_for && $announcement->scheduled_for->isFuture();
                @endphp
                <div class="announcement-card">
                    <div class="announcement-header">
                        <div>
                            <span class="priority-badge {{ $priorityClass }}">{{ strtoupper($announcement->priority ?? 'INFO') }}</span>
                            <span class="announcement-title">{{ $announcement->title }}</span>
                        </div>
                        <div class="announcement-meta">
                            <span><i class="fas fa-user"></i> {{ $announcement->created_by_name }}</span>
                            <span><i class="far fa-calendar"></i> {{ optional($announcement->created_at)->format('M j, Y g:i A') }}</span>
                            @if ($isScheduled)
                                <span style="color:#f59e0b;"><i class="fas fa-clock"></i> Scheduled: {{ $announcement->scheduled_for->format('M j, Y g:i A') }}</span>
                            @endif
                            @if ($isExpired)
                                <span style="color:#ef4444;"><i class="fas fa-ban"></i> Expired</span>
                            @endif
                            <span><i class="fas fa-eye"></i> {{ $announcement->stats['views'] ?? 0 }} views</span>
                            <span><i class="fas fa-check-circle"></i> {{ $announcement->stats['read'] ?? 0 }} read</span>
                        </div>
                    </div>
                    <div class="announcement-content">{{ \Illuminate\Support\Str::limit($announcement->content_preview, 300) }}</div>
                    <div class="announcement-actions">
                        @if ($isScheduled)
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Send this announcement now?')">
                                @csrf
                                <input type="hidden" name="action" value="send_now">
                                <input type="hidden" name="announcement_id" value="{{ $announcement->id }}">
                                <button type="submit" class="action-btn"><i class="fas fa-paper-plane"></i> Send Now</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.announcements.index', ['edit' => $announcement->id]) }}" class="action-btn"><i class="fas fa-edit"></i> Edit</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this announcement?')">
                            @csrf
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="announcement_id" value="{{ $announcement->id }}">
                            <button type="submit" class="action-btn delete"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        updateAnnouncementPreview();
        const textarea = getAnnouncementTextarea();
        if (textarea) {
            textarea.addEventListener('input', updateAnnouncementPreview);
        }
    });

    function getAnnouncementTextarea() {
        return document.getElementById('announcementContent');
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function wrapSelection(before, after) {
        const textarea = getAnnouncementTextarea();
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = textarea.value.substring(start, end);
        const replacement = before + (selected || 'text') + after;

        textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + before.length, start + before.length + (selected.length || 4));
        updateAnnouncementPreview();
    }

    function formatHeading(level) {
        const textarea = getAnnouncementTextarea();
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
        updateAnnouncementPreview();
    }

    function toggleList(marker) {
        const textarea = getAnnouncementTextarea();
        if (!textarea) return;

        const value = textarea.value;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const startOfLine = value.lastIndexOf('\n', start - 1) + 1;
        const endOfLine = value.indexOf('\n', end);
        const lineEnd = endOfLine === -1 ? value.length : endOfLine;
        const lines = value.substring(startOfLine, lineEnd).split('\n');

        const replacement = lines.map((line, index) => {
            const trimmed = line.trim();
            if (trimmed === '') {
                return line;
            }

            const stripped = line.replace(/^([-*+]\s+|\d+\.\s+)/, '');

            if (marker === '- ') {
                return '- ' + stripped;
            }

            return `${index + 1}. ${stripped}`;
        }).join('\n');

        textarea.value = value.substring(0, startOfLine) + replacement + value.substring(lineEnd);
        textarea.focus();
        textarea.setSelectionRange(startOfLine, startOfLine + replacement.length);
        updateAnnouncementPreview();
    }

    function insertLink() {
        const textarea = getAnnouncementTextarea();
        if (!textarea) return;

        const url = prompt('Enter the link URL');
        if (!url) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = textarea.value.substring(start, end) || 'link text';
        const replacement = `[${selected}](${url})`;

        textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + 1, start + 1 + selected.length);
        updateAnnouncementPreview();
    }

    function renderAnnouncementMarkdown(text) {
        const codeBlocks = [];
        let prepared = text.replace(/```([\s\S]*?)```/g, (_, code) => {
            const placeholder = `__ANN_CODE_${codeBlocks.length}__`;
            codeBlocks.push(`<pre><code>${escapeHtml(code.trim())}</code></pre>`);
            return placeholder;
        });

        prepared = escapeHtml(prepared)
            .replace(/(^|\n)######\s+(.*)/g, '$1<h6>$2</h6>')
            .replace(/(^|\n)#####\s+(.*)/g, '$1<h5>$2</h5>')
            .replace(/(^|\n)####\s+(.*)/g, '$1<h4>$2</h4>')
            .replace(/(^|\n)###\s+(.*)/g, '$1<h3>$2</h3>')
            .replace(/(^|\n)##\s+(.*)/g, '$1<h2>$2</h2>')
            .replace(/(^|\n)#\s+(.*)/g, '$1<h1>$2</h1>')
            .replace(/(^|\n)&gt;\s+(.*)/g, '$1<blockquote>$2</blockquote>')
            .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+|\/[^\s)]*|#[^\s)]*|mailto:[^\s)]+|tel:[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(?!\s)([^*\n]+?)(?<!\s)\*/g, '<em>$1</em>')
            .replace(/&lt;u&gt;(.*?)&lt;\/u&gt;/g, '<u>$1</u>')
            .replace(/`([^`\n]+)`/g, '<code>$1</code>');

        const blocks = prepared
            .split(/\n\s*\n/)
            .map(block => {
                const trimmed = block.trim();

                if (trimmed === '') {
                    return '';
                }

                if (/^__ANN_CODE_\d+__$/.test(trimmed)) {
                    return trimmed;
                }

                if (/^<(h[1-6]|blockquote|pre|ul|ol|li|p)/.test(trimmed)) {
                    return trimmed;
                }

                const lines = trimmed.split('\n');
                const isBulleted = lines.every(line => /^[-*+]\s+/.test(line));
                const isNumbered = lines.every(line => /^\d+\.\s+/.test(line));

                if (isBulleted) {
                    return `<ul>${lines.map(line => `<li>${line.replace(/^[-*+]\s+/, '')}</li>`).join('')}</ul>`;
                }

                if (isNumbered) {
                    return `<ol>${lines.map(line => `<li>${line.replace(/^\d+\.\s+/, '')}</li>`).join('')}</ol>`;
                }

                return `<p>${trimmed.replace(/\n/g, '<br>')}</p>`;
            })
            .filter(Boolean)
            .join('');

        return blocks.replace(/__ANN_CODE_(\d+)__/g, (_, index) => codeBlocks[Number(index)] ?? '');
    }

    function updateAnnouncementPreview() {
        const textarea = getAnnouncementTextarea();
        const preview = document.getElementById('announcementPreview');
        if (!textarea || !preview) return;

        if (!textarea.value.trim()) {
            preview.classList.remove('active');
            preview.innerHTML = '';
            return;
        }

        preview.innerHTML = renderAnnouncementMarkdown(textarea.value);
        if (preview.classList.contains('active')) {
            preview.scrollTop = 0;
        }
    }

    function togglePreview() {
        const preview = document.getElementById('announcementPreview');
        if (!preview) return;

        preview.classList.toggle('active');
        if (preview.classList.contains('active')) {
            updateAnnouncementPreview();
        }
    }

    function clearContent() {
        const textarea = getAnnouncementTextarea();
        if (!textarea) return;

        if (textarea.value && confirm('Clear the announcement content?')) {
            textarea.value = '';
            updateAnnouncementPreview();
        }
    }
</script>
@endpush
