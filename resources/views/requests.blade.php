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

    [data-theme="dark"] .requests-page .modal-content { background-color: #1e293b; border-color: #334155; color: #f8fafc; }
    [data-theme="dark"] .requests-page .modal-header { border-bottom-color: #334155; }
    [data-theme="dark"] .requests-page .modal-header h3 { color: #f8fafc; }
    [data-theme="dark"] .requests-page .modal-footer { border-top-color: #334155; }
    [data-theme="dark"] .requests-page .form-control { background-color: #0f172a; border-color: #334155; color: #f8fafc; }
    [data-theme="dark"] .requests-page .form-group label { color: #cbd5e1; }
    [data-theme="dark"] .requests-page .btn-outline { background-color: #1e293b; border-color: #334155; color: #cbd5e1; }
    [data-theme="dark"] .requests-page .btn-outline:hover { background-color: #334155; color: #f8fafc; }
    [data-theme="dark"] .requests-page .btn-danger { background-color: #b91c1c; color: #fff; }
    [data-theme="dark"] .requests-page .btn-danger:hover { background-color: #991b1b; }
    [data-theme="dark"] .requests-page .modal-header button { color: #f8fafc; }
    
    [data-theme="dark"] .requests-page .status-badge.pending { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
    [data-theme="dark"] .requests-page .status-badge.approved { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
    [data-theme="dark"] .requests-page .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #fda4af; }
    [data-theme="dark"] .requests-page .status-badge.pending_return { background: rgba(76, 159, 138, 0.2); color: #94a3b8; }
    
    [data-theme="dark"] .requests-page .empty-state { background-color: #1e293b; border-color: #334155; }
    [data-theme="dark"] .requests-page .empty-state h3 { color: #f8fafc; }
    [data-theme="dark"] .requests-page .empty-state p { color: #94a3b8; }
    [data-theme="dark"] .requests-page .empty-state i { color: #4c9f8a; }
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
                    $statusLabel = ucwords(str_replace('_', ' ', $request->status));
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
                                <button type="button" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" onclick="showRejectModal('{{ $request->id }}')">
                                    <i class="fas fa-times mr-2"></i> Reject
                                </button>
                            </div>
                        @elseif ($request->status === 'approved')
                            <div class="flex flex-wrap gap-2">
                                @if ($borrower?->phone)
                                    <a href="https://wa.me/88{{ preg_replace('/[^0-9]/', '', $borrower->phone) }}" target="_blank" rel="noopener" class="inline-flex flex-1 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                                        <i class="fab fa-whatsapp mr-2"></i> Contact
                                    </a>
                                @endif
                            </div>
                        @endif
                        <a href="{{ route('book.show', ['id' => $request->book_id]) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                            <i class="fas fa-book mr-2"></i> View Book
                        </a>
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
                    $owner = $request->owner;
                    $statusLabel = ucwords(str_replace('_', ' ', $request->status));
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
                                @if ($owner?->phone)
                                    <a href="https://wa.me/88{{ preg_replace('/[^0-9]/', '', $owner->phone) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                                        <i class="fab fa-whatsapp mr-2"></i> Contact Owner
                                    </a>
                                @endif
                            </div>
                        @endif
                        <a href="{{ route('book.show', ['id' => $request->book_id]) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                            <i class="fas fa-book mr-2"></i> View Book
                        </a>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle" style="color:#f59e0b;"></i> Reject Request</h3>
                <button type="button" onclick="closeModal('rejectModal')">&times;</button>
            </div>
            <form method="POST" action="{{ route('requests.index') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" id="rejectRequestId">
                    <div class="form-group">
                        <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Reason for Rejection</label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('rejectModal')">Cancel</button>
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

    document.querySelectorAll('.requests-page .tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = this.dataset.tab;
            document.querySelectorAll('.requests-page .tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.requests-page .tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(tab + '-tab').classList.add('active');
        });
    });

    function approveRequest(requestId) {
        if (!confirm('Approve this borrow request?')) {
            return;
        }

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

    function showRejectModal(requestId) {
        document.getElementById('rejectRequestId').value = requestId;
        document.getElementById('rejectModal').classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    window.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
        }
    });
</script>
@endpush
