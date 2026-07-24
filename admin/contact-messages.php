<?php
session_start();
/**
 * OpenShelf Admin - Contact Messages Management
 * View and manage contact form submissions
 */
require_once dirname(__DIR__) . '/includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$db = getDB();
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $msgId = $_POST['msg_id'] ?? '';
    
    if ($action === 'reply') {
        $adminReply = trim($_POST['admin_reply'] ?? '');
        $repliedBy = $_SESSION['admin_name'] ?? 'Admin';
        
        if (empty($adminReply)) {
            $error = 'Reply message cannot be empty.';
        } else {
            // Save reply to database
            $stmt = $db->prepare("UPDATE contact_messages SET status = 'replied', admin_reply = ?, replied_by = ?, replied_at = NOW() WHERE id = ?");
            if ($stmt->execute([$adminReply, $repliedBy, $msgId])) {
                // Fetch the message details to send email
                $msgStmt = $db->prepare("SELECT name, email, subject FROM contact_messages WHERE id = ?");
                $msgStmt->execute([$msgId]);
                $msgData = $msgStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($msgData) {
                    // Send email to the user
                    $to = $msgData['email'];
                    $emailSubject = 'Re: ' . $msgData['subject'] . ' — OpenShelf Support';
                    $emailBody = "প্রিয় " . $msgData['name'] . ",\n\n";
                    $emailBody .= $adminReply . "\n\n";
                    $emailBody .= "---\n";
                    $emailBody .= "ধন্যবাদ,\n";
                    $emailBody .= $repliedBy . "\n";
                    $emailBody .= "OpenShelf Support Team\n";
                    $emailBody .= "https://duopenshelf.top\n";
                    
                    $headers = "From: OpenShelf Support <support@duopenshelf.top>\r\n";
                    $headers .= "Reply-To: support@duopenshelf.top\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    
                    if (@mail($to, $emailSubject, $emailBody, $headers)) {
                        $message = 'Reply saved and email sent to ' . htmlspecialchars($msgData['email']) . '.';
                    } else {
                        $message = 'Reply saved but email could not be sent. Use the mailto link to send manually.';
                    }
                } else {
                    $message = 'Reply saved successfully.';
                }
            } else {
                $error = 'Failed to save reply.';
            }
        }
    } elseif ($action === 'mark_read') {
        $stmt = $db->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $stmt->execute([$msgId]);
        $message = 'Message marked as read.';
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
        if ($stmt->execute([$msgId])) {
            $message = 'Message deleted.';
        } else {
            $error = 'Failed to delete message.';
        }
    }
}

// Filter
$statusFilter = $_GET['status'] ?? 'all';
$sql = "SELECT * FROM contact_messages WHERE 1=1";
$params = [];
if ($statusFilter !== 'all') { $sql .= " AND status = ?"; $params[] = $statusFilter; }
$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalMsgs = $db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$unreadMsgs = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();
$repliedMsgs = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'replied'")->fetchColumn();

