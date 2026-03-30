<?php
/**
 * OpenShelf About Page
 * Information about the platform and team
 */

session_start();
include 'includes/header.php';
?>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --secondary-glow: rgba(139, 92, 246, 0.4);
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

        body {
            background-color: var(--bg);
            font-family: 'Outfit', system-ui, -apple-system, sans-serif;
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Hero Section */
        .hero-container {
            position: relative;
            padding: 10rem 2.5rem 8rem;
            text-align: center;
            overflow: hidden;
            background: linear-gradient(to bottom, #ffffff, var(--bg));
        }

        .hero-bg-shapes {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .shape-1 {
            position: absolute;
            top: -10%; left: -5%;
            width: 600px; height: 600px;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 60%);
            filter: blur(60px);
            animation: float 8s ease-in-out infinite;
        }

        .shape-2 {
            position: absolute;
            bottom: -10%; right: -5%;
            width: 700px; height: 700px;
            background: radial-gradient(circle, var(--secondary-glow) 0%, transparent 60%);
            filter: blur(80px);
            animation: float 10s ease-in-out infinite reverse;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
        }

        .hero-title {
            font-size: clamp(3rem, 10vw, 5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 2rem;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #0f172a 0%, #4338ca 50%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: fadeUp 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-muted);
            line-height: 1.7;
            animation: fadeUp 1s ease-out 0.2s both;
        }

        /* Mission Section */
        .mission-section {
            padding: 0 2rem 6rem;
            position: relative;
            z-index: 2;
        }

        .mission-glass-card {
            max-width: 1000px;
            margin: -6rem auto 0;
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 4rem;
            text-align: center;
            box-shadow: var(--shadow-premium);
            transition: var(--transition);
        }

        .mission-glass-card:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.85);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .mission-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 2rem;
            display: inline-flex;
            width: 80px; height: 80px;
            align-items: center; justify-content: center;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            border-radius: 24px;
        }

        .mission-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--text-main);
            letter-spacing: -1px;
        }

        .mission-text {
            font-size: 1.15rem;
            color: var(--text-muted);
            line-height: 1.8;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Stats Section */
        .stats-section {
            padding: 6rem 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
        }

        .stat-card {
            background: white;
            padding: 4rem 2rem;
            border-radius: var(--radius-xl);
            text-align: center;
            border: 1px solid #f1f5f9;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-premium);
            transform: translateY(-10px);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .stat-value {
            font-size: 4rem;
            font-weight: 800;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Features Section */
        .features-section {
            padding: 8rem 1.5rem;
            background: #fff;
            border-radius: 60px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 5rem;
        }

        .section-title {
            font-size: clamp(2.5rem, 6vw, 3.5rem);
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -1px;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            font-size: 1.15rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 3rem;
        }

        .feature-item {
            padding: 3rem 2rem;
            border-radius: var(--radius-lg);
            background: var(--bg);
            border: 1px solid transparent;
            transition: var(--transition);
        }

        .feature-item:hover {
            background: white;
            box-shadow: var(--shadow-premium);
            transform: translateY(-8px);
            border-color: rgba(99, 102, 241, 0.1);
        }

        .feature-icon-wrapper {
            width: 72px; height: 72px;
            border-radius: 20px;
            background: white;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            color: var(--primary);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .feature-item:hover .feature-icon-wrapper {
            background: var(--primary);
            color: white;
            transform: scale(1.1) rotate(5deg);
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: var(--text-main);
        }

        .feature-desc {
            color: var(--text-muted);
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* Team Section */
        .team-section {
            padding: 8rem 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 3rem;
        }

        .team-member {
            text-align: center;
            padding: 4rem 3rem;
            border-radius: var(--radius-xl);
            background: white;
            border: 1px solid #f1f5f9;
            transition: var(--transition);
        }

        .team-member:hover {
            transform: translateY(-12px);
            box-shadow: var(--shadow-premium);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .member-avatar {
            width: 140px; height: 140px;
            border-radius: 40px;
            margin: 0 auto 2.5rem;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 4rem;
            color: white;
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
            transition: var(--transition);
        }

        .team-member:hover .member-avatar {
            transform: scale(1.1) rotate(5deg);
            border-radius: 50px;
        }

        .member-role {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .member-desc {
            color: var(--text-muted);
            line-height: 1.7;
            font-size: 1.1rem;
        }

        /* Animations */
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, -30px); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .hero-container { padding-top: 8rem; }
            .mission-glass-card { padding: 2.5rem; margin-top: -4rem; }
            .stats-section, .features-section, .team-section { padding: 4rem 1.5rem; }
            .features-section { border-radius: 40px; }
        }
    </style>

<main class="about-wrapper">
    <!-- Hero Section -->
    <section class="hero-container">
        <div class="hero-bg-shapes">
            <div class="shape-1"></div>
            <div class="shape-2"></div>
        </div>
        <div class="hero-content">
            <h1 class="hero-title">Reimagining Campus Reading</h1>
            <p class="hero-subtitle">OpenShelf empowers students to share books effortlessly, build vibrant communities within residential halls, and embark on endless reading adventures together.</p>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="mission-section">
        <div class="mission-glass-card">
            <div class="mission-icon">
                <i class="fas fa-rocket"></i>
            </div>
            <h2 class="mission-title">Our Mission</h2>
            <p class="mission-text">To break down barriers to reading by creating a decentralized, student-driven library. We believe that every great book deserves to be shared, and every student should have easy access to a diverse catalog of literature right at their doorstep.</p>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">150+</div>
                <div class="stat-label">Books Shared</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">80+</div>
                <div class="stat-label">Active Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">45+</div>
                <div class="stat-label">Books Borrowed</div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="section-header">
            <h2 class="section-title">Why OpenShelf?</h2>
            <p class="section-subtitle">Everything you need to discover, share, and manage your campus reading life.</p>
        </div>
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <h3 class="feature-title">Share Seamlessly</h3>
                <p class="feature-desc">Upload your books in seconds and make them available to fellow students across your hall.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-compass"></i>
                </div>
                <h3 class="feature-title">Discover Reads</h3>
                <p class="feature-desc">Explore a curated collection of thousands of books, all shared by trusted community members.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon-wrapper">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <h3 class="feature-title">Direct Connection</h3>
                <p class="feature-desc">Connect directly via WhatsApp to arrange fast, convenient pickups without any middlemen.</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="feature-title">Community Reviews</h3>
                <p class="feature-desc">Read honest reviews from peers and contribute your own thoughts to guide others.</p>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="section-header">
            <h2 class="section-title">The People Behind It</h2>
            <p class="section-subtitle">A collective effort from passionate individuals dedicated to fostering knowledge.</p>
        </div>
        <div class="team-grid">
            <div class="team-member">
                <div class="member-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3 class="member-role">Students</h3>
                <p class="member-desc">The driving force of our community, actively participating and sharing knowledge daily.</p>
            </div>
            <div class="team-member">
                <div class="member-avatar">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <h3 class="member-role">Developers</h3>
                <p class="member-desc">Engineers crafting scalable, user-friendly solutions to make sharing a breeze.</p>
            </div>
            <div class="team-member">
                <div class="member-avatar">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="member-role">Community</h3>
                <p class="member-desc">Everyone who reads, shares, and connects, making this platform what it is today.</p>
            </div>
        </div>
    </section>
<!-- Note: The footer closes the main tag -->
<?php include 'includes/footer.php'; ?>