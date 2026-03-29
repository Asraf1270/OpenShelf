<?php
/**
 * OpenShelf Terms of Service
 */

session_start();
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .terms-page {
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

        .terms-content {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
        }

        .terms-section {
            margin-bottom: 2rem;
        }

        .terms-section h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .terms-section p {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .terms-section ul {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }

        .terms-section li {
            margin-bottom: 0.5rem;
        }

        .last-updated {
            text-align: center;
            color: var(--text-tertiary);
            font-size: 0.8rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
    </style>
</head>
<body>

    <main>
        <div class="terms-page">
            <div class="hero-section">
                <h1>Terms of Service</h1>
                <p>Please read these terms carefully before using OpenShelf</p>
            </div>

            <div class="terms-content">
                <div class="terms-section">
                    <h2>1. Acceptance of Terms</h2>
                    <p>By accessing or using OpenShelf, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our service.</p>
                </div>

                <div class="terms-section">
                    <h2>2. Eligibility</h2>
                    <p>To use OpenShelf, you must:</p>
                    <ul>
                        <li>Be a currently enrolled student at a recognized university</li>
                        <li>Have a valid university email address</li>
                        <li>Be at least 18 years old</li>
                        <li>Provide accurate and complete registration information</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>3. User Accounts</h2>
                    <p>You are responsible for maintaining the confidentiality of your account credentials. You agree to:</p>
                    <ul>
                        <li>Notify us immediately of any unauthorized use of your account</li>
                        <li>Not share your account with others</li>
                        <li>Keep your profile information accurate and up-to-date</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>4. Book Sharing Rules</h2>
                    <p>When sharing books on OpenShelf, you agree to:</p>
                    <ul>
                        <li>Only share books you own and have permission to share</li>
                        <li>Accurately describe the condition of your books</li>
                        <li>Respond to borrow requests in a timely manner</li>
                        <li>Respect agreed return dates</li>
                        <li>Not share prohibited or inappropriate content</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>5. Borrowing Rules</h2>
                    <p>When borrowing books on OpenShelf, you agree to:</p>
                    <ul>
                        <li>Return books by the agreed date</li>
                        <li>Handle books with care and return them in the same condition</li>
                        <li>Communicate with owners if you need an extension</li>
                        <li>Report any damage to books</li>
                        <li>Not resell borrowed books</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>6. User Conduct</h2>
                    <p>You agree not to:</p>
                    <ul>
                        <li>Harass, threaten, or intimidate other users</li>
                        <li>Post false or misleading information</li>
                        <li>Use the platform for any illegal purpose</li>
                        <li>Attempt to gain unauthorized access to our systems</li>
                        <li>Upload malicious content or viruses</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>7. Content Ownership</h2>
                    <p>You retain ownership of the books you list and the reviews you write. By posting content on OpenShelf, you grant us permission to display and share it within the platform. You are responsible for ensuring you have the right to share any content you post.</p>
                </div>

                <div class="terms-section">
                    <h2>8. Limitation of Liability</h2>
                    <p>OpenShelf is a platform that connects users. We are not responsible for:</p>
                    <ul>
                        <li>The condition of books shared on the platform</li>
                        <li>Transactions between users</li>
                        <li>Loss or damage to books</li>
                        <li>User interactions outside the platform</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h2>9. Termination</h2>
                    <p>We reserve the right to suspend or terminate accounts that violate these terms. You may delete your account at any time by contacting support.</p>
                </div>

                <div class="terms-section">
                    <h2>10. Changes to Terms</h2>
                    <p>We may update these terms from time to time. Continued use of OpenShelf after changes constitutes acceptance of the new terms. We will notify users of significant changes via email or platform notification.</p>
                </div>

                <div class="terms-section">
                    <h2>11. Contact Information</h2>
                    <p>If you have any questions about these terms, please contact us at <a href="mailto:support@openshelf.com" style="color: #6366f1;">support@openshelf.com</a>.</p>
                </div>

                <div class="last-updated">
                    Last Updated: March 2024
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>