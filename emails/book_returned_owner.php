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
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 20px; text-align: center; }
        .logo { font-size: 48px; margin-bottom: 10px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; font-family: sans-serif; }
        .content { padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155; }
        .success-icon { text-align: center; font-size: 64px; margin: 10px 0; }
        .book-card { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; border: 1px solid #e2e8f0; }
        .button { display: inline-block; padding: 14px 32px; background-color: #6366f1; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; text-align: center; margin-top: 15px; }
        .footer { padding: 20px 30px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-family: sans-serif; font-size: 13px; color: #64748b; }
        .footer p { margin: 0 0 10px; }
        @media only screen and (max-width: 600px) {
            .wrapper { padding: 20px 10px !important; }
            .content { padding: 30px 20px !important; }
            .header { padding: 30px 20px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="wrapper" style="width: 100%; background-color: #f1f5f9;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="container" style="width: 100%; max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <tr>
                        <td class="header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 20px; text-align: center;">
                            <div class="logo">📖</div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: sans-serif;">Your Book Has Been Returned!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155;">
                            <div class="success-icon" style="text-align: center; font-size: 64px; margin: 10px 0;">✅</div>
                            <p style="margin: 0 0 15px;">Hello <strong><?php echo htmlspecialchars($owner_name); ?></strong>,</p>
                            <p style="margin: 0 0 20px;">Great news! <strong><?php echo htmlspecialchars($borrower_name); ?></strong> has returned your book <strong>"<?php echo htmlspecialchars($book_title); ?>"</strong> on <strong><?php echo date('F j, Y', strtotime($return_date)); ?></strong>.</p>
                            
                            <div class="book-card" style="background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; border: 1px solid #e2e8f0;">
                                <p style="margin: 0; font-weight: 600; font-size: 18px; color: #0f172a;">"<?php echo htmlspecialchars($book_title); ?>"</p>
                                <p style="margin: 5px 0 0; color: #64748b;">Successfully returned</p>
                            </div>
                            
                            <p style="margin: 20px 0;">Your book is now available for others to borrow again. You can list it back in the library if it's not already marked as available.</p>
                            
                            <div style="text-align: center;">
                                <a href="<?php echo $base_url; ?>/book/?id=<?php echo $book_id ?? ''; ?>" class="button" style="display: inline-block; padding: 14px 32px; background-color: #6366f1; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; text-align: center; margin-top: 15px;">View Your Book</a>
                            </div>
                            
                            <p style="margin-top: 30px; font-size: 14px; color: #64748b; text-align: center;">Thank you for sharing with the community! Your contribution makes OpenShelf better for everyone.</p>
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