$statusLabels = ['unread'=>'Unread','read'=>'Read','replied'=>'Replied'];
$statusColors = ['unread'=>'#f59e0b','read'=>'#3b82f6','replied'=>'#10b981'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Messages - OpenShelf Admin</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--primary:#2C3E50;--secondary:#4C9F8A;--bg:#F8F9FA;--surface:#fff;--border:#E2E8F0;--text-main:#0F172A;--text-muted:#5A6C7D;--shadow-sm:0 1px 3px rgba(0,0,0,.05);--radius-md:16px}
[data-theme="dark"]{--bg:#0F172A;--surface:#1E293B;--border:#334155;--text-main:#F8F9FA;--text-muted:#94A3B8}
body{background:var(--bg);color:var(--text-main);transition:background .3s}
.cp{max-width:1200px;margin:0 auto;padding:2rem 1rem}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2.5rem}
.stat-card{background:var(--surface);padding:1.75rem;border-radius:var(--radius-md);border:1px solid var(--border);box-shadow:var(--shadow-sm);text-align:center}
.stat-card .sv{font-size:2.25rem;font-weight:850;letter-spacing:-1px}
.stat-card .sl{color:var(--text-muted);font-size:.85rem;font-weight:600;margin-top:.5rem}
.filters-bar{display:flex;gap:1rem;margin-bottom:2rem;flex-wrap:wrap}
.filter-select{padding:.75rem 1.25rem;border:1.5px solid var(--border);border-radius:12px;background:var(--surface);color:var(--text-main);font-family:inherit;font-weight:600;font-size:.9rem;cursor:pointer}
.mc{background:var(--surface);border-radius:var(--radius-md);border:1px solid var(--border);box-shadow:var(--shadow-sm);margin-bottom:1.25rem;overflow:hidden;transition:all .3s}
.mc:hover{box-shadow:0 10px 25px -5px rgba(0,0,0,.08)}
.mc-h{display:flex;justify-content:space-between;align-items:center;padding:1.5rem 2rem;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:1rem}
.mc-h h3{font-size:1.1rem;font-weight:750;margin:0 0 .4rem}
.mm{display:flex;gap:1.5rem;color:var(--text-muted);font-size:.85rem;font-weight:500;flex-wrap:wrap}
.mm span i{margin-right:.35rem}
.sb{display:inline-block;padding:.35rem 1rem;border-radius:2rem;font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.mc-b{padding:1.5rem 2rem}
.mc-msg{color:var(--text-main);line-height:1.7;margin-bottom:1rem;white-space:pre-wrap}
.mc-a{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;padding:1.25rem 2rem;border-top:1px solid var(--border);background:rgba(0,0,0,.01)}
.af{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;flex:1}
.af textarea{padding:.6rem 1rem;border:1.5px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text-main);font-family:inherit;font-size:.85rem;flex:1;min-width:200px;resize:none;height:38px}
.ba{padding:.6rem 1.25rem;border:none;border-radius:10px;font-weight:700;font-size:.85rem;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:.4rem}
.bu{background:var(--primary);color:#fff}.bu:hover{background:var(--secondary);transform:translateY(-1px)}
.bm{background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2)}.bm:hover{background:#3b82f6;color:#fff}
.bd{background:rgba(239,68,68,.1);color:#ef4444;border:1px solid rgba(239,68,68,.2)}.bd:hover{background:#ef4444;color:#fff}
.reply-d{background:rgba(76,159,138,.05);border-left:3px solid var(--secondary);padding:1rem 1.25rem;border-radius:0 10px 10px 0;margin-top:1rem;font-size:.9rem}
.reply-d strong{color:var(--secondary);font-size:.8rem;text-transform:uppercase;letter-spacing:.5px}
.empty{text-align:center;padding:5rem 2rem;color:var(--text-muted)}
.empty i{font-size:4rem;margin-bottom:1.5rem;opacity:.3}
@media(max-width:768px){.mc-h,.mc-b,.mc-a{padding:1.25rem}.af{flex-direction:column;width:100%}.af textarea{width:100%}}
</style>
</head>
<body>
<?php include dirname(__DIR__) . '/includes/admin-header.php'; ?>
<main><div class="cp">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem">
<h1 style="margin:0;font-weight:850;letter-spacing:-1.5px"><i class="fas fa-envelope-open-text" style="color:var(--secondary);margin-right:.5rem"></i> Contact Messages</h1>
</div>

<?php if($message): ?><div style="background:rgba(16,185,129,.1);color:#10b981;padding:1rem 1.5rem;border-radius:12px;margin-bottom:1.5rem;font-weight:600;border:1px solid rgba(16,185,129,.2)"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if($error): ?><div style="background:rgba(239,68,68,.1);color:#ef4444;padding:1rem 1.5rem;border-radius:12px;margin-bottom:1.5rem;font-weight:600;border:1px solid rgba(239,68,68,.2)"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="stats-row">
<div class="stat-card"><div class="sv" style="color:var(--primary)"><?php echo $totalMsgs; ?></div><div class="sl">Total Messages</div></div>
<div class="stat-card"><div class="sv" style="color:#f59e0b"><?php echo $unreadMsgs; ?></div><div class="sl">Unread</div></div>
<div class="stat-card"><div class="sv" style="color:#10b981"><?php echo $repliedMsgs; ?></div><div class="sl">Replied</div></div>
</div>

<div class="filters-bar">
<select class="filter-select" onchange="applyFilters()" id="statusFilter">
<option value="all" <?php echo $statusFilter==='all'?'selected':''; ?>>All Status</option>
<option value="unread" <?php echo $statusFilter==='unread'?'selected':''; ?>>Unread</option>
<option value="read" <?php echo $statusFilter==='read'?'selected':''; ?>>Read</option>
<option value="replied" <?php echo $statusFilter==='replied'?'selected':''; ?>>Replied</option>
</select>
</div>

<div>
<?php if(empty($messages)): ?>
<div class="empty"><i class="fas fa-inbox"></i><h3>No messages found</h3><p>No contact messages matching your filter.</p></div>
<?php else: foreach($messages as $m): ?>
<div class="mc">
<div class="mc-h">
<div><h3><?php echo htmlspecialchars($m['subject']); ?></h3>
<div class="mm">
<span><i class="fas fa-user"></i> <?php echo htmlspecialchars($m['name']); ?></span>
<span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($m['email']); ?></span>
<span><i class="fas fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($m['created_at'])); ?></span>
</div></div>
<span class="sb" style="background:<?php echo $statusColors[$m['status']] ?? '#6b7280'; ?>20;color:<?php echo $statusColors[$m['status']] ?? '#6b7280'; ?>"><?php echo $statusLabels[$m['status']] ?? $m['status']; ?></span>
</div>
<div class="mc-b">
<div class="mc-msg"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
<?php if(!empty($m['admin_reply'])): ?>
<div class="reply-d"><strong>Admin Reply</strong><p style="margin:.5rem 0 0"><?php echo nl2br(htmlspecialchars($m['admin_reply'])); ?></p>
<p style="color:var(--text-muted);font-size:.8rem;margin-top:.5rem">— <?php echo htmlspecialchars($m['replied_by']); ?>, <?php echo date('d M Y', strtotime($m['replied_at'])); ?></p>
</div><?php endif; ?>
</div>
<div class="mc-a">
<form method="POST" class="af">
<input type="hidden" name="action" value="reply">
<input type="hidden" name="msg_id" value="<?php echo $m['id']; ?>">
<textarea name="admin_reply" placeholder="Write a reply..."><?php echo htmlspecialchars($m['admin_reply'] ?? ''); ?></textarea>
<button type="submit" class="ba bu"><i class="fas fa-paper-plane"></i> Send Reply</button>
</form>
<a href="mailto:<?php echo htmlspecialchars($m['email']); ?>?subject=<?php echo rawurlencode('Re: ' . $m['subject'] . ' — OpenShelf Support'); ?>&body=<?php echo rawurlencode("প্রিয় " . $m['name'] . ",\n\n\n\n---\nOpenShelf Support Team\nhttps://duopenshelf.top"); ?>" class="ba bml" title="Open in email client"><i class="fas fa-envelope"></i> Mailto</a>
<?php if($m['status']==='unread'): ?>
<form method="POST"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="msg_id" value="<?php echo $m['id']; ?>">
<button type="submit" class="ba bm"><i class="fas fa-eye"></i></button></form>
<?php endif; ?>
<form method="POST" onsubmit="return confirm('Delete this message?')">
<input type="hidden" name="action" value="delete"><input type="hidden" name="msg_id" value="<?php echo $m['id']; ?>">
<button type="submit" class="ba bd"><i class="fas fa-trash"></i></button></form>
</div></div>
<?php endforeach; endif; ?>
</div>
</div></main>

<script>
function applyFilters(){
const s=document.getElementById('statusFilter').value;
let u='/admin/contact-messages.php?';
if(s!=='all')u+='status='+s;
window.location.href=u;
}
</script>
<?php include dirname(__DIR__) . '/includes/admin-footer.php'; ?>
</body></html>
