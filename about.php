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

    .about-container {
        max-width: 1000px;
        margin: 6rem auto;
        padding: 0 1.5rem;
    }

    .hero-section {
        text-align: center;
        margin-bottom: 6rem;
    }

    .hero-section h1 {
        font-size: clamp(2.5rem, 8vw, 4.5rem);
        font-weight: 850;
        letter-spacing: -0.04em;
        line-height: 1.05;
        margin-bottom: 2rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-p {
        font-size: 1.25rem;
        color: var(--text-muted);
        max-width: 700px;
        margin: 0 auto;
        font-weight: 500;
        line-height: 1.6;
    }

    .story-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 5rem;
        margin-bottom: 8rem;
        align-items: center;
    }

    .story-content h2 {
        font-size: 2.25rem;
        font-weight: 850;
        margin-bottom: 1.5rem;
        letter-spacing: -0.02em;
        color: var(--primary);
    }

    [data-theme="dark"] .story-content h2 { color: var(--secondary); }

    .story-content p {
        color: var(--text-muted);
        line-height: 1.8;
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }

    .stats-card {
        background: var(--surface);
        padding: 3.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
        text-align: center;
        transition: transform 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .stat-number {
        display: block;
        font-size: 3.5rem;
        font-weight: 850;
        color: var(--secondary);
        margin-bottom: 0.5rem;
        letter-spacing: -2px;
    }

    .stat-label {
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--text-muted);
        font-size: 0.85rem;
    }

    .features-section {
        background: var(--surface);
        padding: 5rem 4rem;
        border-radius: 40px;
        border: 1px solid var(--border);
        margin-bottom: 8rem;
        box-shadow: var(--shadow-sm);
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 4rem;
    }

    .feature-item h3 {
        font-size: 1.35rem;
        font-weight: 800;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        letter-spacing: -0.5px;
    }

    .feature-item h3 i {
        color: var(--secondary);
        font-size: 1.5rem;
    }

    .feature-item p {
        color: var(--text-muted);
        line-height: 1.7;
        font-size: 1.05rem;
    }

    @media (max-width: 768px) {
        .about-container { margin: 4rem auto; }
        .story-grid { grid-template-columns: 1fr; gap: 3rem; }
        .features-section { padding: 3rem 1.5rem; border-radius: 24px; }
        .hero-section { margin-bottom: 4rem; }
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
        <a href="/register/" style="display: inline-block; padding: 1rem 2.5rem; background: var(--primary); color: white; text-decoration: none; border-radius: 50px; font-weight: 700; box-shadow: 0 10px 20px rgba(44, 62, 80, 0.2);">Start Sharing Today</a>
    </section>
</main>

<?php include 'includes/footer.php'; ?>