<?php
/**
 * OpenShelf About Page
 * Information about the platform and team
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

    .about-container {
        max-width: 1000px;
        margin: 4rem auto;
        padding: 0 1.5rem;
    }

    .hero-section {
        text-align: center;
        margin-bottom: 5rem;
    }

    .hero-section h1 {
        font-size: clamp(2.5rem, 8vw, 4.5rem);
        font-weight: 800;
        letter-spacing: -0.04em;
        line-height: 1.1;
        margin-bottom: 1.5rem;
        background: linear-gradient(135deg, var(--primary), #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-p {
        font-size: 1.25rem;
        color: var(--text-muted);
        max-width: 700px;
        margin: 0 auto;
    }

    .story-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        margin-bottom: 8rem;
        align-items: center;
    }

    .story-content h2 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        letter-spacing: -0.02em;
    }

    .story-content p {
        color: var(--text-muted);
        line-height: 1.8;
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }

    .stats-card {
        background: var(--surface);
        padding: 3rem;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        text-align: center;
    }

    .stat-number {
        display: block;
        font-size: 3rem;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .features-section {
        background: var(--surface);
        padding: 5rem 3rem;
        border-radius: 40px;
        border: 1px solid var(--border);
        margin-bottom: 8rem;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 3rem;
    }

    .feature-item h3 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .feature-item h3 i {
        color: var(--primary);
    }

    .feature-item p {
        color: var(--text-muted);
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .story-grid { grid-template-columns: 1fr; gap: 2rem; }
        .features-section { padding: 3rem 1.5rem; border-radius: 24px; }
    }
</style>

<main class="about-container">
    <section class="hero-section">
        <h1>Reimagining Campus Reading</h1>
        <p class="hero-p">OpenShelf is a decentralized, student-driven library platform designed to make knowledge accessible and sharing effortless.</p>
    </section>

    <div class="story-grid">
        <div class="story-content">
            <h2>The OpenShelf Story</h2>
            <p>Born out of the need for affordable textbooks and a passion for reading, OpenShelf started as a simple idea: what if we could share our personal libraries with the people around us?</p>
            <p>Today, we're building a community where students can connect, share, and discover books without the barriers of cost or location. Every book on our shelf is a contribution from a fellow student.</p>
        </div>
        <div class="stats-card">
            <div style="margin-bottom: 2rem;">
                <span class="stat-number">100%</span>
                <span class="stat-label">Student Driven</span>
            </div>
            <div>
                <span class="stat-number">Infinite</span>
                <span class="stat-label">Possibilities</span>
            </div>
        </div>
    </div>

    <section class="features-section">
        <h2 style="text-align: center; margin-bottom: 4rem; font-size: 2rem; font-weight: 800;">Our Core Values</h2>
        <div class="features-grid">
            <div class="feature-item">
                <h3><i class="fas fa-heart"></i> Accessibility</h3>
                <p>Knowledge should never be behind a paywall. We believe in free access to literature and study materials.</p>
            </div>
            <div class="feature-item">
                <h3><i class="fas fa-users"></i> Community</h3>
                <p>Building trust between students is at the heart of everything we do. We're stronger when we share.</p>
            </div>
            <div class="feature-item">
                <h3><i class="fas fa-leaf"></i> Sustainability</h3>
                <p>By sharing books, we reduce waste and give physical copies a longer, more meaningful life.</p>
            </div>
        </div>
    </section>

    <section style="text-align: center;">
        <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem;">Join the Movement</h2>
        <p class="hero-p" style="margin-bottom: 3rem;">Ready to list your first book? Join thousands of students already on OpenShelf.</p>
        <a href="/register/" style="display: inline-block; padding: 1rem 2.5rem; background: var(--primary); color: white; text-decoration: none; border-radius: 50px; font-weight: 700; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);">Start Sharing Today</a>
    </section>
</main>

<?php include 'includes/footer.php'; ?>