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
        --primary: #6366f1;
        --bg: #f8fafc;
        --surface: #ffffff;
        --text: #0f172a;
        --text-muted: #64748b;
        --border: #e2e8f0;
        --radius: 20px;
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

    .faq-container {
        max-width: 800px;
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

    .faq-category {
        margin-bottom: 3rem;
    }

    .faq-category h2 {
        font-size: 1.25rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--primary);
        margin-bottom: 1.5rem;
        padding-left: 0.5rem;
    }

    .faq-item {
        background: var(--surface);
        border-radius: 15px;
        margin-bottom: 1rem;
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .faq-item:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow);
    }

    .faq-question {
        padding: 1.5rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .faq-question i {
        font-size: 0.9rem;
        transition: transform 0.3s ease;
        color: var(--text-muted);
    }

    .faq-item.active .faq-question i {
        transform: rotate(180deg);
        color: var(--primary);
    }

    .faq-answer {
        padding: 0 1.5rem 1.5rem;
        color: var(--text-muted);
        line-height: 1.7;
        display: none;
    }

    .faq-item.active .faq-answer {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
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