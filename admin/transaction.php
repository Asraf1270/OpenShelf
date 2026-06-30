<?php
/**
 * OpenShelf Admin Transaction History
 * - Add expenditure form for admin
 * - Show all income and expenditure with visual differentiation
 * - Display total income, total expenditure, and net balance
 */

session_start();

require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$search = trim($_GET['search'] ?? '');
$filterType = $_GET['type'] ?? 'all';

$db = getDB();
$successMessage = '';
$errors = [];

// Handle expenditure form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_expenditure') {
    $amount = trim($_POST['amount'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $transactionId = trim($_POST['transaction_id'] ?? '');
    $invoiceNumber = trim($_POST['invoice_number'] ?? '');
    $category = trim($_POST['category'] ?? 'other');

    if ($amount === '' || !is_numeric(str_replace(',', '', $amount)) || floatval(str_replace(',', '', $amount)) <= 0) {
        $errors[] = 'Please enter a valid positive amount for expenditure.';
    }
    if ($description === '') {
        $errors[] = 'Description is required for expenditure.';
    }
    if ($transactionId === '') {
        $transactionId = 'REF' . strtoupper(substr(uniqid('', true), -8));
    }
    if ($invoiceNumber === '') {
        $invoiceNumber = 'EXP' . strtoupper(substr(uniqid('', true), -8));
    }

    $systemUserId = $db->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    if (!$systemUserId) {
        $errors[] = 'A valid user record is required to save this expenditure.';
    }

    if (empty($errors)) {
        $expenseAmount = -abs((float) str_replace(',', '', $amount));
        $adminName = $_SESSION['admin_name'] ?? 'Admin';

        try {
            $insert = $db->prepare("INSERT INTO transactions (id, support_us_id, user_id, user_name, user_email, provider, account_number, amount, transaction_id, invoice_number, status, created_by, created_at) VALUES (?, NULL, ?, ?, NULL, ?, '', ?, ?, ?, 'expense', ?, NOW())");
            $saved = $insert->execute([
                'TXN' . strtoupper(substr(uniqid('', true), -12)),
                $systemUserId,
                $adminName,
                $category,
                number_format($expenseAmount, 2, '.', ''),
                $transactionId,
                $invoiceNumber,
                $adminName
            ]);

            if ($saved) {
                $successMessage = 'Expenditure of ৳' . number_format(abs($expenseAmount), 2) . ' recorded successfully.';
            } else {
                $errors[] = 'Unable to save expenditure. Please try again.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error while saving expenditure.';
        }
    }
}

// Build query with optional type filter
$sql = "SELECT t.*, s.user_name AS support_user_name, s.provider AS support_provider, CASE WHEN t.amount < 0 THEN 'expenditure' ELSE 'income' END AS transaction_type FROM transactions t LEFT JOIN support_us s ON s.id = t.support_us_id";
$params = [];
$where = [];

if (!empty($search)) {
    $where[] = '(t.invoice_number LIKE :search OR t.transaction_id LIKE :search OR t.user_name LIKE :search OR t.user_email LIKE :search OR t.provider LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($filterType === 'income') {
    $where[] = 't.amount >= 0';
} elseif ($filterType === 'expenditure') {
    $where[] = 't.amount < 0';
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY t.created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalIncome = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE amount >= 0")->fetchColumn();
$totalExpenditure = abs((float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE amount < 0")->fetchColumn());
$netBalance = $totalIncome - $totalExpenditure;
$totalTransactions = (int) $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$incomeCount = (int) $db->query("SELECT COUNT(*) FROM transactions WHERE amount >= 0")->fetchColumn();
$expenditureCount = (int) $db->query("SELECT COUNT(*) FROM transactions WHERE amount < 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Transactions - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { background: #f1f5f9; color: #0f172a; font-family: 'Inter', sans-serif; margin: 0; }
        .admin-panel { max-width: 1260px; margin: 0 auto; padding: 28px 20px 48px; }

        /* Header Card */
        .header-card { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 24px; padding: 32px; margin-bottom: 24px; color: #fff; }
        .header-card h1 { font-size: 1.85rem; margin: 0; font-weight: 800; }
        .header-card p { margin: 6px 0 0; color: #94a3b8; font-size: 0.95rem; }
        .header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .header-actions a, .header-actions button { display: inline-flex; align-items: center; gap: 8px; padding: 11px 20px; border-radius: 14px; font-size: 0.9rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-outline-light { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .btn-outline-light:hover { background: rgba(255,255,255,0.2); }
        .btn-accent { background: #10b981; color: #fff; }
        .btn-accent:hover { background: #059669; transform: translateY(-1px); }

        /* Alert Messages */
        .alert { padding: 16px 20px; border-radius: 16px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* Stats Grid */
        .stats-grid { display: grid; gap: 16px; grid-template-columns: repeat(4, 1fr); margin-bottom: 24px; }
        @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 500px) { .stats-grid { grid-template-columns: 1fr; } }
        .stat-card { padding: 24px; border-radius: 20px; background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(15,23,42,0.04); transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(15,23,42,0.08); }
        .stat-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; margin-bottom: 14px; }
        .stat-icon.income { background: rgba(16,185,129,0.12); color: #10b981; }
        .stat-icon.expense { background: rgba(239,68,68,0.12); color: #ef4444; }
        .stat-icon.balance { background: rgba(59,130,246,0.12); color: #3b82f6; }
        .stat-icon.total { background: rgba(139,92,246,0.12); color: #8b5cf6; }
        .stat-card h2 { margin: 0 0 4px; font-size: 1.65rem; font-weight: 800; }
        .stat-card h2.text-green { color: #10b981; }
        .stat-card h2.text-red { color: #ef4444; }
        .stat-card h2.text-blue { color: #3b82f6; }
        .stat-card p { margin: 0; color: #64748b; font-size: 0.88rem; font-weight: 500; }
        .stat-card .stat-count { font-size: 0.8rem; color: #94a3b8; margin-top: 6px; }

        /* Card */
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(15,23,42,0.04); }
        .card-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 6px; display: flex; align-items: center; gap: 10px; }
        .card-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; }

        /* Form */
        .expenditure-form { display: none; }
        .expenditure-form.active { display: block; animation: slideDown 0.35s ease; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-top: 20px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.88rem; color: #334155; margin-bottom: 7px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 14px; font-size: 0.95rem; font-family: inherit; background: #f8fafc; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.12); background: #fff; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .form-actions { display: flex; gap: 12px; margin-top: 22px; flex-wrap: wrap; }
        .btn-submit { display: inline-flex; align-items: center; gap: 8px; padding: 13px 28px; border-radius: 14px; background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; font-weight: 700; font-size: 0.95rem; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(239,68,68,0.3); }
        .btn-cancel { display: inline-flex; align-items: center; gap: 8px; padding: 13px 28px; border-radius: 14px; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.95rem; border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s; }
        .btn-cancel:hover { background: #e2e8f0; }

        /* Filter Row */
        .filter-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 20px; }
        .filter-row input, .filter-row select { padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 14px; font-size: 0.9rem; font-family: inherit; background: #f8fafc; }
        .filter-row input { width: 260px; }
        .filter-row input:focus, .filter-row select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
        .btn-filter { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 14px; background: #0f172a; color: #fff; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-filter:hover { background: #1e293b; transform: translateY(-1px); }

        /* Table */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; text-align: left; vertical-align: middle; font-size: 0.9rem; }
        th { background: #f8fafc; font-weight: 700; color: #475569; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em; }
        tbody tr { transition: background 0.15s; }
        tbody tr:hover { background: #f8fafc; }
        .text-muted { color: #94a3b8; font-size: 0.82rem; }

        /* Type Badges */
        .type-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; font-size: 0.8rem; font-weight: 700; }
        .type-badge.income { background: rgba(16,185,129,0.1); color: #059669; }
        .type-badge.expenditure { background: rgba(239,68,68,0.1); color: #dc2626; }
        .amount-income { color: #059669; font-weight: 700; }
        .amount-expense { color: #dc2626; font-weight: 700; }

        /* Empty State */
        .empty-state { text-align: center; padding: 48px 20px; color: #94a3b8; }
        .empty-state i { font-size: 2.5rem; margin-bottom: 14px; display: block; }
        .empty-state p { font-size: 1rem; margin: 0; }
    </style>
</head>
<body>
    <div class="admin-panel">
        <!-- Header -->
        <div class="header-card">
            <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:16px; align-items:center;">
                <div>
                    <h1><i class="fas fa-receipt"></i> Transaction History</h1>
                    <p>Track all income and expenditure. Manage your organization finances.</p>
                </div>
                <div class="header-actions">
                    <button onclick="toggleExpForm()" class="btn-accent" id="toggleFormBtn">
                        <i class="fas fa-plus"></i> Add Expenditure
                    </button>
                    <a href="/admin/support_us.php" class="btn-outline-light">
                        <i class="fas fa-hand-holding-dollar"></i> Support Requests
                    </a>
                    <a href="/admin/dashboard/" class="btn-outline-light">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Add Expenditure Form -->
        <div class="card expenditure-form <?php echo (!empty($errors)) ? 'active' : ''; ?>" id="expenditureForm">
            <h3 class="card-title"><i class="fas fa-file-invoice-dollar" style="color:#ef4444;"></i> Record Expenditure</h3>
            <p class="card-subtitle">Add a new expenditure entry. The amount will be deducted from the net balance.</p>
            <form method="post" action="">
                <input type="hidden" name="action" value="add_expenditure">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="exp_amount"><i class="fas fa-bangladeshi-taka-sign"></i> Amount *</label>
                        <input type="number" id="exp_amount" name="amount" placeholder="e.g. 5000" step="0.01" min="0.01" required value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="exp_category"><i class="fas fa-tag"></i> Category</label>
                        <select id="exp_category" name="category">
                            <option value="office_supplies">Office Supplies</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="logistics">Logistics / Delivery</option>
                            <option value="marketing">Marketing</option>
                            <option value="salary">Salary / Payment</option>
                            <option value="utilities">Utilities</option>
                            <option value="event">Event / Program</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exp_txn_id"><i class="fas fa-hashtag"></i> Reference / Transaction ID</label>
                        <input type="text" id="exp_txn_id" name="transaction_id" placeholder="Auto-generated if empty" value="<?php echo htmlspecialchars($_POST['transaction_id'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="exp_invoice"><i class="fas fa-file-alt"></i> Invoice Number</label>
                        <input type="text" id="exp_invoice" name="invoice_number" placeholder="Auto-generated if empty" value="<?php echo htmlspecialchars($_POST['invoice_number'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-grid" style="grid-template-columns: 1fr; margin-top: 0;">
                    <div class="form-group">
                        <label for="exp_desc"><i class="fas fa-align-left"></i> Description *</label>
                        <textarea id="exp_desc" name="description" placeholder="What was this expenditure for?" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Record Expenditure</button>
                    <button type="button" class="btn-cancel" onclick="toggleExpForm()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon income"><i class="fas fa-arrow-down"></i></div>
                <h2 class="text-green">৳<?php echo number_format($totalIncome, 2); ?></h2>
                <p>Total Income</p>
                <div class="stat-count"><?php echo $incomeCount; ?> transaction<?php echo $incomeCount !== 1 ? 's' : ''; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon expense"><i class="fas fa-arrow-up"></i></div>
                <h2 class="text-red">৳<?php echo number_format($totalExpenditure, 2); ?></h2>
                <p>Total Expenditure</p>
                <div class="stat-count"><?php echo $expenditureCount; ?> transaction<?php echo $expenditureCount !== 1 ? 's' : ''; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon balance"><i class="fas fa-scale-balanced"></i></div>
                <h2 class="text-blue">৳<?php echo number_format($netBalance, 2); ?></h2>
                <p>Net Balance</p>
                <div class="stat-count">Income − Expenditure</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon total"><i class="fas fa-list-ol"></i></div>
                <h2><?php echo $totalTransactions; ?></h2>
                <p>Total Transactions</p>
                <div class="stat-count">All records</div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
                <div>
                    <h3 class="card-title"><i class="fas fa-table-list" style="color:#3b82f6;"></i> All Transactions</h3>
                    <p class="card-subtitle">Showing <?php echo count($transactions); ?> record<?php echo count($transactions) !== 1 ? 's' : ''; ?></p>
                </div>
            </div>
            <div class="filter-row">
                <form method="get" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; width:100%;">
                    <select name="type">
                        <option value="all"<?php echo $filterType === 'all' ? ' selected' : ''; ?>>All Types</option>
                        <option value="income"<?php echo $filterType === 'income' ? ' selected' : ''; ?>>Income Only</option>
                        <option value="expenditure"<?php echo $filterType === 'expenditure' ? ' selected' : ''; ?>>Expenditure Only</option>
                    </select>
                    <input type="search" name="search" placeholder="Search invoice, transaction id, user, category..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
            <div class="table-responsive">
                <table>
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
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>No transactions found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $txn):
                                $isExpense = (float) $txn['amount'] < 0;
                                $typeClass = $isExpense ? 'expenditure' : 'income';
                                $typeLabel = $isExpense ? 'Expenditure' : 'Income';
                                $typeIcon = $isExpense ? 'fa-arrow-up' : 'fa-arrow-down';
                                $displayAmount = abs((float) $txn['amount']);
                            ?>
                                <tr>
                                    <td>
                                        <span class="type-badge <?php echo $typeClass; ?>">
                                            <i class="fas <?php echo $typeIcon; ?>"></i> <?php echo $typeLabel; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($txn['invoice_number']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($txn['user_name'] ?: $txn['support_user_name'] ?: 'System'); ?>
                                        <?php if (!empty($txn['user_email'])): ?>
                                            <br><span class="text-muted"><?php echo htmlspecialchars($txn['user_email']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $txn['provider'] ?? '-'))); ?></td>
                                    <td class="<?php echo $isExpense ? 'amount-expense' : 'amount-income'; ?>">
                                        <?php echo $isExpense ? '−' : '+'; ?> ৳<?php echo number_format($displayAmount, 2); ?>
                                    </td>
                                    <td><span class="text-muted"><?php echo htmlspecialchars($txn['transaction_id']); ?></span></td>
                                    <td>
                                        <span class="type-badge <?php echo $isExpense ? 'expenditure' : 'income'; ?>" style="font-size:0.75rem;">
                                            <?php echo htmlspecialchars(ucfirst($txn['status'])); ?>
                                        </span>
                                    </td>
                                    <td><span class="text-muted"><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($txn['created_at']))); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
</body>
</html>
