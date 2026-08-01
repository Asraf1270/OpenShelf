@extends('layouts.app')

@section('content')
<div class="help-support-shell">
    <div class="help-support-card">
        <div class="help-support-header">
            <span class="help-support-kicker">OpenShelf Support</span>
            <h1>Help & Support</h1>
            <p>Find the answer you need, or reach out to the team.</p>
        </div>

        <div class="help-support-grid">
            <a href="{{ route('faq') }}" class="help-support-tile">
                <i class="fas fa-circle-question"></i>
                <span>FAQs</span>
                <small>Common account and borrowing questions</small>
            </a>

            <a href="{{ route('guidelines') }}" class="help-support-tile">
                <i class="fas fa-book-open-reader"></i>
                <span>Borrowing Rules</span>
                <small>Community guidelines and borrowing expectations</small>
            </a>

            <a href="{{ route('contact') }}" class="help-support-tile">
                <i class="fas fa-envelope"></i>
                <span>Contact Support</span>
                <small>Send a message to the OpenShelf team</small>
            </a>

            <a href="{{ route('support-us') }}" class="help-support-tile">
                <i class="fas fa-heart"></i>
                <span>Support Us</span>
                <small>Help keep the campus sharing community strong</small>
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .help-support-shell {
        width: 100%;
        padding: 1rem 0.75rem 2rem;
    }

    .help-support-card {
        max-width: 880px;
        margin: 0 auto;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 1.5rem;
        padding: 1rem;
        box-shadow: var(--shadow-md);
    }

    .help-support-header {
        margin-bottom: 1.25rem;
    }

    .help-support-kicker {
        display: inline-block;
        margin-bottom: 0.5rem;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--primary);
    }

    .help-support-header h1 {
        margin: 0;
        font-size: 1.7rem;
        line-height: 1.15;
        font-weight: 800;
    }

    .help-support-header p {
        margin: 0.55rem 0 0;
        color: var(--text-secondary);
    }

    .help-support-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.9rem;
    }

    .help-support-tile {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        padding: 1rem;
        border-radius: 1rem;
        border: 1px solid var(--border);
        background: var(--bg);
        color: var(--text);
        text-decoration: none;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .help-support-tile:hover {
        transform: translateY(-2px);
        border-color: rgba(44, 62, 80, 0.28);
        box-shadow: var(--shadow-sm);
    }

    .help-support-tile i {
        color: var(--primary);
        font-size: 1.05rem;
    }

    .help-support-tile span {
        font-weight: 800;
        font-size: 1rem;
    }

    .help-support-tile small {
        color: var(--text-secondary);
        line-height: 1.5;
    }

    @media (min-width: 768px) {
        .help-support-shell {
            padding: 2rem 1rem 3rem;
        }

        .help-support-card {
            padding: 1.5rem;
        }

        .help-support-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    [data-theme="dark"] .help-support-card,
    [data-theme="dark"] .help-support-tile {
        border-color: var(--border);
    }

    [data-theme="dark"] .help-support-tile {
        background: rgba(15, 23, 42, 0.55);
    }
</style>
@endpush
