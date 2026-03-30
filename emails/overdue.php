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
        .header { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 40px 20px; text-align: center; }
        .logo { font-size: 48px; margin-bottom: 10px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; font-family: sans-serif; }
        .content { padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155; }
        .urgent-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; text-align: center; margin: 20px 0; border-radius: 12px; }
        .days-number { font-size: 48px; font-weight: 700; margin: 10px 0; color: #ef4444; }
        .book-card { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; border: 1px solid #e2e8f0; }
        .contact-btn { display: inline-block; padding: 14px 28px; background-color: #25D366; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; margin: 5px; }
        .button { display: inline-block; padding: 14px 28px; background-color: #6366f1; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; margin: 5px; }
        .footer { padding: 20px 30px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-family: sans-serif; font-size: 13px; color: #64748b; }
        .footer p { margin: 0 0 10px; }
        @media only screen and (max-width: 600px) {
            .wrapper { padding: 20px 10px !important; }
            .content { padding: 30px 20px !important; }
            .header { padding: 30px 20px !important; }
            .contact-btn, .button { display: block; margin: 10px auto; width: 80%; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="wrapper" style="width: 100%; background-color: #f1f5f9;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="container" style="width: 100%; max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <tr>
                        <td class="header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 40px 20px; text-align: center;">
                            <div class="logo">⚠️</div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: sans-serif;">Book Overdue!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155;">
                            <div class="urgent-box" style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; text-align: center; margin: 20px 0; border-radius: 12px;">
                                <p style="margin: 0; font-weight: 600; color: #b91c1c;">URGENT: This book is overdue!</p>
                                <div class="days-number" style="font-size: 48px; font-weight: 700; margin: 10px 0; color: #ef4444;"><?php echo $overdue_days; ?> days</div>
                                <p style="margin: 0; color: #991b1b;">past the return date</p>
                            </div>
                            
                            <p style="margin: 0 0 15px;">Hello <strong><?php echo htmlspecialchars($borrower_name); ?></strong>,</p>
                            <p style="margin: 0 0 20px;">This is a reminder that <strong>"<?php echo htmlspecialchars($book_title); ?>"</strong> is now <strong><?php echo $overdue_days; ?> days overdue</strong>.</p>
                            
                            <div class="book-card" style="background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; border: 1px solid #e2e8f0;">
                                <p style="margin: 0 0 10px; font-weight: 600; font-size: 18px; color: #0f172a;">"<?php echo htmlspecialchars($book_title); ?>"</p>
                                <p style="margin: 0 0 5px;"><strong>Due Date:</strong> <?php echo date('F j, Y', strtotime($due_date)); ?></p>
                                <p style="margin: 0;"><strong>Owner:</strong> <?php echo htmlspecialchars($owner_name); ?></p>
                            </div>
                            
                            <p style="margin: 20px 0 10px;"><strong>Please take immediate action:</strong></p>
                            <ul style="margin: 0 0 20px; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">Return the book as soon as possible</li>
                                <li style="margin-bottom: 8px;">Contact the owner to arrange return</li>
                                <li style="margin-bottom: 8px;">If you need an extension, message the owner directly</li>
                            </ul>
                            
                            <div style="text-align: center; margin: 25px 0;">
                                <?php if (!empty($owner_phone)): ?>
                                <a href="https://wa.me/88<?php echo preg_replace('/[^0-9]/', '', $owner_phone); ?>?text=Hello! I'm returning the book '<?php echo htmlspecialchars($book_title); ?>'" class="contact-btn" style="display: inline-block; padding: 14px 28px; background-color: #25D366; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; margin: 5px;">
                                    Contact Owner
                                </a>
                                <?php endif; ?>
                                <a href="<?php echo $base_url; ?>/requests/?id=<?php echo $request_id; ?>" class="button" style="display: inline-block; padding: 14px 28px; background-color: #6366f1; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; margin: 5px;">View Request</a>
                            </div>
                            
                            <p style="margin-top: 30px; font-size: 14px; color: #ef4444; border-top: 1px solid #e2e8f0; padding-top: 20px;"><strong>Note:</strong> Extended overdue periods may affect your borrowing privileges. Please return the book promptly.</p>
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