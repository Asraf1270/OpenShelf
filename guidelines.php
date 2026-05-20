<?php
/**
 * OpenShelf Community Guidelines
 */

session_start();
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Guidelines - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

    :root {
        --primary: #2C3E50;
        --secondary: #4C9F8A;
        --accent: #3A7B6B;
        --bg: #F8F9FA;
        --surface: #ffffff;
        --text-main: #0F172A;
        --text-muted: #5A6C7D;
        --border: #E2E8F0;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
        --shadow-md: 0 10px 30px -5px rgba(44, 62, 80, 0.08);
        --radius-lg: 24px;
        --radius-md: 16px;
        --radius-xl: 32px;
    }

    [data-theme="dark"] {
        --bg: #0F172A;
        --surface: #1E293B;
        --text-main: #F8F9FA;
        --text-muted: #94A3B8;
        --border: #334155;
        --shadow-md: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
    }

    body {
        background-color: var(--bg);
        color: var(--text-main);
        font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif;
        transition: background 0.3s ease;
    }

    .guidelines-page {
        max-width: 900px;
        margin: 6rem auto;
        padding: 0 1.5rem;
    }

    .hero-section {
        text-align: center;
        margin-bottom: 5rem;
    }

    .hero-section h1 {
        font-size: clamp(2.5rem, 6vw, 3.5rem);
        font-weight: 850;
        letter-spacing: -0.03em;
        margin-bottom: 1.5rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .guidelines-content {
        background: var(--surface);
        padding: 4.5rem;
        border-radius: var(--radius-xl);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
    }

    .guideline-section {
        margin-bottom: 4rem;
    }

    .guideline-section:last-of-type {
        margin-bottom: 0;
    }

    .guideline-section h2 {
        font-size: 1.6rem;
        font-weight: 850;
        margin-bottom: 1.75rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        letter-spacing: -0.5px;
        color: var(--primary);
    }

    [data-theme="dark"] .guideline-section h2 { color: var(--secondary); }

    .guideline-section h2::before {
        content: '';
        width: 6px;
        height: 28px;
        background: var(--secondary);
        border-radius: 4px;
    }

    .guideline-section p {
        color: var(--text-muted);
        line-height: 1.85;
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .guideline-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .guideline-section li {
        position: relative;
        padding-left: 2.5rem;
        margin-bottom: 1.25rem;
        color: var(--text-muted);
        line-height: 1.7;
        font-size: 1.05rem;
        font-weight: 500;
    }

    .guideline-section li::before {
        content: '→';
        position: absolute;
        left: 0;
        color: var(--secondary);
        font-weight: 800;
        font-size: 1.2rem;
        line-height: 1.4;
    }

    .dos-donts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
        margin: 2.5rem 0;
    }

    .dos {
        background: rgba(16, 185, 129, 0.03);
        padding: 2.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid rgba(16, 185, 129, 0.1);
        border-top: 5px solid #10b981;
    }

    .donts {
        background: rgba(239, 68, 68, 0.03);
        padding: 2.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid rgba(239, 68, 68, 0.1);
        border-top: 5px solid #ef4444;
    }

    .dos strong, .donts strong {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        font-size: 1.2rem;
        font-weight: 850;
        letter-spacing: -0.5px;
    }

    @media (max-width: 768px) {
        .guidelines-page { margin: 4rem auto; padding: 0 1rem; }
        .guidelines-content { padding: 2.5rem 1.5rem; border-radius: 24px; }
        .dos-donts { grid-template-columns: 1fr; gap: 1.5rem; }
        .guideline-section h2 { font-size: 1.35rem; }
    }
    </style>
</head>
<body>

    <main>
        <div class="guidelines-page">
            <div class="hero-section">
                <h1>Community Guidelines</h1>
                <p>Building a respectful and trustworthy community</p>
            </div>

            <div class="guidelines-content">
                <div class="guideline-section">
                    <h2>Our Shared Values</h2>
                    <p>OpenShelf is built on trust, respect, and a shared love of reading. By joining our community, you agree to uphold these values and help create a positive environment for everyone.</p>
                </div>

                <div class="guideline-section">
                    <h2>Book Sharing Etiquette</h2>
                    <div class="dos-donts">
                        <div class="dos">
                            <strong><i class="fas fa-check-circle" style="color: #10b981;"></i> DO:</strong>
                            <ul style="margin-top: 0.5rem;">
                                <li>Accurately describe book condition</li>
                                <li>Respond to requests within 48 hours</li>
                                <li>Communicate clearly about pickup times</li>
                                <li>Respect return dates</li>
                                <li>Keep books in good condition</li>
                            </ul>
                        </div>
                        <div class="donts">
                            <strong><i class="fas fa-times-circle" style="color: #ef4444;"></i> DON'T:</strong>
                            <ul style="margin-top: 0.5rem;">
                                <li>Misrepresent book condition</li>
                                <li>Ignore requests or messages</li>
                                <li>Cancel without notice</li>
                                <li>Damage books intentionally</li>
                                <li>Resell borrowed books</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="guideline-section">
                    <h2>Communication Guidelines</h2>
                    <ul>
                        <li><strong>Be Respectful:</strong> Treat all members with courtesy and kindness</li>
                        <li><strong>Be Responsive:</strong> Reply to messages within 24-48 hours</li>
                        <li><strong>Be Clear:</strong> Specify pickup times, locations, and expectations</li>
                        <li><strong>Use WhatsApp Wisely:</strong> Use WhatsApp for coordination only, not spam</li>
                    </ul>
                </div>

                <div class="guideline-section">
                    <h2>Book Condition Standards</h2>
                    <ul>
                        <li><strong>New:</strong> Brand new, never read</li>
                        <li><strong>Like New:</strong> Perfect condition, no visible wear</li>
                        <li><strong>Very Good:</strong> Minor wear, clean copy</li>
                        <li><strong>Good:</strong> Normal wear, may have markings</li>
                        <li><strong>Acceptable:</strong> Well-read, usable condition</li>
                        <li><strong>Poor:</strong> Damaged but readable - disclose damage details</li>
                    </ul>
                </div>

                <div class="guideline-section">
                    <h2>Reporting Issues</h2>
                    <p>If you encounter any issues with another user, please report it immediately:</p>
                    <ul>
                        <li><strong>Unresponsive Users:</strong> Report if a user doesn't respond to requests</li>
                        <li><strong>Damaged Books:</strong> Report if a book is returned in worse condition than described</li>
                        <li><strong>Harassment:</strong> Report any inappropriate behavior</li>
                        <li><strong>Fake Listings:</strong> Report books that don't exist or aren't available</li>
                    </ul>
                    <p>Use the "Report" button or contact support directly.</p>
                </div>

                <div class="guideline-section">
                    <h2>Consequences of Violations</h2>
                    <p>Depending on the severity, violations may result in:</p>
                    <ul>
                        <li>Warning notification</li>
                        <li>Temporary account suspension</li>
                        <li>Permanent account ban</li>
                        <li>Reporting to university authorities for serious violations</li>
                    </ul>
                </div>

                <div class="guideline-section">
                    <h2>Tips for a Great Experience</h2>
                    <ul>
                        <li>📸 Add clear cover images to attract more interest</li>
                        <li>💬 Include a friendly message when requesting books</li>
                        <li>⭐ Leave reviews after borrowing to help the community</li>
                        <li>📅 Set calendar reminders for return dates</li>
                        <li>🤝 Be flexible and understanding with others</li>
                    </ul>
                </div>

                <div class="guideline-section">
                    <h2>Safety Tips</h2>
                    <ul>
                        <li>Meet in public, well-lit areas when exchanging books</li>
                        <li>Share your location with a friend if meeting someone new</li>
                        <li>Trust your instincts - if something feels wrong, don't proceed</li>
                        <li>Never share sensitive personal information beyond what's needed</li>
                        <li>Use the WhatsApp chat to verify arrangements</li>
                    </ul>
                </div>

                <div class="guideline-section">
                    <h2>Questions or Concerns?</h2>
                    <p>If you're unsure about something or need help, reach out to us at <a href="mailto:support@openshelf.com" style="color: var(--secondary);">support@openshelf.com</a>. We're here to help make your experience positive and rewarding.</p>
                </div>

                <div class="last-updated" style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border); text-align: center; color: var(--text-tertiary); font-size: 0.8rem;">
                    Last Updated: March 2024
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>