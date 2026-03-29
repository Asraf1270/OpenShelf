<?php
/**
 * OpenShelf - Simple Animated Library Dashboard
 * Shows only member count, book count, and available books with smooth animation
 */

session_start();
include 'includes/header.php';

// Configuration
define('DATA_PATH', __DIR__ . '/data/');

/**
 * Get total books count
 */
function getTotalBooks() {
    $booksFile = DATA_PATH . 'books.json';
    if (!file_exists($booksFile)) return 0;
    $books = json_decode(file_get_contents($booksFile), true) ?? [];
    return count($books);
}

/**
 * Get total users count
 */
function getTotalUsers() {
    $usersFile = DATA_PATH . 'users.json';
    if (!file_exists($usersFile)) return 0;
    $users = json_decode(file_get_contents($usersFile), true) ?? [];
    return count($users);
}

/**
 * Get available books count
 */
function getAvailableBooks() {
    $booksFile = DATA_PATH . 'books.json';
    if (!file_exists($booksFile)) return 0;
    $books = json_decode(file_get_contents($booksFile), true) ?? [];
    return count(array_filter($books, fn($b) => ($b['status'] ?? '') === 'available'));
}

// Load statistics
$totalBooks = getTotalBooks();
$totalUsers = getTotalUsers();
$availableBooks = getAvailableBooks();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>OpenShelf - Share Books, Share Knowledge</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ========================================
           BEAUTIFUL DASHBOARD - SIMPLE & CLEAN
        ======================================== */
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #0f172a;
            --accent: #10b981;
            --surface: #ffffff;
            --surface-hover: #f8fafc;
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-tertiary: #64748b;
            --border: #e2e8f0;
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --radius-2xl: 32px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            font-family: system-ui, -apple-system, 'Inter', 'Segoe UI', Roboto, sans-serif;
            color: var(--text-primary);
            line-height: 1.5;
        }

        /* Hero Section */
        .hero {
            position: relative;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            padding: 5rem 0 7rem;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%238b5cf6' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
            pointer-events: none;
        }

        .hero .container {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(99, 102, 241, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #a5b4fc;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .hero-title {
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, #c4b5fd 50%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 3vw, 1.25rem);
            color: #cbd5e1;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Stats Dashboard - 3 Cards */
        .stats-dashboard {
            margin-top: -3rem;
            margin-bottom: 4rem;
            position: relative;
            z-index: 10;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem 1.5rem;
            border-radius: var(--radius-2xl);
            text-align: center;
            box-shadow: var(--shadow-xl);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-2xl);
            background: white;
        }

        .stat-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 2.5rem;
            color: var(--primary);
            transition: var(--transition);
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .stat-value {
            font-size: 3rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-tertiary);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Features Section */
        .features-section {
            padding: 4rem 0;
            background: var(--surface);
            border-radius: var(--radius-2xl);
            margin-bottom: 4rem;
            box-shadow: var(--shadow-lg);
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--text-primary), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-subtitle {
            color: var(--text-tertiary);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            text-align: center;
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 1.75rem;
            color: white;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.3);
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--text-tertiary);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        /* How It Works */
        .how-it-works {
            padding: 4rem 0;
            background: var(--surface-hover);
            border-radius: var(--radius-2xl);
            margin-bottom: 4rem;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .step-card {
            text-align: center;
            padding: 1.5rem;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .step-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .step-description {
            color: var(--text-tertiary);
            font-size: 0.875rem;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: var(--radius-2xl);
            padding: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .cta-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.75rem;
        }

        .cta-text {
            color: rgba(255,255,255,0.9);
            max-width: 500px;
            margin: 0 auto 1.5rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.75rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.5);
        }

        .btn-outline-light {
            background: transparent;
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
        }

        .btn-outline-light:hover {
            background: white;
            color: var(--primary);
            border-color: white;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .stat-card {
                padding: 1.5rem;
            }
            .stat-value {
                font-size: 2rem;
            }
            .features-grid,
            .steps-grid {
                grid-template-columns: 1fr;
            }
            .hero {
                padding: 3rem 0 5rem;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
</head>
<body>
    
    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-badge">
                    <i class="fas fa-star"></i> Community Library
                </div>
                <h1 class="hero-title">OpenShelf Library</h1>
                <p class="hero-subtitle">
                    A community-powered library for students. Share books, discover new reads,<br>
                    and connect with fellow book lovers.
                </p>
                <div class="hero-buttons">
                    <a href="/books/" class="btn btn-primary">
                        <i class="fas fa-book-open"></i> Explore Library
                    </a>
                    <a href="/add-book/" class="btn btn-outline-light">
                        <i class="fas fa-plus-circle"></i> Share a Book
                    </a>
                </div>
            </div>
        </section>
        
        <div class="container">
            <!-- Statistics Dashboard - Only 3 Cards -->
            <div class="stats-dashboard">
                <div class="stats-grid">
                    <div class="stat-card animate-in" style="animation-delay: 0.1s">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value" id="stat-totalUsers">0</div>
                        <div class="stat-label">Active Members</div>
                    </div>
                    <div class="stat-card animate-in" style="animation-delay: 0.2s">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-value" id="stat-totalBooks">0</div>
                        <div class="stat-label">Total Books</div>
                    </div>
                    <div class="stat-card animate-in" style="animation-delay: 0.3s">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value" id="stat-availableBooks">0</div>
                        <div class="stat-label">Available Now</div>
                    </div>
                </div>
            </div>
            
            <!-- Features Section - Explaining What the System Does -->
            <div class="features-section">
                <div class="section-header">
                    <h2 class="section-title">✨ What is OpenShelf?</h2>
                    <p class="section-subtitle">A complete book sharing platform for university students</p>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h3 class="feature-title">Share Books</h3>
                        <p class="feature-description">
                            Add books you own to the library. Share your collection with fellow students and help build a community library.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="feature-title">Discover & Borrow</h3>
                        <p class="feature-description">
                            Browse through thousands of books shared by other students. Request to borrow books and expand your reading horizons.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="feature-title">Connect & Review</h3>
                        <p class="feature-description">
                            Write reviews, rate books, and comment on your favorite reads. Connect with fellow book lovers in your hall.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- How It Works Section -->
            <div class="how-it-works">
                <div class="section-header">
                    <h2 class="section-title">🚀 How It Works</h2>
                    <p class="section-subtitle">Simple steps to start sharing and borrowing books</p>
                </div>
                
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Create Account</h3>
                        <p class="step-description">
                            Sign up with your university email and join the OpenShelf community.
                        </p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Add Your Books</h3>
                        <p class="step-description">
                            Upload books you own with cover images, category, and condition details.
                        </p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Browse & Request</h3>
                        <p class="step-description">
                            Discover books from other students and send borrow requests.
                        </p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h3 class="step-title">Connect & Return</h3>
                        <p class="step-description">
                            Connect with owners via WhatsApp, arrange pickup, and return books on time.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Key Features List -->
            <div class="features-section" style="background: var(--surface);">
                <div class="section-header">
                    <h2 class="section-title">🌟 Key Features</h2>
                    <p class="section-subtitle">Everything you need for a seamless book sharing experience</p>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Mobile First Design</h3>
                        <p class="feature-description">
                            Fully responsive design that works perfectly on phones, tablets, and desktops.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="feature-title">Real-time Notifications</h3>
                        <p class="feature-description">
                            Get instant alerts when someone requests your book or approves your request.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="feature-title">Reviews & Ratings</h3>
                        <p class="feature-description">
                            Rate books you've read and help others discover great reads.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <h3 class="feature-title">WhatsApp Integration</h3>
                        <p class="feature-description">
                            Direct WhatsApp contact with book owners for quick communication.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Activity Feed</h3>
                        <p class="feature-description">
                            See what's happening in the community with real-time activity updates.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #84cc16, #65a30d);">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Secure & Trusted</h3>
                        <p class="feature-description">
                            University email verification and admin approval ensures trusted community.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Call to Action -->
            <div class="cta-section">
                <h2 class="cta-title">Ready to Join the Community?</h2>
                <p class="cta-text">
                    Be part of a growing community of readers. Share your books, discover new ones, and connect with fellow students.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="/register/" class="btn btn-outline-light">
                        <i class="fas fa-user-plus"></i> Create Free Account
                    </a>
                    <a href="/books/" class="btn btn-primary" style="background: white; color: var(--primary);">
                        <i class="fas fa-book"></i> Explore Books
                    </a>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Statistics data from PHP
        const statsData = {
            totalBooks: <?php echo $totalBooks; ?>,
            totalUsers: <?php echo $totalUsers; ?>,
            availableBooks: <?php echo $availableBooks; ?>
        };
        
        // Smooth counter animation
        function animateCounter(element, start, end, duration = 1500) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const currentValue = Math.floor(progress * (end - start) + start);
                element.textContent = currentValue.toLocaleString();
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    element.textContent = end.toLocaleString();
                }
            };
            window.requestAnimationFrame(step);
        }
        
        // Initialize counters
        function initCounters() {
            animateCounter(document.getElementById('stat-totalUsers'), 0, statsData.totalUsers, 1500);
            animateCounter(document.getElementById('stat-totalBooks'), 0, statsData.totalBooks, 1500);
            animateCounter(document.getElementById('stat-availableBooks'), 0, statsData.availableBooks, 1500);
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', initCounters);
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>