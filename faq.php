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
        <h1>প্রশ্ন? উত্তর।</h1>
        <p style="color: var(--text-muted); font-size: 1.2rem;">OpenShelf সম্পর্কে আপনার জানা প্রয়োজন এমন সবকিছু।</p>
    </section>

    <div class="faq-category">
        <h2>সাধারণ</h2>
        <div class="faq-item">
            <div class="faq-question">OpenShelf কী? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">OpenShelf হলো একটি কমিউনিটি-চালিত লাইব্রেরি প্ল্যাটফর্ম যেখানে শিক্ষার্থীরা বিনামূল্যে বই শেয়ার এবং ধার করতে পারে। আমরা জ্ঞানকে ক্যাম্পাস জুড়ে আরও সহজলভ্য করতে লক্ষ্য রাখি।</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">এটি সত্যিই বিনামূল্যে? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">হ্যাঁ! কোনো সাবস্ক্রিপশন ফি বা ধার নেওয়ার খরচ নেই। প্ল্যাটফর্মটি একটি "ভাগাভাগি-এবং-ধার" মডেলের উপর নির্মিত যেখানে শিক্ষার্থীরা একে অপরকে সাহায্য করে। আমাদের এই প্রকল্পটি পরিচালনা ও আরও উন্নত করতে আর্থিক সহায়তার প্রয়োজন হয়। আপনি চাইলে এই উদ্যোগে অবদান রেখে আমাদের পাশে থাকতে পারেন। আপনার মূল্যবান সহযোগিতা আমাদের সেবাকে আরও সমৃদ্ধ করতে অনুপ্রাণিত করবে। <br> সহায়তা করতে অনুগ্রহ করে আমাদের Support Us পেজটি ভিজিট করুন।</div>
        </div>
    </div>

    <div class="faq-category">
        <h2>ধার নেওয়া</h2>
        <div class="faq-item">
            <div class="faq-question">আমি কীভাবে বই ধার নিই? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">সহজেই লাইব্রেরি ব্রাউজ করুন, আপনার পছন্দের বই খুঁজুন এবং "ধার নেওয়ার অনুরোধ" ক্লিক করুন। মালিককে বিজ্ঞপ্তি দেওয়া হবে এবং তিনি আপনার অনুরোধ অনুমোদন করতে পারবেন।</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">আমি একটি বই কত দিন রাখতে পারি? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">ডিফল্ট ধার নেওয়ার সময়কাল ১৪ দিন, তবে মালিক সম্মত হলে আপনি এক্সটেনশন অনুরোধ করতে পারেন। সর্বদা সম্মত রিটার্ন তারিখকে সম্মান করুন।</div>
        </div>
    </div>

    <div class="faq-category">
        <h2>শেয়ার করা</h2>
        <div class="faq-item">
            <div class="faq-question">আমি আমার বই কীভাবে তালিকাভুক্ত করি? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">আপনার ড্যাশবোর্ডে যান, "বই যোগ করুন" ক্লিক করুন এবং বিবরণ পূরণ করুন। আপনার বই অন্যদের জন্য উপলব্ধ করতে মাত্র এক মিনিটের চেয়ে কম সময় লাগে।</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">২-বই নিয়ম কী? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">কমিউনিটিকে সক্রিয় রাখতে, আমরা প্রতিটি ব্যবহারকারীকে যোগদানের ৩০ দিনের মধ্যে শেয়ারিংয়ের জন্য কমপক্ষে ২টি বই তালিকাভুক্ত করতে প্রয়োজন।</div>
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