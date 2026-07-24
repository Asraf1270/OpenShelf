<?php
/**
 * OpenShelf - Modern Header with Hamburger Menu
 * Works for ALL pages - includes proper CSS and JS
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/search_helper.php';
require_once __DIR__ . '/helpers.php';

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];

// Get user data if logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'Guest';
$userEmail = $_SESSION['user_email'] ?? '';
$userAvatar = 'default-avatar.jpg';
$notificationCount = 0;

if ($isLoggedIn && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Primary source: Load from MySQL users table
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    
    if ($u) {
        $userName = $u['name'] ?? $userName;
        $userEmail = $u['email'] ?? $userEmail;
        $userAvatar = $u['profile_pic'] ?? 'default-avatar.jpg';
        
        // Sync hall to session if missing
        if (!isset($_SESSION['user_hall']) && isset($u['hall'])) {
            $_SESSION['user_hall'] = $u['hall'];
        }
    }

    // Also check session for override (e.g. immediately after update)
    if (isset($_SESSION['user_name'])) {
        $userName = $_SESSION['user_name'];
    }
    if (isset($_SESSION['user_avatar'])) {
        $userAvatar = $_SESSION['user_avatar'];
    }
    
    // Get notification count from database
    try {
        $notifStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM `notifications` 
            WHERE `user_id` = ? 
            AND `is_read` = 0 
            AND (`expires_at` IS NULL OR `expires_at` > NOW())
        ");
        $notifStmt->execute([$userId]);
        $notificationCount = (int)$notifStmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting notification count in header: " . $e->getMessage());
        $notificationCount = 0;
    }
}

// Check if profile picture file exists
$avatarPath = '/uploads/profile/' . $userAvatar;
$defaultAvatar = '/assets/images/avatars/default.jpg';

// Verify file exists on server
$fullAvatarPath = dirname(__DIR__) . '/uploads/profile/' . $userAvatar;
if (!file_exists($fullAvatarPath) || $userAvatar === 'default-avatar.jpg') {
    $avatarPath = $defaultAvatar;
}

// ==========================================
// DYNAMIC SEO ENGINE FOR OPENSHELF
// ==========================================
$siteName = "OpenShelf";
$defaultTitle = "OpenShelf - Share Books, Share Knowledge";
$defaultDesc = "OpenShelf is a student-led, peer-to-peer book sharing platform. Share and borrow textbooks, novels, and guides within your campus community for free.";
$defaultKeywords = "book sharing, university library, campus books, borrow books, free books, peer-to-peer, OpenShelf, DU OpenShelf, book exchange";
$defaultImage = "/assets/images/pwa/icon-192x192.png";

// Get protocol and host for absolute URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'duopenshelf.top';
$baseUrl = $protocol . "://" . $host;

// Dynamic rating values for structured data, initialized to safe defaults to satisfy static code analysis
$seoRatingCount = 0;
$seoAvgRating = "0.0";

// Detect current page file to set automatic, smart defaults
$selfPath = $_SERVER['PHP_SELF'] ?? '';
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Check if we are on a book detail page and have a $book variable loaded
if (isset($book) && is_array($book) && !empty($book['title'])) {
    $seoTitle = htmlspecialchars($book['title']) . " by " . htmlspecialchars($book['author']) . " | " . $siteName;
    $seoDesc = !empty($book['description']) 
        ? mb_strimwidth(strip_tags($book['description']), 0, 155, "...") 
        : "Borrow " . htmlspecialchars($book['title']) . " by " . htmlspecialchars($book['author']) . " on OpenShelf for free. Connect with campus students.";
    $seoKeywords = htmlspecialchars($book['title']) . ", " . htmlspecialchars($book['author']) . ", " . htmlspecialchars($book['category'] ?? '') . ", borrow books, free books, OpenShelf";
    
    // Resolve Cover Image
    if (!empty($book['cover_image'])) {
        $seoImage = $baseUrl . "/uploads/book_cover/" . $book['cover_image'];
    } else {
        $seoImage = $baseUrl . $defaultImage;
    }
    $seoOgType = "book";

    // Dynamic rating values for structured data, defaulting to safe values to prevent undefined variable errors
    $seoRatingCount = isset($ratingCount) ? $ratingCount : ($book['rating_count'] ?? 0);
    $seoAvgRating = isset($avgRating) ? $avgRating : number_format($book['rating'] ?? 0, 1);
} else {
    // Determine page-specific defaults if variables are not explicitly set
    $autoTitle = "";
    $autoDesc = "";
    $autoKeywords = "";

    if (strpos($selfPath, '/books/') !== false) {
        $autoTitle = "Explore Shared Books";
        $autoDesc = "Browse and search through hundreds of books shared by fellow students on OpenShelf. Borrow academic textbooks, novels, literature, and more for free.";
        $autoKeywords = "book list, browse books, borrow textbooks, academic books, novels, free reading";
    } elseif (strpos($selfPath, '/feed/') !== false) {
        $autoTitle = "Community Book Activity Feed";
        $autoDesc = "See the latest book sharing activities, new uploads, and updates from the OpenShelf campus community.";
    } elseif (strpos($selfPath, '/announcements/') !== false) {
        $autoTitle = "Campus Announcements";
        $autoDesc = "Stay updated with the latest library updates, notifications, and platform news from the OpenShelf team.";
    } elseif (strpos($selfPath, '/notifications/') !== false) {
        $autoTitle = "My Notifications";
        $autoDesc = "Manage your borrow requests, returns, and wishlist notifications on OpenShelf.";
    } elseif (strpos($selfPath, '/settings/') !== false) {
        $autoTitle = "Account Settings";
        $autoDesc = "Manage your OpenShelf profile details, library preferences, and security settings.";
    } elseif (strpos($selfPath, '/support_us/') !== false) {
        $autoTitle = "Support Our Mission";
        $autoDesc = "Support OpenShelf and help us keep the platform running. Help us make university education more affordable through free book sharing.";
    } elseif (strpos($selfPath, '/my-borrowed/') !== false) {
        $autoTitle = "My Borrowed Books";
        $autoDesc = "Track your borrowed books, active requests, and book return status on OpenShelf.";
    } elseif (strpos($selfPath, '/about.php') !== false) {
        $autoTitle = "About Us - Our Mission";
        $autoDesc = "Learn more about OpenShelf, our mission to make knowledge accessible, our core campus values, and how student-led book sharing works.";
        $autoKeywords = "about openshelf, student library, book sharing mission, book exchange project";
    } elseif (strpos($selfPath, '/faq.php') !== false) {
        $autoTitle = "Frequently Asked Questions (FAQ)";
        $autoDesc = "Find quick answers to common questions about borrowing books, returning books, listing books, and security on OpenShelf.";
        $autoKeywords = "faq, help center, how to borrow books, returning books query";
    } elseif (strpos($selfPath, '/contact.php') !== false) {
        $autoTitle = "Contact Support";
        $autoDesc = "Get in touch with the OpenShelf support team for assistance, feedback, or partnerships.";
        $autoKeywords = "contact, support, customer care, send message, feedback";
    } elseif (strpos($selfPath, '/privacy.php') !== false) {
        $autoTitle = "Privacy Policy";
        $autoDesc = "Read the OpenShelf Privacy Policy to understand how we collect, use, and protect your personal information.";
        $autoKeywords = "privacy policy, data protection, personal data security";
    } elseif (strpos($selfPath, '/terms.php') !== false) {
        $autoTitle = "Terms of Service";
        $autoDesc = "Read the OpenShelf Terms of Service and user agreement for our peer-to-peer book sharing platform.";
        $autoKeywords = "terms of service, legal terms, user agreement";
    } elseif (strpos($selfPath, '/guidelines.php') !== false) {
        $autoTitle = "Community Guidelines";
        $autoDesc = "OpenShelf community guidelines to ensure a safe, respectful, and helpful environment for sharing books on campus.";
        $autoKeywords = "community guidelines, code of conduct, safe book sharing";
    } elseif (strpos($selfPath, '/report.php') !== false) {
        $autoTitle = "Report an Issue";
        $autoDesc = "Report a bug, user misconduct, or other issues to the OpenShelf administrators.";
    } elseif (strpos($selfPath, '/index.php') !== false || $selfPath === '/' || $selfPath === '') {
        $autoTitle = "Share Books, Share Knowledge";
        $autoDesc = $defaultDesc;
        $autoKeywords = $defaultKeywords;
    }

    // Set final title
    if (isset($pageTitle)) {
        $seoTitle = htmlspecialchars($pageTitle) . " | " . $siteName;
    } elseif (!empty($autoTitle)) {
        $seoTitle = $autoTitle . " | " . $siteName;
    } else {
        $seoTitle = $defaultTitle;
    }

    // Set final description
    if (isset($metaDescription)) {
        $seoDesc = htmlspecialchars($metaDescription);
    } elseif (!empty($autoDesc)) {
        $seoDesc = $autoDesc;
    } else {
        $seoDesc = $defaultDesc;
    }

    // Set final keywords
    if (isset($metaKeywords)) {
        $seoKeywords = htmlspecialchars($metaKeywords);
    } elseif (!empty($autoKeywords)) {
        $seoKeywords = $autoKeywords . ", " . $defaultKeywords;
    } else {
        $seoKeywords = $defaultKeywords;
    }

    // Set final image
    if (isset($metaImage)) {
        $seoImage = (strpos($metaImage, 'http') === 0) ? $metaImage : $baseUrl . $metaImage;
    } else {
        $seoImage = $baseUrl . $defaultImage;
    }

    $seoOgType = isset($ogType) ? htmlspecialchars($ogType) : "website";
}

// Canonical URL
$seoCanonical = isset($canonicalUrl) ? htmlspecialchars($canonicalUrl) : $baseUrl . $_SERVER['REQUEST_URI'];

// Robots Index / Noindex logic
// Automatically block search engines from indexing sensitive pages
$noIndexPages = ['/login/', '/register/', '/settings/', '/admin/', '/notifications/', '/requests/', '/borrow-request/', '/return-book/', '/confirm-return/'];
$isNoIndex = false;
if (isset($noIndex) && $noIndex === true) {
    $isNoIndex = true;
} else {
    foreach ($noIndexPages as $nip) {
        if (strpos($currentUri, $nip) !== false) {
            $isNoIndex = true;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $seoTitle; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo $seoDesc; ?>">
    <meta name="keywords" content="<?php echo $seoKeywords; ?>">
    <meta name="author" content="OpenShelf">
    
    <!-- Robots meta tag for search engine indexing -->
    <?php if ($isNoIndex): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
    <meta name="robots" content="index, follow">
    <?php endif; ?>

    <!-- Canonical Link -->
    <link rel="canonical" href="<?php echo $seoCanonical; ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo $seoOgType; ?>">
    <meta property="og:url" content="<?php echo $seoCanonical; ?>">
    <meta property="og:title" content="<?php echo $seoTitle; ?>">
    <meta property="og:description" content="<?php echo $seoDesc; ?>">
    <meta property="og:image" content="<?php echo $seoImage; ?>">
    <meta property="og:site_name" content="OpenShelf">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $seoCanonical; ?>">
    <meta property="twitter:title" content="<?php echo $seoTitle; ?>">
    <meta property="twitter:description" content="<?php echo $seoDesc; ?>">
    <meta property="twitter:image" content="<?php echo $seoImage; ?>">

    <!-- Schema.org Structured Data -->
    <?php if (isset($book) && is_array($book) && !empty($book['title'])): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Book",
      "name": "<?php echo addslashes($book['title']); ?>",
      "author": {
        "@type": "Person",
        "name": "<?php echo addslashes($book['author']); ?>"
      },
      "image": "<?php echo $seoImage; ?>",
      "description": "<?php echo addslashes(strip_tags($seoDesc)); ?>",
      "genre": "<?php echo addslashes($book['category'] ?? ''); ?>",
      "offers": {
        "@type": "Offer",
        "price": "0.00",
        "priceCurrency": "BDT",
        "availability": "https://schema.org/InStock",
        "seller": {
          "@type": "Person",
          "name": "<?php echo addslashes($book['owner_name'] ?? 'OpenShelf Member'); ?>"
        }
      }
      <?php if (isset($seoRatingCount) && $seoRatingCount > 0): ?>,
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "<?php echo $seoAvgRating; ?>",
        "reviewCount": "<?php echo $seoRatingCount; ?>"
      }
      <?php endif; ?>
    }
    </script>
    <?php else: ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "OpenShelf",
      "url": "<?php echo $baseUrl; ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?php echo $baseUrl; ?>/books/?search={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "OpenShelf",
      "url": "<?php echo $baseUrl; ?>",
      "logo": "<?php echo $baseUrl; ?>/assets/images/pwa/icon-192x192.png",
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+8801987971270",
        "contactType": "customer service",
        "email": "support@duopenshelf.top"
      }
    }
    </script>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/images/logo-icon.svg">
    <link rel="apple-touch-icon" href="/assets/images/pwa/icon-192x192.png">
    
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-title" content="OpenShelf">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" id="themeColorMeta" content="#ffffff">
    <meta name="msapplication-TileColor" content="#2C3E50">
    <meta name="msapplication-TileImage" content="/assets/images/pwa/icon-144x144.png">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css?v=3.0.2">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-KJ8R2FCT18"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-KJ8R2FCT18');
    </script>

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MGWJT89N');</script>
    <!-- End Google Tag Manager -->
    
    <!-- Theme Init -->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    
    <style>
        /* ========================================
           UNIVERSAL HEADER STYLES
           Works on ALL pages
        ======================================== */
        
        :root {
            --header-bg: #ffffff;
            --header-blur: none;
            --header-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            --header-border: #E2E8F0;
            --nav-link-color: #5A6C7D;
            --nav-link-hover: #2C3E50;
            --nav-link-active: #2C3E50;
            --text-primary: #0F172A;
            --text-secondary: #5A6C7D;
            --text-tertiary: #94a3b8;
            --border: #E2E8F0;
            --surface-hover: #F1F5F9;
            --primary: #2C3E50;
            --primary-soft: rgba(44, 62, 80, 0.08);
        }

        :root[data-theme="dark"] {
            --header-bg: #0F172A;
            --header-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            --header-border: #1e293b;
            --nav-link-color: #cbd5e1;
            --nav-link-hover: #4C9F8A;
            --nav-link-active: #4C9F8A;
            --text-primary: #F8F9FA;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --border: #334155;
            --surface-hover: #1e293b;
            --primary: #4C9F8A;
            --primary-soft: rgba(76, 159, 138, 0.1);
        }

        [data-theme="dark"] body {
            background: #0f172a;
            color: #cbd5e1;
        }

        [data-theme="dark"] .mobile-menu,
        [data-theme="dark"] .mobile-header,
        [data-theme="dark"] .mobile-overlay {
            background: #0f172a;
        }
        
        [data-theme="dark"] .mobile-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
        }

        [data-theme="dark"] .user-dropdown {
            background: #1e293b;
        }
        
        [data-theme="dark"] .dropdown-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-bottom-color: #334155;
        }

        /* Reset any conflicting styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, 'Inter', 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            padding-top: 72px;
        }

        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1100;
            background: var(--header-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--header-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            height: 72px;
            display: flex;
            align-items: center;
        }

        .site-header.nav-up {
            transform: translateY(-100%);
        }

        .header-container {
            width: 100%;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Logo Wrapper Styling */
        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2C3E50 0%, #4C9F8A 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.2);
            transition: all 0.3s ease;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .logo-name {
            font-size: 1.35rem;
            font-weight: 850;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #2C3E50 0%, #4C9F8A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-tagline {
            font-size: 0.6rem;
            font-weight: 600;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .logo:hover .logo-wrapper {
            transform: scale(1.02);
        }

        .logo:hover .logo-icon {
            transform: rotate(-5deg) scale(1.1);
            box-shadow: 0 6px 16px rgba(76, 159, 138, 0.3);
        }

        /* Desktop Navigation */
        .nav-desktop {
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link {
            padding: 0.5rem 0.85rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--nav-link-color);
            text-decoration: none;
            border-radius: 2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
        }

        .nav-link i {
            margin-right: 0.4rem;
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .nav-link:hover {
            color: var(--nav-link-hover);
            background: var(--primary-soft);
            transform: translateY(-1px);
        }

        .nav-link.active {
            color: var(--nav-link-active);
            background: var(--primary-soft);
            box-shadow: inset 0 0 0 1px var(--header-border);
        }

        /* Notification Bell */
        .notification-wrapper {
            position: relative;
        }

        .notification-btn {
            background: none;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.2s ease;
            color: var(--nav-link-color);
            position: relative;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .notification-btn:hover {
            background: var(--primary-soft);
            color: var(--nav-link-hover);
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 18px;
            height: 18px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            font-size: 0.65rem;
            font-weight: 600;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid white;
            transform: translate(30%, -30%);
        }

        /* User Menu */
        .user-menu {
            position: relative;
        }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem 0.25rem 0.25rem;
            background: none;
            border: none;
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--primary-soft);
        }

        .user-btn:hover {
            background: rgba(44, 62, 80, 0.12);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .user-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-primary);
            display: none;
        }

        .user-dropdown {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            min-width: 240px;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--border);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            z-index: 1000;
        }

        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-header {
            padding: 1rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid var(--border);
        }

        .dropdown-user-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .dropdown-user-email {
            font-size: 0.75rem;
            color: var(--text-tertiary);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .dropdown-item i {
            width: 20px;
            color: var(--primary);
            font-size: 1rem;
        }

        .dropdown-item:hover {
            background: var(--surface-hover);
            color: var(--primary);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--border);
            margin: 0.5rem 0;
        }

        /* Auth Buttons */
        .auth-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .btn-login {
            padding: 0.5rem 1.25rem;
            background: transparent;
            border: 1.5px solid var(--border);
            border-radius: 2rem;
            color: var(--text-secondary);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-register {
            padding: 0.5rem 1.25rem;
            background: linear-gradient(135deg, #2C3E50, #4C9F8A);
            border: none;
            border-radius: 2rem;
            color: white;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.4);
        }

        /* Mobile Menu Toggle - THE THREE LINES */
        .menu-toggle {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 28px;
            height: 22px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
        }

        .menu-toggle span {
            width: 100%;
            height: 2px;
            background: var(--text-primary);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .menu-toggle.active span:nth-child(1) {
            transform: translateY(10px) rotate(45deg);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: translateY(-10px) rotate(-45deg);
        }

        /* Mobile Menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            left: -100%;
            width: 85%;
            max-width: 320px;
            height: 100vh;
            background: var(--header-bg);
            box-shadow: 20px 0 40px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease;
            z-index: 1200; /* Higher than header */
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .mobile-menu.active {
            left: 0;
        }

        .mobile-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #2C3E50, #4C9F8A);
            color: white;
        }

        .mobile-user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mobile-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .mobile-user-name {
            font-weight: 600;
            font-size: 1rem;
        }

        .mobile-user-email {
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .mobile-nav {
            flex: 1;
            padding: 1rem 0;
        }

        .mobile-nav-item {
            list-style: none;
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .mobile-nav-link i {
            width: 24px;
            color: var(--primary);
        }

        .mobile-nav-link:hover {
            background: var(--surface-hover);
            color: var(--primary);
        }

        .mobile-nav-link.active {
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 500;
            border-left: 3px solid var(--primary);
        }

        .mobile-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            padding: 0.15rem 0.45rem;
            border-radius: 1rem;
        }

        .mobile-divider {
            height: 1px;
            background: var(--border);
            margin: 0.75rem 1.5rem;
        }

        .mobile-nav-section-label {
            list-style: none;
            padding: 0.5rem 1.5rem 0.25rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-tertiary);
        }

        .mobile-support-link {
            color: #f59e0b !important;
            font-weight: 600 !important;
        }

        .mobile-support-link i {
            color: #f59e0b !important;
        }

        .mobile-logout-link {
            color: #ef4444 !important;
        }

        .mobile-logout-link i {
            color: #ef4444 !important;
        }

        /* Overlay */
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .mobile-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Desktop Styles */
        @media (min-width: 768px) {
            .nav-desktop {
                display: flex;
            }
            
            .user-name {
                display: inline;
            }
            
            .header-container {
                padding: 0 1.5rem;
            }

            .site-header {
                height: 70px;
            }
        }

        @media (max-width: 768px) {
            .auth-buttons {
                display: none;
            }
            
            .notification-wrapper {
                display: none;
            }
            
            .nav-desktop {
                display: none;
            }
        }

        /* Hamburger menu always visible */
        .menu-toggle {
            display: flex !important;
            margin-left: 0.5rem;
        }

        /* Animation */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user-dropdown.show {
            animation: slideDown 0.2s ease;
        }
        /* Header Search Form */
        .header-search-form {
            display: flex;
            align-items: center;
            background: transparent;
            border-radius: 2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .header-search-form.active {
            background: var(--gray-100, #F1F5F9);
            box-shadow: inset 0 0 0 1px var(--border);
        }
        
        [data-theme="dark"] .header-search-form.active {
            background: var(--surface-hover);
        }

        .header-search-input {
            width: 0;
            opacity: 0;
            border: none;
            background: transparent;
            padding: 0;
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .header-search-form.active .header-search-input {
            width: 200px;
            opacity: 1;
            padding: 0.5rem 0.5rem 0.5rem 1rem;
        }

        .logo-full-img {
            height: 45px;
            display: block;
            transition: opacity 0.2s ease;
        }
        
        .logo-icon-img {
            height: 40px;
            display: none;
            border-radius: 10px;
        }

        .header-container.search-active .logo-full-img {
            display: none;
        }
        
        .header-container.search-active .logo-icon-img {
            display: block;
        }

        /* Hide other elements when search is active on mobile */
        @media (max-width: 768px) {
            .header-search-form.active .header-search-input {
                width: calc(100vw - 140px);
            }
            .header-container.search-active .nav-desktop,
            .header-container.search-active .auth-buttons,
            .header-container.search-active .user-menu,
            .header-container.search-active .notification-wrapper {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MGWJT89N"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<header class="site-header">
    <div class="header-container">
        <!-- Logo -->
        <a href="/" class="logo">
            <img src="/assets/images/logo-full.svg" alt="OpenShelf" class="logo-full-img">
            <img src="/assets/images/logo-icon.svg" alt="OpenShelf" class="logo-icon-img">
        </a>

        <!-- Desktop Navigation -->
        <nav class="nav-desktop">
            <a href="/" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="/books/" class="nav-link <?php echo strpos($currentPath, '/books/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Books
            </a>
            <a href="/feed/" class="nav-link <?php echo strpos($currentPath, '/feed/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-rss"></i> Feed
            </a>
            <a href="/announcements/" class="nav-link <?php echo strpos($currentPath, '/announcements/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Announcements
            </a>
            <a href="/support_us/" class="nav-link <?php echo strpos($currentPath, '/support_us/') !== false ? 'active' : ''; ?>" style="color: #f59e0b; font-weight: 600;">
                <i class="fas fa-heart"></i> Support Us
            </a>
            <?php if ($isLoggedIn): ?>
                <a href="/notifications/" class="nav-link <?php echo strpos($currentPath, '/notifications/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge" style="position: relative; top: -2px; right: -4px; display: inline-flex; width: 18px; height: 18px; font-size: 0.6rem;"><?php echo $notificationCount > 9 ? '9+' : $notificationCount; ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </nav>

        <!-- Right Section -->
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <!-- Header Search -->
            <form action="/books/" method="GET" class="header-search-form" id="headerSearchForm">
                <input type="text" name="search" class="header-search-input" placeholder="Search books..." autocomplete="off">
                <button type="submit" class="notification-btn" id="headerSearchBtn" title="Search">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <!-- Notification Bell (Desktop) -->
            <?php if ($isLoggedIn): ?>
            <div class="notification-wrapper">
                <a href="/notifications/" class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge"><?php echo $notificationCount > 9 ? '9+' : $notificationCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- Auth Buttons (Desktop) -->
            <?php if (!$isLoggedIn): ?>
            <div class="auth-buttons">
                <a href="/login/" class="btn-login">Login</a>
                <a href="/register/" class="btn-register">Register</a>
            </div>
            <?php endif; ?>

            <!-- Mobile Menu Toggle (THREE LINES) - Visible on mobile only -->
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-header">
        <?php if ($isLoggedIn): ?>
        <div class="mobile-user-info">
            <img src="<?php echo $avatarPath; ?>" 
                 alt="<?php echo htmlspecialchars($userName); ?>" 
                 class="mobile-avatar" 
                 onerror="this.src='/assets/images/avatars/default.jpg'">
            <div>
                <div class="mobile-user-name"><?php echo htmlspecialchars($userName); ?></div>
                <div class="mobile-user-email"><?php echo htmlspecialchars($userEmail); ?></div>
            </div>
        </div>
        <?php else: ?>
        <div style="text-align: center;">
            <div class="mobile-user-name">Welcome to OpenShelf</div>
            <div style="margin-top: 1rem; display: flex; gap: 0.75rem; justify-content: center;">
                <a href="/login/" style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 2rem; color: white; text-decoration: none;">Login</a>
                <a href="/register/" style="background: white; padding: 0.5rem 1rem; border-radius: 2rem; color: #1a252f; text-decoration: none;">Register</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <nav class="mobile-nav">
        <ul style="list-style: none;">
            <!-- General Section -->
            <li class="mobile-nav-section-label">General</li>
            <li class="mobile-nav-item">
                <a href="/feed/" class="mobile-nav-link <?php echo strpos($currentPath, '/feed/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-rss"></i> Feed
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/announcements/" class="mobile-nav-link <?php echo strpos($currentPath, '/announcements/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>
            <?php if ($isLoggedIn): ?>
            <!-- Management Section -->
            <div class="mobile-divider"></div>
            <li class="mobile-nav-section-label">Management</li>
            <li class="mobile-nav-item">
                <a href="/my-borrowed/" class="mobile-nav-link <?php echo strpos($currentPath, '/my-borrowed/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-book-reader"></i> My Borrowed
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/notifications/" class="mobile-nav-link <?php echo strpos($currentPath, '/notifications/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($notificationCount > 0): ?>
                        <span class="mobile-badge"><?php echo $notificationCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/settings/" class="mobile-nav-link <?php echo strpos($currentPath, '/settings/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <?php endif; ?>
            <!-- Support Section -->
            <div class="mobile-divider"></div>
            <li class="mobile-nav-section-label">Support</li>
            <li class="mobile-nav-item">
                <a href="/faq.php" class="mobile-nav-link <?php echo $currentPage === 'faq.php' ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle"></i> FAQ
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/support_us/" class="mobile-nav-link mobile-support-link <?php echo strpos($currentPath, '/support_us/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-heart"></i> Support Us
                </a>
            </li>
            <?php if ($isLoggedIn): ?>
            <div class="mobile-divider"></div>
            <li class="mobile-nav-item">
                <a href="/logout.php" class="mobile-nav-link mobile-logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
            <?php endif; ?>
            <div class="mobile-divider"></div>
            <li class="mobile-nav-item" id="pwaInstallItem" style="display: none;">
                <a href="#" class="mobile-nav-link" id="pwaInstallBtn" style="color: var(--accent-brand); font-weight: 700;">
                    <i class="fas fa-download"></i> Install App
                </a>
            </li>
        </ul>
    </nav>
</div>

<!-- Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<script>
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileOverlay = document.getElementById('mobileOverlay');

    function closeMobileMenu() {
        if (menuToggle) menuToggle.classList.remove('active');
        if (mobileMenu) mobileMenu.classList.remove('active');
        if (mobileOverlay) mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (menuToggle && mobileMenu && mobileOverlay) {
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
            document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
        });

        mobileOverlay.addEventListener('click', closeMobileMenu);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                closeMobileMenu();
            }
        });
    }


    // Smart Scroll-to-Reveal Header
    let lastScrollTop = 0;
    const header = document.querySelector('.site-header');
    const scrollDelta = 5;
    const headerHeight = header.offsetHeight;

    window.addEventListener('scroll', () => {
        let st = window.pageYOffset || document.documentElement.scrollTop;
        
        // Scroll effect (shrink)
        if (st > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }

        // Hide/Show on scroll
        if (Math.abs(lastScrollTop - st) <= scrollDelta) return;

        if (st > lastScrollTop && st > headerHeight) {
            // Scroll Down - Hide Header
            header.classList.add('nav-up');
            document.body.classList.add('header-hidden');
        } else {
            // Scroll Up - Show Header
            if (st + window.innerHeight < document.documentElement.scrollHeight) {
                header.classList.remove('nav-up');
                document.body.classList.remove('header-hidden');
            }
        }
        
        lastScrollTop = st;
    });

    // Header Search Toggle
    const headerSearchBtn = document.getElementById('headerSearchBtn');
    const headerSearchForm = document.getElementById('headerSearchForm');
    const headerContainer = document.querySelector('.header-container');

    if (headerSearchBtn && headerSearchForm) {
        const headerSearchInput = headerSearchForm.querySelector('.header-search-input');
        
        // Auto-expand if we are on books page
        if (window.location.pathname === '/books/' || window.location.pathname === '/books/index.php') {
            // Optional: headerSearchForm.classList.add('active');
            // Optional: headerContainer.classList.add('search-active');
        }
        
        headerSearchBtn.addEventListener('click', function(e) {
            if (!headerSearchForm.classList.contains('active')) {
                e.preventDefault();
                // If not on books page, redirect to books page
                if (window.location.pathname !== '/books/' && window.location.pathname !== '/books/index.php') {
                    window.location.href = '/books/';
                    return;
                }
                
                headerSearchForm.classList.add('active');
                headerContainer.classList.add('search-active');
                headerSearchInput.focus();
            } else if (headerSearchInput.value.trim() === '') {
                e.preventDefault();
                headerSearchForm.classList.remove('active');
                headerContainer.classList.remove('search-active');
            }
        });

        document.addEventListener('click', function(e) {
            if (headerSearchForm.classList.contains('active') && !headerSearchForm.contains(e.target)) {
                headerSearchForm.classList.remove('active');
                headerContainer.classList.remove('search-active');
            }
        });

        headerSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                headerSearchForm.classList.remove('active');
                headerContainer.classList.remove('search-active');
            }
        });
    }
</script>

<main class="main-content">