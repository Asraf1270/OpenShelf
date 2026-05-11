<?php
/**
 * OpenShelf FAQ Page
 * Frequently Asked Questions
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

    .faq-container {
        max-width: 800px;
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

    .faq-category {
        margin-bottom: 4rem;
    }

    .faq-category h2 {
        font-size: 1.15rem;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--secondary);
        margin-bottom: 2rem;
        padding-left: 0.75rem;
        border-left: 4px solid var(--secondary);
    }

    .faq-item {
        background: var(--surface);
        border-radius: var(--radius-md);
        margin-bottom: 1.25rem;
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: var(--shadow-sm);
    }

    .faq-item:hover {
        border-color: var(--secondary);
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .faq-question {
        padding: 1.75rem 2rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 750;
        font-size: 1.15rem;
        color: var(--text-main);
        transition: all 0.3s ease;
    }

    .faq-item:hover .faq-question {
        color: var(--primary);
    }

    .faq-question i {
        font-size: 0.9rem;
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        color: var(--text-muted);
    }

    .faq-item.active .faq-question {
        background: rgba(76, 159, 138, 0.03);
        color: var(--secondary);
    }

    .faq-item.active .faq-question i {
        transform: rotate(180deg);
        color: var(--secondary);
    }

    .faq-answer {
        padding: 0 2rem 2rem;
        color: var(--text-muted);
        line-height: 1.8;
        display: none;
        font-size: 1.05rem;
        font-weight: 500;
    }

    .faq-item.active .faq-answer {
        display: block;
        animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 640px) {
        .faq-container { margin: 4rem auto; }
        .faq-question { padding: 1.25rem 1.5rem; font-size: 1rem; }
        .faq-answer { padding: 0 1.5rem 1.5rem; font-size: 0.95rem; }
    }
</style>

<main class="faq-container">
    <section class="hero-section">
        <h1>Questions? Answers.</h1>
        <p style="color: var(--text-muted); font-size: 1.2rem;">Everything you need to know about OpenShelf.</p>
    </section>

    <div class="faq-category">
        <h2>General</h2>
        <div class="faq-item">
            <div class="faq-question">What is OpenShelf? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">OpenShelf is a community-driven library platform where students can share and borrow books for free. We aim to make knowledge more accessible across campuses.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Is it really free? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">Yes! There are no subscription fees or borrowing costs. The platform is built on a "share-and-borrow" model where students help each other.</div>
        </div>
    </div>

    <div class="faq-category">
        <h2>Borrowing</h2>
        <div class="faq-item">
            <div class="faq-question">How do I borrow a book? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">Simply browse the library, find a book you like, and click "Request Borrow". The owner will be notified and can approve your request.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">How long can I keep a book? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">The default borrowing period is 14 days, but you can request an extension if the owner agrees. Always respect the agreed-upon return date.</div>
        </div>
    </div>

    <div class="faq-category">
        <h2>Sharing</h2>
        <div class="faq-item">
            <div class="faq-question">How do I list my books? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">Go to your dashboard, click "Add Book", and fill in the details. It takes less than a minute to make your book available to others.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">What is the 2-book rule? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">To keep the community active, we require every user to list at least 2 books for sharing within 30 days of joining.</div>
        </div>
    </div>
</main>

<script>
    document.querySelectorAll('.faq-question').forEach(q => {
        q.addEventListener('click', () => {
            const item = q.parentElement;
            item.classList.toggle('active');
        });
    });
</script>

<?php include 'includes/footer.php'; ?>