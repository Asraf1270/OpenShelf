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
        .header { background: <?php echo $isOverdue ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' : 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'; ?>; padding: 40px 20px; text-align: center; }
        .logo { font-size: 48px; margin-bottom: 10px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; font-family: sans-serif; }
        .content { padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155; }
        .alert-box { background: <?php echo $isOverdue ? '#fef2f2' : '#fffbeb'; ?>; border-left: 4px solid <?php echo $isOverdue ? '#ef4444' : '#f59e0b'; ?>; padding: 20px; border-radius: 12px; margin: 20px 0; text-align: center; }
        .days-number { font-size: 48px; font-weight: 700; margin: 10px 0; color: <?php echo $isOverdue ? '#ef4444' : '#d97706'; ?>; }
        .book-card { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; border: 1px solid #e2e8f0; }
        .button { display: inline-block; padding: 14px 32px; background-color: <?php echo $isOverdue ? '#ef4444' : '#6366f1'; ?>; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; text-align: center; margin-top: 15px; }
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
                        <td class="header" style="background: <?php echo $isOverdue ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' : 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'; ?>; padding: 40px 20px; text-align: center;">
                            <div class="logo"><?php echo $isOverdue ? '⚠️' : '⏰'; ?></div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: sans-serif;"><?php echo $isOverdue ? 'Book Overdue!' : 'Book Due Soon'; ?></h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155;">
                            <p style="margin: 0 0 15px;">Hello <strong><?php echo htmlspecialchars($borrower_name); ?></strong>,</p>
                            
                            <div class="alert-box" style="background: <?php echo $isOverdue ? '#fef2f2' : '#fffbeb'; ?>; border-left: 4px solid <?php echo $isOverdue ? '#ef4444' : '#f59e0b'; ?>; padding: 20px; border-radius: 12px; margin: 20px 0; text-align: center;">
                                <p style="margin: 0; font-weight: 600; color: <?php echo $isOverdue ? '#b91c1c' : '#b45309'; ?>;"><?php echo $isOverdue ? 'This book is past its return date!' : 'This book is due soon!'; ?></p>
                                <div class="days-number" style="font-size: 48px; font-weight: 700; margin: 10px 0; color: <?php echo $isOverdue ? '#ef4444' : '#d97706'; ?>;"><?php echo $isOverdue ? $overdueDays : $days_remaining; ?> days</div>
                                <p style="margin: 0; color: <?php echo $isOverdue ? '#991b1b' : '#92400e'; ?>;"><?php echo $isOverdue ? 'overdue' : 'remaining'; ?></p>
                            </div>
                            
                            <div class="book-card" style="background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; border: 1px solid #e2e8f0;">
                                <p style="margin: 0 0 5px; font-weight: 600; font-size: 18px; color: #0f172a;">"<?php echo htmlspecialchars($book_title); ?>"</p>
                                <p style="margin: 0 0 15px; color: #64748b;">by <?php echo htmlspecialchars($book_author); ?></p>
                                <p style="margin: 0 0 5px;"><strong>Due Date:</strong> <?php echo date('F j, Y', strtotime($due_date)); ?></p>
                                <p style="margin: 0;"><strong>Owner:</strong> <?php echo htmlspecialchars($owner_name); ?></p>
                            </div>
                            
                            <p style="margin: 20px 0 10px;"><strong>What you need to do:</strong></p>
                            <?php if ($isOverdue): ?>
                            <ul style="margin: 0 0 20px; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">Return the book as soon as possible</li>
                                <li style="margin-bottom: 8px;">Contact the owner to arrange return</li>
                                <li style="margin-bottom: 8px;">If you need an extension, message the owner</li>
                            </ul>
                            <?php else: ?>
                            <ul style="margin: 0 0 20px; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">Plan to return the book by the due date</li>
                                <li style="margin-bottom: 8px;">Contact the owner to arrange return</li>
                                <li style="margin-bottom: 8px;">Request an extension if you need more time</li>
                            </ul>
                            <?php endif; ?>
                            
                            <div style="text-align: center; margin-top: 25px;">
                                <a href="<?php echo $base_url; ?>/requests/?id=<?php echo $request_id; ?>" class="button" style="display: inline-block; padding: 14px 32px; background-color: <?php echo $isOverdue ? '#ef4444' : '#6366f1'; ?>; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px;">View Request Details</a>
                            </div>
                            
                            <p style="margin-top: 30px; font-size: 14px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: center;"><?php echo $isOverdue ? 'Please return the book promptly to maintain good standing in the community.' : 'Thanks for being a responsible borrower!'; ?></p>
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