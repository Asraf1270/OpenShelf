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
        .faq-page {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .hero-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0f172a, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .faq-category {
            margin-bottom: 2rem;
        }

        .category-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border);
        }

        .faq-item {
            background: white;
            border-radius: 1rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .faq-question {
            padding: 1.25rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
            transition: background 0.2s;
        }

        .faq-question:hover {
            background: var(--surface-hover);
        }

        .faq-question i {
            color: #6366f1;
            transition: transform 0.2s;
        }

        .faq-question.active i {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 1.25rem 1.25rem 1.25rem;
            display: none;
            color: var(--text-secondary);
            line-height: 1.6;
            border-top: 1px solid var(--border);
        }

        .faq-answer.show {
            display: block;
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
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            answer.classList.toggle('show');
            element.classList.toggle('active');
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>