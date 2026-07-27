@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
@if(session('success'))
    <div style="max-width: 1200px; margin: 1rem auto 0; padding: 0 1.5rem;">
        <div style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); padding: 1rem 1.25rem; border-radius: 12px; font-weight: 600;">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    </div>
@endif
<main class="landing-wrapper">
    <!-- Hero Section -->
    <section class="hero animate-in">
        <h1 class="hero-h1">Knowledge is better <br>when it's shared.</h1>
        <p class="hero-p">
            OpenShelf হলো আপনার ক্যাম্পাসের ছাত্র-নেতৃত্বাধীন লাইব্রেরি।
            আপনার বইগুলোকে নতুন জীবন দিন এবং সহপাঠীদের কাছ থেকে নতুন বিশ্বের পরিচয় পান।
        </p>
        <div class="hero-cta">
            <a href="{{ route('register') }}" class="btn-main btn-primary">
                কমিউনিটিতে যোগ দিন <i class="fas fa-chevron-right" style="font-size: 0.8rem;"></i>
            </a>
            <a href="{{ route('books') }}" class="btn-main btn-outline">
                বই অন্বেষণ করুন
            </a>
        </div>
    </section>

    <!-- Stats Banner -->
    <section class="stats-banner animate-in delay-1">
        <div class="stat-item">
            <span class="stat-number">{{ number_format($stats['books']) }}</span>
            <span class="stat-label">মোট বই</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">{{ number_format($stats['users']) }}</span>
            <span class="stat-label">সক্রিয় পাঠক</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">{{ number_format($stats['available']) }}</span>
            <span class="stat-label">Available Now</span>
        </div>
    </section>

    <!-- About Section -->
    <section class="section-title animate-in delay-2">
        <h2>OpenShelf কী?</h2>
        <p>জ্ঞানকে বাধা ছাড়া ভাগ করার আধুনিক এক পথ।</p>
    </section>

    <div class="about-grid animate-in delay-2">
        <div class="about-card">
            <div class="card-icon"><i class="fas fa-hand-holding-heart"></i></div>
            <h3>Free & Open</h3>
            <p>কোনো বিলম্ব ফি, কোনো জরিমানা, কোনো মেম্বারশিপ নেই। OpenShelf বিশ্বাস ও ভাগাভাগির ওপর ভিত্তি করে তৈরি, যাতে প্রতিটি ছাত্র-ছাত্রী সহজেই পড়াশোনা চালিয়ে যেতে পারে।</p>
        </div>
        <div class="about-card">
            <div class="card-icon"><i class="fas fa-university"></i></div>
            <h3>Campus Focused</h3>
            <p>বিশেষভাবে বিশ্ববিদ্যালয় হল ও বিভাগগুলোর জন্য ডিজাইন করা। আপনার হাতের কাছেই এমন বই খুঁজুন, যা আপনার থেকে মাত্র কয়েক মিনিট দূরে।</p>
        </div>
        <div class="about-card">
            <div class="card-icon"><i class="fab fa-whatsapp"></i></div>
            <h3>স্মুথ হ্যান্ডঅফ</h3>
            <p>একবার অনুরোধ গৃহীত হলে, আমাদের সরাসরি WhatsApp ইন্টিগ্রেশন ব্যবহার করে ক্যাম্পাসে দ্রুত মিলিত হওয়া যাবে। এটিই খুব সহজ।</p>
        </div>
    </div>

    <!-- How it works -->
    <section class="how-it-works animate-in delay-3">
        <div class="section-title" style="margin-top: 0; margin-bottom: 4rem;">
            <h2>কীভাবে ব্যবহার করবেন</h2>
            <p>OpenShelf ব্যবহার শুরু করতে দুই মিনিটেরও কম সময় লাগে।</p>
        </div>

        <div class="step-list">
            <div class="step-item">
                <div class="step-number">1</div>
                <h4>সাইন আপ করুন</h4>
                <p>আপনার ইমেইল দিয়ে রেজিস্ট্রেশন করে আপনার স্থানীয় ক্যাম্পাস হাবে যোগ দিন।</p>
            </div>
            <div class="step-item">
                <div class="step-number">2</div>
                <h4>খুঁজে নিন</h4>
                <p>সহপাঠীরা ভাগ করা হাজারো পাঠ্যপুস্তক, উপন্যাস ও গাইড ব্রাউজ করুন।</p>
            </div>
            <div class="step-item">
                <div class="step-number">3</div>
                <h4>অনুরোধ করুন</h4>
                <p>কিছু পেয়ে গেলে অনুরোধ পাঠান। মালিককে তাৎক্ষণিকভাবে নোটিফিকেশন যাবে।</p>
            </div>
            <div class="step-item">
                <div class="step-number">4</div>
                <h4>যোগাযোগ করুন</h4>
                <p>WhatsApp-এর মাধ্যমে যোগাযোগ করে বই pickup-এর উপযুক্ত সময় ঠিক করুন।</p>
            </div>
            <div class="step-item">
                <div class="step-number">5</div>
                <h4>পড়ুন ও অন্যকে সাহায্য করুন</h4>
                <p>বই উপভোগ করুন, তারপর ফেরত দিন বা নিজের বইও তালিকাভুক্ত করুন—এভাবে শেলফটি আরও বড় হয়।</p>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="final-cta animate-in delay-4">
        <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1.5rem; letter-spacing: -0.03em;">শেলফে যোগ দিতে প্রস্তুত?</h2>
        <p class="hero-p" style="margin-bottom: 3rem;">
            এমন হাজার হাজার ছাত্র-ছাত্রীদের সাথে যোগ দিন, যারা ইতোমধ্যে জ্ঞান ভাগ করে আর টাকা বাঁচাচ্ছে।
        </p>
        <div class="hero-cta">
            <a href="{{ route('register') }}" class="btn-main btn-primary">
                আপনার একাউন্ট তৈরি করুন
            </a>
            <a href="{{ route('login') }}" class="btn-main btn-outline">
                সাইন ইন করুন
            </a>
        </div>
    </section>
</main>


@endsection
