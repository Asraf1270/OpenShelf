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
    --primary-glow: rgba(99, 102, 241, 0.4);
    --secondary-glow: rgba(139, 92, 246, 0.4);
}

.about-wrapper {
    overflow-x: hidden;
    background: #f8fafc;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
}

/* Hero Section */
.hero-container {
    position: relative;
    padding: 8rem 2rem 6rem;
    text-align: center;
    overflow: hidden;
    background: linear-gradient(to bottom, #ffffff, #f8fafc);
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
    top: 20%; right: -10%;
    width: 700px; height: 700px;
    background: radial-gradient(circle, var(--secondary-glow) 0%, transparent 60%);
    filter: blur(80px);
    animation: float 10s ease-in-out infinite reverse;
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 800px;
    margin: 0 auto;
}

.hero-title {
    font-size: 4rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 1.5rem;
    background: linear-gradient(135deg, #0f172a 0%, #4338ca 50%, #6366f1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: fadeUp 1s ease-out;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: #475569;
    margin-bottom: 2.5rem;
    line-height: 1.6;
    animation: fadeUp 1s ease-out 0.2s both;
}

/* Mission Section */
.mission-section {
    padding: 2rem 2rem 4rem;
    position: relative;
    z-index: 2;
}

.mission-glass-card {
    max-width: 1000px;
    margin: -4rem auto 0;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 2rem;
    padding: 4rem;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05);
    transform: translateY(0);
    transition: all 0.4s ease;
}

.mission-glass-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 60px -15px rgba(99, 102, 241, 0.15);
}

.mission-icon {
    font-size: 3rem;
    color: #6366f1;
    margin-bottom: 1.5rem;
    display: inline-block;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
    border-radius: 50%;
}

.mission-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: #1e293b;
}

.mission-text {
    font-size: 1.15rem;
    color: #475569;
    line-height: 1.8;
    max-width: 800px;
    margin: 0 auto;
}

/* Stats Section */
.stats-section {
    padding: 2rem 2rem 5rem;
    max-width: 1200px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2.5rem;
}

.stat-card {
    background: white;
    padding: 3rem 2rem;
    border-radius: 1.5rem;
    text-align: center;
    border: 1px solid #e2e8f0;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 5px;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
    transform: scaleX(0);
    transition: transform 0.4s ease;
    transform-origin: left;
}

.stat-card:hover::before {
    transform: scaleX(1);
}

.stat-card:hover {
    box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.1);
    transform: translateY(-8px);
    border-color: #c7d2fe;
}

.stat-value {
    font-size: 3.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1.1rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Features Block */
.features-section {
    padding: 6rem 2rem;
    background: white;
}

.section-header {
    text-align: center;
    margin-bottom: 4rem;
}

.section-title {
    font-size: 2.75rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.15rem;
    color: #64748b;
    max-width: 600px;
    margin: 0 auto;
}

.features-grid {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 2.5rem;
}

.feature-item {
    padding: 2.5rem;
    border-radius: 1.5rem;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.feature-item:hover {
    background: white;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
    transform: translateY(-5px);
    border-color: #e2e8f0;
}

.feature-icon-wrapper {
    width: 68px;
    height: 68px;
    border-radius: 1.2rem;
    background: #e0e7ff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    font-size: 2rem;
    color: #4f46e5;
    transition: all 0.3s ease;
}

.feature-item:hover .feature-icon-wrapper {
    background: #4f46e5;
    color: white;
    transform: rotate(10deg) scale(1.1);
    box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
}

.feature-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #1e293b;
}

.feature-desc {
    color: #475569;
    line-height: 1.7;
}

/* Team Section */
.team-section {
    padding: 6rem 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 3rem;
}

.team-member {
    text-align: center;
    padding: 3rem 2rem;
    border-radius: 2rem;
    background: white;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.02);
    border: 1px solid #e2e8f0;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.team-member:hover {
    transform: translateY(-12px);
    box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.15);
    border-color: #c7d2fe;
}

.member-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    color: #4f46e5;
    box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.2);
    position: relative;
    transition: all 0.3s ease;
}

.team-member:hover .member-avatar {
    transform: scale(1.05);
}

.member-avatar::after {
    content: '';
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    border: 2px dashed #a5b4fc;
    animation: rotate 10s linear infinite;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.team-member:hover .member-avatar::after {
    opacity: 1;
}

.member-role {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.member-desc {
    color: #64748b;
    line-height: 1.6;
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

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title { font-size: 2.75rem; }
    .mission-glass-card { padding: 2.5rem 1.5rem; margin-top: -2rem; }
    .mission-title { font-size: 2rem; }
    .stats-section, .features-section, .team-section { padding: 4rem 1.5rem; }
    .stat-value { font-size: 3rem; }
}

@media (max-width: 480px) {
    .hero-title { font-size: 2.25rem; }
    .section-title { font-size: 2.25rem; }
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