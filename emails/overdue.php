<?php
/**
 * Overdue Book Email Template
 * Sent when a book is overdue
 * 
 * Variables:
 * $borrower_name - Borrower's name
 * $book_title - Book title
 * $due_date - Expected return date
 * $overdue_days - Days overdue
 * $owner_name - Book owner's name
 * $owner_phone - Owner's phone number
 * $request_id - Request ID
 * $base_url - Base URL
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚠️ Book Overdue - OpenShelf</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #ef4444, #dc2626); padding: 40px 20px; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .logo { font-size: 48px; margin-bottom: 10px; position: relative; }
        .header h1 { color: white; margin: 0; font-size: 28px; position: relative; }
        .content { padding: 40px 30px; }
        .urgent-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; text-align: center; margin: 20px 0; border-radius: 12px; }
        .days-number { font-size: 48px; font-weight: 700; margin: 10px 0; color: #ef4444; }
        .book-card { background: #f1f5f9; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; }
        .contact-btn { display: inline-block; padding: 10px 24px; background: #25D366; color: white; text-decoration: none; border-radius: 40px; font-weight: 600; margin: 10px 5px; }
        .button { display: inline-block; padding: 10px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 40px; font-weight: 600; margin: 10px 5px; }
        .footer { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; background: #f8fafc; }
        @media (max-width: 480px) { .content { padding: 25px 20px; } .contact-btn, .button { display: block; margin: 10px 0; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">⚠️</div>
                <h1>Book Overdue!</h1>
            </div>
            <div class="content">
                <div class="urgent-box">
                    <p style="margin: 0; font-weight: 600;">URGENT: This book is overdue!</p>
                    <div class="days-number"><?php echo $overdue_days; ?> days</div>
                    <p style="margin: 0;">past the return date</p>
                </div>
                
                <p>Hello <strong><?php echo htmlspecialchars($borrower_name); ?></strong>,</p>
                <p>This is a reminder that <strong>"<?php echo htmlspecialchars($book_title); ?>"</strong> is now <strong><?php echo $overdue_days; ?> days overdue</strong>.</p>
                
                <div class="book-card">
                    <p style="margin: 0; font-weight: 600;">"<?php echo htmlspecialchars($book_title); ?>"</p>
                    <p><strong>Due Date:</strong> <?php echo date('F j, Y', strtotime($due_date)); ?></p>
                    <p><strong>Owner:</strong> <?php echo htmlspecialchars($owner_name); ?></p>
                </div>
                
                <p><strong>Please take immediate action:</strong></p>
                <ul>
                    <li>Return the book as soon as possible</li>
                    <li>Contact the owner to arrange return</li>
                    <li>If you need an extension, message the owner directly</li>
                </ul>
                
                <div style="text-align: center;">
                    <?php if (!empty($owner_phone)): ?>
                    <a href="https://wa.me/88<?php echo preg_replace('/[^0-9]/', '', $owner_phone); ?>?text=Hello! I'm returning the book '<?php echo htmlspecialchars($book_title); ?>'" class="contact-btn" target="_blank">
                        <i class="fab fa-whatsapp"></i> Contact Owner
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo $base_url; ?>/requests/?id=<?php echo $request_id; ?>" class="button">View Request Details</a>
                </div>
                
                <p style="margin-top: 30px; font-size: 14px; color: #ef4444;"><strong>Note:</strong> Extended overdue periods may affect your borrowing privileges. Please return the book promptly.</p>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>