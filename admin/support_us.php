<?php
/**
 * OpenShelf Admin Support Requests
 * View support submissions and approve them to generate transactions.
 */

session_start();

require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';

$success = '';
$error = '';

function loadSupportRequests($status = 'all', $search = '') {
    $db = getDB();
    $where = ['1=1'];
    $params = [];

    if ($status === 'pending') {
        $where[] = "s.status = 'pending'";
    } elseif ($status === 'approved') {
        $where[] = "s.status = 'approved'";
    }

    if (!empty($search)) {
        $where[] = '(s.user_name LIKE :search OR s.user_email LIKE :search OR s.provider LIKE :search OR s.transaction_id LIKE :search OR t.invoice_number LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    $sql = "SELECT s.*, t.id AS transaction_id, t.invoice_number, t.status AS transaction_status FROM support_us s LEFT JOIN transactions t ON t.support_us_id = s.id WHERE " . implode(' AND ', $where) . " ORDER BY s.submitted_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSupportRequest($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM support_us WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createInvoiceNumber() {
    return 'INV' . date('YmdHis') . strtoupper(substr(uniqid('', true), -6));
}

function createTransactionForSupport($support, $adminId, $adminName) {
    $db = getDB();
    $existing = $db->prepare("SELECT id FROM transactions WHERE support_us_id = ?");
    $existing->execute([$support['id']]);
    if ($existing->fetch()) {
        return true;
    }

    $transactionId = 'TRX' . strtoupper(substr(uniqid('', true), -10));
    $invoiceNumber = createInvoiceNumber();

    $stmt = $db->prepare("INSERT INTO transactions (id, support_us_id, user_id, user_name, user_email, provider, account_number, amount, transaction_id, invoice_number, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $transactionId,
        $support['id'],
        $support['user_id'],
        $support['user_name'],
        $support['user_email'],
        $support['provider'],
        $support['account_number'],
        $support['amount'],
        $support['transaction_id'],
        $invoiceNumber,
        'completed',
        $adminName,
        date('Y-m-d H:i:s')
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['support_id'])) {
    $action = $_POST['action'];
    $supportId = $_POST['support_id'];

    if ($action === 'approve') {
        $support = getSupportRequest($supportId);
        if (!$support) {
            $error = 'Support request not found.';
        } elseif ($support['status'] !== 'pending') {
            $error = 'Only pending requests can be approved.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("UPDATE support_us SET status = 'approved', approved_at = ?, approved_by = ?, invoice_number = ? WHERE id = ?");
            $invoiceNumber = createInvoiceNumber();
            $updated = $stmt->execute([date('Y-m-d H:i:s'), $adminId, $invoiceNumber, $supportId]);

            if ($updated) {
                $support = getSupportRequest($supportId);
                if (createTransactionForSupport($support, $adminId, $adminName)) {
                    $success = 'Support request approved and transaction created successfully.';
                } else {
                    $error = 'Support approved, but failed to create a transaction record.';
                }
            } else {
                $error = 'Failed to approve support request.';
            }
        }
    }
}

$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$supportRequests = loadSupportRequests($status, $search);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Support Requests - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8fafc; color: #0f172a; }
        .admin-panel { max-width: 1200px; margin: 0 auto; padding: 32px; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; padding: 28px; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(15,23,42,0.04); }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: middle; }
        th { background: #f1f5f9; font-weight: 700; }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 999px; font-size: 0.85rem; font-weight: 700; }
        .badge-pending { background: rgba(245, 158, 11, 0.16); color: #d97706; }
        .badge-approved { background: rgba(16, 185, 129, 0.16); color: #10b981; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 0.9rem 1.3rem; border-radius: 14px; border: none; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .btn-primary { background: #4c9f8a; color: #fff; }
        .btn-secondary { background: #0f172a; color: #fff; }
        .btn:hover { transform: translateY(-1px); }
        .alert { padding: 16px 20px; border-radius: 18px; margin-bottom: 18px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .filter-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 18px; }
        .filter-row input, .filter-row select { padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 14px; width: 220px; }
        .text-muted { color: #64748b; }
    </style>
</head>
<body>
    <div class="admin-panel">
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <h1 style="font-size:2rem; margin:0;">Support Requests</h1>
                    <p class="text-muted" style="margin:0.5rem 0 0;">Review submitted support requests and approve them to create transaction records.</p>
                </div>
                <a href="/admin/transaction.php" class="btn btn-secondary"><i class="fas fa-dollar-sign"></i> View Transactions</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="filter-row">
                <form method="get" style="display:flex; gap:12px; flex-wrap:wrap;">
                    <select name="status">
                        <option value="all"<?php echo $status === 'all' ? ' selected' : ''; ?>>All Status</option>
                        <option value="pending"<?php echo $status === 'pending' ? ' selected' : ''; ?>>Pending</option>
                        <option value="approved"<?php echo $status === 'approved' ? ' selected' : ''; ?>>Approved</option>
                    </select>
                    <input type="search" name="search" placeholder="Search user, provider, txn or invoice" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Submitted</th>
                            <th>User</th>
                            <th>Provider</th>
                            <th>Amount</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                            <th>Invoice</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($supportRequests)): ?>
                            <tr><td colspan="8" style="text-align:center; padding: 24px;">No support requests found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($supportRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($request['submitted_at'] ?? $request['created_at'] ?? ''))); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($request['user_name'] ?: 'Unknown'); ?></strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($request['user_email'] ?: $request['user_phone'] ?: 'No contact'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars(ucfirst($request['provider'])); ?></td>
                                    <td><?php echo htmlspecialchars(number_format((float)$request['amount'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($request['transaction_id']); ?></td>
                                    <td><span class="badge badge-<?php echo $request['status'] === 'approved' ? 'approved' : 'pending'; ?>"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($request['invoice_number'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <form method="post" style="margin:0;">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="support_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                                <button type="submit" class="btn btn-primary">Approve</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Approved</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
