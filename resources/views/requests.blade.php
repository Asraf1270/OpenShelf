@extends('layouts.app')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        50: '#f0fdf9',
                        500: '#4c9f8a',
                        600: '#2d5d7a',
                        700: '#234c63'
                    }
                }
            }
        }
    };
</script>
<style>
    .requests-page .tab-content { display: none; }
    .requests-page .tab-content.active { display: block; }
    .requests-page .tab-btn.active {
        background: linear-gradient(135deg, #4c9f8a 0%, #2d5d7a 100%);
        color: #fff;
    }
    .requests-page .status-badge.pending { background: rgba(245, 158, 11, 0.12); color: #b45309; }
    .requests-page .status-badge.approved { background: rgba(16, 185, 129, 0.12); color: #047857; }
    .requests-page .status-badge.rejected { background: rgba(239, 68, 68, 0.12); color: #b91c1c; }
    .requests-page .status-badge.pending_return { background: rgba(76, 159, 138, 0.12); color: #2d5d7a; }

    /* Dark Mode Overrides */
    [data-theme="dark"] .requests-page .bg-white { background-color: #1e293b; border-color: #334155; }
    [data-theme="dark"] .requests-page .border-slate-200 { border-color: #334155; }
    [data-theme="dark"] .requests-page .text-slate-900 { color: #f8fafc; }
    [data-theme="dark"] .requests-page .text-slate-700 { color: #cbd5e1; }
    [data-theme="dark"] .requests-page .text-slate-600 { color: #94a3b8; }
    [data-theme="dark"] .requests-page .text-slate-500 { color: #64748b; }
    [data-theme="dark"] .requests-page .text-slate-400 { color: #94a3b8; }
    
    [data-theme="dark"] .requests-page .bg-slate-50\/80 { background-color: #0f172a; border-color: #334155; }
    [data-theme="dark"] .requests-page .bg-amber-50 { background-color: #451a03; border-color: #78350f; }
    [data-theme="dark"] .requests-page .border-amber-100 { border-color: #78350f; }
    [data-theme="dark"] .requests-page .text-amber-700 { color: #fcd34d; }
    [data-theme="dark"] .requests-page .bg-emerald-50 { background-color: #022c22; border-color: #064e3b; }
    [data-theme="dark"] .requests-page .border-emerald-200 { border-color: #064e3b; }
    [data-theme="dark"] .requests-page .text-emerald-700 { color: #6ee7b7; }
    [data-theme="dark"] .requests-page .bg-rose-50 { background-color: #4c0519; border-color: #881337; }
    [data-theme="dark"] .requests-page .border-rose-100 { border-color: #881337; }
    [data-theme="dark"] .requests-page .border-rose-200 { border-color: #881337; }
    [data-theme="dark"] .requests-page .text-rose-700 { color: #fda4af; }

    [data-theme="dark"] .requests-page .bg-amber-100 { background-color: #78350f; color: #fcd34d; }
    [data-theme="dark"] .requests-page .bg-emerald-100 { background-color: #022c22; color: #6ee7b7; }
    [data-theme="dark"] .requests-page .bg-rose-100 { background-color: #4c0519; color: #fda4af; }
    [data-theme="dark"] .requests-page .bg-sky-100 { background-color: #082f49; color: #7dd3fc; }
    
    [data-theme="dark"] .requests-page .text-amber-600 { color: #fcd34d; }
    [data-theme="dark"] .requests-page .text-emerald-600 { color: #6ee7b7; }
    [data-theme="dark"] .requests-page .text-rose-600 { color: #fda4af; }
    [data-theme="dark"] .requests-page .text-sky-600 { color: #7dd3fc; }



    
    .requests-page .request-card-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1rem; }
    .requests-page .request-card-actions > a { flex: 1 1 0; min-width: 0; }
    .requests-page .request-contact-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        border-radius: 0.9rem;
        padding: 0.72rem 1rem;
        font-size: 0.82rem;
        font-weight: 700;
        color: #2d5d7a;
        background: #eef8f6;
        border: 1px solid #cfe7df;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .requests-page .request-contact-link:hover {
        background: #dff2ee;
        color: #234c63;
        transform: translateY(-1px);
    }
    .requests-page .request-book-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        border-radius: 0.9rem;
        padding: 0.72rem 1rem;
        font-size: 0.82rem;
        font-weight: 700;
        color: #fff;
        background: #0f172a;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .requests-page .request-book-link:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    [data-theme="dark"] .requests-page .status-badge.pending { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
    [data-theme="dark"] .requests-page .status-badge.approved { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
    [data-theme="dark"] .requests-page .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #fda4af; }
    [data-theme="dark"] .requests-page .status-badge.pending_return { background: rgba(76, 159, 138, 0.2); color: #94a3b8; }
    [data-theme="dark"] .requests-page .request-contact-link {
        background: rgba(76, 159, 138, 0.14);
        border-color: rgba(76, 159, 138, 0.35);
        color: #c7f9ea;
    }
    [data-theme="dark"] .requests-page .request-contact-link:hover {
        background: rgba(76, 159, 138, 0.24);
        color: #f8fafc;
    }
    [data-theme="dark"] .requests-page .request-book-link {
        background: #1e293b;
    }
    [data-theme="dark"] .requests-page .request-book-link:hover {
        background: #334155;
    }
    
    [data-theme="dark"] .requests-page .empty-state { background-color: #1e293b; border-color: #334155; }
    [data-theme="dark"] .requests-page .empty-state h3 { color: #f8fafc; }
    [data-theme="dark"] .requests-page .empty-state p { color: #94a3b8; }
    [data-theme="dark"] .requests-page .empty-state i { color: #4c9f8a; }

    .requests-page .btn:disabled {
        cursor: not-allowed;
        opacity: 0.55;
        transform: none;
    }
</style>
@endpush

@section('content')
<div class="requests-page">
    <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
        <div class="overflow-hidden rounded-[28px] bg-gradient-to-br from-brand-500 via-teal-600 to-brand-600 p-6 text-white shadow-[0_20px_45px_rgba(45,93,122,0.22)] sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/15 px-3 py-1 text-[0.72rem] font-semibold uppercase tracking-[0.2em] text-white/90">
                        <i class="fas fa-layer-group"></i>
                        Borrowing hub
                    </div>
                    <h1 class="mb-2 flex items-center gap-2 text-2xl font-semibold sm:text-3xl">
                        <i class="fas fa-exchange-alt"></i>
                        My Requests
                    </h1>
                    <p class="max-w-2xl text-sm text-white/90 sm:text-base">
                        Track incoming requests, approve or reject borrowals, and manage your return flow from one calm dashboard.
                    </p>
                </div>
                <a href="{{ route('books') }}" class="inline-flex items-center justify-center rounded-full border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                    Browse Books
                </a>
            </div>
        </div>

        @if ($message)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                <i class="fas fa-check-circle mr-2"></i> {{ $message }}
            </div>
        @endif

        @if ($error)
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                <i class="fas fa-exclamation-circle mr-2"></i> {{ $error }}
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div>
                        <div class="text-xl font-semibold text-amber-600">{{ $stats['pending'] }}</div>
                        <div class="text-sm font-medium text-slate-500">Pending</div>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="text-xl font-semibold text-emerald-600">{{ $stats['approved'] }}</div>
                        <div class="text-sm font-medium text-slate-500">Approved</div>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <div class="text-xl font-semibold text-rose-600">{{ $stats['rejected'] }}</div>
                        <div class="text-sm font-medium text-slate-500">Rejected</div>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                    <div>
                        <div class="text-xl font-semibold text-sky-600">{{ $stats['returned'] }}</div>
                        <div class="text-sm font-medium text-slate-500">Returned</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="inline-flex flex-wrap gap-2 rounded-full border border-slate-200 bg-white p-2 shadow-sm">
            <button type="button" class="tab-btn active inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-slate-600" data-tab="received">
                <i class="fas fa-inbox"></i> Received ({{ $receivedRequests->count() }})
            </button>
            <button type="button" class="tab-btn inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-slate-600" data-tab="sent">
                <i class="fas fa-paper-plane"></i> Sent ({{ $sentRequests->count() }})
            </button>
        </div>

    <div id="received-tab" class="tab-content active">
        @if ($receivedRequests->isEmpty())
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Received Requests</h3>
                <p>When someone requests to borrow your books, they'll appear here.</p>
                <a href="{{ route('books') }}" class="btn btn-outline" style="margin-top: 1rem;">Browse Books</a>
            </div>
        @else
            @foreach ($receivedRequests as $request)
                @php
                    $borrower = $request->borrower;
                    $owner = $request->owner;
                    $statusLabel = ucwords(str_replace('_', ' ', $request->status));
                    $borrowerPhone = $borrower?->phone ? preg_replace('/[^0-9]/', '', $borrower->phone) : '';
                    if (strlen($borrowerPhone) === 11) {
                        $borrowerPhone = '88' . $borrowerPhone;
                    }
                    $borrowerGender = strtolower($borrower?->gender ?? '');
                    $ownerGender = strtolower($owner?->gender ?? '');
                    $canShowBorrowerContact = $borrowerGender !== '' && $ownerGender !== '' && $borrowerGender === $ownerGender;
                    $borrowerContactMessage = 'Hello ' . rawurlencode($request->borrower_name ?? 'Borrower') . '%0A%0A'
                        . 'I am following up on your request for "%22' . rawurlencode($request->book_title ?? 'this book') . '%22" on OpenShelf.%0A%0A'
                        . 'Please let me know the best time to coordinate the borrowing details.';
                @endphp
                <div class="overflow-hidden rounded-[22px] border border-slate-200 bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)] sm:p-5">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('book.show', ['id' => $request->book_id]) }}" class="block text-base font-semibold leading-6 text-slate-900 transition hover:text-brand-600">
                                {{ $request->book_title }}
                            </a>
                            <div class="mt-1 text-sm text-slate-500">by {{ $request->book_author ?? 'Unknown' }}</div>
                        </div>
                        <span class="status-badge {{ $request->status }} shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.14em]">{{ $statusLabel }}</span>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3 shadow-sm">
                        <div class="grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Requester</div>
                                <div class="mt-0.5 font-medium text-slate-700">{{ $request->borrower_name }}</div>
                            </div>
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Contact</div>
                                <div class="mt-0.5 font-medium text-slate-700">{{ $borrower?->phone ?: 'Not provided' }}</div>
                            </div>
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Requested</div>
                                <div class="mt-0.5 font-medium text-slate-700">{{ $request->request_date?->format('M j, Y') ?? 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Duration</div>
                                <div class="mt-0.5 font-medium text-slate-700">{{ $request->duration_days ?? 14 }} days</div>
                            </div>
                            @if ($request->expected_return_date)
                                <div class="sm:col-span-2">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Due</div>
                                    <div class="mt-0.5 font-medium text-slate-700">{{ $request->expected_return_date->format('M j, Y') }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($request->message)
                        <div class="mt-3 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2.5 text-sm text-slate-700">
                            <div class="mb-1 flex items-center gap-2 text-[0.75rem] font-semibold uppercase tracking-[0.14em] text-amber-700">
                                <i class="fas fa-comment-dots"></i>
                                User note
                            </div>
                            <div class="leading-6">{!! nl2br(e($request->message)) !!}</div>
                        </div>
                    @endif

                    <div class="mt-3 space-y-2">
                        @if ($request->status === 'pending')
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="inline-flex flex-1 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700" onclick="approveRequest('{{ $request->id }}')">
                                    <i class="fas fa-check mr-2"></i> Approve
                                </button>
                                <button type="button" class="reject-request-btn inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" data-request-id="{{ $request->id }}">
                                    <i class="fas fa-times mr-2"></i> Reject
                                </button>
                            </div>
                        @endif
                        <div class="request-card-actions">
                            @if ($canShowBorrowerContact && $borrowerPhone)
                                <a href="https://wa.me/{{ $borrowerPhone }}?text={{ $borrowerContactMessage }}" target="_blank" rel="noopener" class="request-contact-link">
                                    <i class="fab fa-whatsapp"></i> Contact
                                </a>
                            @endif
                            <a href="{{ route('book.show', ['id' => $request->book_id]) }}" class="request-book-link">
                                <i class="fas fa-book"></i> View Book
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div id="sent-tab" class="tab-content">
        @if ($sentRequests->isEmpty())
            <div class="empty-state">
                <i class="fas fa-paper-plane"></i>
                <h3>No Sent Requests</h3>
                <p>When you request books from others, they'll appear here.</p>
                <a href="{{ route('books') }}" class="btn btn-outline" style="margin-top: 1rem;">Browse Books</a>
            </div>
        @else
            @foreach ($sentRequests as $request)
                @php
                    $borrower = $request->borrower;
                    $owner = $request->owner;
                    $statusLabel = ucwords(str_replace('_', ' ', $request->status));
                    $ownerPhone = $owner?->phone ? preg_replace('/[^0-9]/', '', $owner->phone) : '';
                    if (strlen($ownerPhone) === 11) {
                        $ownerPhone = '88' . $ownerPhone;
                    }
                    $borrowerGender = strtolower($borrower?->gender ?? '');
                    $ownerGender = strtolower($owner?->gender ?? '');
                    $canShowOwnerContact = $borrowerGender !== '' && $ownerGender !== '' && $borrowerGender === $ownerGender;
                    $ownerContactMessage = 'Hello ' . rawurlencode($request->owner_name ?? 'Owner') . '%0A%0A'
                        . 'I am following up on the request for "%22' . rawurlencode($request->book_title ?? 'this book') . '%22" on OpenShelf.%0A%0A'
                        . 'Please confirm the next step so I can complete the borrowing process.';
                @endphp
                <div class="overflow-hidden rounded-[22px] border border-slate-200 bg-white p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)] sm:p-5">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('book.show', ['id' => $request->book_id]) }}" class="block text-base font-semibold leading-6 text-slate-900 transition hover:text-brand-600">
                                {{ $request->book_title }}
                            </a>
                            <div class="mt-1 text-sm text-slate-500">by {{ $request->book_author ?? 'Unknown' }}</div>
                        </div>
                        <span class="status-badge {{ $request->status }} shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.14em]">{{ $statusLabel }}</span>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3 shadow-sm">
                        <div class="grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Owner</div>
                                <div class="mt-0.5 font-medium text-slate-700">{{ $request->owner_name }}</div>
                            </div>
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Requested</div>
                                <div class="mt-0.5 font-medium text-slate-700">{{ $request->request_date?->format('M j, Y') ?? 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Duration</div>
                                <div class="mt-0.5 font-medium text-slate-700">{{ $request->duration_days ?? 14 }} days</div>
                            </div>
                            @if ($request->expected_return_date)
                                <div class="sm:col-span-2">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">Due</div>
                                    <div class="mt-0.5 font-medium text-slate-700">{{ $request->expected_return_date->format('M j, Y') }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($request->status === 'rejected' && $request->rejection_reason)
                        <div class="mt-3 rounded-xl border border-rose-100 bg-rose-50 px-3 py-2.5 text-sm text-slate-700">
                            <div class="mb-1 flex items-center gap-2 text-[0.75rem] font-semibold uppercase tracking-[0.14em] text-rose-700">
                                <i class="fas fa-times-circle"></i>
                                Rejection reason
                            </div>
                            <div class="leading-6">{{ $request->rejection_reason }}</div>
                        </div>
                    @endif

                    @if ($request->status === 'pending_return')
                        <div class="mt-3 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2.5 text-sm text-slate-700">
                            <div class="mb-1 flex items-center gap-2 text-[0.75rem] font-semibold uppercase tracking-[0.14em] text-amber-700">
                                <i class="fas fa-hourglass-half"></i>
                                Pending review
                            </div>
                            <div class="leading-6">The owner has been notified to confirm physical receipt.</div>
                        </div>
                    @endif

                    <div class="mt-3 space-y-2">
                        @if ($request->status === 'approved')
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('return-book', ['id' => $request->id]) }}" class="inline-flex flex-1 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                    <i class="fas fa-undo-alt mr-2"></i> Return Book
                                </a>
                            </div>
                        @endif
                        <div class="request-card-actions">
                            @if ($canShowOwnerContact && $ownerPhone)
                                <a href="https://wa.me/{{ $ownerPhone }}?text={{ $ownerContactMessage }}" target="_blank" rel="noopener" class="request-contact-link">
                                    <i class="fab fa-whatsapp"></i> Contact
                                </a>
                            @endif
                            <a href="{{ route('book.show', ['id' => $request->book_id]) }}" class="request-book-link">
                                <i class="fas fa-book"></i> View Book
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Reject Request Bottom Sheet / Modal --}}
    <div id="rejectModal" class="reject-overlay" role="dialog" aria-modal="true" aria-labelledby="rejectModalTitle">
        <div class="reject-sheet">
            {{-- Drag handle for mobile --}}
            <div class="reject-sheet__handle" aria-hidden="true"><span></span></div>

            {{-- Header --}}
            <div class="reject-sheet__header">
                <div class="reject-sheet__icon-wrap">
                    <div class="reject-sheet__icon">
                        <i class="fas fa-hand-paper"></i>
                    </div>
                </div>
                <div class="reject-sheet__titles">
                    <h3 id="rejectModalTitle" class="reject-sheet__title">Reject Request</h3>
                    <p class="reject-sheet__subtitle">Let the borrower know why this time.</p>
                </div>
                <button type="button" class="reject-sheet__close" onclick="closeModal('rejectModal')" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="rejectRequestForm" method="POST" action="{{ route('requests.index') }}">
                @csrf
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" id="rejectRequestId">

                <div class="reject-sheet__body">
                    {{-- Quick-pick chips --}}
                    <div class="reject-chips">
                        <span class="reject-chips__label">Quick pick a reason</span>
                        <div class="reject-chips__list">
                            <button type="button" class="reject-chip" data-reason="The book is currently lent to someone else.">📚 Already lent</button>
                            <button type="button" class="reject-chip" data-reason="I need the book myself right now.">🙋 Need it myself</button>
                            <button type="button" class="reject-chip" data-reason="The book is not available at the moment.">⏳ Not available</button>
                            <button type="button" class="reject-chip" data-reason="Sorry, I no longer have this book.">🚫 No longer own</button>
                        </div>
                    </div>

                    {{-- Textarea --}}
                    <div class="reject-field">
                        <label for="rejectRequestReason" class="reject-field__label">
                            Reason for rejection
                            <span class="reject-field__required">Required</span>
                        </label>
                        <div class="reject-field__textarea-wrap" id="rejectTextareaWrap">
                            <textarea
                                id="rejectRequestReason"
                                name="rejection_reason"
                                class="reject-field__textarea"
                                rows="4"
                                maxlength="500"
                                required
                                placeholder="Describe why you're declining this request…"
                                aria-label="Reason for rejection"
                            ></textarea>
                            <div class="reject-field__counter">
                                <span id="rejectReasonCount">0</span><span class="reject-field__counter-max"> / 500</span>
                            </div>
                        </div>
                        <p class="reject-field__hint">
                            <i class="fas fa-lightbulb"></i>
                            A clear reason helps the borrower understand and try again later.
                        </p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="reject-sheet__footer">
                    <button type="button" class="reject-btn reject-btn--cancel" onclick="closeModal('rejectModal')">Cancel</button>
                    <button id="rejectRequestSubmit" type="submit" class="reject-btn reject-btn--submit" disabled>
                        <i class="fas fa-times-circle"></i>
                        <span>Reject Request</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    /* ── Tab Switching ── */
    document.querySelectorAll('.requests-page .tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = this.dataset.tab;
            document.querySelectorAll('.requests-page .tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.requests-page .tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(tab + '-tab').classList.add('active');
        });
    });

    /* ── Reject Modal ── */
    const rejectReasonField = document.getElementById('rejectRequestReason');
    const rejectReasonCount = document.getElementById('rejectReasonCount');
    const rejectSubmitButton = document.getElementById('rejectRequestSubmit');
    const rejectTextareaWrap = document.getElementById('rejectTextareaWrap');

    function updateRejectReasonState() {
        if (!rejectReasonField || !rejectReasonCount || !rejectSubmitButton) return;

        const length = rejectReasonField.value.trim().length;
        rejectReasonCount.textContent = length;
        rejectSubmitButton.disabled = length < 3;

        // Color-coded counter feedback
        if (rejectTextareaWrap) {
            rejectTextareaWrap.classList.remove('reject-field__textarea-wrap--warn', 'reject-field__textarea-wrap--danger');
            if (length >= 450) {
                rejectTextareaWrap.classList.add('reject-field__textarea-wrap--danger');
            } else if (length >= 350) {
                rejectTextareaWrap.classList.add('reject-field__textarea-wrap--warn');
            }
        }
    }

    if (rejectReasonField) {
        rejectReasonField.addEventListener('input', updateRejectReasonState);
    }

    /* ── Quick-pick chips ── */
    document.querySelectorAll('.reject-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            if (!rejectReasonField) return;
            rejectReasonField.value = this.dataset.reason;
            // Deselect all, select clicked
            document.querySelectorAll('.reject-chip').forEach(c => c.classList.remove('reject-chip--active'));
            this.classList.add('reject-chip--active');
            updateRejectReasonState();
            rejectReasonField.focus();
        });
    });

    // If user types, deselect chips
    if (rejectReasonField) {
        rejectReasonField.addEventListener('keydown', function () {
            document.querySelectorAll('.reject-chip').forEach(c => c.classList.remove('reject-chip--active'));
        });
    }

    /* ── Reject button click ── */
    document.querySelectorAll('.reject-request-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            showRejectModal(this.dataset.requestId);
        });
    });

    /* ── Approve flow ── */
    function approveRequest(requestId) {
        if (!confirm('Approve this borrow request?')) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('requests.index') }}";

        const fields = {
            _token: csrfToken,
            action: 'approve',
            request_id: requestId,
        };

        Object.entries(fields).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }

    /* ── Modal Open / Close ── */
    function showRejectModal(requestId) {
        const rejectInput = document.getElementById('rejectRequestId');
        const rejectModal = document.getElementById('rejectModal');
        const rejectReason = document.getElementById('rejectRequestReason');

        if (!rejectInput || !rejectModal || !rejectReason) return;

        rejectInput.value = requestId;
        rejectReason.value = '';
        document.querySelectorAll('.reject-chip').forEach(c => c.classList.remove('reject-chip--active'));
        rejectModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        updateRejectReasonState();

        // Slight delay so the slide animation finishes before focusing
        setTimeout(() => rejectReason.focus(), 320);
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    window.addEventListener('click', function (e) {
        if (e.target.classList.contains('reject-overlay')) {
            closeModal(e.target.id);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.reject-overlay.active').forEach(m => closeModal(m.id));
        }
    });
</script>
@endpush
