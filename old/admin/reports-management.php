<?php
session_start();
/**
 * OpenShelf Admin - Reports Management
 * View and manage user-submitted reports
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
    $reportId = $_POST['report_id'] ?? '';
    
    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        $resolvedBy = ($newStatus === 'resolved') ? ($_SESSION['admin_name'] ?? 'Admin') : null;
        $resolvedAt = ($newStatus === 'resolved') ? date('Y-m-d H:i:s') : null;
        
        $stmt = $db->prepare("UPDATE reports SET status = ?, admin_notes = ?, resolved_by = ?, resolved_at = ? WHERE id = ?");
        if ($stmt->execute([$newStatus, $adminNotes, $resolvedBy, $resolvedAt, $reportId])) {
            $message = 'Report status updated successfully.';
        } else {
            $error = 'Failed to update report status.';
        }
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM reports WHERE id = ?");
        if ($stmt->execute([$reportId])) {
            $message = 'Report deleted successfully.';
        } else {
            $error = 'Failed to delete report.';
        }
    }
}

// Filter
$statusFilter = $_GET['status'] ?? 'all';
$typeFilter = $_GET['type'] ?? 'all';
$sql = "SELECT * FROM reports WHERE 1=1";
$params = [];
if ($statusFilter !== 'all') { $sql .= " AND status = ?"; $params[] = $statusFilter; }
if ($typeFilter !== 'all') { $sql .= " AND type = ?"; $params[] = $typeFilter; }
$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalReports = $db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$pendingReports = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$resolvedReports = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();

$typeLabels = ['bug'=>'Bug','user'=>'User','book'=>'Book','suggestion'=>'Suggestion','other'=>'Other'];
$statusLabels = ['pending'=>'Pending','in_progress'=>'In Progress','resolved'=>'Resolved','dismissed'=>'Dismissed'];
$statusColors = ['pending'=>'#f59e0b','in_progress'=>'#3b82f6','resolved'=>'#10b981','dismissed'=>'#6b7280'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports Management - OpenShelf Admin</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--primary:#2C3E50;--secondary:#4C9F8A;--bg:#F8F9FA;--surface:#fff;--border:#E2E8F0;--text-main:#0F172A;--text-muted:#5A6C7D;--shadow-sm:0 1px 3px rgba(0,0,0,.05);--radius-md:16px}
[data-theme="dark"]{--bg:#0F172A;--surface:#1E293B;--border:#334155;--text-main:#F8F9FA;--text-muted:#94A3B8}
body{background:var(--bg);color:var(--text-main);transition:background .3s}
.rp{max-width:1200px;margin:0 auto;padding:2rem 1rem}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2.5rem}
.stat-card{background:var(--surface);padding:1.75rem;border-radius:var(--radius-md);border:1px solid var(--border);box-shadow:var(--shadow-sm);text-align:center}
.stat-card .sv{font-size:2.25rem;font-weight:850;letter-spacing:-1px}
.stat-card .sl{color:var(--text-muted);font-size:.85rem;font-weight:600;margin-top:.5rem}
.filters-bar{display:flex;gap:1rem;margin-bottom:2rem;flex-wrap:wrap}
.filter-select{padding:.75rem 1.25rem;border:1.5px solid var(--border);border-radius:12px;background:var(--surface);color:var(--text-main);font-family:inherit;font-weight:600;font-size:.9rem;cursor:pointer}
.rc{background:var(--surface);border-radius:var(--radius-md);border:1px solid var(--border);box-shadow:var(--shadow-sm);margin-bottom:1.25rem;overflow:hidden;transition:all .3s}
.rc:hover{box-shadow:0 10px 25px -5px rgba(0,0,0,.08)}
.rc-h{display:flex;justify-content:space-between;align-items:center;padding:1.5rem 2rem;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:1rem}
.rc-h h3{font-size:1.1rem;font-weight:750;margin:0 0 .4rem}
.rm{display:flex;gap:1.5rem;color:var(--text-muted);font-size:.85rem;font-weight:500;flex-wrap:wrap}
.rm span i{margin-right:.35rem}
.sb{display:inline-block;padding:.35rem 1rem;border-radius:2rem;font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.tb{display:inline-block;padding:.3rem .85rem;border-radius:8px;font-size:.78rem;font-weight:600;background:rgba(76,159,138,.1);color:var(--secondary)}
.rc-b{padding:1.5rem 2rem}
.rc-msg{color:var(--text-main);line-height:1.7;margin-bottom:1rem;white-space:pre-wrap}
.rc-a{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;padding:1.25rem 2rem;border-top:1px solid var(--border);background:rgba(0,0,0,.01)}
.af{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;flex:1}
.af select,.af textarea{padding:.6rem 1rem;border:1.5px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text-main);font-family:inherit;font-size:.85rem}
.af textarea{flex:1;min-width:200px;resize:none;height:38px}
.ba{padding:.6rem 1.25rem;border:none;border-radius:10px;font-weight:700;font-size:.85rem;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:.4rem}
.bu{background:var(--primary);color:#fff}.bu:hover{background:var(--secondary);transform:translateY(-1px)}
.bd{background:rgba(239,68,68,.1);color:#ef4444;border:1px solid rgba(239,68,68,.2)}.bd:hover{background:#ef4444;color:#fff}
.an-d{background:rgba(76,159,138,.05);border-left:3px solid var(--secondary);padding:1rem 1.25rem;border-radius:0 10px 10px 0;margin-top:1rem;font-size:.9rem}
.empty{text-align:center;padding:5rem 2rem;color:var(--text-muted)}
.empty i{font-size:4rem;margin-bottom:1.5rem;opacity:.3}
@media(max-width:768px){.rc-h,.rc-b,.rc-a{padding:1.25rem}.af{flex-direction:column;width:100%}.af textarea{width:100%}}
</style>
</head>
<body>
<?php include dirname(__DIR__) . '/includes/admin-header.php'; ?>
<main><div class="rp">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem">
<h1 style="margin:0;font-weight:850;letter-spacing:-1.5px"><i class="fas fa-flag" style="color:var(--secondary);margin-right:.5rem"></i> Reports Management</h1>
</div>

<?php if($message): ?><div style="background:rgba(16,185,129,.1);color:#10b981;padding:1rem 1.5rem;border-radius:12px;margin-bottom:1.5rem;font-weight:600;border:1px solid rgba(16,185,129,.2)"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if($error): ?><div style="background:rgba(239,68,68,.1);color:#ef4444;padding:1rem 1.5rem;border-radius:12px;margin-bottom:1.5rem;font-weight:600;border:1px solid rgba(239,68,68,.2)"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="stats-row">
<div class="stat-card"><div class="sv" style="color:var(--primary)"><?php echo $totalReports; ?></div><div class="sl">Total Reports</div></div>
<div class="stat-card"><div class="sv" style="color:#f59e0b"><?php echo $pendingReports; ?></div><div class="sl">Pending</div></div>
<div class="stat-card"><div class="sv" style="color:#10b981"><?php echo $resolvedReports; ?></div><div class="sl">Resolved</div></div>
</div>

<div class="filters-bar">
<select class="filter-select" onchange="applyFilters()" id="statusFilter">
<option value="all" <?php echo $statusFilter==='all'?'selected':''; ?>>All Status</option>
<option value="pending" <?php echo $statusFilter==='pending'?'selected':''; ?>>Pending</option>
<option value="in_progress" <?php echo $statusFilter==='in_progress'?'selected':''; ?>>In Progress</option>
<option value="resolved" <?php echo $statusFilter==='resolved'?'selected':''; ?>>Resolved</option>
<option value="dismissed" <?php echo $statusFilter==='dismissed'?'selected':''; ?>>Dismissed</option>
</select>
<select class="filter-select" onchange="applyFilters()" id="typeFilter">
<option value="all" <?php echo $typeFilter==='all'?'selected':''; ?>>All Types</option>
<option value="bug" <?php echo $typeFilter==='bug'?'selected':''; ?>>Bug</option>
<option value="user" <?php echo $typeFilter==='user'?'selected':''; ?>>User</option>
<option value="book" <?php echo $typeFilter==='book'?'selected':''; ?>>Book</option>
<option value="suggestion" <?php echo $typeFilter==='suggestion'?'selected':''; ?>>Suggestion</option>
<option value="other" <?php echo $typeFilter==='other'?'selected':''; ?>>Other</option>
</select>
</div>

<div>
<?php if(empty($reports)): ?>
<div class="empty"><i class="fas fa-inbox"></i><h3>No reports found</h3><p>No reports matching your filter.</p></div>
<?php else: foreach($reports as $r): ?>
<div class="rc">
<div class="rc-h">
<div><h3><?php echo htmlspecialchars($r['subject']); ?></h3>
<div class="rm">
<span><i class="fas fa-user"></i> <?php echo htmlspecialchars($r['name']); ?></span>
<span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($r['email']); ?></span>
<span><i class="fas fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($r['created_at'])); ?></span>
</div></div>
<div style="display:flex;gap:.75rem;align-items:center">
<span class="tb"><?php echo $typeLabels[$r['type']] ?? $r['type']; ?></span>
<span class="sb" style="background:<?php echo $statusColors[$r['status']] ?? '#6b7280'; ?>20;color:<?php echo $statusColors[$r['status']] ?? '#6b7280'; ?>"><?php echo $statusLabels[$r['status']] ?? $r['status']; ?></span>
</div></div>
<div class="rc-b">
<div class="rc-msg"><?php echo nl2br(htmlspecialchars($r['message'])); ?></div>
<?php if(!empty($r['admin_notes'])): ?><div class="an-d"><strong>Admin Notes</strong><p style="margin:.5rem 0 0"><?php echo nl2br(htmlspecialchars($r['admin_notes'])); ?></p></div><?php endif; ?>
<?php if($r['resolved_by']): ?><p style="color:var(--text-muted);font-size:.85rem;margin-top:1rem"><i class="fas fa-check-double"></i> Resolved by <strong><?php echo htmlspecialchars($r['resolved_by']); ?></strong> on <?php echo date('d M Y', strtotime($r['resolved_at'])); ?></p><?php endif; ?>
</div>
<div class="rc-a">
<form method="POST" class="af">
<input type="hidden" name="action" value="update_status">
<input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
<select name="status">
<option value="pending" <?php echo $r['status']==='pending'?'selected':''; ?>>Pending</option>
<option value="in_progress" <?php echo $r['status']==='in_progress'?'selected':''; ?>>In Progress</option>
<option value="resolved" <?php echo $r['status']==='resolved'?'selected':''; ?>>Resolved</option>
<option value="dismissed" <?php echo $r['status']==='dismissed'?'selected':''; ?>>Dismissed</option>
</select>
<textarea name="admin_notes" placeholder="Admin notes..."><?php echo htmlspecialchars($r['admin_notes'] ?? ''); ?></textarea>
<button type="submit" class="ba bu"><i class="fas fa-save"></i> Update</button>
</form>
<form method="POST" onsubmit="return confirm('Delete this report?')">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
<button type="submit" class="ba bd"><i class="fas fa-trash"></i></button>
</form>
</div></div>
<?php endforeach; endif; ?>
</div>
</div></main>

<script>
function applyFilters(){
const s=document.getElementById('statusFilter').value;
const t=document.getElementById('typeFilter').value;
let u='/admin/reports-management.php?';
const p=[];
if(s!=='all')p.push('status='+s);
if(t!=='all')p.push('type='+t);
window.location.href=u+p.join('&');
}
</script>
<?php include dirname(__DIR__) . '/includes/admin-footer.php'; ?>
</body></html>
