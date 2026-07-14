<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- SEO Meta Tags -->
    <title>{{ isset($seoTitle) ? $seoTitle : 'OpenShelf - Share Books, Share Knowledge' }}</title>
    <meta name="description" content="{{ isset($seoDesc) ? $seoDesc : 'OpenShelf is a student-led, peer-to-peer book sharing platform. Share and borrow textbooks, novels, and guides within your campus community for free.' }}">
    <meta name="keywords" content="{{ isset($seoKeywords) ? $seoKeywords : 'book sharing, university library, campus books, borrow books, free books, peer-to-peer, OpenShelf' }}">
    <meta name="theme-color" content="#4C9F8A">
    <meta name="msapplication-TileColor" content="#4C9F8A">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{{ isset($seoOgType) ? $seoOgType : 'website' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ isset($seoTitle) ? $seoTitle : 'OpenShelf' }}">
    <meta property="og:description" content="{{ isset($seoDesc) ? $seoDesc : 'Share and borrow books within your campus community' }}">
    <meta property="og:image" content="{{ isset($seoImage) ? $seoImage : asset('assets/images/pwa/icon-192x192.png') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="{{ isset($seoTitle) ? $seoTitle : 'OpenShelf' }}">
    <meta property="twitter:description" content="{{ isset($seoDesc) ? $seoDesc : 'Share and borrow books within your campus community' }}">
    <meta property="twitter:image" content="{{ isset($seoImage) ? $seoImage : asset('assets/images/pwa/icon-192x192.png') }}">

    <!-- Structured Data (Schema.org) -->
    @if(isset($book) && is_array($book))
    <script type="application/ld+json">
    {
        "@context": "https://schema.org/",
        "@type": "Book",
        "name": "{{ $book['title'] ?? 'Unknown' }}",
        "author": {
            "@type": "Person",
            "name": "{{ $book['author'] ?? 'Unknown Author' }}"
        },
        "publisher": {
            "@type": "Organization",
            "name": "OpenShelf"
        },
        "image": "{{ isset($seoImage) ? $seoImage : asset('assets/images/default-book.png') }}",
        "description": "{{ isset($book['description']) ? substr(strip_tags($book['description']), 0, 155) : 'Borrow this book on OpenShelf' }}",
        @if(isset($book['rating']) && $book['rating'] > 0)
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "{{ number_format($book['rating'] ?? 0, 1) }}",
            "ratingCount": "{{ $book['rating_count'] ?? 0 }}"
        }
        @endif
    }
    </script>
    @else
    <script type="application/ld+json">
    {
        "@context": "https://schema.org/",
        "@type": "Organization",
        "name": "OpenShelf",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('assets/images/logo.png') }}",
        "description": "A student-led, peer-to-peer book sharing platform"
    }
    </script>
    @endif

    <!-- Favicon & PWA Icons -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/pwa/icon-192x192.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <!-- Skip to main content link for accessibility -->
    <a href="#main" class="skip-to-main">Skip to main content</a>

    <!-- Header with Navigation -->
    @include('partials.header')

    <!-- Main Content Area -->
    <main id="main" class="main-wrapper">
        @yield('content')
    </main>

    <!-- Bottom Navigation Bar -->
    @include('partials.navbar')

    <!-- Footer -->
    @include('partials.footer')

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    @stack('scripts')

    <!-- Theme Manager -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</body>
</html>
