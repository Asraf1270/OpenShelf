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
        <h1>ক্যাম্পাসে পড়াশোনাকে নতুনভাবে ভাবা</h1>
        <p class="hero-p">OpenShelf হলো একটি বিকেন্দ্রীভূত, শিক্ষার্থী-কেন্দ্রিক লাইব্রেরি প্ল্যাটফর্ম, যা জ্ঞানকে সহজলভ্য করে তুলতে এবং বই ভাগাভাগি করা সহজ করে তুলতে ডিজাইন করা হয়েছে।</p>
    </section>

    <div class="story-grid">
        <div class="story-content">
            <h2>OpenShelf-এর গল্প</h2>
            <p>সাশ্রয়ী পাঠ্যপুস্তকের প্রয়োজন এবং পড়াশোনার ভালোবাসা থেকে OpenShelf-এর ধারণা জন্ম নেয়। শুরুতে এটি ছিল এক সহজ চিন্তা: আমরা কি আমাদের ব্যক্তিগত লাইব্রেরি আশপাশের মানুষদের সাথে ভাগ করতে পারি না?</p>
            <p>আজ আমরা এমন এক কমিউনিটি গড়ে তোলার চেষ্টা করছি, যেখানে ছাত্র-ছাত্রীরা সহজে যুক্ত হতে পারে, বই ভাগ করতে পারে এবং খরচ বা অবস্থানের বাধা ছাড়াই নতুন বই আবিষ্কার করতে পারে। আমাদের শেলফে থাকা প্রতিটি বইই একজন সহপাঠীর অবদানের ফল।</p>
        </div>
        <div class="stats-card">
            <div style="margin-bottom: 2rem;">
                <span class="stat-number">100%</span>
                <span class="stat-label">শিক্ষার্থী-কেন্দ্রিক</span>
            </div>
            <div>
                <span class="stat-number">অসীম</span>
                <span class="stat-label">সম্ভাবনা</span>
            </div>
        </div>
    </div>

    <section class="features-section">
        <h2 style="text-align: center; margin-bottom: 4rem; font-size: 2rem; font-weight: 800;">আমাদের মূল মূল্যবোধ</h2>
        <div class="features-grid">
            <div class="feature-item">
                <h3><i class="fas fa-heart"></i> সহজলভ্যতা</h3>
                <p>জ্ঞান কখনওই কোনো পে-ওয়াল চালুর আড়ালে থাকা উচিত নয়। আমরা সাহিত্যের বই ও পড়াশোনার উপকরণ বিনামূল্যে সহজে পেতে বিশ্বাস করি।</p>
            </div>
            <div class="feature-item">
                <h3><i class="fas fa-users"></i> কমিউনিটি</h3>
                <p>ছাত্রদের মধ্যে বিশ্বাস গড়ে তোলাই আমাদের মূল লক্ষ্য। আমরা একসাথে ভাগ করলে আরও শক্তিশালী হই।</p>
            </div>
            <div class="feature-item">
                <h3><i class="fas fa-leaf"></i> টেকসই</h3>
                <p>বই শেয়ার করে আমরা বর্জ্য কমাই এবং শারীরিক কপি যাতে দীর্ঘ ও অর্থপূর্ণ জীবন পায়, সে দিকে কাজ করি।</p>
            </div>
        </div>
    </section>

    <section style="text-align: center;">
        <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem;">এই আন্দোলনে যোগ দিন</h2>
        <p class="hero-p" style="margin-bottom: 3rem;">আপনার প্রথম বই তালিকাভুক্ত করতে প্রস্তুত? OpenShelf-এ ইতোমধ্যে হাজার হাজার ছাত্র-ছাত্রী যোগ দিয়েছেন।</p>
        <a href="/register/" style="display: inline-block; padding: 1rem 2.5rem; background: var(--primary); color: white; text-decoration: none; border-radius: 50px; font-weight: 700; box-shadow: 0 10px 20px rgba(44, 62, 80, 0.2);">আজই ভাগ করা শুরু করুন</a>
    </section>
</main>

<?php include 'includes/footer.php'; ?>