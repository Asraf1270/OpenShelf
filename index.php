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
           PREMIUM GLASSMORPHIC DASHBOARD
        ======================================== */
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #6366f1;
            --primary-h: 239;
            --primary-s: 84%;
            --primary-l: 67%;
            --primary-rgb: 99, 102, 241;
            --accent: #10b981;
            --bg: #f8fafc;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-premium: 0 20px 40px -15px rgba(0, 0, 0, 0.1);
            --radius-lg: 24px;
            --radius-xl: 32px;
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg);
            font-family: 'Outfit', system-ui, -apple-system, sans-serif;
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Hero Section */
        .hero {
            position: relative;
            padding: 8rem 0 10rem;
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(139, 92, 246, 0.1), transparent 40%),
                        linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: white;
            text-align: center;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to bottom, transparent, var(--bg));
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 100px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.85rem;
            font-weight: 500;
            color: #a5b4fc;
            margin-bottom: 2rem;
            animation: fadeInDown 0.8s ease;
        }

        .hero-title {
            font-size: clamp(3rem, 10vw, 5.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #fff 30%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: fadeInUp 1s ease 0.2s both;
        }

        .hero-subtitle {
            font-size: clamp(1.1rem, 3vw, 1.4rem);
            color: rgba(255, 255, 255, 0.7);
            max-width: 700px;
            margin: 0 auto 3rem;
            animation: fadeInUp 1s ease 0.4s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease 0.6s both;
        }

        /* Stats Cards */
        .stats-dashboard {
            margin-top: -5rem;
            margin-bottom: 6rem;
            position: relative;
            z-index: 10;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            padding: 3rem 2rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-premium);
            transition: var(--transition);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(99, 102, 241, 0.4);
            background: rgba(255, 255, 255, 0.85);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(99, 102, 241, 0.2));
            color: var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .stat-card:hover .stat-icon {
            background: var(--primary);
            color: white;
            transform: rotate(10deg);
        }

        .stat-value {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -1px;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Feature Section */
        .features-section {
            padding: 6rem 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 5rem;
        }

        .section-tag {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 1rem;
            display: block;
        }

        .section-title {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 1.5rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2.5rem;
        }

        .feature-card {
            background: white;
            padding: 3rem 2rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--glass-border);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: var(--primary);
            opacity: 0;
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateX(10px);
            box-shadow: var(--shadow-premium);
        }

        .feature-card:hover::before { opacity: 1; }

        .feature-icon-box {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 2rem;
            opacity: 0.8;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--text-muted);
            line-height: 1.7;
        }

        /* Steps Section */
        .steps-container {
            background: #fff;
            padding: 8rem 0;
            border-radius: 60px;
            margin: 4rem 0;
            box-shadow: inset 0 0 100px rgba(0,0,0,0.02);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 3rem;
            position: relative;
        }

        .step-card {
            text-align: center;
            position: relative;
        }

        .step-number {
            font-size: 8rem;
            font-weight: 900;
            color: rgba(99, 102, 241, 0.05);
            position: absolute;
            top: -2rem;
            left: 50%;
            transform: translateX(-50%);
            line-height: 1;
            z-index: 1;
        }

        .step-content {
            position: relative;
            z-index: 2;
        }

        .step-icon {
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.5rem;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        /* Buttons */
        .btn {
            padding: 1.2rem 2.5rem;
            border-radius: 100px;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            transition: var(--transition);
        }

        .btn-primary {
            background: white;
            color: var(--primary);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            background: var(--bg);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
            transform: translateY(-5px);
        }

        /* Animations */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 768px) {
            .hero { padding: 6rem 0 8rem; }
            .stats-dashboard { margin-top: -4rem; }
            .stat-value { font-size: 2.8rem; }
            .steps-container { padding: 4rem 0; border-radius: 30px; }
        }
    </style>
