<?php
/**
 * OpenShelf - Reimagined Landing Page
 * A clean, informative, and modern entry point for the community library.
 */

session_start();

// Redirect logged-in users to books page
if (isset($_SESSION['user_id'])) {
    header('Location: /books/');
    exit;
}

// Include database connection
require_once __DIR__ . '/includes/db.php';

/**
 * Get statistics from DB
 */
function getStats() {
    $db = getDB();
    $stats = [];
    try {
        $stats['books'] = (int) $db->query("SELECT COUNT(*) FROM books")->fetchColumn();
        $stats['users'] = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['available'] = (int) $db->query("SELECT COUNT(*) FROM books WHERE status = 'available'")->fetchColumn();
    } catch (Exception $e) {
        $stats = ['books' => 0, 'users' => 0, 'available' => 0];
    }
    return $stats;
}

$stats = getStats();

include 'includes/header.php';
?>

<style>
    /* ---------------------------------------------------------
       NEW DESIGN SYSTEM: OPEN SHELF 2.0
       Mobile-first, Clean, Responsive, Theme-aware
    --------------------------------------------------------- */
    :root {
        --primary-brand: #6366f1;
        --primary-soft: rgba(99, 102, 241, 0.1);
        --accent-brand: #10b981;
        --surface-1: #ffffff;
        --surface-2: #f8fafc;
        --text-primary: #0f172a;
        --text-secondary: #475569;
        --border-color: rgba(226, 232, 240, 0.8);
        --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        --shadow-hover: 0 20px 30px -10px rgba(99, 102, 241, 0.15);
        --radius: 24px;
        --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    [data-theme="dark"] {
        --surface-1: #0f172a;
        --surface-2: #1e293b;
        --text-primary: #f8fafc;
        --text-secondary: #94a3b8;
        --border-color: rgba(51, 65, 85, 0.5);
        --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        --primary-soft: rgba(99, 102, 241, 0.2);
    }

    body {
        background-color: var(--surface-1);
        color: var(--text-primary);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        line-height: 1.6;
        margin: 0;
        padding: 0;
    }

    .landing-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }

    /* Hero Section */
    .hero {
        padding: 6rem 0 4rem;
        text-align: center;
    }

    .hero-h1 {
        font-size: clamp(2.5rem, 8vw, 4.5rem);
        font-weight: 800;
        letter-spacing: -0.04em;
        line-height: 1.1;
        margin-bottom: 1.5rem;
        background: linear-gradient(135deg, var(--primary-brand), #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-p {
        font-size: clamp(1.1rem, 2.5vw, 1.35rem);
        color: var(--text-secondary);
        max-width: 650px;
        margin: 0 auto 2.5rem;
    }

    .hero-cta {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-main {
        padding: 0.9rem 2.2rem;
        border-radius: 100px;
        font-weight: 700;
        text-decoration: none;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1rem;
    }

    .btn-primary {
        background: var(--primary-brand);
        color: white;
        box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(99, 102, 241, 0.3);
    }

    .btn-outline {
        border: 2.5px solid var(--border-color);
        color: var(--text-primary);
    }

    .btn-outline:hover {
        background: var(--surface-2);
        border-color: var(--primary-brand);
        transform: translateY(-3px);
    }

    /* About Cards */
    .section-title {
        text-align: center;
        margin: 6rem 0 3.5rem;
    }

    .section-title h2 {
        font-size: clamp(1.8rem, 4vw, 2.5rem);
        font-weight: 800;
        margin-bottom: 0.75rem;
        letter-spacing: -0.02em;
    }

    .section-title p {
        color: var(--text-secondary);
        font-size: 1.1rem;
    }

    .about-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 2.5rem;
        margin-bottom: 8rem;
    }

    .about-card {
        background: var(--surface-2);
        padding: 3rem 2.5rem;
        border-radius: var(--radius);
        border: 1px solid var(--border-color);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .about-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-hover);
        border-color: var(--primary-brand);
    }

    .card-icon {
        width: 64px;
        height: 64px;
        background: var(--primary-soft);
        color: var(--primary-brand);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 1.5rem;
        transition: var(--transition);
    }

    .about-card:hover .card-icon {
        background: var(--primary-brand);
        color: white;
        transform: rotate(8deg) scale(1.1);
    }

    .about-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .about-card p {
        color: var(--text-secondary);
        font-size: 1.05rem;
    }

    /* How it works */
    .how-it-works {
        background: var(--surface-2);
        border-radius: 48px;
        padding: 5rem 2rem;
        margin: 5rem 0;
        border: 1px solid var(--border-color);
    }

    .step-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 3rem;
        position: relative;
    }

    .step-item {
        text-align: center;
        position: relative;
    }

    .step-number {
        font-size: 1rem;
        font-weight: 800;
        color: var(--primary-brand);
        background: var(--surface-1);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        box-shadow: var(--shadow-soft);
        border: 2px solid var(--primary-soft);
    }

    .step-item h4 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .step-item p {
        font-size: 1rem;
        color: var(--text-secondary);
    }

    /* Stats Banner */
    .stats-banner {
        display: flex;
        justify-content: space-around;
        padding: 4rem 0;
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
        margin: 6rem 0;
        flex-wrap: wrap;
        gap: 3rem;
    }

    .stat-item {
        text-align: center;
        min-width: 150px;
    }

    .stat-number {
        display: block;
        font-size: 3.5rem;
        font-weight: 800;
        color: var(--primary-brand);
        line-height: 1;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        font-weight: 700;
        color: var(--text-secondary);
    }

    /* CTA Section */
    .final-cta {
        padding: 6rem 1rem 8rem;
        text-align: center;
        background: radial-gradient(circle at center, var(--primary-soft) 0%, transparent 70%);
    }

    /* Animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-in {
        opacity: 0;
        animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    .delay-4 { animation-delay: 0.4s; }

    @media (max-width: 768px) {
        .hero { padding: 4rem 0 3rem; }
        .about-grid { grid-template-columns: 1fr; }
        .step-list { grid-template-columns: 1fr; gap: 2.5rem; }
        .stats-banner { justify-content: center; text-align: center; padding: 3rem 0; }
        .stat-item { flex: 1 1 100%; }
        .stat-number { font-size: 3rem; }
        .how-it-works { border-radius: 32px; padding: 4rem 1.5rem; }
    }
</style>

<main class="landing-wrapper">
    <!-- Hero Section -->
    <section class="hero animate-in">
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.25rem; background: var(--primary-soft); color: var(--primary-brand); border-radius: 50px; font-weight: 700; font-size: 0.85rem; margin-bottom: 2rem;">
            <i class="fas fa-rocket"></i> v2.4.0 Stable
        </div>
        <h1 class="hero-h1">Knowledge is better <br>when it's shared.</h1>
        <p class="hero-p">
            OpenShelf is a student-led library for your campus. 
            Give your books a second life and discover new worlds from your peers.
        </p>
        <div class="hero-cta">
            <a href="/register/" class="btn-main btn-primary">
                Join the Community <i class="fas fa-chevron-right" style="font-size: 0.8rem;"></i>
            </a>
            <a href="/books/" class="btn-main btn-outline">
                Explore Books
            </a>
        </div>
    </section>

    <!-- Stats Banner -->
    <section class="stats-banner animate-in delay-1">
        <div class="stat-item">
            <span class="stat-number"><?php echo number_format($stats['books']); ?></span>
            <span class="stat-label">Total Books</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo number_format($stats['users']); ?></span>
            <span class="stat-label">Active Readers</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo number_format($stats['available']); ?></span>
            <span class="stat-label">Available Now</span>
        </div>
    </section>

    <!-- About Section -->
    <section class="section-title animate-in delay-2">
        <h2>What is OpenShelf?</h2>
        <p>A modern way to share knowledge without the barriers.</p>
    </section>

    <div class="about-grid animate-in delay-2">
        <div class="about-card">
            <div class="card-icon"><i class="fas fa-hand-holding-heart"></i></div>
            <h3>Free & Open</h3>
            <p>No late fees, no fines, no memberships. OpenShelf is built on trust and the shared goal of making reading accessible to every student.</p>
        </div>
        <div class="about-card">
            <div class="card-icon"><i class="fas fa-university"></i></div>
            <h3>Campus Focused</h3>
            <p>Designed specifically for university halls and departments. Find books that are literally just a few minutes away from you.</p>
        </div>
        <div class="about-card">
            <div class="card-icon"><i class="fab fa-whatsapp"></i></div>
            <h3>Seamless Handoff</h3>
            <p>Once a request is accepted, use our direct WhatsApp integration to coordinate a quick meet-up on campus. It's that simple.</p>
        </div>
    </div>

    <!-- How it works -->
    <section class="how-it-works animate-in delay-3">
        <div class="section-title" style="margin-top: 0; margin-bottom: 4rem;">
            <h2>How to Use</h2>
            <p>Getting started with OpenShelf takes less than two minutes.</p>
        </div>
        
        <div class="step-list">
            <div class="step-item">
                <div class="step-number">1</div>
                <h4>Sign Up</h4>
                <p>Register with your university email to join your local campus hub.</p>
            </div>
            <div class="step-item">
                <div class="step-number">2</div>
                <h4>Discover</h4>
                <p>Browse thousands of textbooks, novels, and guides shared by peers.</p>
            </div>
            <div class="step-item">
                <div class="step-number">3</div>
                <h4>Request</h4>
                <p>Found something? Send a request. The owner gets notified instantly.</p>
            </div>
            <div class="step-item">
                <div class="step-number">4</div>
                <h4>Connect</h4>
                <p>Chat via WhatsApp to arrange a convenient time to pick up the book.</p>
            </div>
            <div class="step-item">
                <div class="step-number">5</div>
                <h4>Read & Pay Forward</h4>
                <p>Enjoy your book, then return it or list your own to keep the shelf growing.</p>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="final-cta animate-in delay-4">
        <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1.5rem; letter-spacing: -0.03em;">Ready to join the shelf?</h2>
        <p class="hero-p" style="margin-bottom: 3rem;">
            Join hundreds of students who are already sharing knowledge and saving money.
        </p>
        <div class="hero-cta">
            <a href="/register/" class="btn-main btn-primary">
                Create Your Account
            </a>
            <a href="/login/" class="btn-main btn-outline">
                Sign In
            </a>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>