<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- SEO Meta Tags -->
    <title>{{ $seoTitle ?? 'OpenShelf - Share Books, Share Knowledge' }}</title>
    <meta name="description" content="{{ $seoDesc ?? 'OpenShelf is a student-led, peer-to-peer book sharing platform. Share and borrow textbooks, novels, and guides within your campus community for free.' }}">
    <meta name="keywords" content="{{ $seoKeywords ?? 'book sharing, university library, campus books, borrow books, free books, peer-to-peer, OpenShelf' }}">
    <link rel="canonical" href="{{ $seoCanonical ?? url()->current() }}">
    <meta name="theme-color" content="#4C9F8A">
    <meta name="msapplication-TileColor" content="#4C9F8A">

    <!-- Open Graph / Facebook -->
    <meta property="og:site_name" content="OpenShelf">
    <meta property="og:type" content="{{ $seoOgType ?? 'website' }}">
    <meta property="og:url" content="{{ $seoCanonical ?? url()->current() }}">
    <meta property="og:title" content="{{ $seoTitle ?? 'OpenShelf - Share Books, Share Knowledge' }}">
    <meta property="og:description" content="{{ $seoDesc ?? 'Share and borrow books within your campus community for free.' }}">
    <meta property="og:image" content="{{ $seoImage ?? asset('images/pwa/icon-512x512.png') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $seoCanonical ?? url()->current() }}">
    <meta name="twitter:title" content="{{ $seoTitle ?? 'OpenShelf - Share Books, Share Knowledge' }}">
    <meta name="twitter:description" content="{{ $seoDesc ?? 'Share and borrow books within your campus community for free.' }}">
    <meta name="twitter:image" content="{{ $seoImage ?? asset('images/pwa/icon-512x512.png') }}">

    <!-- Structured Data (Schema.org) -->
    @if(isset($book))
    @php
        $bookTitle = is_object($book) ? ($book->title ?? '') : ($book['title'] ?? '');
        $bookAuthor = is_object($book) ? ($book->author ?? '') : ($book['author'] ?? '');
        $bookCategory = is_object($book) ? ($book->category ?? '') : ($book['category'] ?? '');
        $bookDesc = is_object($book) ? ($book->description ?? '') : ($book['description'] ?? '');
        $bookStatus = is_object($book) ? ($book->status ?? 'available') : ($book['status'] ?? 'available');
        $bookRating = is_object($book) ? ($book->rating ?? 0) : ($book['rating'] ?? 0);
        $bookRatingCount = is_object($book) ? ($book->rating_count ?? 0) : ($book['rating_count'] ?? 0);
        $bookImage = $seoImage ?? (is_object($book) ? ($book->detail_cover_url ?? asset('images/default-book-cover.jpg')) : asset('images/default-book-cover.jpg'));

        $schemaBook = [
            '@context' => 'https://schema.org',
            '@type' => 'Book',
            'name' => $bookTitle,
            'author' => [
                '@type' => 'Person',
                'name' => $bookAuthor ?: 'Unknown Author'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'OpenShelf',
                'url' => url('/')
            ],
            'image' => $bookImage,
            'description' => \Illuminate\Support\Str::limit(strip_tags($bookDesc ?: 'Borrow ' . $bookTitle . ' on OpenShelf campus book sharing platform for free.'), 200),
            'offers' => [
                '@type' => 'Offer',
                'price' => '0.00',
                'priceCurrency' => 'BDT',
                'availability' => $bookStatus === 'available' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/UsedCondition',
                'offeredBy' => [
                    '@type' => 'Organization',
                    'name' => 'OpenShelf'
                ]
            ]
        ];

        if (!empty($bookCategory)) {
            $schemaBook['genre'] = $bookCategory;
        }

        if ((float)$bookRating > 0 && (int)$bookRatingCount > 0) {
            $schemaBook['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float)$bookRating, 1),
                'ratingCount' => (int)$bookRatingCount,
                'bestRating' => '5',
                'worstRating' => '1'
            ];
        }
    @endphp
    <script type="application/ld+json">
    {!! json_encode($schemaBook, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @else
    @php
        $schemaWebsite = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'OpenShelf',
            'url' => url('/'),
            'description' => 'A student-led, peer-to-peer book sharing platform',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/books') . '?search={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ];
        $schemaOrg = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'OpenShelf',
            'url' => url('/'),
            'logo' => asset('images/logo-full.svg'),
            'description' => 'A student-led, peer-to-peer book sharing platform'
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($schemaWebsite, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode($schemaOrg, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endif

    <!-- Favicon & PWA Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192x192.png') }}">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ file_exists(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : '1' }}">
    @stack('styles')

    <!-- App JS -->
    <script src="{{ asset('js/app.js') }}" defer></script>

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
    @stack('scripts')

    <!-- Service Worker Registration for PWA -->
    @php $swUrl = asset('sw.js'); @endphp
    <script>
        // Register service worker for PWA support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ $swUrl }}')
                    .then((registration) => {
                        console.log('[PWA] Service Worker registered:', registration);
                    })
                    .catch((error) => {
                        console.warn('[PWA] Service Worker registration failed:', error);
                    });
            });
        }
    </script>

    <!-- Theme Manager -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</body>
</html>
