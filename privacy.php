<?php
/**
 * OpenShelf Privacy Policy
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

    .privacy-container {
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

    .privacy-card {
        background: var(--surface);
        padding: 4.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
    }

    .privacy-section {
        margin-bottom: 4rem;
    }

    .privacy-section:last-of-type {
        margin-bottom: 0;
    }

    .privacy-section h2 {
        font-size: 1.6rem;
        font-weight: 850;
        margin-bottom: 1.75rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        letter-spacing: -0.5px;
        color: var(--primary);
    }

    [data-theme="dark"] .privacy-section h2 { color: var(--secondary); }

    .privacy-section h2 i {
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

    .privacy-section p {
        color: var(--text-muted);
        line-height: 1.85;
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .privacy-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .privacy-section li {
        position: relative;
        padding-left: 2.5rem;
        margin-bottom: 1.25rem;
        color: var(--text-muted);
        line-height: 1.7;
        font-size: 1.05rem;
        font-weight: 500;
    }

    .privacy-section li::before {
        content: '→';
        position: absolute;
        left: 0;
        color: var(--secondary);
        font-weight: 800;
        font-size: 1.2rem;
        line-height: 1.4;
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
        .privacy-container { margin: 4rem auto; }
        .privacy-card { padding: 2.5rem 1.5rem; border-radius: 24px; }
        .privacy-section h2 { font-size: 1.35rem; gap: 0.75rem; }
        .privacy-section h2 i { width: 40px; height: 40px; font-size: 1.1rem; }
    }
</style>

<main class="privacy-container">
    <div class="hero-section">
        <h1>গোপনীয়তা নীতি</h1>
        <p style="color: var(--text-muted); font-size: 1.2rem;">আমরা আপনার তথ্য কীভাবে পরিচালনা ও সুরক্ষা করি</p>
    </div>

    <div class="privacy-card">
        <div class="privacy-section">
            <h2><i class="fas fa-database"></i> ১. তথ্য সংগ্রহ</h2>
            <p>আমরা প্ল্যাটফর্ম পরিচালনার জন্য কেবল সেই তথ্যগুলোই সংগ্রহ করি যা প্রয়োজন:</p>
            <ul>
                <li>যাচাইয়ের জন্য আপনার ইমেইল।</li>
                <li>আপনার পাবলিক প্রোফাইলে প্রদর্শনের জন্য আপনার নাম ও বিভাগ।</li>
                <li>আপনার ফোন নম্বর (শুধু সেইসব ব্যক্তি যারা আপনার কাছ থেকে বই ধার নিচ্ছে বা আপনাকে ধার দিচ্ছে, তাদের জন্য দৃশ্যমান)।</li>
            </ul>
        </div>

        <div class="privacy-section">
            <h2><i class="fas fa-shield-alt"></i> ২. তথ্য ব্যবহার</h2>
            <p>আপনার তথ্য বই বিনিময় সহজ করতে, অনুরোধের বিষয়ে নোটিফিকেশন পাঠাতে এবং সামগ্রিক ব্যবহারকারীর অভিজ্ঞতা উন্নত করতে ব্যবহৃত হয়। আমরা আপনার তথ্য কখনোই তৃতীয় পক্ষকে বিক্রি করি না।</p>
        </div>

        <div class="privacy-section">
            <h2><i class="fas fa-eye-slash"></i> ৩. দৃশ্যমানতা</h2>
            <p>আপনার সঠিক অবস্থান (রুম নম্বর) ও ফোন নম্বর ধার নেওয়ার পদ্ধতি সহজ করার জন্য, যাতে বই হ্যান্ডঅফ সহজে সম্পন্ন করা যায়।</p>
        </div>

        <div class="privacy-section">
            <h2><i class="fas fa-user-lock"></i> ৪. আপনার অধিকার</h2>
            <p>আপনি আপনার প্রোফাইল যেকোনো সময় এডিট করতে পারেন। আপনি যদি আপনার একাউন্ট ও সংশ্লিষ্ট সব তথ্য মুছে দিতে চান, তাহলে সেটিংস পেজের মাধ্যমে অথবা সাপোর্টের সাথে যোগাযোগ করে তা করতে পারেন।</p>
        </div>

        <div class="last-updated">
            সর্বশেষ আপডেট: জুলাই ২০২৬
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>