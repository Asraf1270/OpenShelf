@extends('admin.layouts.app')

@section('title', 'System Control - OpenShelf Admin')
@section('page_title', 'System Control')

@push('styles')
<style>
    .system-commands-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    .command-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .command-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }
    .command-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .command-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
    }
    .icon-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .icon-green { background: linear-gradient(135deg, #10b981, #059669); }
    .icon-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .icon-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
    .icon-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .command-title {
        font-weight: 700;
        font-size: 1.1rem;
        margin: 0;
    }
    .command-command {
        font-family: monospace;
        font-size: 0.8rem;
        color: var(--text-muted);
        background: rgba(0,0,0,0.05);
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        margin-top: 0.25rem;
        display: inline-block;
    }
    [data-theme="dark"] .command-command { background: rgba(255,255,255,0.1); }
    .command-desc {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
        flex-grow: 1;
        line-height: 1.5;
    }
    .command-btn {
        width: 100%;
        padding: 0.75rem;
        border-radius: 12px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: opacity 0.2s;
    }
    .command-btn:hover {
        opacity: 0.9;
    }
    .command-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .command-btn.btn-blue { background: #eff6ff; color: #2563eb; }
    .command-btn.btn-green { background: #ecfdf5; color: #059669; }
    .command-btn.btn-orange { background: #fffbeb; color: #d97706; }
    .command-btn.btn-purple { background: #f5f3ff; color: #6d28d9; }
    .command-btn.btn-red { background: #fef2f2; color: #dc2626; }
    
    [data-theme="dark"] .command-btn.btn-blue { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
    [data-theme="dark"] .command-btn.btn-green { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    [data-theme="dark"] .command-btn.btn-orange { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
    [data-theme="dark"] .command-btn.btn-purple { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
    [data-theme="dark"] .command-btn.btn-red { background: rgba(239, 68, 68, 0.2); color: #f87171; }

    #commandOutputModal .modal-content {
        max-width: 600px;
    }
    .output-pre {
        background: #1e293b;
        color: #e2e8f0;
        padding: 1rem;
        border-radius: 12px;
        font-family: monospace;
        font-size: 0.85rem;
        overflow-x: auto;
        white-space: pre-wrap;
        margin-top: 1rem;
        max-height: 300px;
        overflow-y: auto;
    }
</style>
@endpush

@section('content')
<div class="welcome-banner" style="padding: 2rem; border-radius: 24px; margin-bottom: 2rem; background: linear-gradient(135deg, #1e293b, #0f172a); color: white;">
    <h2 style="margin: 0 0 0.5rem; font-size: 1.8rem;"><i class="fas fa-terminal"></i> System Control Panel</h2>
    <p style="margin: 0; opacity: 0.8; font-size: 0.95rem;">Execute safe Laravel Artisan commands without SSH access. Ideal for shared hosting environments.</p>
</div>

<div class="system-commands-grid">
    <!-- Optimize Clear -->
    <div class="command-card">
        <div class="command-header">
            <div class="command-icon icon-red"><i class="fas fa-trash-alt"></i></div>
            <div>
                <h3 class="command-title">Optimize Clear</h3>
                <span class="command-command">php artisan optimize:clear</span>
            </div>
        </div>
        <div class="command-desc">
            Clears all cached files including views, cache, route, config, and compiled services. Use this when experiencing stale data or after deploying updates.
        </div>
        <button class="command-btn btn-red" onclick="runCommand('optimize:clear', this)">
            <i class="fas fa-bolt"></i> Run Optimize Clear
        </button>
    </div>

    <!-- Storage Link -->
    <div class="command-card">
        <div class="command-header">
            <div class="command-icon icon-blue"><i class="fas fa-link"></i></div>
            <div>
                <h3 class="command-title">Storage Link</h3>
                <span class="command-command">php artisan storage:link</span>
            </div>
        </div>
        <div class="command-desc">
            Creates the symbolic links configured for the application. Crucial for serving uploaded files (like local avatars and book covers) directly from the public disk.
        </div>
        <button class="command-btn btn-blue" onclick="runCommand('storage:link', this)">
            <i class="fas fa-play"></i> Run Storage Link
        </button>
    </div>

    <!-- Cache Clear -->
    <div class="command-card">
        <div class="command-header">
            <div class="command-icon icon-orange"><i class="fas fa-eraser"></i></div>
            <div>
                <h3 class="command-title">Cache Clear</h3>
                <span class="command-command">php artisan cache:clear</span>
            </div>
        </div>
        <div class="command-desc">
            Flushes the application cache. Use this to clear temporarily stored data (like cached database queries or external API responses).
        </div>
        <button class="command-btn btn-orange" onclick="runCommand('cache:clear', this)">
            <i class="fas fa-play"></i> Run Cache Clear
        </button>
    </div>

    <!-- Config Cache -->
    <div class="command-card">
        <div class="command-header">
            <div class="command-icon icon-green"><i class="fas fa-cogs"></i></div>
            <div>
                <h3 class="command-title">Config Cache</h3>
                <span class="command-command">php artisan config:cache</span>
            </div>
        </div>
        <div class="command-desc">
            Creates a cache file for faster configuration loading. Run this after modifying `.env` variables on production.
        </div>
        <button class="command-btn btn-green" onclick="runCommand('config:cache', this)">
            <i class="fas fa-play"></i> Run Config Cache
        </button>
    </div>

    <!-- Route Cache -->
    <div class="command-card">
        <div class="command-header">
            <div class="command-icon icon-purple"><i class="fas fa-route"></i></div>
            <div>
                <h3 class="command-title">Route Cache</h3>
                <span class="command-command">php artisan route:cache</span>
            </div>
        </div>
        <div class="command-desc">
            Creates a route cache file for faster route registration. Recommended for production environments to speed up requests.
        </div>
        <button class="command-btn btn-purple" onclick="runCommand('route:cache', this)">
            <i class="fas fa-play"></i> Run Route Cache
        </button>
    </div>

    <!-- View Cache -->
    <div class="command-card">
        <div class="command-header">
            <div class="command-icon icon-blue" style="background: linear-gradient(135deg, #06b6d4, #0891b2);"><i class="fas fa-layer-group"></i></div>
            <div>
                <h3 class="command-title">View Cache</h3>
                <span class="command-command">php artisan view:cache</span>
            </div>
        </div>
        <div class="command-desc">
            Compiles all of the application's Blade templates. Improves performance since templates won't be compiled on the fly.
        </div>
        <button class="command-btn btn-blue" style="color: #0891b2; background: rgba(6, 182, 212, 0.1);" onclick="runCommand('view:cache', this)">
            <i class="fas fa-play"></i> Run View Cache
        </button>
    </div>

    <!-- Database Migration -->
    <div class="command-card">
        <div class="command-header">
            <div class="command-icon icon-green" style="background: linear-gradient(135deg, #14b8a6, #0f766e);"><i class="fas fa-database"></i></div>
            <div>
                <h3 class="command-title">Database Migration</h3>
                <span class="command-command">php artisan migrate</span>
            </div>
        </div>
        <div class="command-desc">
            Runs pending Laravel database migrations to apply the latest schema changes safely from the admin panel.
        </div>
        <button class="command-btn btn-green" style="color: #0f766e; background: rgba(20, 184, 166, 0.12);" onclick="runCommand('migrate', this)">
            <i class="fas fa-play"></i> Run Migration
        </button>
    </div>
</div>

<!-- Modal for Output -->
<div id="commandOutputModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin:0;"><i class="fas fa-terminal"></i> Command Output</h3>
        </div>
        <div class="modal-body">
            <div id="commandStatus" style="font-weight: 600; margin-bottom: 0.5rem;"></div>
            <pre id="commandOutputText" class="output-pre"></pre>
        </div>
        <div class="modal-footer">
            <button class="btn-outline" style="padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer;" onclick="closeModal('commandOutputModal')">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function runCommand(command, btnElement) {
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running...';
        btnElement.disabled = true;

        try {
            const formData = new FormData();
            formData.append('command', command);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            const response = await fetch("{{ route('admin.system-control.execute') }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            
            const statusEl = document.getElementById('commandStatus');
            const outputEl = document.getElementById('commandOutputText');
            
            if (result.success) {
                statusEl.innerHTML = `<span style="color: #10b981;"><i class="fas fa-check-circle"></i> ${result.message}</span>`;
            } else {
                statusEl.innerHTML = `<span style="color: #ef4444;"><i class="fas fa-times-circle"></i> Error: ${result.message}</span>`;
            }
            
            outputEl.textContent = result.output || 'No output produced.';
            document.getElementById('commandOutputModal').classList.add('active');

        } catch (error) {
            alert('A network error occurred while executing the command.');
        } finally {
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        }
    }
</script>
@endpush
