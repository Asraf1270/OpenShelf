@extends('layouts.app')

@section('content')
<main>
<div class="cr-wrap">
    <div class="cr-card">
        <div class="cr-banner"></div>
        <div class="cr-body">

            @if(isset($pageError))
            <!-- ── ERROR ─────────────────────────────────────────── -->
            <div class="cr-icon info"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="cr-title">Invalid Link</div>
            <div class="cr-sub">{{ $pageError }}</div>
            <div style="text-align:center">
                <a href="/" class="btn btn-outline">Go to Homepage</a>
            </div>

            @elseif(isset($alreadyDone))
            <!-- ── ALREADY HANDLED ────────────────────────────────── -->
            @if ($alreadyDone === 'confirmed')
                <div class="cr-result-icon">✅</div>
                <div class="cr-result-title" style="color:#10b981">Already Confirmed</div>
                <div class="cr-result-msg">This return has already been confirmed. The book is marked as Available.</div>
            @else
                <div class="cr-result-icon">❌</div>
                <div class="cr-result-title" style="color:#ef4444">Already Processed</div>
                <div class="cr-result-msg">This return confirmation has already been handled.</div>
            @endif
            <div style="text-align:center">
                <a href="/requests/" class="btn btn-outline">View Requests</a>
            </div>

            @elseif($actionResult === 'confirmed')
            <!-- ── SUCCESS CONFIRMED ──────────────────────────────── -->
            <div class="cr-result-icon">🎉</div>
            <div class="cr-result-title" style="color:#10b981">Return Confirmed!</div>
            <div class="cr-result-msg">
                You have confirmed receiving <strong>"{{ $borrowRequest->book_title }}"</strong>.
                The book is now marked as <strong>Available</strong> and the borrower has been notified.
            </div>
            <div style="text-align:center">
                <a href="/requests/" class="btn btn-confirm" style="display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 2rem;border-radius:12px;text-decoration:none;font-weight:700;">
                    <i class="fas fa-list"></i> View My Requests
                </a>
            </div>

            @elseif($actionResult === 'rejected')
            <!-- ── REJECTED ───────────────────────────────────────── -->
            <div class="cr-result-icon">⚠️</div>
            <div class="cr-result-title" style="color:#ef4444">Not Received – Reported</div>
            <div class="cr-result-msg">
                You have indicated that you have <strong>not</strong> received
                <strong>"{{ $borrowRequest->book_title }}"</strong>.
                The borrower has been notified to contact you.
            </div>
            <div style="text-align:center">
                <a href="/requests/" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 2rem;border-radius:12px;text-decoration:none;">
                    <i class="fas fa-list"></i> View My Requests
                </a>
            </div>

            @else
            <!-- ── PENDING: show confirm/reject UI ───────────────── -->
            @php($showReject = $intendedAction === 'reject')

            <div class="cr-icon warn"><i class="fas fa-book-open"></i></div>
            <div class="cr-title">Confirm Book Return</div>
            <div class="cr-sub">
                Please confirm whether you have <strong>physically received</strong> your book back.
            </div>

            <!-- Book info -->
            <div class="cr-book-card">
                <div class="cr-book-title">{{ $borrowRequest->book_title }}</div>
                <div class="cr-book-meta">
                    <span><i class="fas fa-user" style="color:var(--secondary,#4c9f8a)"></i>
                        Returned by: {{ $borrowRequest->returned_by_name ?? $borrowRequest->borrower_name }}
                    </span>
                    <span><i class="far fa-calendar-alt" style="color:var(--secondary,#4c9f8a)"></i>
                        Filed: {{ ($borrowRequest->returned_at ?? $borrowRequest->updated_at)?->format('M j, Y') }}
                    </span>
                    @if ($borrowRequest->return_condition)
                    <span><i class="fas fa-book" style="color:var(--secondary,#4c9f8a)"></i>
                        Condition: {{ $borrowRequest->return_condition === 'damaged' ? '⚠️ Has Damage' : '✅ Good/Intact' }}
                    </span>
                    @endif
                </div>
                @if ($borrowRequest->notes)
                <div style="margin-top:.75rem;font-size:.82rem;color:var(--text-secondary,#5a6c7d);border-top:1px solid var(--border,#e2e8f0);padding-top:.75rem;">
                    <strong>Borrower notes:</strong> {{ $borrowRequest->notes }}
                </div>
                @endif
            </div>

            @if (!$showReject)
            <!-- Confirm/Reject buttons -->
            <div class="cr-actions">
                <form method="POST" style="flex:1" action="{{ route('confirm-return', ['token' => $token]) }}">
                    @csrf
                    <input type="hidden" name="action" value="confirm">
                    <button type="submit" class="btn btn-confirm" style="width:100%;padding:.85rem;border-radius:12px;font-size:.95rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;">
                        <i class="fas fa-check-circle"></i> Yes, I Received It
                    </button>
                </form>
                <a href="{{ route('confirm-return', ['token' => $token, 'action' => 'reject']) }}"
                   class="btn btn-reject" style="flex:1;padding:.85rem;border-radius:12px;font-size:.95rem;font-weight:700;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;">
                    <i class="fas fa-times-circle"></i> Not Received
                </a>
            </div>

            @else
            <!-- Reject form with optional reason -->
            <div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:1rem;margin-bottom:1.25rem;font-size:.88rem;color:#b91c1c;">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Before rejecting</strong>, please try contacting the borrower first.
                Rejecting will notify them and keep the book marked as borrowed.
            </div>
            <form method="POST" class="cr-reject-form" action="{{ route('confirm-return', ['token' => $token]) }}">
                @csrf
                <input type="hidden" name="action" value="reject">
                <label class="form-label" style="font-size:.88rem;font-weight:600;margin-bottom:.5rem;display:block;">
                    Reason (optional)
                </label>
                <textarea name="reject_reason" placeholder="e.g. I have not yet received the book physically..."></textarea>
                <div style="display:flex;gap:.75rem;margin-top:1rem;">
                    <button type="submit" class="btn" style="flex:1;background:#ef4444;border:none;color:#fff;border-radius:12px;padding:.8rem;font-weight:700;cursor:pointer;">
                        <i class="fas fa-times-circle"></i> Confirm – I Did Not Receive It
                    </button>
                    <a href="{{ route('confirm-return', ['token' => $token]) }}" class="btn btn-outline" style="flex:1;border-radius:12px;padding:.8rem;text-align:center;text-decoration:none;">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </a>
                </div>
            </form>
            @endif

            @endif

        </div>
        <div class="cr-footer">
            OpenShelf &mdash; Secure book return confirmation.
            If you did not expect this email, you can ignore it.
        </div>
    </div>
</div>
</main>
@endsection
