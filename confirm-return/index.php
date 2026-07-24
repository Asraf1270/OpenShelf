<?php
/**
 * OpenShelf – Confirm Return Page
 * Owner clicks the email link to confirm or reject physical receipt.
 */

session_start();

define('BASE_URL', 'https://duopenshelf.top');
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load mailer
$mailer = null;
try {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    require_once dirname(__DIR__) . '/lib/Mailer.php';
    $mailer = new Mailer();
} catch (Exception $e) {
    error_log('Mailer init failed in confirm-return: ' . $e->getMessage());
}

// ── helpers ─────────────────────────────────────────────────────────────────
function createNotification($userId, $type, $title, $message, $link) {
    try {
        $db   = getDB();
        $stmt = $db->prepare("
            INSERT INTO `notifications`
            (id, user_id, type, title, message, link, is_read, created_at, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)
        ");
        return $stmt->execute([
            'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
            $userId, $type, $title, $message, $link,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s', strtotime('+30 days'))
        ]);
    } catch (Exception $e) {
        error_log('Error creating notification: ' . $e->getMessage());
        return false;
    }
}

// ── token lookup ─────────────────────────────────────────────────────────────
$token = trim($_GET['token'] ?? '');
if (empty($token) || strlen($token) !== 64) {
    $pageError = 'Invalid or missing confirmation token.';
} else {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM borrow_requests WHERE return_confirmation_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $req  = $stmt->fetch();
    if (!$req) {
        $pageError = 'Token not found or already used.';
    } elseif ($req['return_confirmation_status'] !== 'pending_owner') {
        $alreadyDone = $req['return_confirmation_status']; // 'confirmed' | 'rejected'
    }
}

// ── determine intended action (GET param or form POST) ────────────────────────
$intendedAction = trim($_GET['action'] ?? 'confirm'); // 'confirm' or 'reject'

// ── process POST (rejection reason form) ─────────────────────────────────────
$actionResult = null;
if (!isset($pageError) && !isset($alreadyDone) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedAction    = trim($_POST['action'] ?? 'confirm');
    $rejectReason    = trim($_POST['reject_reason'] ?? '');

    $book    = getBookById($req['book_id']);
    $borrower = getUserById($req['borrower_id']);
    $owner    = getUserById($req['owner_id']);

    // Load user lists update helpers
    define('USERS_PATH', dirname(__DIR__) . '/users/');
    
    function updateUserBorrowedList($userId, $bookId) {
        $userFile = USERS_PATH . $userId . '.json';
        if (!file_exists($userFile)) return false;
        
        $userData = json_decode(file_get_contents($userFile), true);
        
        if (isset($userData['currently_borrowed'])) {
            $userData['currently_borrowed'] = array_values(array_filter(
                $userData['currently_borrowed'],
                function($id) use ($bookId) { return $id !== $bookId; }
            ));
            $userData['stats']['books_borrowed'] = count($userData['currently_borrowed']);
        }
        
        // Add to borrow history
        if (!isset($userData['borrow_history'])) {
            $userData['borrow_history'] = [];
        }
        $userData['borrow_history'][] = [
            'book_id' => $bookId,
            'returned_at' => date('Y-m-d H:i:s'),
            'status' => 'completed'
        ];
        
        return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT));
    }

    function updateOwnerLentList($userId, $bookId, $borrowerId) {
        $userFile = USERS_PATH . $userId . '.json';
        if (!file_exists($userFile)) return false;
        
        $userData = json_decode(file_get_contents($userFile), true);
        
        if (isset($userData['currently_lent'])) {
            $userData['currently_lent'] = array_values(array_filter(
                $userData['currently_lent'],
                function($id) use ($bookId) { return $id !== $bookId; }
            ));
            $userData['stats']['books_lent'] = count($userData['currently_lent']);
        }
        
        // Add to lent history
        if (!isset($userData['lent_history'])) {
            $userData['lent_history'] = [];
        }
        $userData['lent_history'][] = [
            'book_id' => $bookId,
            'returned_at' => date('Y-m-d H:i:s'),
            'returned_by' => $borrowerId
        ];
        
        // Sort lent_history by date desc and limit to 25
        usort($userData['lent_history'], function($a, $b) {
            $dateA = $a['returned_at'] ?? $a['date'] ?? '1970-01-01';
            $dateB = $b['returned_at'] ?? $b['date'] ?? '1970-01-01';
            return strtotime($dateB) <=> strtotime($dateA);
        });
        $userData['lent_history'] = array_slice($userData['lent_history'], 0, 25);
        
        return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    if ($postedAction === 'confirm') {
        // ── CONFIRM ──────────────────────────────────────────────────────────
        $history = json_decode($req['history'] ?? '[]', true);
        $history[] = [
            'action' => 'return_confirmed',
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $req['owner_id'],
            'user_name' => $req['owner_name'],
            'notes' => 'Owner confirmed physical receipt'
        ];

        $db->prepare("
            UPDATE borrow_requests
            SET status = 'returned',
                return_confirmation_status = 'confirmed',
                return_confirmed_at = ?,
                return_confirmation_token = NULL,
                history = ?,
                updated_at = ?
            WHERE id = ?
        ")->execute([date('Y-m-d H:i:s'), json_encode($history), date('Y-m-d H:i:s'), $req['id']]);

        // Make book available
        $db->prepare("UPDATE books SET status = 'available', updated_at = ? WHERE id = ?")
           ->execute([date('Y-m-d H:i:s'), $req['book_id']]);

        // Update JSON lists
        updateUserBorrowedList($req['borrower_id'], $req['book_id']);
        updateOwnerLentList($req['owner_id'], $req['book_id'], $req['borrower_id']);

        // Reset wishlist notified flags
        try {
            $db->prepare("UPDATE wishlist SET notified = 0, updated_at = ? WHERE book_id = ?")
               ->execute([date('Y-m-d H:i:s'), $req['book_id']]);
        } catch (Exception $e) { /* wishlist table may not exist */ }

        // In-app notifications
        createNotification($req['borrower_id'], 'return_confirmed', 'Return Confirmed',
            'Your return of "' . $req['book_title'] . '" has been confirmed by the owner.',
            '/requests/?id=' . $req['id']);

        createNotification($req['owner_id'], 'book_returned', 'Book Return Complete',
            '"' . $req['book_title'] . '" is now marked as available.',
            '/requests/?id=' . $req['id']);

        // Email borrower
        if ($mailer && !empty($borrower['personal_info']['email'])) {
            try {
                $mailer->sendTemplate(
                    $borrower['personal_info']['email'],
                    $borrower['personal_info']['name'] ?? $req['borrower_name'],
                    'return_confirmed_borrower',
                    [
                        'subject'       => 'Your Return of "' . $req['book_title'] . '" is Confirmed!',
                        'borrower_name' => $borrower['personal_info']['name'] ?? $req['borrower_name'],
                        'book_title'    => $req['book_title'],
                        'owner_name'    => $owner['personal_info']['name'] ?? $req['owner_name'],
                        'confirm_date'  => date('Y-m-d H:i'),
                        'book_id'       => $req['book_id'],
                        'base_url'      => BASE_URL,
                        'type'          => 'success',
                    ]
                );
            } catch (Exception $e) {
                error_log('Failed borrower confirm email: ' . $e->getMessage());
            }
        }

        $actionResult = 'confirmed';

    } else {
        // ── REJECT ───────────────────────────────────────────────────────────
        $history = json_decode($req['history'] ?? '[]', true);
        $history[] = [
            'action' => 'return_rejected',
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $req['owner_id'],
            'user_name' => $req['owner_name'],
            'notes' => $rejectReason ?: 'Owner indicated they did not physically receive the book'
        ];

        $db->prepare("
            UPDATE borrow_requests
            SET status = 'approved',
                return_confirmation_status = 'rejected',
                return_rejected_at = ?,
                return_reject_reason = ?,
                return_confirmation_token = NULL,
                history = ?,
                updated_at = ?
            WHERE id = ?
        ")->execute([date('Y-m-d H:i:s'), $rejectReason, json_encode($history), date('Y-m-d H:i:s'), $req['id']]);

        // Keep book as 'borrowed' (no change to books table)

        // Notify borrower in-app
        createNotification($req['borrower_id'], 'return_rejected', 'Return Not Confirmed',
            'The owner of "' . $req['book_title'] . '" has not received the book. Please contact them.',
            '/requests/?id=' . $req['id']);

        // Email borrower
        if ($mailer && !empty($borrower['personal_info']['email'])) {
            try {
                $mailer->sendTemplate(
                    $borrower['personal_info']['email'],
                    $borrower['personal_info']['name'] ?? $req['borrower_name'],
                    'return_rejected_borrower',
                    [
                        'subject'       => 'Action Required: Return of "' . $req['book_title'] . '" Not Confirmed',
                        'borrower_name' => $borrower['personal_info']['name'] ?? $req['borrower_name'],
                        'book_title'    => $req['book_title'],
                        'owner_name'    => $owner['personal_info']['name'] ?? $req['owner_name'],
                        'reject_reason' => $rejectReason,
                        'book_id'       => $req['book_id'],
                        'base_url'      => BASE_URL,
                        'type'          => 'danger',
                    ]
                );
            } catch (Exception $e) {
                error_log('Failed borrower reject email: ' . $e->getMessage());
            }
        }

        $actionResult = 'rejected';
    }
}

$pageTitle = 'Confirm Book Return';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $pageTitle; ?> | OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        @keyframes popIn  { 0%{transform:scale(.8);opacity:0} 70%{transform:scale(1.05)} 100%{transform:scale(1);opacity:1} }

        .cr-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: var(--surface-hover, #f8fafc);
        }
        .cr-card {
            width: 100%;
            max-width: 520px;
            background: var(--header-bg, #fff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,.08);
            overflow: hidden;
            animation: fadeUp .45s ease-out;
        }
        .cr-banner {
            height: 6px;
            background: linear-gradient(90deg, var(--primary,#2c3e50), var(--secondary,#4c9f8a));
        }
        .cr-body  { padding: 2.5rem 2rem; }
        .cr-icon  {
            width: 72px; height: 72px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; margin: 0 auto 1.25rem;
            animation: popIn .5s ease-out;
        }
        .cr-icon.warn    { background:rgba(245,158,11,.12); color:#f59e0b; }
        .cr-icon.success { background:rgba(16,185,129,.12); color:#10b981; }
        .cr-icon.danger  { background:rgba(239,68,68,.12);  color:#ef4444; }
        .cr-icon.info    { background:rgba(59,130,246,.12); color:#3b82f6; }

        .cr-title { font-size:1.4rem; font-weight:800; text-align:center; color:var(--text-primary,#0f172a); margin-bottom:.5rem; }
        .cr-sub   { text-align:center; color:var(--text-secondary,#5a6c7d); font-size:.9rem; margin-bottom:1.5rem; }

        .cr-book-card {
            background: linear-gradient(135deg,rgba(44,62,80,.05),rgba(76,159,138,.05));
            border: 1px solid var(--border,#e2e8f0);
            border-radius: 14px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .cr-book-title { font-weight:700; color:var(--text-primary,#0f172a); margin-bottom:.25rem; }
        .cr-book-meta  { font-size:.82rem; color:var(--text-secondary,#5a6c7d); display:flex; gap:1rem; flex-wrap:wrap; }
        .cr-book-meta span { display:flex; align-items:center; gap:.3rem; }

        .cr-actions { display:flex; gap:.75rem; }
        .cr-actions .btn { flex:1; justify-content:center; }
        .btn-confirm { background:linear-gradient(135deg,#10b981,#059669); border:none; color:#fff; }
        .btn-confirm:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(16,185,129,.35); }
        .btn-reject  { background:transparent; border:1.5px solid #ef4444; color:#ef4444; }
        .btn-reject:hover  { background:rgba(239,68,68,.06); }

        .cr-reject-form { margin-top:1.25rem; }
        .cr-reject-form textarea {
            width:100%; border-radius:10px; border:1.5px solid var(--border,#e2e8f0);
            padding:.75rem 1rem; font-size:.9rem; color:var(--text-primary,#0f172a);
            background:var(--header-bg,#fff); resize:vertical; min-height:90px;
            font-family:inherit;
        }
        .cr-reject-form textarea:focus { outline:none; border-color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.1); }

        .cr-result-icon { font-size:4rem; text-align:center; margin-bottom:1rem; }
        .cr-result-title { font-size:1.5rem; font-weight:800; text-align:center; margin-bottom:.6rem; }
        .cr-result-msg   { text-align:center; color:var(--text-secondary,#5a6c7d); font-size:.92rem; margin-bottom:1.75rem; }

        .cr-divider { height:1px; background:var(--border,#e2e8f0); margin:1.25rem 0; }
        .cr-footer  { text-align:center; font-size:.8rem; color:var(--text-tertiary,#94a3b8); padding:1rem 2rem 1.5rem; }

        [data-theme="dark"] .cr-card { background:#1e293b; }
        [data-theme="dark"] .cr-book-card { background:rgba(255,255,255,.03); }
        [data-theme="dark"] .cr-reject-form textarea { background:#0f172a; color:#cbd5e1; }
    </style>
</head>
<body>
<?php include dirname(__DIR__) . '/includes/header.php'; ?>
<main>
<div class="cr-wrap">
    <div class="cr-card">
        <div class="cr-banner"></div>
        <div class="cr-body">

            <?php if (isset($pageError)): ?>
            <!-- ── ERROR ─────────────────────────────────────────── -->
            <div class="cr-icon info"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="cr-title">Invalid Link</div>
            <div class="cr-sub"><?php echo htmlspecialchars($pageError); ?></div>
            <div style="text-align:center">
                <a href="/" class="btn btn-outline">Go to Homepage</a>
            </div>

            <?php elseif (isset($alreadyDone)): ?>
            <!-- ── ALREADY HANDLED ────────────────────────────────── -->
            <?php if ($alreadyDone === 'confirmed'): ?>
                <div class="cr-result-icon">✅</div>
                <div class="cr-result-title" style="color:#10b981">Already Confirmed</div>
                <div class="cr-result-msg">This return has already been confirmed. The book is marked as Available.</div>
            <?php else: ?>
                <div class="cr-result-icon">❌</div>
                <div class="cr-result-title" style="color:#ef4444">Already Processed</div>
                <div class="cr-result-msg">This return confirmation has already been handled.</div>
            <?php endif; ?>
            <div style="text-align:center">
                <a href="/requests/" class="btn btn-outline">View Requests</a>
            </div>

            <?php elseif ($actionResult === 'confirmed'): ?>
            <!-- ── SUCCESS CONFIRMED ──────────────────────────────── -->
            <div class="cr-result-icon">🎉</div>
            <div class="cr-result-title" style="color:#10b981">Return Confirmed!</div>
            <div class="cr-result-msg">
                You have confirmed receiving <strong>"<?php echo htmlspecialchars($req['book_title']); ?>"</strong>.
                The book is now marked as <strong>Available</strong> and the borrower has been notified.
            </div>
            <div style="text-align:center">
                <a href="/requests/" class="btn btn-confirm" style="display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 2rem;border-radius:12px;text-decoration:none;font-weight:700;">
                    <i class="fas fa-list"></i> View My Requests
                </a>
            </div>

            <?php elseif ($actionResult === 'rejected'): ?>
            <!-- ── REJECTED ───────────────────────────────────────── -->
            <div class="cr-result-icon">⚠️</div>
            <div class="cr-result-title" style="color:#ef4444">Not Received – Reported</div>
            <div class="cr-result-msg">
                You have indicated that you have <strong>not</strong> received
                <strong>"<?php echo htmlspecialchars($req['book_title']); ?>"</strong>.
                The borrower has been notified to contact you.
            </div>
            <div style="text-align:center">
                <a href="/requests/" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 2rem;border-radius:12px;text-decoration:none;">
                    <i class="fas fa-list"></i> View My Requests
                </a>
            </div>

            <?php else: ?>
            <!-- ── PENDING: show confirm/reject UI ───────────────── -->
            <?php $showReject = ($intendedAction === 'reject'); ?>

            <div class="cr-icon warn"><i class="fas fa-book-open"></i></div>
            <div class="cr-title">Confirm Book Return</div>
            <div class="cr-sub">
                Please confirm whether you have <strong>physically received</strong> your book back.
            </div>

            <!-- Book info -->
            <div class="cr-book-card">
                <div class="cr-book-title"><?php echo htmlspecialchars($req['book_title']); ?></div>
                <div class="cr-book-meta">
                    <span><i class="fas fa-user" style="color:var(--secondary,#4c9f8a)"></i>
                        Returned by: <?php echo htmlspecialchars($req['returned_by_name'] ?? $req['borrower_name']); ?>
                    </span>
                    <span><i class="far fa-calendar-alt" style="color:var(--secondary,#4c9f8a)"></i>
                        Filed: <?php echo date('M j, Y', strtotime($req['returned_at'] ?? $req['updated_at'])); ?>
                    </span>
                    <?php if (!empty($req['return_condition'])): ?>
                    <span><i class="fas fa-book" style="color:var(--secondary,#4c9f8a)"></i>
                        Condition: <?php echo $req['return_condition'] === 'damaged' ? '⚠️ Has Damage' : '✅ Good/Intact'; ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($req['notes'])): ?>
                <div style="margin-top:.75rem;font-size:.82rem;color:var(--text-secondary,#5a6c7d);border-top:1px solid var(--border,#e2e8f0);padding-top:.75rem;">
                    <strong>Borrower notes:</strong> <?php echo htmlspecialchars($req['notes']); ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!$showReject): ?>
            <!-- Confirm/Reject buttons -->
            <div class="cr-actions">
                <form method="POST" style="flex:1">
                    <input type="hidden" name="action" value="confirm">
                    <button type="submit" class="btn btn-confirm" style="width:100%;padding:.85rem;border-radius:12px;font-size:.95rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;">
                        <i class="fas fa-check-circle"></i> Yes, I Received It
                    </button>
                </form>
                <a href="?token=<?php echo urlencode($token); ?>&action=reject"
                   class="btn btn-reject" style="flex:1;padding:.85rem;border-radius:12px;font-size:.95rem;font-weight:700;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;">
                    <i class="fas fa-times-circle"></i> Not Received
                </a>
            </div>

            <?php else: ?>
            <!-- Reject form with optional reason -->
            <div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:1rem;margin-bottom:1.25rem;font-size:.88rem;color:#b91c1c;">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Before rejecting</strong>, please try contacting the borrower first.
                Rejecting will notify them and keep the book marked as borrowed.
            </div>
            <form method="POST" class="cr-reject-form">
                <input type="hidden" name="action" value="reject">
                <label class="form-label" style="font-size:.88rem;font-weight:600;margin-bottom:.5rem;display:block;">
                    Reason (optional)
                </label>
                <textarea name="reject_reason" placeholder="e.g. I have not yet received the book physically..."></textarea>
                <div style="display:flex;gap:.75rem;margin-top:1rem;">
                    <button type="submit" class="btn" style="flex:1;background:#ef4444;border:none;color:#fff;border-radius:12px;padding:.8rem;font-weight:700;cursor:pointer;">
                        <i class="fas fa-times-circle"></i> Confirm – I Did Not Receive It
                    </button>
                    <a href="?token=<?php echo urlencode($token); ?>" class="btn btn-outline" style="flex:1;border-radius:12px;padding:.8rem;text-align:center;text-decoration:none;">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </a>
                </div>
            </form>
            <?php endif; ?>

            <?php endif; ?>

        </div>
        <div class="cr-footer">
            OpenShelf &mdash; Secure book return confirmation.
            If you did not expect this email, you can ignore it.
        </div>
    </div>
</div>
</main>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>
