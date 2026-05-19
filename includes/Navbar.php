<?php
/**
 * OpenShelf Bottom Navbar v2.1
 * Refined Floating Curved Bottom Design based on Planning Sketch
 */

$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
$currentPath = parse_url($currentPath, PHP_URL_PATH) ?: '/';

$isLoggedIn = isset($_SESSION['user_id']);
$profileLink = $isLoggedIn ? '/profile/' : '/login/';

$homeActive = $currentPath === '/' || basename($currentPath) === 'index.php' || str_contains($currentPath, '/books');
$requestsActive = str_contains($currentPath, '/requests');
$addBookActive = str_contains($currentPath, '/add-book');
$borrowedActive = str_contains($currentPath, '/my-borrowed');
$profileActive = str_contains($currentPath, '/profile') || str_contains($currentPath, '/settings') || (!$isLoggedIn && str_contains($currentPath, '/login'));
?>

<div class="bottom-nav-container" id="bottomNavbar">
    <div class="bottom-nav-bar">
        <!-- Left Side Icons -->
        <div class="nav-section nav-left">
            <a href="/" class="nav-item <?php echo $homeActive ? 'active' : ''; ?>" aria-label="Home">
                <i class="fas fa-house"></i>
            </a>
            <a href="/requests/" class="nav-item <?php echo $requestsActive ? 'active' : ''; ?>" aria-label="Requests">
                <i class="fas fa-paper-plane"></i>
            </a>
        </div>

        <!-- Central FAB Notch Area -->
        <div class="nav-center">
            <div class="fab-notch"></div>
            <a href="/add-book/" class="fab-button <?php echo $addBookActive ? 'active' : ''; ?>" aria-label="Add Book">
                <i class="fas fa-plus"></i>
            </a>
        </div>

        <!-- Right Side Icons -->
        <div class="nav-section nav-right">
            <a href="/my-borrowed/" class="nav-item <?php echo $borrowedActive ? 'active' : ''; ?>" aria-label="My Borrowed">
                <i class="fas fa-book-reader"></i>
            </a>
            <a href="<?php echo $profileLink; ?>" class="nav-item <?php echo $profileActive ? 'active' : ''; ?>" aria-label="Profile">
                <i class="fas fa-user"></i>
            </a>
        </div>
    </div>
</div>

<script>
(function() {
    const navbar = document.getElementById('bottomNavbar');
    if (!navbar) return;

    let lastScrollY = window.pageYOffset;
    let ticking = false;

    function updateNavbar() {
        const currentScrollY = window.pageYOffset;
        const scrollDelta = currentScrollY - lastScrollY;

        if (Math.abs(scrollDelta) < 10) {
            ticking = false;
            return;
        }

        if (currentScrollY > lastScrollY && currentScrollY > 100) {
            navbar.classList.add('nav-hidden');
        } else {
            navbar.classList.remove('nav-hidden');
        }

        lastScrollY = currentScrollY;
        ticking = false;
    }

    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(updateNavbar);
            ticking = true;
        }
    }, { passive: true });
})();
</script>

<style>
:root {
    --nav-bg: #ffffff;
    --nav-active-bg: rgba(76, 159, 138, 0.15); /* Soft teal background for active item */
    --nav-active-color: var(--secondary, #4C9F8A); /* Teal for active icon */
    --nav-inactive-color: #94a3b8; /* Slate gray for inactive icons */
    --fab-bg: var(--secondary, #4C9F8A);
    --fab-size: 60px; /* Adjusted size for thick border */
    --fab-border: #f8fafc; /* Matches light body background for cut-out effect */
    --nav-height: 64px;
}

:root[data-theme="dark"] {
    --nav-bg: #1e293b;
    --nav-inactive-color: #64748b;
    --fab-border: #0f172a; /* Matches dark body background */
}

.bottom-nav-container {
    position: fixed;
    bottom: 20px; /* Floating */
    left: 15px;
    right: 15px;
    z-index: 1001;
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.bottom-nav-container.nav-hidden {
    transform: translateY(150%);
    opacity: 0;
    pointer-events: none;
}

.bottom-nav-bar {
    position: relative;
    background: var(--nav-bg);
    height: var(--nav-height);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); /* Lightened shadow for white theme */
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: background 0.3s ease, box-shadow 0.3s ease;
}

[data-theme="dark"] .bottom-nav-bar {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

/* The Notch Effect */
.bottom-nav-bar::before {
    content: '';
    position: absolute;
    top: -12px; /* Shallower notch for lower button */
    left: 50%;
    transform: translateX(-50%);
    width: 86px;
    height: 43px;
    background: transparent;
    border-radius: 0 0 50px 50px;
    box-shadow: 0 12px 0 0 var(--nav-bg); /* Shallower curve */
    pointer-events: none;
    transition: box-shadow 0.3s ease;
}

.nav-section {
    display: flex;
    flex: 1;
    justify-content: space-evenly;
    align-items: center;
    height: 100%;
}

.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    color: var(--nav-inactive-color);
    text-decoration: none;
    font-size: 1.25rem;
    border-radius: 12px;
    transition: all 0.25s ease;
    gap: 2px;
}

.nav-item.active {
    color: var(--nav-active-color);
    background: var(--nav-active-bg);
    transform: translateY(-2px);
}

.nav-center {
    position: relative;
    width: 70px;
    height: 100%;
    display: flex;
    justify-content: center;
}

.fab-button {
    position: absolute;
    top: 0;
    transform: translateY(-18px); /* Sits lower: ~75% inside, 25% protruding */
    width: var(--fab-size);
    height: var(--fab-size);
    background: var(--fab-bg);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    border: none; /* Removing physical border to fix flicker */
    /* Stacked box-shadow: inner creates the border, outer creates the drop shadow */
    box-shadow: 0 0 0 6px var(--fab-border), 0 4px 12px rgba(76, 159, 138, 0.3);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease, background 0.3s ease;
    z-index: 1002;
    will-change: transform, box-shadow; /* Optimize rendering */
}

.fab-button:hover {
    transform: translateY(-22px) scale(1.05);
    box-shadow: 0 0 0 6px var(--fab-border), 0 8px 16px rgba(76, 159, 138, 0.4);
}

.fab-button.active {
    background: #3a8270;
}

@media (max-width: 380px) {
    :root {
        --fab-size: 54px;
        --nav-height: 56px;
    }
    .nav-item {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }
    .bottom-nav-bar::before {
        width: 76px;
        height: 38px;
        top: -10px;
        box-shadow: 0 10px 0 0 var(--nav-bg);
    }
    .fab-button {
        top: 0;
        transform: translateY(-15px); /* ~25% protrusion */
        border: none;
        box-shadow: 0 0 0 4px var(--fab-border), 0 4px 12px rgba(76, 159, 138, 0.3);
    }
    .fab-button:hover {
        transform: translateY(-19px) scale(1.05);
        box-shadow: 0 0 0 4px var(--fab-border), 0 8px 16px rgba(76, 159, 138, 0.4);
    }
}

@media (min-width: 768px) {
    /* Hide bottom navbar on desktop screens */
    .bottom-nav-container {
        display: none !important;
    }
}
</style>
