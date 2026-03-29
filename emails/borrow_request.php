<?php
/**
 * Borrow Request Email Template
 * Sent to book owner when someone requests their book
 * 
 * Variables:
 * $owner_name - Book owner's name
 * $borrower_name - Requester's name
 * $book_title - Book title
 * $book_author - Book author
 * $duration_days - Requested duration
 * $message - Personal message from borrower
 * $request_id - Request ID
 * $borrower_department - Borrower's department
 * $borrower_session - Borrower's session
 * $borrower_room - Borrower's room number
 * $borrower_phone - Borrower's phone number
 * $base_url - Base URL
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Borrow Request - OpenShelf</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #f59e0b, #d97706); padding: 40px 20px; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .logo { font-size: 48px; margin-bottom: 10px; position: relative; }
        .header h1 { color: white; margin: 0; font-size: 28px; position: relative; }
        .content { padding: 40px 30px; }
        .book-card { background: #f1f5f9; border-radius: 16px; padding: 20px; margin: 20px 0; text-align: center; }
        .book-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
        .book-author { color: #6366f1; margin: 5px 0 0; }
        .borrower-info { background: #f8fafc; border-radius: 12px; padding: 15px; margin: 20px 0; border: 1px solid #e2e8f0; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .info-row:last-child { border-bottom: none; }
        .message-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 12px; margin: 20px 0; font-style: italic; }
        .button-group { display: flex; gap: 15px; justify-content: center; margin: 25px 0; flex-wrap: wrap; }
        .btn-approve { display: inline-block; padding: 10px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 40px; font-weight: 600; }
        .btn-reject { display: inline-block; padding: 10px 24px; background: #ef4444; color: white; text-decoration: none; border-radius: 40px; font-weight: 600; }
        .btn-approve:hover, .btn-reject:hover { transform: translateY(-2px); }
        .footer { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; background: #f8fafc; }
        @media (max-width: 480px) { .content { padding: 25px 20px; } .info-row { flex-direction: column; gap: 5px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">📖</div>
                <h1>New Borrow Request!</h1>
            </div>
            <div class="content">
                <p>Hello <strong><?php echo htmlspecialchars($owner_name); ?></strong>,</p>
                <p><strong><?php echo htmlspecialchars($borrower_name); ?></strong> wants to borrow your book!</p>
                
                <div class="book-card">
                    <div class="book-title">"<?php echo htmlspecialchars($book_title); ?>"</div>
                    <div class="book-author">by <?php echo htmlspecialchars($book_author); ?></div>
                </div>
                
                <div class="borrower-info">
                    <h3 style="margin: 0 0 10px 0;">📋 Borrower Details</h3>
                    <div class="info-row"><strong>Name:</strong> <?php echo htmlspecialchars($borrower_name); ?></div>
                    <div class="info-row"><strong>Department:</strong> <?php echo htmlspecialchars($borrower_department); ?></div>
                    <div class="info-row"><strong>Session:</strong> <?php echo htmlspecialchars($borrower_session); ?></div>
                    <div class="info-row"><strong>Room:</strong> <?php echo htmlspecialchars($borrower_room); ?></div>
                    <div class="info-row"><strong>Phone:</strong> <?php echo htmlspecialchars($borrower_phone); ?></div>
                    <div class="info-row"><strong>Duration:</strong> <?php echo $duration_days; ?> days</div>
                </div>
                
                <?php if (!empty($message)): ?>
                <div class="message-box">
                    <p style="margin: 0;"><strong>📝 Message from borrower:</strong></p>
                    <p style="margin: 10px 0 0 0;">"<?php echo nl2br(htmlspecialchars($message)); ?>"</p>
                </div>
                <?php endif; ?>
                
                <div class="button-group">
                    <a href="<?php echo $base_url; ?>/requests/?id=<?php echo $request_id; ?>" class="btn-approve">✓ Approve Request</a>
                    <a href="<?php echo $base_url; ?>/requests/?id=<?php echo $request_id; ?>" class="btn-reject">✗ Reject Request</a>
                </div>
                
                <p style="font-size: 14px; color: #64748b; text-align: center;">Please respond within 48 hours to keep the community active.</p>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>