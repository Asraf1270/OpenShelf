@extends('layouts.app')

@push('styles')
<style>
    .requests-page {
        padding: 1rem 0 2rem;
    }

    .requests-page .page-shell {
        max-width: 1120px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 1.1rem;
    }

    .requests-page .page-hero {
        background: linear-gradient(135deg, #4c9f8a 0%, #2d5d7a 100%);
        border-radius: 24px;
        padding: 1.4rem 1.5rem;
        color: #fff;
        box-shadow: 0 22px 45px rgba(45, 93, 122, 0.18);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .requests-page .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.18);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 0.7rem;
    }

    .requests-page .page-hero h1 {
        margin: 0 0 0.3rem;
        font-size: clamp(1.35rem, 2vw, 1.8rem);
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }

    .requests-page .page-hero p {
        margin: 0;
        opacity: 0.9;
        max-width: 660px;
    }

    .requests-page .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .requests-page .stat-card {
        background: var(--surface, #ffffff);
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 18px;
        padding: 1rem 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .requests-page .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        color: #fff;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .requests-page .stat-card.pending .stat-icon { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .requests-page .stat-card.approved .stat-icon { background: linear-gradient(135deg, #10b981, #34d399); }
    .requests-page .stat-card.rejected .stat-icon { background: linear-gradient(135deg, #ef4444, #f87171); }
    .requests-page .stat-card.returned .stat-icon { background: linear-gradient(135deg, #4c9f8a, #2d5d7a); }

    .requests-page .stat-value {
        font-size: 1.3rem;
        font-weight: 800;
        line-height: 1;
    }

    .requests-page .stat-label {
        font-size: 0.85rem;
        color: var(--text-secondary, #64748b);
        font-weight: 600;
    }

    .requests-page .tabs {
        display: flex;
        gap: 0.75rem;
        padding: 0.45rem;
        background: var(--surface, #ffffff);
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 999px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        width: fit-content;
    }

    .requests-page .tab-btn {
        border: none;
        background: transparent;
        color: var(--text-secondary, #64748b);
        padding: 0.7rem 1rem;
        border-radius: 999px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .requests-page .tab-btn.active {
        background: linear-gradient(135deg, #4c9f8a 0%, #2d5d7a 100%);
        color: #fff;
    }

    .requests-page .tab-content {
        display: none;
    }

    .requests-page .tab-content.active {
        display: block;
    }

    .requests-page .request-card {
        background: var(--surface, #ffffff);
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 20px;
        padding: 1.1rem 1.15rem;
        margin-bottom: 1rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .requests-page .request-card.pending { border-left: 4px solid #f59e0b; }
    .requests-page .request-card.approved { border-left: 4px solid #10b981; }
    .requests-page .request-card.rejected { border-left: 4px solid #ef4444; }
    .requests-page .request-card.pending_return { border-left: 4px solid #4c9f8a; }

    .requests-page .request-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 0.9rem;
    }

    .requests-page .book-title a {
        color: var(--text-primary, #0f172a);
        font-weight: 700;
        font-size: 1.02rem;
        text-decoration: none;
    }

    .requests-page .book-author {
        color: var(--text-secondary, #64748b);
        margin-top: 0.2rem;
        font-size: 0.92rem;
    }

    .requests-page .status-badge {
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: capitalize;
        white-space: nowrap;
    }

    .requests-page .status-pending { background: rgba(245, 158, 11, 0.12); color: #b45309; }
    .requests-page .status-approved { background: rgba(16, 185, 129, 0.12); color: #047857; }
    .requests-page .status-rejected { background: rgba(239, 68, 68, 0.12); color: #b91c1c; }
    .requests-page .status-pending_return { background: rgba(76, 159, 138, 0.12); color: #2d5d7a; }

    .requests-page .request-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem 1rem;
        margin-bottom: 0.9rem;
    }

    .requests-page .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-secondary, #64748b);
        font-size: 0.92rem;
    }

    .requests-page .request-message,
    .requests-page .pending-return-notice {
        border-radius: 14px;
        padding: 0.8rem 0.9rem;
        margin-bottom: 0.9rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: var(--text-secondary, #64748b);
    }

    .requests-page .request-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.7rem;
    }

    .requests-page .empty-state {
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 24px;
        padding: 2.2rem 1.5rem;
        text-align: center;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .requests-page .empty-state i {
        font-size: 2.2rem;
        color: #4c9f8a;
        margin-bottom: 0.5rem;
    }

    @media (max-width: 768px) {
        .requests-page .page-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .requests-page .stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .requests-page .tabs {
            width: 100%;
            justify-content: center;
        }

        .requests-page .request-meta {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="requests-page">
    <div class="page-shell">
        <div class="page-hero">
            <div>
                <div class="hero-badge"><i class="fas fa-layer-group"></i> Borrowing hub</div>
                <h1><i class="fas fa-exchange-alt"></i> My Requests</h1>
                <p>Track incoming requests, approve or reject borrowals, and manage your return flow from one calm dashboard.</p>
            </div>
            <a href="{{ route('books') }}" class="btn btn-outline" style="background: rgba(255,255,255,0.12); color: #fff; border-color: rgba(255,255,255,0.3);">Browse Books</a>
        </div>

        @if ($message)
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> {{ $message }}
            </div>
        @endif

        @if ($error)
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> {{ $error }}
            </div>
        @endif

        <div class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div class="stat-value" style="color: #f59e0b;">{{ $stats['pending'] }}</div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="stat-card approved">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="stat-value" style="color: #10b981;">{{ $stats['approved'] }}</div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="stat-value" style="color: #ef4444;">{{ $stats['rejected'] }}</div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
            <div class="stat-card returned">
                <div class="stat-icon"><i class="fas fa-undo-alt"></i></div>
                <div>
                    <div class="stat-value" style="color: var(--primary);">{{ $stats['returned'] }}</div>
                    <div class="stat-label">Returned</div>
                </div>
            </div>
        </div>

        <div class="tabs">
            <button type="button" class="tab-btn active" data-tab="received">
                <i class="fas fa-inbox"></i> Received ({{ $receivedRequests->count() }})
            </button>
            <button type="button" class="tab-btn" data-tab="sent">
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
                <div class="request-card {{ $request->status }}">
                    <div class="request-header">
                        <div>
                            <div class="book-title">
                                <a href="{{ route('book.show', ['id' => $request->book_id]) }}">{{ $request->book_title }}</a>
                            </div>
                            <div class="book-author">by {{ $request->book_author ?? 'Unknown' }}</div>
                        </div>
                        <div>
                            <span class="status-badge status-{{ $request->status }}">{{ $statusLabel }}</span>
                        </div>
                    </div>

                    <div class="request-meta">
                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            <span><strong>{{ $request->borrower_name }}</strong></span>
                        </div>
                        <div class="meta-item">
                            <i class="far fa-calendar-alt"></i>
                            <span>Requested: {{ $request->request_date?->format('M j, Y') ?? 'N/A' }}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>Duration: {{ $request->duration_days ?? 14 }} days</span>
                        </div>
                        @if ($request->expected_return_date)
                            <div class="meta-item">
                                <i class="far fa-calendar-check"></i>
                                <span>Due: {{ $request->expected_return_date->format('M j, Y') }}</span>
                            </div>
                        @endif
                        @if ($borrower?->phone)
                            <div class="meta-item">
                                <i class="fas fa-phone"></i>
                                <span>{{ $borrower->phone }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($request->message)
                        <div class="request-message">
                            <i class="fas fa-quote-left" style="margin-right:0.5rem;color:#f59e0b;"></i>
                            {!! nl2br(e($request->message)) !!}
                        </div>
                    @endif

                    <div class="request-actions">
                        @if ($request->status === 'pending')
                            <button type="button" class="btn btn-success" onclick="approveRequest('{{ $request->id }}')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button type="button" class="btn btn-danger" onclick="showRejectModal('{{ $request->id }}')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        @elseif ($request->status === 'approved')
                            <a href="{{ route('book.show', ['id' => $request->book_id]) }}" class="btn btn-outline">
                                <i class="fas fa-book"></i> View Book
                            </a>
                            @if ($borrower?->phone)
                                <a href="https://wa.me/88{{ preg_replace('/[^0-9]/', '', $borrower->phone) }}" target="_blank" rel="noopener" class="btn btn-outline btn-whatsapp">
                                    <i class="fab fa-whatsapp"></i> Contact
                                </a>
                            @endif
                        @else
                            <a href="{{ route('book.show', ['id' => $request->book_id]) }}" class="btn btn-outline">
                                <i class="fas fa-book"></i> View Book
                            </a>
                        @endif
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
                <div class="request-card {{ $request->status }}">
                    <div class="request-header">
                        <div>
                            <div class="book-title">
                                <a href="{{ route('book.show', ['id' => $request->book_id]) }}">{{ $request->book_title }}</a>
                            </div>
                            <div class="book-author">by {{ $request->book_author ?? 'Unknown' }}</div>
                        </div>
                        <div>
                            <span class="status-badge status-{{ $request->status }}">{{ $statusLabel }}</span>
                        </div>
                    </div>

                    <div class="request-meta">
                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            <span><strong>{{ $request->owner_name }}</strong></span>
                        </div>
                        <div class="meta-item">
                            <i class="far fa-calendar-alt"></i>
                            <span>Requested: {{ $request->request_date?->format('M j, Y') ?? 'N/A' }}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>Duration: {{ $request->duration_days ?? 14 }} days</span>
                        </div>
                        @if ($request->expected_return_date)
                            <div class="meta-item">
                                <i class="far fa-calendar-check"></i>
                                <span>Due: {{ $request->expected_return_date->format('M j, Y') }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($request->status === 'rejected' && $request->rejection_reason)
                        <div class="request-message rejected">
                            <i class="fas fa-times-circle" style="color:#ef4444;margin-right:0.5rem;"></i>
                            <strong>Reason:</strong> {{ $request->rejection_reason }}
                        </div>
                    @endif

                    <div class="request-actions">
                        @if ($request->status === 'approved')
                            <a href="{{ route('return-book', ['id' => $request->id]) }}" class="btn btn-success">
                                <i class="fas fa-undo-alt"></i> Return Book
                            </a>
                            @if ($owner?->phone)
                                <a href="https://wa.me/88{{ preg_replace('/[^0-9]/', '', $owner->phone) }}" target="_blank" rel="noopener" class="btn btn-outline btn-whatsapp">
                                    <i class="fab fa-whatsapp"></i> Contact Owner
                                </a>
                            @endif
                        @elseif ($request->status === 'pending_return')
                            <div class="pending-return-notice">
                                <i class="fas fa-hourglass-half" style="color:#f59e0b;"></i>
                                <span><strong>Awaiting owner confirmation</strong> — The owner has been notified to confirm physical receipt.</span>
                            </div>
                        @endif
                        <a href="{{ route('book.show', ['id' => $request->book_id]) }}" class="btn btn-outline">
                            <i class="fas fa-book"></i> View Book
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
        form.action = @json(route('requests.index'));

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
