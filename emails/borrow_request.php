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
    <!--[if mso]>
    <style type="text/css">
        table {border-collapse: collapse;}
        .container {border: 1px solid #e2e8f0;}
    </style>
    <![endif]-->
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; background-color: #f1f5f9; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; background-color: #f1f5f9; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 40px 20px; text-align: center; }
        .logo { font-size: 48px; margin-bottom: 10px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; font-family: sans-serif; }
        .content { padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155; }
        .book-card { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; border: 1px solid #e2e8f0; }
        .book-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
        .book-author { color: #6366f1; margin: 5px 0 0; }
        .borrower-info { background: #f8fafc; border-radius: 12px; padding: 15px; margin: 20px 0; border: 1px solid #e2e8f0; }
        .info-row { padding: 8px 0; border-bottom: 1px solid #e2e8f0; display: table; width: 100%; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; width: 40%; display: table-cell; vertical-align: middle; }
        .info-value { display: table-cell; vertical-align: middle; color: #475569; }
        .message-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 12px; margin: 20px 0; font-style: italic; }
        .button-group { text-align: center; margin: 25px 0; }
        .btn-approve { display: inline-block; padding: 14px 28px; background-color: #10b981; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; margin: 5px; }
        .btn-reject { display: inline-block; padding: 14px 28px; background-color: #ef4444; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; margin: 5px; }
        .footer { padding: 20px 30px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-family: sans-serif; font-size: 13px; color: #64748b; }
        .footer p { margin: 0 0 10px; }
        @media only screen and (max-width: 600px) {
            .wrapper { padding: 20px 10px !important; }
            .content { padding: 30px 20px !important; }
            .header { padding: 30px 20px !important; }
            .info-label, .info-value { display: block; width: 100%; margin-bottom: 5px; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="wrapper" style="width: 100%; background-color: #f1f5f9;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="container" style="width: 100%; max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <tr>
                        <td class="header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 40px 20px; text-align: center;">
                            <div class="logo">📖</div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: sans-serif;">New Borrow Request!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155;">
                            <p style="margin: 0 0 15px;">Hello <strong><?php echo htmlspecialchars($owner_name); ?></strong>,</p>
                            <p style="margin: 0 0 20px;"><strong><?php echo htmlspecialchars($borrower_name); ?></strong> wants to borrow your book!</p>
                            
                            <div class="book-card" style="background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; border: 1px solid #e2e8f0;">
                                <div class="book-title" style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;">"<?php echo htmlspecialchars($book_title); ?>"</div>
                                <div class="book-author" style="color: #6366f1; margin: 5px 0 0;">by <?php echo htmlspecialchars($book_author); ?></div>
                            </div>
                            
                            <div class="borrower-info" style="background: #f8fafc; border-radius: 12px; padding: 15px; margin: 20px 0; border: 1px solid #e2e8f0;">
                                <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #0f172a;">📋 Borrower Details</h3>
                                <div class="info-row"><div class="info-label">Name:</div> <div class="info-value"><?php echo htmlspecialchars($borrower_name); ?></div></div>
                                <div class="info-row"><div class="info-label">Department:</div> <div class="info-value"><?php echo htmlspecialchars($borrower_department); ?></div></div>
                                <div class="info-row"><div class="info-label">Session:</div> <div class="info-value"><?php echo htmlspecialchars($borrower_session); ?></div></div>
                                <div class="info-row"><div class="info-label">Room:</div> <div class="info-value"><?php echo htmlspecialchars($borrower_room); ?></div></div>
                                <div class="info-row"><div class="info-label">Phone:</div> <div class="info-value"><?php echo htmlspecialchars($borrower_phone); ?></div></div>
                                <div class="info-row" style="border-bottom: none;"><div class="info-label">Duration:</div> <div class="info-value"><?php echo $duration_days; ?> days</div></div>
                            </div>
                            
                            <?php if (!empty($message)): ?>
                            <div class="message-box" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 12px; margin: 20px 0; font-style: italic;">
                                <p style="margin: 0;"><strong>📝 Message from borrower:</strong></p>
                                <p style="margin: 10px 0 0 0;">"<?php echo nl2br(htmlspecialchars($message)); ?>"</p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="button-group" style="text-align: center; margin: 25px 0;">
                                <a href="<?php echo $base_url; ?>/requests/?id=<?php echo $request_id; ?>" class="btn-approve" style="display: inline-block; padding: 14px 28px; background-color: #10b981; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; margin: 5px;">✓ Approve Request</a>
                                <a href="<?php echo $base_url; ?>/requests/?id=<?php echo $request_id; ?>" class="btn-reject" style="display: inline-block; padding: 14px 28px; background-color: #ef4444; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; margin: 5px;">✗ Reject Request</a>
                            </div>
                            
                            <p style="font-size: 14px; color: #64748b; text-align: center;">Please respond within 48 hours to keep the community active.</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="footer" style="padding: 20px 30px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-family: sans-serif; font-size: 13px; color: #64748b;">
                            <p style="margin: 0;">&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                            <p style="margin: 5px 0 0;">This is an automated message, please do not reply.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>