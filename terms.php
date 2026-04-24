<?php
/**
 * OpenShelf Terms of Service
 */

session_start();
include 'includes/header.php';
?>

<style>
    :root {
        --primary: #6366f1;
        --bg: #f8fafc;
        --surface: #ffffff;
        --text: #0f172a;
        --text-muted: #64748b;
        --border: #e2e8f0;
        --radius: 24px;
        --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
    }

    [data-theme="dark"] {
        --bg: #0f172a;
        --surface: #1e293b;
        --text: #f8fafc;
        --text-muted: #94a3b8;
        --border: #334155;
        --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
    }

    body {
        background-color: var(--bg);
        color: var(--text);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .terms-container {
        max-width: 900px;
        margin: 4rem auto;
        padding: 0 1.5rem;
    }

    .hero-section {
        text-align: center;
        margin-bottom: 4rem;
    }

    .hero-section h1 {
        font-size: clamp(2.5rem, 6vw, 3.5rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, var(--primary), #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .terms-card {
        background: var(--surface);
        padding: 4rem;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
    }

    .terms-section {
        margin-bottom: 3.5rem;
    }

    .terms-section h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .terms-section h2 i {
        color: var(--primary);
        font-size: 1.25rem;
    }

    .terms-section p {
        color: var(--text-muted);
        line-height: 1.8;
        margin-bottom: 1.25rem;
        font-size: 1.05rem;
    }

    .terms-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .terms-section li {
        position: relative;
        padding-left: 2rem;
        margin-bottom: 1rem;
        color: var(--text-muted);
        line-height: 1.6;
    }

    .terms-section li::before {
        content: '→';
        position: absolute;
        left: 0;
        color: var(--primary);
        font-weight: 800;
    }

    .important-note {
        background: rgba(99, 102, 241, 0.05);
        border-left: 4px solid var(--primary);
        padding: 1.5rem;
        border-radius: 0 12px 12px 0;
        margin: 1.5rem 0;
    }

    .important-note p {
        margin: 0;
        font-weight: 600;
        color: var(--text);
    }

    .last-updated {
        text-align: center;
        margin-top: 4rem;
        color: var(--text-muted);
        font-size: 0.9rem;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .terms-card { padding: 2.5rem 1.5rem; }
        .hero-section { margin-bottom: 2rem; }
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