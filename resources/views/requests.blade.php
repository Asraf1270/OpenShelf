@extends('layouts.app')

@section('content')
<div class="requests-page">
    <div class="page-header">
        <h1><i class="fas fa-exchange-alt" style="color: var(--secondary);"></i> My Requests</h1>
        <p>Manage your book borrowing requests</p>
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
        <div class="stat-card">
            <div class="stat-value" style="color: #f59e0b;">{{ $stats['pending'] }}</div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #10b981;">{{ $stats['approved'] }}</div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #ef4444;">{{ $stats['rejected'] }}</div>
            <div class="stat-label">Rejected</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--primary);">{{ $stats['returned'] }}</div>
            <div class="stat-label">Returned</div>
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
