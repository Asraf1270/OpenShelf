<?php
/**
 * Book Returned Email Template (for owner)
 * Sent to book owner when their book is returned
 * 
 * Variables:
 * $owner_name - Book owner's name
 * $book_title - Book title
 * $return_date - Date of return
 * $borrower_name - Borrower's name
 * $base_url - Base URL
 * $book_id - Book ID (optional)
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Book Has Been Returned - OpenShelf</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #10b981, #059669); padding: 40px 20px; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .logo { font-size: 48px; margin-bottom: 10px; position: relative; }
        .header h1 { color: white; margin: 0; font-size: 28px; position: relative; }
        .content { padding: 40px 30px; }
        .success-icon { text-align: center; font-size: 64px; margin: 10px 0; }
        .book-card { background: #f1f5f9; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; }
        .button { display: inline-block; padding: 10px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 40px; font-weight: 600; margin-top: 15px; }
        .footer { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; background: #f8fafc; }
        @media (max-width: 480px) { .content { padding: 25px 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">📖</div>
                <h1>Your Book Has Been Returned!</h1>
            </div>
            <div class="content">
                <div class="success-icon">✅</div>
                <p>Hello <strong><?php echo htmlspecialchars($owner_name); ?></strong>,</p>
                <p>Great news! <strong><?php echo htmlspecialchars($borrower_name); ?></strong> has returned your book <strong>"<?php echo htmlspecialchars($book_title); ?>"</strong> on <strong><?php echo date('F j, Y', strtotime($return_date)); ?></strong>.</p>
                
                <div class="book-card">
                    <p style="margin: 0; font-weight: 600;">"<?php echo htmlspecialchars($book_title); ?>"</p>
                    <p style="margin: 5px 0 0; color: #64748b;">Successfully returned</p>
                </div>
                
                <p>Your book is now available for others to borrow again. You can list it back in the library if it's not already marked as available.</p>
                
                <div style="text-align: center;">
                    <a href="<?php echo $base_url; ?>/book/?id=<?php echo $book_id ?? ''; ?>" class="button">View Your Book</a>
                </div>
                
                <p style="margin-top: 30px; font-size: 14px; color: #64748b;">Thank you for sharing with the community! Your contribution makes OpenShelf better for everyone.</p>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>