<?php
/**
 * OpenShelf Terms of Service
 */

session_start();
include 'includes/header.php';
?>

<style>
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

    .terms-container {
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

    .terms-card {
        background: var(--surface);
        padding: 4.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
    }

    .terms-section {
        margin-bottom: 4rem;
    }

    .terms-section:last-of-type {
        margin-bottom: 0;
    }

    .terms-section h2 {
        font-size: 1.6rem;
        font-weight: 850;
        margin-bottom: 1.75rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        letter-spacing: -0.5px;
        color: var(--primary);
    }

    [data-theme="dark"] .terms-section h2 { color: var(--secondary); }

    .terms-section h2 i {
        color: var(--secondary);
        font-size: 1.4rem;
        background: rgba(76, 159, 138, 0.1);
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .terms-section p {
        color: var(--text-muted);
        line-height: 1.85;
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .terms-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .terms-section li {
        position: relative;
        padding-left: 2.5rem;
        margin-bottom: 1.25rem;
        color: var(--text-muted);
        line-height: 1.7;
        font-size: 1.05rem;
        font-weight: 500;
    }

    .terms-section li::before {
        content: '→';
        position: absolute;
        left: 0;
        color: var(--secondary);
        font-weight: 800;
        font-size: 1.2rem;
        line-height: 1.4;
    }

    .important-note {
        background: rgba(76, 159, 138, 0.05);
        border-left: 4px solid var(--secondary);
        padding: 2rem;
        border-radius: 0 16px 16px 0;
        margin: 2rem 0;
    }

    .important-note p {
        margin: 0;
        font-weight: 700;
        color: var(--text-main);
        font-size: 1.1rem;
        line-height: 1.6;
    }

    .last-updated {
        text-align: center;
        margin-top: 5rem;
        color: var(--text-muted);
        font-size: 0.9rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        opacity: 0.7;
    }

    @media (max-width: 768px) {
        .terms-container { margin: 4rem auto; }
        .terms-card { padding: 2.5rem 1.5rem; border-radius: 24px; }
        .terms-section h2 { font-size: 1.35rem; gap: 0.75rem; }
        .terms-section h2 i { width: 40px; height: 40px; font-size: 1.1rem; }
    }
</style>

<main class="terms-container">
    <div class="hero-section">
        <h1>Terms of Service</h1>
        <p style="color: var(--text-muted); font-size: 1.2rem;">Our guidelines for a better community</p>
    </div>

    <div class="terms-card">
        <div class="terms-section">
            <h2><i class="fas fa-check-circle"></i> 1. Acceptance</h2>
            <p>By using OpenShelf, you agree to these terms. We aim to foster a safe, trust-based environment for sharing knowledge.</p>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-user-graduate"></i> 2. Eligibility</h2>
            <ul>
                <li>Current university student with a valid .edu email.</li>
                <li>At least 18 years of age.</li>
                <li>Provide accurate personal information during registration.</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-book"></i> 3. Contribution Rule</h2>
            <div class="important-note">
                <p>To maintain a healthy library, every user is required to list at least 2 books for sharing within 30 days of registration.</p>
            </div>
            <p>This ensures that our community continues to grow and that everyone contributes to the shared pool of knowledge.</p>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-hand-holding-heart"></i> 4. Sharing Rules</h2>
            <ul>
                <li>Only list books you actually own.</li>
                <li>Accurately describe the condition of the book.</li>
                <li>Respond to borrow requests within 48 hours.</li>
                <li>Coordinate safe handoffs on campus.</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-undo"></i> 5. Borrowing & Returns</h2>
            <ul>
                <li>Return books on or before the agreed date.</li>
                <li>Handle books with care; no writing or highlighting unless permitted by the owner.</li>
                <li>If a book is lost or damaged, you are responsible for replacement or compensation.</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-shield-alt"></i> 6. User Conduct</h2>
            <p>Harassment, spamming, or fraudulent activity will result in immediate and permanent account suspension. Respect your fellow students.</p>
        </div>

        <div class="last-updated">
            Last Updated: April 2024
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>