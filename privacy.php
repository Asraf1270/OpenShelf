<?php
/**
 * OpenShelf Privacy Policy
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

    .privacy-container {
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

    .privacy-card {
        background: var(--surface);
        padding: 4rem;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
    }

    .privacy-section {
        margin-bottom: 3.5rem;
    }

    .privacy-section h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .privacy-section h2 i {
        color: var(--primary);
        font-size: 1.25rem;
    }

    .privacy-section p {
        color: var(--text-muted);
        line-height: 1.8;
        margin-bottom: 1.25rem;
        font-size: 1.05rem;
    }

    .privacy-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .privacy-section li {
        position: relative;
        padding-left: 2rem;
        margin-bottom: 1rem;
        color: var(--text-muted);
        line-height: 1.6;
    }

    .privacy-section li::before {
        content: '•';
        position: absolute;
        left: 0;
        color: var(--primary);
        font-weight: 800;
        font-size: 1.5rem;
        line-height: 1;
    }

    .last-updated {
        text-align: center;
        margin-top: 4rem;
        color: var(--text-muted);
        font-size: 0.9rem;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .privacy-card { padding: 2.5rem 1.5rem; }
    }
</style>

<main class="privacy-container">
    <div class="hero-section">
        <h1>Privacy Policy</h1>
        <p style="color: var(--text-muted); font-size: 1.2rem;">How we handle and protect your data</p>
    </div>

    <div class="privacy-card">
        <div class="privacy-section">
            <h2><i class="fas fa-database"></i> 1. Data Collection</h2>
            <p>We collect only what is necessary to run the platform:</p>
            <ul>
                <li>Your university email for verification.</li>
                <li>Your name and department for your public profile.</li>
                <li>Your phone number (visible only to people you are actively borrowing from or lending to).</li>
            </ul>
        </div>

        <div class="privacy-section">
            <h2><i class="fas fa-shield-alt"></i> 2. Data Usage</h2>
            <p>Your data is used to facilitate book exchanges, send notifications about requests, and improve the overall user experience. We never sell your data to third parties.</p>
        </div>

        <div class="privacy-section">
            <h2><i class="fas fa-eye-slash"></i> 3. Visibility</h2>
            <p>Your exact location (room number) and phone number are kept private until a borrow request is approved. At that point, they are shared with the other party to coordinate the handoff.</p>
        </div>

        <div class="privacy-section">
            <h2><i class="fas fa-user-lock"></i> 4. Your Rights</h2>
            <p>You can edit your profile at any time. If you wish to delete your account and all associated data, you can do so through the settings page or by contacting support.</p>
        </div>

        <div class="last-updated">
            Last Updated: April 2024
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>