@extends('admin.layouts.app')

@section('title', 'Transactions - OpenShelf Admin')
@section('page_title', 'Transaction History')

@push('styles')
<style>
    .tx-wrap { max-width: 1260px; margin: 0 auto; }
    .tx-header-card { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 24px; padding: 32px; margin-bottom: 24px; color: #fff; }
    .tx-header-card h1 { font-size: 1.85rem; margin: 0; font-weight: 800; }
    .tx-header-card p { margin: 6px 0 0; color: #94a3b8; font-size: 0.95rem; }
    .tx-header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .tx-header-actions a, .tx-header-actions button { display: inline-flex; align-items: center; gap: 8px; padding: 11px 20px; border-radius: 14px; font-size: 0.9rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; }
    .tx-btn-outline-light { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
    .tx-btn-outline-light:hover { background: rgba(255,255,255,0.2); }
    .tx-btn-accent { background: #10b981; color: #fff; }
    .tx-btn-accent:hover { background: #059669; transform: translateY(-1px); }
    .tx-stats-grid { display: grid; gap: 16px; grid-template-columns: repeat(4, 1fr); margin-bottom: 24px; }
    @media (max-width: 900px) { .tx-stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 500px) { .tx-stats-grid { grid-template-columns: 1fr; } }
    .tx-stat-card { padding: 24px; border-radius: 20px; background: var(--surface); border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(15,23,42,0.04); }
    .tx-stat-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; margin-bottom: 14px; }
    .tx-stat-icon.income { background: rgba(16,185,129,0.12); color: #10b981; }
    .tx-stat-icon.expense { background: rgba(239,68,68,0.12); color: #ef4444; }
    .tx-stat-icon.balance { background: rgba(59,130,246,0.12); color: #3b82f6; }
    .tx-stat-icon.total { background: rgba(139,92,246,0.12); color: #8b5cf6; }
    .tx-stat-card h2 { margin: 0 0 4px; font-size: 1.65rem; font-weight: 800; }
    .tx-stat-card h2.text-green { color: #10b981; }
    .tx-stat-card h2.text-red { color: #ef4444; }
    .tx-stat-card h2.text-blue { color: #3b82f6; }
    .tx-stat-card p { margin: 0; color: var(--text-muted); font-size: 0.88rem; font-weight: 500; }
    .tx-stat-count { font-size: 0.8rem; color: #94a3b8; margin-top: 6px; }
    .tx-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(15,23,42,0.04); }
    .tx-card-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 6px; display: flex; align-items: center; gap: 10px; }
    .tx-card-subtitle { color: var(--text-muted); font-size: 0.9rem; margin: 0; }
    .tx-exp-form { display: none; }
    .tx-exp-form.active { display: block; animation: txSlideDown 0.35s ease; }
    @keyframes txSlideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .tx-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-top: 20px; }
    .tx-form-group label { display: block; font-weight: 600; font-size: 0.88rem; color: var(--text-main); margin-bottom: 7px; }
    .tx-form-group input, .tx-form-group select, .tx-form-group textarea { width: 100%; padding: 12px 14px; border: 1.5px solid var(--border); border-radius: 14px; font-size: 0.95rem; font-family: inherit; background: var(--bg-body); color: var(--text-main); transition: border-color 0.2s, box-shadow 0.2s; }
    .tx-form-group input:focus, .tx-form-group select:focus, .tx-form-group textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.12); background: var(--surface); }
    .tx-form-group textarea { resize: vertical; min-height: 60px; }
    .tx-form-actions { display: flex; gap: 12px; margin-top: 22px; flex-wrap: wrap; }
    .tx-btn-submit { display: inline-flex; align-items: center; gap: 8px; padding: 13px 28px; border-radius: 14px; background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; font-weight: 700; font-size: 0.95rem; border: none; cursor: pointer; }
    .tx-btn-cancel { display: inline-flex; align-items: center; gap: 8px; padding: 13px 28px; border-radius: 14px; background: var(--bg-body); color: var(--text-muted); font-weight: 600; font-size: 0.95rem; border: 1px solid var(--border); cursor: pointer; }
    .tx-filter-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 20px; }
    .tx-filter-row input, .tx-filter-row select { padding: 12px 14px; border: 1.5px solid var(--border); border-radius: 14px; font-size: 0.9rem; font-family: inherit; background: var(--bg-body); color: var(--text-main); }
    .tx-filter-row input { width: 260px; }
    .tx-btn-filter { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 14px; background: #0f172a; color: #fff; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; }
    .tx-table-wrap { overflow-x: auto; }
    .tx-table { width: 100%; border-collapse: collapse; }
    .tx-table th, .tx-table td { padding: 14px 12px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: middle; font-size: 0.9rem; }
    .tx-table th { background: rgba(148,163,184,0.08); font-weight: 700; color: var(--text-muted); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em; }
    .tx-muted { color: var(--text-muted); font-size: 0.82rem; }
    .tx-type-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; font-size: 0.8rem; font-weight: 700; }
    .tx-type-badge.income { background: rgba(16,185,129,0.1); color: #059669; }
    .tx-type-badge.expenditure { background: rgba(239,68,68,0.1); color: #dc2626; }
    .tx-amount-income { color: #059669; font-weight: 700; }
    .tx-amount-expense { color: #dc2626; font-weight: 700; }
    .tx-empty { text-align: center; padding: 48px 20px; color: var(--text-muted); }
    .tx-empty i { font-size: 2.5rem; margin-bottom: 14px; display: block; }
</style>
@endpush

@section('content')
<div class="tx-wrap">
    <div class="tx-header-card">
        <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:16px; align-items:center;">
            <div>
                <h1><i class="fas fa-receipt"></i> Transaction History</h1>
                <p>Track all income and expenditure. Manage your organization finances.</p>
            </div>
            <div class="tx-header-actions">
                <button type="button" onclick="toggleExpForm()" class="tx-btn-accent" id="toggleFormBtn">
                    <i class="fas fa-plus"></i> Add Expenditure
                </button>
                <a href="{{ route('admin.support-us.index') }}" class="tx-btn-outline-light">
                    <i class="fas fa-hand-holding-dollar"></i> Support Requests
                </a>
                <a href="{{ route('admin.dashboard') }}" class="tx-btn-outline-light">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="tx-card tx-exp-form {{ ($showExpenditureForm || $errors->any()) ? 'active' : '' }}" id="expenditureForm">
        <h3 class="tx-card-title"><i class="fas fa-file-invoice-dollar" style="color:#ef4444;"></i> Record Expenditure</h3>
        <p class="tx-card-subtitle">Add a new expenditure entry. The amount will be deducted from the net balance.</p>
        <form method="post" action="{{ route('admin.transactions.index') }}">
            @csrf
            <input type="hidden" name="action" value="add_expenditure">
            <div class="tx-form-grid">
                <div class="tx-form-group">
                    <label for="exp_amount"><i class="fas fa-bangladeshi-taka-sign"></i> Amount *</label>
                    <input type="number" id="exp_amount" name="amount" placeholder="e.g. 5000" step="0.01" min="0.01" required value="{{ old('amount') }}">
                </div>
                <div class="tx-form-group">
                    <label for="exp_category"><i class="fas fa-tag"></i> Category</label>
                    <select id="exp_category" name="category">
                        @foreach (['office_supplies' => 'Office Supplies', 'maintenance' => 'Maintenance', 'logistics' => 'Logistics / Delivery', 'marketing' => 'Marketing', 'salary' => 'Salary / Payment', 'utilities' => 'Utilities', 'event' => 'Event / Program', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', 'other') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tx-form-group">
                    <label for="exp_txn_id"><i class="fas fa-hashtag"></i> Reference / Transaction ID</label>
                    <input type="text" id="exp_txn_id" name="transaction_id" placeholder="Auto-generated if empty" value="{{ old('transaction_id') }}">
                </div>
                <div class="tx-form-group">
                    <label for="exp_invoice"><i class="fas fa-file-alt"></i> Invoice Number</label>
                    <input type="text" id="exp_invoice" name="invoice_number" placeholder="Auto-generated if empty" value="{{ old('invoice_number') }}">
                </div>
            </div>
            <div class="tx-form-grid" style="grid-template-columns: 1fr; margin-top: 0;">
                <div class="tx-form-group">
                    <label for="exp_desc"><i class="fas fa-align-left"></i> Description *</label>
                    <textarea id="exp_desc" name="description" placeholder="What was this expenditure for?" required>{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="tx-form-actions">
                <button type="submit" class="tx-btn-submit"><i class="fas fa-save"></i> Record Expenditure</button>
                <button type="button" class="tx-btn-cancel" onclick="toggleExpForm()"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </form>
    </div>

    <div class="tx-stats-grid">
        <div class="tx-stat-card">
            <div class="tx-stat-icon income"><i class="fas fa-arrow-down"></i></div>
            <h2 class="text-green">৳{{ number_format($stats['total_income'], 2) }}</h2>
            <p>Total Income</p>
            <div class="tx-stat-count">{{ $stats['income_count'] }} transaction{{ $stats['income_count'] !== 1 ? 's' : '' }}</div>
        </div>
        <div class="tx-stat-card">
            <div class="tx-stat-icon expense"><i class="fas fa-arrow-up"></i></div>
            <h2 class="text-red">৳{{ number_format($stats['total_expenditure'], 2) }}</h2>
            <p>Total Expenditure</p>
            <div class="tx-stat-count">{{ $stats['expenditure_count'] }} transaction{{ $stats['expenditure_count'] !== 1 ? 's' : '' }}</div>
        </div>
        <div class="tx-stat-card">
            <div class="tx-stat-icon balance"><i class="fas fa-scale-balanced"></i></div>
            <h2 class="text-blue">৳{{ number_format($stats['net_balance'], 2) }}</h2>
            <p>Net Balance</p>
            <div class="tx-stat-count">Income − Expenditure</div>
        </div>
        <div class="tx-stat-card">
            <div class="tx-stat-icon total"><i class="fas fa-list-ol"></i></div>
            <h2>{{ $stats['total_transactions'] }}</h2>
            <p>Total Transactions</p>
            <div class="tx-stat-count">All records</div>
        </div>
    </div>

    <div class="tx-card">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
            <div>
                <h3 class="tx-card-title"><i class="fas fa-table-list" style="color:#3b82f6;"></i> All Transactions</h3>
                <p class="tx-card-subtitle">Showing {{ $transactions->count() }} record{{ $transactions->count() !== 1 ? 's' : '' }}</p>
            </div>
        </div>
        <div class="tx-filter-row">
            <form method="get" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; width:100%;">
                <select name="type">
                    <option value="all" @selected($filterType === 'all')>All Types</option>
                    <option value="income" @selected($filterType === 'income')>Income Only</option>
                    <option value="expenditure" @selected($filterType === 'expenditure')>Expenditure Only</option>
                </select>
                <input type="search" name="search" placeholder="Search invoice, transaction id, user, category..." value="{{ $search }}">
                <button type="submit" class="tx-btn-filter"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        <div class="tx-table-wrap">
            <table class="tx-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Invoice</th>
                        <th>User / Source</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $txn)
                        @php
                            $isExpense = $txn->isExpenditure();
                            $typeClass = $isExpense ? 'expenditure' : 'income';
                            $typeLabel = $isExpense ? 'Expenditure' : 'Income';
                            $typeIcon = $isExpense ? 'fa-arrow-up' : 'fa-arrow-down';
                            $displayAmount = abs((float) $txn->amount);
                            $sourceName = $txn->user_name ?: optional($txn->supportUs)->user_name ?: 'System';
                        @endphp
                        <tr>
                            <td>
                                <span class="tx-type-badge {{ $typeClass }}">
                                    <i class="fas {{ $typeIcon }}"></i> {{ $typeLabel }}
                                </span>
                            </td>
                            <td><strong>{{ $txn->invoice_number }}</strong></td>
                            <td>
                                {{ $sourceName }}
                                @if ($txn->user_email)
                                    <br><span class="tx-muted">{{ $txn->user_email }}</span>
                                @endif
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', $txn->provider ?? '-')) }}</td>
                            <td class="{{ $isExpense ? 'tx-amount-expense' : 'tx-amount-income' }}">
                                {{ $isExpense ? '−' : '+' }} ৳{{ number_format($displayAmount, 2) }}
                            </td>
                            <td><span class="tx-muted">{{ $txn->transaction_id }}</span></td>
                            <td>
                                <span class="tx-type-badge {{ $typeClass }}" style="font-size:0.75rem;">
                                    {{ ucfirst($txn->status) }}
                                </span>
                            </td>
                            <td><span class="tx-muted">{{ optional($txn->created_at)->format('d M Y, h:i A') }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="tx-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No transactions found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleExpForm() {
        const form = document.getElementById('expenditureForm');
        const btn = document.getElementById('toggleFormBtn');
        form.classList.toggle('active');
        if (form.classList.contains('active')) {
            btn.innerHTML = '<i class="fas fa-times"></i> Close Form';
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Expenditure';
        }
    }
</script>
@endpush
