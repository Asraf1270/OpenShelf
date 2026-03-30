<?php
/**
 * OpenShelf FAQ Page
 * Frequently Asked Questions
 */

session_start();
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --bg: #f8fafc;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-premium: 0 20px 40px -15px rgba(0, 0, 0, 0.1);
            --radius-lg: 24px;
            --radius-xl: 32px;
            --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            background-color: var(--bg);
            font-family: 'Outfit', system-ui, -apple-system, sans-serif;
            color: var(--text-main);
            line-height: 1.6;
        }

        .faq-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 4rem 1.5rem;
        }

        .hero-section {
            text-align: center;
            margin-bottom: 4rem;
        }

        .hero-section h1 {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #0f172a 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .hero-section p {
            font-size: 1.1rem;
            color: var(--text-muted);
        }

        .faq-category {
            margin-bottom: 4rem;
        }

        .category-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-left: 1rem;
            border-left: 4px solid var(--primary);
            color: var(--text-main);
        }

        .faq-item {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            border: 1px solid var(--glass-border);
            overflow: hidden;
            transition: var(--transition);
        }

        .faq-item:hover {
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateX(5px);
            background: rgba(255, 255, 255, 0.85);
            box-shadow: var(--shadow-premium);
        }

        .faq-question {
            padding: 1.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.2s;
        }

        .faq-question i {
            color: var(--primary);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            background: rgba(99, 102, 241, 0.1);
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .faq-item.active {
            border-color: var(--primary);
            background: white;
            box-shadow: var(--shadow-premium);
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
            background: var(--primary);
            color: white;
        }

        .faq-answer {
            padding: 0 1.5rem 1.5rem 1.5rem;
            display: none;
            color: #475569;
            line-height: 1.8;
            font-size: 1rem;
            animation: slideDown 0.3s ease-out;
        }

        .faq-answer.show {
            display: block;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <main>
        <div class="faq-page">
            <div class="hero-section">
                <h1>Frequently Asked Questions</h1>
                <p>Find answers to common questions about OpenShelf</p>
            </div>

            <div class="faq-category">
                <h2 class="category-title">Getting Started</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        How do I create an account?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Click the "Register" button in the top right corner. Fill in your details including your university email, department, session, and room number. After registration, your account will be pending admin approval. Once approved, you'll receive a confirmation email and can start using OpenShelf.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        What email should I use to register?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        You must use your university email address (e.g., name@university.edu.bd). This helps verify that you're a student and maintains the integrity of our community.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        How long does account approval take?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Account approval typically takes 24-48 hours. You'll receive an email notification once your account is approved. If you haven't received approval after 48 hours, please contact support.
                    </div>
                </div>
            </div>

            <div class="faq-category">
                <h2 class="category-title">Borrowing Books</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        How do I borrow a book?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Browse the book collection, click on a book you're interested in, then click the "Request to Borrow" button. You'll be asked to select a duration (7-30 days) and can leave a message for the owner. Once the owner approves your request, you can arrange pickup via WhatsApp.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        How long can I borrow a book?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        You can choose between 7, 14, 21, or 30 days when making a request. The standard borrowing period is 14 days, but you can request a longer duration if needed.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        What if the book is damaged?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        When returning a book, you'll be asked about its condition. If the book is damaged, please describe the damage. This helps maintain trust in the community. Owners can also report damage when books are returned.
                    </div>
                </div>
            </div>

            <div class="faq-category">
                <h2 class="category-title">Sharing Books</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        How do I add a book to the library?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Click "Add Book" in your profile menu. Fill in the book details including title, author, description, category, condition, and upload a cover image. Once submitted, the book will be available for others to borrow.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        Can I edit or delete my books?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Yes! You can edit or delete any book you've added. Go to your profile, find the book in your "Owned Books" section, and click the edit or delete buttons.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        What happens when someone requests my book?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        You'll receive a notification in your dashboard and an email. Go to "My Requests" to view pending requests. You can approve or reject requests. If you approve, the book will be marked as borrowed and you can coordinate pickup via WhatsApp.
                    </div>
                </div>
            </div>

            <div class="faq-category">
                <h2 class="category-title">Account & Privacy</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        Is my personal information safe?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Yes! We take privacy seriously. Your email is only used for account verification and notifications. Your phone number and room number are only visible to other members when you share books or request books from them. We never share your information with third parties.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        How do I reset my password?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Click "Forgot Password" on the login page. Enter your email address and we'll send you instructions to reset your password. If you don't receive the email, check your spam folder or contact support.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        Can I delete my account?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Yes, you can request account deletion by contacting support. Please note that this action is permanent and all your data will be removed from our system.
                    </div>
                </div>
            </div>

            <div class="faq-category">
                <h2 class="category-title">Technical Issues</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        I'm not receiving email notifications. What should I do?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Check your spam or junk folder first. If emails are going there, add noreply@openshelf.com to your contacts. If you still don't receive emails, contact support to verify your email address is correct.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        How do I report a bug or issue?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Please contact us through the Contact page with details about the issue you're experiencing. Include screenshots if possible, and we'll investigate and fix it as soon as possible.
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleAnswer(element) {
            const faqItem = element.parentElement;
            const answer = element.nextElementSibling;
            
            // Toggle current
            answer.classList.toggle('show');
            faqItem.classList.toggle('active');
            
            // Close others (optional)
            /*
            document.querySelectorAll('.faq-answer').forEach(el => {
                if (el !== answer) el.classList.remove('show');
            });
            document.querySelectorAll('.faq-item').forEach(el => {
                if (el !== faqItem) el.classList.remove('active');
            });
            */
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>