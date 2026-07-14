<div class="bottom-nav-container" id="bottomNavbar">
    <div class="bottom-nav-bar">
        <!-- Left Side Icons -->
        <div class="nav-section nav-left">
            <a href="/" class="nav-item @if(Route::currentRouteName() === 'home' || Route::currentRouteName() === 'books.index') active @endif" aria-label="Home">
                <i class="fas fa-house"></i>
            </a>
            <a href="/requests" class="nav-item @if(Route::currentRouteName() === 'requests.index') active @endif" aria-label="Requests">
                <i class="fas fa-paper-plane"></i>
            </a>
        </div>

        <!-- Central FAB Notch Area -->
        <div class="nav-center">
            <div class="fab-notch"></div>
            @if(session('user_id'))
                <a href="/add-book" class="fab-button @if(Route::currentRouteName() === 'books.create') active @endif" aria-label="Add Book">
                    <i class="fas fa-plus"></i>
                </a>
            @else
                <a href="/login" class="fab-button" aria-label="Login to add books">
                    <i class="fas fa-plus"></i>
                </a>
            @endif
        </div>

        <!-- Right Side Icons -->
        <div class="nav-section nav-right">
            <a href="/my-borrowed" class="nav-item @if(Route::currentRouteName() === 'borrowed.index') active @endif" aria-label="My Borrowed">
                <i class="fas fa-book-reader"></i>
            </a>
            @if(session('user_id'))
                <a href="/profile" class="nav-item @if(Route::currentRouteName() === 'profile.show') active @endif" aria-label="Profile">
                    <i class="fas fa-user"></i>
                </a>
            @else
                <a href="/login" class="nav-item" aria-label="Login">
                    <i class="fas fa-user"></i>
                </a>
            @endif
        </div>
    </div>
</div>

<style>
:root {
    --nav-bg: #ffffff;
    --nav-active-bg: rgba(76, 159, 138, 0.15);
    --nav-active-color: var(--secondary, #4C9F8A);
    --nav-inactive-color: #94a3b8;
    --fab-bg: var(--secondary, #4C9F8A);
    --fab-size: 60px;
    --fab-border: #f8fafc;
    --nav-height: 64px;
}

:root[data-theme="dark"] {
    --nav-bg: #1e293b;
    --nav-active-bg: rgba(76, 159, 138, 0.15);
    --nav-active-color: #4C9F8A;
    --nav-inactive-color: #64748b;
    --fab-bg: #4C9F8A;
    --fab-border: #0f172a;
}

.bottom-nav-container {
    position: fixed;
    bottom: 20px;
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
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
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
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 86px;
    height: 43px;
    background: transparent;
    border-radius: 0 0 50px 50px;
    box-shadow: 0 12px 0 0 var(--nav-bg);
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

.nav-item:hover {
    color: var(--nav-active-color);
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
    transform: translateY(-18px);
    width: var(--fab-size);
    height: var(--fab-size);
    background: var(--fab-bg);
    border: 4px solid var(--fab-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 4px 12px rgba(76, 159, 138, 0.3);
}

.fab-button:hover,
.fab-button.active {
    transform: translateY(-18px) scale(1.1);
    box-shadow: 0 8px 24px rgba(76, 159, 138, 0.4);
}

.fab-button:active {
    transform: translateY(-18px) scale(0.95);
}

.fab-notch {
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 64px;
    height: 20px;
    background: var(--fab-border);
    border-radius: 0 0 32px 32px;
    z-index: -1;
}

/* Scroll hide effect */
@media (max-width: 768px) {
    .bottom-nav-bar {
        border-radius: 16px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
