<?php
/**
 * OpenShelf Community Guidelines
 */

session_start();
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>কমিউনিটি গাইডলাইন - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

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
        --radius-xl: 32px;
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

    .guidelines-page {
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

    .guidelines-content {
        background: var(--surface);
        padding: 4.5rem;
        border-radius: var(--radius-xl);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
    }

    .guideline-section {
        margin-bottom: 4rem;
    }

    .guideline-section:last-of-type {
        margin-bottom: 0;
    }

    .guideline-section h2 {
        font-size: 1.6rem;
        font-weight: 850;
        margin-bottom: 1.75rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        letter-spacing: -0.5px;
        color: var(--primary);
    }

    [data-theme="dark"] .guideline-section h2 { color: var(--secondary); }

    .guideline-section h2::before {
        content: '';
        width: 6px;
        height: 28px;
        background: var(--secondary);
        border-radius: 4px;
    }

    .guideline-section p {
        color: var(--text-muted);
        line-height: 1.85;
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .guideline-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .guideline-section li {
        position: relative;
        padding-left: 2.5rem;
        margin-bottom: 1.25rem;
        color: var(--text-muted);
        line-height: 1.7;
        font-size: 1.05rem;
        font-weight: 500;
    }

    .guideline-section li::before {
        content: '→';
        position: absolute;
        left: 0;
        color: var(--secondary);
        font-weight: 800;
        font-size: 1.2rem;
        line-height: 1.4;
    }

    .dos-donts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
        margin: 2.5rem 0;
    }

    .dos {
        background: rgba(16, 185, 129, 0.03);
        padding: 2.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid rgba(16, 185, 129, 0.1);
        border-top: 5px solid #10b981;
    }

    .donts {
        background: rgba(239, 68, 68, 0.03);
        padding: 2.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid rgba(239, 68, 68, 0.1);
        border-top: 5px solid #ef4444;
    }

    .dos strong, .donts strong {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        font-size: 1.2rem;
        font-weight: 850;
        letter-spacing: -0.5px;
    }

    @media (max-width: 768px) {
        .guidelines-page { margin: 4rem auto; padding: 0 1rem; }
        .guidelines-content { padding: 2.5rem 1.5rem; border-radius: 24px; }
        .dos-donts { grid-template-columns: 1fr; gap: 1.5rem; }
        .guideline-section h2 { font-size: 1.35rem; }
    }
    </style>
</head>
<body>

    <main>
        <div class="guidelines-page">
            <div class="hero-section">
                <h1>কমিউনিটি গাইডলাইন</h1>
                <p>আত্মসম্মান ও আস্থার ভিত্তিতে একটি কমিউনিটি গড়ে তোলা</p>
            </div>

            <div class="guidelines-content">
                <div class="guideline-section">
                    <h2>আমাদের ভাগ করা মূল্যবোধ</h2>
                    <p>OpenShelf বিশ্বাস, সম্মান এবং বই পড়ার ভালোবাসার ওপর দাঁড়িয়ে আছে। আমাদের কমিউনিটিতে যোগ দিয়ে, আপনি এই মূল্যবোধকে বজায় রাখতে এবং সবার জন্য একটি ইতিবাচক পরিবেশ তৈরি করতে সম্মত হচ্ছেন।</p>
                </div>

                <div class="guideline-section">
                    <h2>বই আদান-প্রদানের শিষ্টাচার</h2>
                    <div class="dos-donts">
                        <div class="dos">
                            <strong><i class="fas fa-check-circle" style="color: #10b981;"></i> করবেন:</strong>
                            <ul style="margin-top: 0.5rem;">
                                <li>বইয়ের অবস্থা সঠিকভাবে বর্ণনা করুন</li>
                                <li>৪৮ ঘণ্টার মধ্যে অনুরোধের উত্তর দিন</li>
                                <li>পিকআপের সময় সম্পর্কে স্পষ্টভাবে যোগাযোগ করুন</li>
                                <li>ফেরত দেওয়ার তারিখের প্রতি সম্মান দেখান</li>
                                <li>বই ভালো অবস্থায় রাখুন</li>
                            </ul>
                        </div>
                        <div class="donts">
                            <strong><i class="fas fa-times-circle" style="color: #ef4444;"></i> করবেন না:</strong>
                            <ul style="margin-top: 0.5rem;">
                                <li>বইয়ের অবস্থার ভুল বর্ণনা করবেন না</li>
                                <li>অনুরোধ বা বার্তা উপেক্ষা করবেন না</li>
                                <li>বিনা নোটিশে বাতিল করবেন না</li>
                                <li>জানবাজি করে বই নষ্ট করবেন না</li>
                                <li>ধার নেওয়া বই অন্যের কাছে ফেরত বা বিক্রি করবেন না</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="guideline-section">
                    <h2>যোগাযোগের নির্দেশিকা</h2>
                    <ul>
                        <li><strong>সম্মান দেখান:</strong> সব সদস্যকে বিনয় ও সদয় আচরণে ব্যবহার করুন</li>
                        <li><strong>সাড়া দিন:</strong> বার্তার উত্তর ২৪-৪৮ ঘণ্টার মধ্যে দিন</li>
                        <li><strong>স্পষ্ট থাকুন:</strong> পিকআপের সময়, স্থান ও প্রত্যাশা স্পষ্ট করে বলুন</li>
                        <li><strong>WhatsApp সচেতনতার সাথে ব্যবহার করুন:</strong> যোগাযোগের জন্য ব্যবহার করুন, স্প্যামের জন্য নয়</li>
                    </ul>
                </div>

                <div class="guideline-section">
                    <h2>বইয়ের অবস্থা সম্পর্কিত মানদণ্ড</h2>
                    <ul>
                        <li><strong>নতুন:</strong> পুরো নতুন, একদম পড়া হয়নি</li>
                        <li><strong>প্রায় নতুন:</strong> নিখুঁত অবস্থা, কোনো দৃশ্যমান ক্ষতি নেই</li>
                        <li><strong>খুব ভালো:</strong> সামান্য ঘর্ষণ, বই পরিষ্কার</li>
                        <li><strong>ভালো:</strong> স্বাভাবিক পরিধান, কিছু মার্কিং থাকতে পারে</li>
                        <li><strong>গ্রহণযোগ্য:</strong> বেশি পড়া হয়েছে, ব্যবহারযোগ্য</li>
                        <li><strong>খারাপ:</strong> ক্ষতিগ্রস্ত কিন্তু পড়া যায়—ক্ষতির বিবরণ অবশ্যই জানান</li>
                    </ul>
                </div>

                <div class="guideline-section">
                    <h2>সমস্যা রিপোর্ট করা</h2>
                    <p>যদি অন্য কোনো ব্যবহারকারীর বিষয়ে কোনো সমস্যা দেখেন, তাহলে তা অবিলম্বে রিপোর্ট করুন:</p>
                    <ul>
                        <li><strong>সাড়া না দেওয়া ব্যবহারকারী:</strong> কোনো ব্যবহারকারী অনুরোধের উত্তর না দিলে রিপোর্ট করুন</li>
                        <li><strong>ক্ষতিগ্রস্ত বই:</strong> বই ফেরত দেওয়ার সময় বর্ণনা অনুযায়ী ভালো অবস্থায় না থাকলে রিপোর্ট করুন</li>
                        <li><strong>অপমানজনক আচরণ:</strong> কোনো অনুচিত আচরণ হলে রিপোর্ট করুন</li>
                        <li><strong>ভু তালিকা:</strong> এমন বই যা নেই বা উপলব্ধ নয়, তার রিপোর্ট করুন</li>
                    </ul>
                    <p>“রিপোর্ট” বাটন ব্যবহার করুন বা সরাসরি সহায়তার সাথে যোগাযোগ করুন।</p>
                </div>

                <div class="guideline-section">
                    <h2>নিয়ম ভঙ্গের পরিণতি</h2>
                    <p>নিয়ম ভঙ্গের তীব্রতার উপর নির্ভর করে, ভিন্ন ভিন্ন জরিমানা বা পদক্ষেপ নেওয়া হতে পারে:</p>
                    <ul>
                        <li>সতর্কতামূলক নোটিফিকেশন</li>
                        <li>অস্থায়ী একাউন্ট স্থগিত</li>
                        <li>স্থায়ী একাউন্ট নিষিদ্ধ</li>
                        <li>গুরুতর ক্ষেত্রে বিশ্ববিদ্যালয়ের কর্তৃপক্ষের কাছে রিপোর্ট</li>
                    </ul>
                </div>

                <div class="guideline-section">
                    <h2>চমৎকার অভিজ্ঞতার জন্য টিপস</h2>
                    <ul>
                        <li>📸 আরও আগ্রহ আকর্ষণের জন্য পরিষ্কার কভার ইমেজ যোগ করুন</li>
                        <li>💬 বই অনুরোধের সময় সৌহার্দ্যপূর্ণ বার্তা লিখুন</li>
                        <li>⭐ ধার নেওয়ার পর রিভিউ দিন, যাতে কমিউনিটি উপকৃত হয়</li>
                        <li>📅 ফেরতের তারিখের জন্য ক্যালেন্ডার রিমাইন্ডার সেট করুন</li>
                        <li>🤝 অন্যের প্রতি নমনীয় ও বোঝাপড়াপূর্ণ থাকুন</li>
                    </ul>
                </div>

                <div class="guideline-section">
                    <h2>নিরাপত্তা নির্দেশিকা</h2>
                    <ul>
                        <li>বই বিনিময়ের সময় জনবহুল, ভালো আলোযুক্ত স্থানে সাক্ষাৎ করুন</li>
                        <li>যদি নতুন কাউকে দেখেন, তাহলে বন্ধুর সঙ্গে অবস্থান শেয়ার করুন</li>
                        <li>আপনার instinct-এ বিশ্বাস করুন—কিছু অস্বস্তিকর মনে হলে এগিয়ে যাবেন না</li>
                        <li>প্রয়োজনীয় তথ্য ছাড়া কোনো সংবেদনশীল ব্যক্তিগত তথ্য শেয়ার করবেন না</li>
                        <li>ব্যবস্থাগুলো নিশ্চিত করতে WhatsApp চ্যাট ব্যবহার করুন</li>
                    </ul>
                </div>

                <div class="guideline-section">
                    <h2>প্রশ্ন বা উদ্বেগ আছে?</h2>
                    <p>আপনার যদি কিছু বুঝতে অসুবিধা হয় বা সাহায্যের প্রয়োজন হয়, তাহলে আমাদের সাথে যোগাযোগ করুন <a href="mailto:support@duopenshelf.top" style="color: var(--secondary);">support@duopenshelf.top</a>। আপনার অভিজ্ঞতাকে ইতিবাচক ও ফলপ্রসু করতে আমরা সাহায্য করতে প্রস্তুত।</p>
                </div>

                <div class="last-updated" style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border); text-align: center; color: var(--text-tertiary); font-size: 0.8rem;">
                    সর্বশেষ আপডেট: জুলাই ২০২৬
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>