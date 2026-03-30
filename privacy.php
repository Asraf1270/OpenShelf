<?php
/**
 * OpenShelf Privacy Policy
 */

session_start();
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - OpenShelf</title>
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
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            background-color: var(--bg);
            font-family: 'Outfit', system-ui, -apple-system, sans-serif;
            color: var(--text-main);
            line-height: 1.6;
        }

        .privacy-page {
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

        .privacy-content {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            padding: 4rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-premium);
        }

        .privacy-section {
            margin-bottom: 3rem;
        }

        .privacy-section h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .privacy-section h2::before {
            content: '';
            width: 4px;
            height: 24px;
            background: var(--primary);
            border-radius: 4px;
        }

        .privacy-section p {
            color: #475569;
            line-height: 1.8;
            margin-bottom: 1rem;
            font-size: 1.05rem;
        }

        .privacy-section ul {
            margin-left: 1.5rem;
            margin-bottom: 1.5rem;
            color: #475569;
        }

        .privacy-section li {
            margin-bottom: 0.75rem;
        }

        .last-updated {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(226, 232, 240, 0.5);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .privacy-page { padding: 2rem 1rem; }
            .privacy-content { padding: 2rem; }
        }
    </style>
</head>
<body>

    <main>
        <div class="privacy-page">
            <div class="hero-section">
                <h1>Privacy Policy</h1>
                <p>How we protect and handle your information</p>
            </div>

            <div class="privacy-content">
                <div class="privacy-section">
                    <h2>1. Information We Collect</h2>
                    <p>We collect the following information to provide and improve our services:</p>
                    <ul>
                        <li><strong>Account Information:</strong> Name, university email, department, session, room number, phone number</li>
                        <li><strong>Book Information:</strong> Books you add to the library, books you borrow, books you lend</li>
                        <li><strong>Activity Data:</strong> Requests, reviews, comments, and interactions</li>
                        <li><strong>Technical Data:</strong> IP address, browser type, device information</li>
                    </ul>
                </div>

                <div class="privacy-section">
                    <h2>2. How We Use Your Information</h2>
                    <p>Your information is used to:</p>
                    <ul>
                        <li>Create and manage your account</li>
                        <li>Facilitate book sharing and borrowing</li>
                        <li>Send notifications about requests and approvals</li>
                        <li>Improve our platform and user experience</li>
                        <li>Verify your student status</li>
                        <li>Respond to support inquiries</li>
                    </ul>
                </div>

                <div class="privacy-section">
                    <h2>3. Information Sharing</h2>
                    <p>We share your information only in these limited circumstances:</p>
                    <ul>
                        <li><strong>With other users:</strong> When you share books or request books, your name, room number, and phone number are visible to the other party</li>
                        <li><strong>To comply with law:</strong> If required by legal process or government request</li>
                        <li><strong>With your consent:</strong> When you explicitly agree to share information</li>
                    </ul>
                    <p>We never sell your personal information to third parties.</p>
                </div>

                <div class="privacy-section">
                    <h2>4. Data Security</h2>
                    <p>We implement security measures to protect your information:</p>
                    <ul>
                        <li>Password hashing for secure authentication</li>
                        <li>Encrypted connections (HTTPS)</li>
                        <li>Regular security updates</li>
                        <li>Access controls and monitoring</li>
                    </ul>
                </div>

                <div class="privacy-section">
                    <h2>5. Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access your personal information</li>
                        <li>Correct inaccurate information</li>
                        <li>Delete your account and associated data</li>
                        <li>Opt out of marketing communications</li>
                    </ul>
                    <p>To exercise these rights, contact us at <a href="mailto:privacy@openshelf.com" style="color: #6366f1;">privacy@openshelf.com</a>.</p>
                </div>

                <div class="privacy-section">
                    <h2>6. Data Retention</h2>
                    <p>We retain your information as long as your account is active. When you delete your account, we remove your personal information within 30 days. Some aggregated, anonymized data may be retained for analytics purposes.</p>
                </div>

                <div class="privacy-section">
                    <h2>7. Cookies</h2>
                    <p>We use cookies to:</p>
                    <ul>
                        <li>Keep you logged in</li>
                        <li>Remember your preferences</li>
                        <li>Analyze site usage</li>
                    </ul>
                    <p>You can disable cookies in your browser settings, but this may affect site functionality.</p>
                </div>

                <div class="privacy-section">
                    <h2>8. Third-Party Services</h2>
                    <p>We use the following third-party services:</p>
                    <ul>
                        <li>PHPMailer - For sending email notifications</li>
                        <li>Font Awesome - For icons</li>
                        <li>Google Fonts - For typography</li>
                    </ul>
                    <p>These services may collect data according to their own privacy policies.</p>
                </div>

                <div class="privacy-section">
                    <h2>9. Children's Privacy</h2>
                    <p>OpenShelf is not intended for users under 18. We do not knowingly collect information from minors. If we discover such information, we will delete it immediately.</p>
                </div>

                <div class="privacy-section">
                    <h2>10. Changes to This Policy</h2>
                    <p>We may update this privacy policy occasionally. We'll notify you of significant changes via email or platform notification. The "Last Updated" date at the bottom indicates when changes were made.</p>
                </div>

                <div class="privacy-section">
                    <h2>11. Contact Us</h2>
                    <p>If you have questions about this privacy policy, please contact us:</p>
                    <ul>
                        <li>Email: <a href="mailto:privacy@openshelf.com" style="color: #6366f1;">privacy@openshelf.com</a></li>
                        <li>Through the <a href="/contact/" style="color: #6366f1;">Contact Page</a></li>
                    </ul>
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