<?php
/**
 * OpenShelf Terms of Service
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

    .terms-container {
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

    .terms-card {
        background: var(--surface);
        padding: 4.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
    }

    .terms-section {
        margin-bottom: 4rem;
    }

    .terms-section:last-of-type {
        margin-bottom: 0;
    }

    .terms-section h2 {
        font-size: 1.6rem;
        font-weight: 850;
        margin-bottom: 1.75rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        letter-spacing: -0.5px;
        color: var(--primary);
    }

    [data-theme="dark"] .terms-section h2 { color: var(--secondary); }

    .terms-section h2 i {
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

    .terms-section p {
        color: var(--text-muted);
        line-height: 1.85;
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .terms-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .terms-section li {
        position: relative;
        padding-left: 2.5rem;
        margin-bottom: 1.25rem;
        color: var(--text-muted);
        line-height: 1.7;
        font-size: 1.05rem;
        font-weight: 500;
    }

    .terms-section li::before {
        content: '→';
        position: absolute;
        left: 0;
        color: var(--secondary);
        font-weight: 800;
        font-size: 1.2rem;
        line-height: 1.4;
    }

    .important-note {
        background: rgba(76, 159, 138, 0.05);
        border-left: 4px solid var(--secondary);
        padding: 2rem;
        border-radius: 0 16px 16px 0;
        margin: 2rem 0;
    }

    .important-note p {
        margin: 0;
        font-weight: 700;
        color: var(--text-main);
        font-size: 1.1rem;
        line-height: 1.6;
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
        .terms-container { margin: 4rem auto; }
        .terms-card { padding: 2.5rem 1.5rem; border-radius: 24px; }
        .terms-section h2 { font-size: 1.35rem; gap: 0.75rem; }
        .terms-section h2 i { width: 40px; height: 40px; font-size: 1.1rem; }
    }
</style>

<main class="terms-container">
    <div class="hero-section">
        <h1>Terms of Service</h1>
        <p style="color: var(--text-muted); font-size: 1.2rem;">একটি ভালো কমিউনিটির জন্য আমাদের নির্দেশিকা</p>
    </div>

    <div class="terms-card">
        <div class="terms-section">
            <h2><i class="fas fa-check-circle"></i> ১. গ্রহণযোগ্যতা</h2>
            <p>OpenShelf ব্যবহার করে, আপনি এই শর্তাবলী মেনে নিতে সম্মত হচ্ছেন। আমরা জ্ঞানের ভাগাভাগি করতে নিরাপদ, বিশ্বাস-ভিত্তিক পরিবেশ গড়ে তোলার লক্ষ্য রাখি।</p>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-user-graduate"></i> ২. যোগ্যতা</h2>
            <ul>
                <li>বৈধ ও বর্তমান বিশ্ববিদ্যালয়ের ছাত্র/ছাত্রী।</li>
                <li>কমপক্ষে ১৮ বছরের বয়সী।</li>
                <li>রেজিস্ট্রেশনের সময় সঠিক ব্যক্তিগত তথ্য প্রদান করতে হবে।</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-book"></i> ৩. অবদান বিধি</h2>
            <div class="important-note">
                <p>একটি সুস্থ লাইব্রেরি বজায় রাখার জন্য, প্রত্যেক ব্যবহারকারীকে নিবন্ধনের ৩০ দিনের মধ্যে কমপক্ষে ২টি বই আদান-প্রদানের জন্য তালিকাভুক্ত করতে হবে।</p>
            </div>
            <p>এটি নিশ্চিত করে যে আমাদের কমিউনিটি ক্রমাগত বৃদ্ধি পায় এবং প্রত্যেকে জ্ঞানের আদান-প্রদানের কাজের অংশীদার হয়।</p>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-hand-holding-heart"></i> ৪. আদান-প্রদানের নিয়ম</h2>
            <ul>
                <li>শুধুমাত্র সেই বইগুলো তালিকাভুক্ত করুন, যা আপনার প্রকৃতপক্ষে আছে।</li>
                <li>বইয়ের অবস্থা সঠিকভাবে বর্ণনা করুন।</li>
                <li>ধার নেওয়ার অনুরোধের উত্তর ৪৮ ঘণ্টার মধ্যে দিন।</li>
                <li>ক্যাম্পাসে নিরাপদে হ্যান্ডঅফের ব্যবস্থা করুন।</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-undo"></i> ৫. ধার ও ফেরত</h2>
            <ul>
                <li>বই নির্দিষ্ট তারিখে বা তার আগেই ফেরত দিন।</li>
                <li>বই যত্ন সহকারে ব্যবহার করুন; মালিকের অনুমতি ছাড়া লেখালেখি বা হাইলাইট করবেন না।</li>
                <li>যদি বই হারিয়ে যায় বা ক্ষতিগ্রস্ত হয়, তবে তার বদল বা ক্ষতিপূরণের দায় আপনার ওপর থাকবে।</li>
            </ul>
        </div>

        <div class="terms-section">
            <h2><i class="fas fa-shield-alt"></i> ৬. ব্যবহারকারীর আচরণ</h2>
            <p>অপ্রীতিকর আচরণ, স্প্যাম বা প্রতারণামূলক কার্যকলাপের ক্ষেত্রে অবিলম্বে ও স্থায়ীভাবে একাউন্ট স্থগিত করা হতে পারে। আপনার সহপাঠীদের প্রতি সম্মান প্রদর্শন করুন।</p>
        </div>

        <div class="last-updated">
            সর্বশেষ আপডেট: জুলাই ২০২৬
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>