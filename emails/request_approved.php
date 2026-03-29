<?php
/**
 * Request Approved Email Template
 * Sent to borrower when their request is approved
 * 
 * Variables:
 * $borrower_name - Borrower's name
 * $owner_name - Book owner's name
 * $book_title - Book title
 * $book_author - Book author
 * $due_date - Expected return date
 * $owner_room - Owner's room number
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
    <title>Request Approved - OpenShelf</title>
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
        .book-card { background: #f1f5f9; border-radius: 16px; padding: 20px; margin: 20px 0; text-align: center; }
        .owner-card { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; border: 1px solid #e2e8f0; }
        .contact-btn { display: inline-block; padding: 10px 24px; background: #25D366; color: white; text-decoration: none; border-radius: 40px; font-weight: 600; margin: 10px 0; }
        .whatsapp-icon { display: inline-block; margin-right: 8px; }
        .button { display: inline-block; padding: 10px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 40px; font-weight: 600; margin-top: 15px; }
        .footer { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; background: #f8fafc; }
        @media (max-width: 480px) { .content { padding: 25px 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">🎉</div>
                <h1>Request Approved!</h1>
            </div>
            <div class="content">
                <p>Hello <strong><?php echo htmlspecialchars($borrower_name); ?></strong>,</p>
                <p>Great news! <strong><?php echo htmlspecialchars($owner_name); ?></strong> has approved your request to borrow:</p>
                
                <div class="book-card">
                    <div class="book-title" style="font-size: 20px; font-weight: 700;">"<?php echo htmlspecialchars($book_title); ?>"</div>
                    <div class="book-author">by <?php echo htmlspecialchars($book_author); ?></div>
                </div>
                
                <div class="owner-card">
                    <h3 style="margin: 0 0 10px 0;">📍 Pickup Information</h3>
                    <p><strong>Owner's Room:</strong> <?php echo htmlspecialchars($owner_room); ?></p>
                    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($owner_phone); ?></p>
                    <p><strong>Return By:</strong> <?php echo date('F j, Y', strtotime($due_date)); ?></p>
                    <a href="https://wa.me/88<?php echo preg_replace('/[^0-9]/', '', $owner_phone); ?>?text=Hello! I'm here to pick up '<?php echo htmlspecialchars($book_title); ?>'" class="contact-btn" target="_blank">
                        <i class="fab fa-whatsapp" style="margin-right: 8px;"></i> WhatsApp Owner
                    </a>
                </div>
                
                <p><strong>What to do next?</strong></p>
                <ol>
                    <li>Contact the owner via WhatsApp to arrange pickup</li>
                    <li>Meet at their room or agreed location</li>
                    <li>Enjoy reading! Remember to return by the due date</li>
                </ol>
                
                <div style="text-align: center;">
                    <a href="<?php echo $base_url; ?>/requests/?id=<?php echo $request_id; ?>" class="button">View Request Details</a>
                </div>
                
                <p style="margin-top: 30px; font-size: 14px; color: #64748b;">Happy reading! If you need an extension, please contact the owner directly.</p>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>