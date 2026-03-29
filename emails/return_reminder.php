<?php
/**
 * Return Reminder Email Template
 * Sent to borrower when book is due soon or overdue
 * 
 * Variables:
 * $borrower_name - Borrower's name
 * $book_title - Book title
 * $book_author - Book author
 * $due_date - Expected return date
 * $days_remaining - Days until due (negative if overdue)
 * $overdue_days - Days overdue (if applicable)
 * $owner_name - Book owner's name
 * $request_id - Request ID
 * $base_url - Base URL
 */
$isOverdue = $days_remaining < 0;
$overdueDays = abs($days_remaining);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isOverdue ? 'Book Overdue' : 'Book Due Soon'; ?> - OpenShelf</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: <?php echo $isOverdue ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #f59e0b, #d97706)'; ?>; padding: 40px 20px; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .logo { font-size: 48px; margin-bottom: 10px; position: relative; }
        .header h1 { color: white; margin: 0; font-size: 28px; position: relative; }
        .content { padding: 40px 30px; }
        .alert-box { background: <?php echo $isOverdue ? '#fef2f2' : '#fffbeb'; ?>; border-left: 4px solid <?php echo $isOverdue ? '#ef4444' : '#f59e0b'; ?>; padding: 20px; border-radius: 12px; margin: 20px 0; text-align: center; }
        .days-number { font-size: 48px; font-weight: 700; margin: 10px 0; }
        .book-card { background: #f1f5f9; border-radius: 12px; padding: 15px; margin: 20px 0; text-align: center; }
        .button { display: inline-block; padding: 10px 24px; background: <?php echo $isOverdue ? '#ef4444' : '#6366f1'; ?>; color: white; text-decoration: none; border-radius: 40px; font-weight: 600; margin-top: 10px; }
        .button:hover { transform: translateY(-2px); }
        .footer { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; background: #f8fafc; }
        @media (max-width: 480px) { .content { padding: 25px 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo"><?php echo $isOverdue ? '⚠️' : '⏰'; ?></div>
                <h1><?php echo $isOverdue ? 'Book Overdue!' : 'Book Due Soon'; ?></h1>
            </div>
            <div class="content">
                <p>Hello <strong><?php echo htmlspecialchars($borrower_name); ?></strong>,</p>
                
                <div class="alert-box">
                    <p style="margin: 0; font-weight: 600;"><?php echo $isOverdue ? 'This book is past its return date!' : 'This book is due soon!'; ?></p>
                    <div class="days-number"><?php echo $isOverdue ? $overdueDays : $days_remaining; ?> days</div>
                    <p style="margin: 0;"><?php echo $isOverdue ? 'overdue' : 'remaining'; ?></p>
                </div>
                
                <div class="book-card">
                    <p style="margin: 0; font-weight: 600;">"<?php echo htmlspecialchars($book_title); ?>"</p>
                    <p style="margin: 5px 0 0; color: #64748b;">by <?php echo htmlspecialchars($book_author); ?></p>
                    <p style="margin: 10px 0 0;"><strong>Due Date:</strong> <?php echo date('F j, Y', strtotime($due_date)); ?></p>
                    <p><strong>Owner:</strong> <?php echo htmlspecialchars($owner_name); ?></p>
                </div>
                
                <p><strong>What you need to do:</strong></p>
                <?php if ($isOverdue): ?>
                <ul>
                    <li>Return the book as soon as possible</li>
                    <li>Contact the owner to arrange return</li>
                    <li>If you need an extension, message the owner</li>
                </ul>
                <?php else: ?>
                <ul>
                    <li>Plan to return the book by the due date</li>
                    <li>Contact the owner to arrange return</li>
                    <li>Request an extension if you need more time</li>
                </ul>
                <?php endif; ?>
                
                <div style="text-align: center;">
                    <a href="<?php echo $base_url; ?>/requests/?id=<?php echo $request_id; ?>" class="button">View Request Details</a>
                </div>
                
                <p style="margin-top: 30px; font-size: 14px; color: #64748b;"><?php echo $isOverdue ? 'Please return the book promptly to maintain good standing in the community.' : 'Thanks for being a responsible borrower!'; ?></p>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>