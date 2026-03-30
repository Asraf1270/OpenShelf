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
        .book-card { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; border: 1px solid #e2e8f0; }
        .owner-card { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; border: 1px solid #e2e8f0; }
        .contact-btn { display: inline-block; padding: 14px 28px; background-color: #25D366; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; margin: 15px 0; }
        .button { display: inline-block; padding: 14px 28px; background-color: #6366f1; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; margin-top: 15px; }
        .footer { padding: 20px 30px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-family: sans-serif; font-size: 13px; color: #64748b; }
        .footer p { margin: 0 0 10px; }
        @media only screen and (max-width: 600px) {
            .wrapper { padding: 20px 10px !important; }
            .content { padding: 30px 20px !important; }
            .header { padding: 30px 20px !important; }
            .contact-btn, .button { display: block; width: 80%; margin: 15px auto; text-align: center; }
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
                            <div class="logo">🎉</div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: sans-serif;">Request Approved!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155;">
                            <p style="margin: 0 0 15px;">Hello <strong><?php echo htmlspecialchars($borrower_name); ?></strong>,</p>
                            <p style="margin: 0 0 20px;">Great news! <strong><?php echo htmlspecialchars($owner_name); ?></strong> has approved your request to borrow:</p>
                            
                            <div class="book-card" style="background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; border: 1px solid #e2e8f0;">
                                <div class="book-title" style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 5px;">"<?php echo htmlspecialchars($book_title); ?>"</div>
                                <div class="book-author" style="color: #64748b;">by <?php echo htmlspecialchars($book_author); ?></div>
                            </div>
                            
                            <div class="owner-card" style="background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; border: 1px solid #e2e8f0;">
                                <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #0f172a;">📍 Pickup Information</h3>
                                <p style="margin: 0 0 8px;"><strong>Owner's Room:</strong> <?php echo htmlspecialchars($owner_room); ?></p>
                                <p style="margin: 0 0 8px;"><strong>Contact Number:</strong> <?php echo htmlspecialchars($owner_phone); ?></p>
                                <p style="margin: 0 0 15px;"><strong>Return By:</strong> <?php echo date('F j, Y', strtotime($due_date)); ?></p>
                                
                                <div style="text-align: center;">
                                    <a href="https://wa.me/88<?php echo preg_replace('/[^0-9]/', '', $owner_phone); ?>?text=Hello! I'm here to pick up '<?php echo htmlspecialchars($book_title); ?>'" class="contact-btn" style="display: inline-block; padding: 14px 28px; background-color: #25D366; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px;" target="_blank">
                                        WhatsApp Owner
                                    </a>
                                </div>
                            </div>
                            
                            <p style="margin: 20px 0 10px;"><strong>What to do next?</strong></p>
                            <ol style="margin: 0 0 20px; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">Contact the owner via WhatsApp to arrange pickup</li>
                                <li style="margin-bottom: 8px;">Meet at their room or agreed location</li>
                                <li style="margin-bottom: 8px;">Enjoy reading! Remember to return by the due date</li>
                            </ol>
                            
                            <div style="text-align: center; margin-top: 25px;">
                                <a href="<?php echo $base_url; ?>/requests/?id=<?php echo $request_id; ?>" class="button" style="display: inline-block; padding: 14px 28px; background-color: #6366f1; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px;">View Request Details</a>
                            </div>
                            
                            <p style="margin-top: 30px; font-size: 14px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: center;">Happy reading! If you need an extension, please contact the owner directly.</p>
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