</head>
<body>
    
    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-badge">
                    <i class="fas fa-sparkles"></i> OpenShelf v1.0.1 Stable
                </div>
                <h1 class="hero-title">Share Knowledge.<br>Share Books.</h1>
                <p class="hero-subtitle">
                    The ultimate community-powered library for university students. 
                    Manage your collection, discover new reads, and connect with fellow book lovers.
                </p>
                <div class="hero-buttons">
                    <a href="/books/" class="btn btn-primary">
                        <i class="fas fa-search"></i> Explore Library
                    </a>
                    <a href="/add-book/" class="btn btn-outline">
                        <i class="fas fa-plus"></i> Share a Book
                    </a>
                </div>
            </div>
        </section>
        
        <div class="container">
            <!-- Statistics Dashboard -->
            <div class="stats-dashboard">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users-viewfinder"></i></div>
                        <div class="stat-value" id="stat-totalUsers">0</div>
                        <div class="stat-label">Active Readers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-book-sparkles"></i></div>
                        <div class="stat-value" id="stat-totalBooks">0</div>
                        <div class="stat-label">Total Books</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                        <div class="stat-value" id="stat-availableBooks">0</div>
                        <div class="stat-label">Available Now</div>
                    </div>
                </div>
            </div>
            
            <!-- Features Section -->
            <section class="features-section">
                <div class="section-header">
                    <span class="section-tag">Features</span>
                    <h2 class="section-title">Designed for Communities</h2>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon-box"><i class="fas fa-heart-pulse"></i></div>
                        <h3 class="feature-title">Community Driven</h3>
                        <p class="feature-description">Built for and by students. Help grow the library by sharing books you've already read.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon-box"><i class="fas fa-envelope-open-text"></i></div>
                        <h3 class="feature-title">Smart Notifications</h3>
                        <p class="feature-description">Instant email and in-app alerts keep you updated on all your borrow requests and returns.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon-box"><i class="fab fa-whatsapp"></i></div>
                        <h3 class="feature-title">Fast Coordination</h3>
                        <p class="feature-description">Direct WhatsApp integration for quick communication between lenders and borrowers.</p>
                    </div>
                </div>
            </section>
            
            <!-- Steps Section -->
            <section class="steps-container">
                <div class="container">
                    <div class="section-header">
                        <span class="section-tag">How it works</span>
                        <h2 class="section-title">Start Borrowing in Minutes</h2>
                    </div>
                    
                    <div class="steps-grid">
                        <div class="step-card">
                            <div class="step-number">01</div>
                            <div class="step-content">
                                <div class="step-icon"><i class="fas fa-user-plus"></i></div>
                                <h3 class="step-title">Join Us</h3>
                                <p class="step-description">Create your account with your university email.</p>
                            </div>
                        </div>
                        <div class="step-card">
                            <div class="step-number">02</div>
                            <div class="step-content">
                                <div class="step-icon"><i class="fas fa-upload"></i></div>
                                <h3 class="step-title">List Books</h3>
                                <p class="step-description">Share your books with the community easily.</p>
                            </div>
                        </div>
                        <div class="step-card">
                            <div class="step-number">03</div>
                            <div class="step-content">
                                <div class="step-icon"><i class="fas fa-magnifying-glass"></i></div>
                                <h3 class="step-title">Browse</h3>
                                <p class="step-description">Find the book you need using smart filters.</p>
                            </div>
                        </div>
                        <div class="step-card">
                            <div class="step-number">04</div>
                            <div class="step-content">
                                <div class="step-icon"><i class="fas fa-handshake"></i></div>
                                <h3 class="step-title">Connect</h3>
                                <p class="step-description">Request to borrow and coordinate pickup.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    
    <script>
        const statsData = {
            totalBooks: <?php echo $totalBooks; ?>,
            totalUsers: <?php echo $totalUsers; ?>,
            availableBooks: <?php echo $availableBooks; ?>
        };
        
        function animateCounter(element, start, end, duration = 2000) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                // Use easeOutExpo for smoother ending
                const easeOutExpo = 1 - Math.pow(2, -10 * progress);
                const currentValue = Math.floor(easeOutExpo * (end - start) + start);
                element.textContent = currentValue.toLocaleString();
                if (progress < 1) window.requestAnimationFrame(step);
                else element.textContent = end.toLocaleString();
            };
            window.requestAnimationFrame(step);
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                animateCounter(document.getElementById('stat-totalUsers'), 0, statsData.totalUsers);
                animateCounter(document.getElementById('stat-totalBooks'), 0, statsData.totalBooks);
                animateCounter(document.getElementById('stat-availableBooks'), 0, statsData.availableBooks);
            }, 500);
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>