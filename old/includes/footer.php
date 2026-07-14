<?php
/**
 * OpenShelf Modern Footer
 * Responsive footer with all links and social media
 */
?>
    </main>

    <?php include __DIR__ . '/Navbar.php'; ?>

    <!-- Footer -->
    <?php if (!isset($hideFooter) || !$hideFooter): ?>
    <footer class="footer">
        <div class="footer-container">
            <!-- Footer Top -->
            <div class="footer-top">
                <!-- Brand Column -->
                <div class="footer-section">
                    <div class="footer-logo">
                        <a href="/">
                            <img src="/assets/images/logo-wordmark.svg" alt="OpenShelf" height="28" style="filter: brightness(0) invert(1) sepia(1) saturate(5) hue-rotate(130deg);">
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-section" data-accordion="quick-links">
                    <h3 class="footer-title" role="button" aria-expanded="false" tabindex="0">
                        দ্রুত লিঙ্ক
                        <span class="footer-chevron"><i class="fas fa-chevron-down"></i></span>
                    </h3>
                    <div class="footer-accordion-content">
                        <ul class="footer-links">
                            <li><a href="/"><i class="fas fa-chevron-right"></i> হোম</a></li>
                            <li><a href="/books/"><i class="fas fa-chevron-right"></i> বই ব্রাউজ করুন</a></li>
                            <li><a href="/feed/"><i class="fas fa-chevron-right"></i> কার্যকলাপ ফিড</a></li>
                            <li><a href="/about.php"><i class="fas fa-chevron-right"></i> আমাদের সম্পর্কে</a></li>
                            <li><a href="/contact.php"><i class="fas fa-chevron-right"></i> যোগাযোগ</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Support -->
                <div class="footer-section" data-accordion="support">
                    <h3 class="footer-title" role="button" aria-expanded="false" tabindex="0">
                        সহায়তা
                        <span class="footer-chevron"><i class="fas fa-chevron-down"></i></span>
                    </h3>
                    <div class="footer-accordion-content">
                        <ul class="footer-links">
                            <li><a href="/faq.php"><i class="fas fa-chevron-right"></i> প্রায়শই জিজ্ঞাসিত প্রশ্ন</a></li>
                            <li><a href="/guidelines.php"><i class="fas fa-chevron-right"></i> সম্প্রদায়ের নির্দেশিকা</a></li>
                            <li><a href="/terms.php"><i class="fas fa-chevron-right"></i> সেবার শর্তাবলী</a></li>
                            <li><a href="/privacy.php"><i class="fas fa-chevron-right"></i> গোপনীয়তা নীতি</a></li>
                            <li><a href="/report.php"><i class="fas fa-chevron-right"></i> সমস্যা রিপোর্ট করুন</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="footer-section" data-accordion="contact">
                    <h3 class="footer-title" role="button" aria-expanded="false" tabindex="0">
                        আমাদের সাথে যোগাযোগ করুন
                        <span class="footer-chevron"><i class="fas fa-chevron-down"></i></span>
                    </h3>
                    <div class="footer-accordion-content">
                        <ul class="footer-contact">
                            <li>
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:support@duopenshelf.top">support@duopenshelf.top</a>
                            </li>
                            <li>
                                <i class="fas fa-phone"></i>
                                <a href="tel:+8801987971270">+880 1987 971270</a>
                            </li>
                            <li>
                                <i class="fab fa-whatsapp"></i>
                                <a href="https://wa.me/8801987971270" target="_blank">WhatsApp সহায়তা</a>
                            </li>
                            <li>
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Dhaka University, Bangladesh</span>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Standalone Social icons section (completely outside and below the Contact Us accordion box) -->
                    <div class="footer-social-box">
                        <div class="footer-social">
                            <a href="#" class="social-link" aria-label="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. সর্বাধিকার সংরক্ষিত।</p>
                <p class="footer-heart">বই প্রেমীদের জন্য <i class="fas fa-heart" style="color: #ef4444;"></i> দিয়ে তৈরি</p>
            </div>
        </div>
    </footer>

    <style>
        /* ========================================
           MODERN FOOTER STYLES
        ======================================== */
        
        .footer {
            background: #0B0F19;
            color: #cbd5e1;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 1.5rem;
            padding: 2rem 0 1rem;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        [data-theme="dark"] .footer {
            background: #0B0F19;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .footer-top {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .footer-section {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-section[data-accordion] {
            padding-right: 1.5rem;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }

        .footer-section[data-accordion="contact"] {
            border-right: none;
            padding-right: 0;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }

        .footer-logo-img {
            width: 36px;
            height: 36px;
            border-radius: 10px;
        }

        .footer-logo span {
            background: linear-gradient(135deg, #ffffff, #4C9F8A);
            --webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .footer-tagline {
            color: #cbd5e1;
            font-size: 0.875rem;
            line-height: 1.6;
            margin: 0;
        }

        .footer-title {
            color: #f8fafc !important;
            font-size: 1.05rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            letter-spacing: 0.5px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s ease;
        }

        .footer-title::after {
            content: '';
            width: 24px;
            height: 3px;
            background: #4C9F8A;
            border-radius: 10px;
            position: absolute;
            bottom: -4px;
            left: 0;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.4rem;
        }

        .footer-links a {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.8125rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .footer-links a i {
            font-size: 0.7rem;
            transition: transform 0.2s ease;
        }

        .footer-links a:hover {
            color: #4C9F8A;
            transform: translateX(4px);
        }

        .footer-links a:hover i {
            transform: translateX(4px);
        }

        .footer-contact {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-contact li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.8125rem;
            color: #cbd5e1;
            font-weight: 500;
        }

        .footer-contact li i {
            width: 20px;
            color: #4C9F8A;
            font-size: 1rem;
        }

        .footer-contact a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-contact a:hover {
            color: #4C9F8A;
        }

        .footer-social-box {
            margin-top: 1rem;
        }

        .footer-social {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .social-link:hover {
            background: linear-gradient(135deg, #4C9F8A, #2C3E50);
            border-color: transparent;
            color: white;
            transform: translateY(-5px) rotate(8deg);
            box-shadow: 0 10px 20px rgba(76, 159, 138, 0.3);
        }

        .footer-bottom {
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
            letter-spacing: 0.5px;
        }

        .footer-bottom p {
            margin: 0.25rem 0;
        }

        .footer-heart {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }

        /* Accordion CSS Variables & Desktop Defaults */
        .footer-accordion-content {
            display: block;
        }

        .footer-chevron {
            display: none;
        }

        /* Responsive styling */
        @media (max-width: 768px) {
            .footer {
                padding: 2rem 0 1rem;
            }
            
            .footer-top {
                grid-template-columns: 1fr;
                gap: 1rem;
                text-align: center;
            }

            .footer-section {
                border-right: none !important;
                padding-right: 0 !important;
            }

            .footer-social-box {
                margin-top: 1.25rem;
                padding-top: 1rem;
                border-top: 1px solid rgba(255, 255, 255, 0.05);
                width: 100%;
            }
            
            .footer-logo {
                justify-content: center;
            }

            .footer-social {
                justify-content: center;
            }

            /* Accordion Styling on Mobile */
            .footer-section[data-accordion] {
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                padding-bottom: 0.5rem;
                margin-bottom: 0.35rem;
                display: block; /* Override flex */
            }
            
            .footer-section[data-accordion]:last-of-type {
                border-bottom: none;
                padding-bottom: 0;
                margin-bottom: 0;
            }

            .footer-section[data-accordion] .footer-title {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0 !important;
                padding: 0.75rem 0.5rem;
                cursor: pointer;
                user-select: none;
                font-size: 1.05rem;
                outline: none;
                border-radius: 8px;
                transition: background-color 0.2s ease;
            }

            .footer-section[data-accordion] .footer-title:focus-visible {
                background: rgba(255, 255, 255, 0.05);
            }
            
            .footer-section[data-accordion] .footer-title::after {
                display: none !important; /* Hide desktop bottom line indicator */
            }

            .footer-chevron {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.85rem;
                color: #4C9F8A;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .footer-section[data-accordion].active .footer-chevron {
                transform: rotate(-180deg);
            }

            .footer-accordion-content {
                display: grid;
                grid-template-rows: 0fr;
                opacity: 0;
                visibility: hidden;
                transition: grid-template-rows 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                            opacity 0.25s ease, 
                            visibility 0.3s ease,
                            margin-top 0.3s ease;
                margin-top: 0;
            }

            .footer-section[data-accordion].active .footer-accordion-content {
                grid-template-rows: 1fr;
                opacity: 1;
                visibility: visible;
                margin-top: 1rem;
            }

            .footer-accordion-content > ul {
                overflow: hidden;
                padding: 0 0.75rem 0.5rem;
                text-align: left; /* Keep left alignment for list scannability */
            }
            
            .footer-links a, 
            .footer-contact li {
                justify-content: flex-start !important;
            }
            
            .footer-contact li {
                align-items: center;
            }
        }
    </style>
    <?php endif; ?>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" aria-label="শীর্ষে ফিরে যান">
        <i class="fas fa-arrow-up"></i>
    </button>

    <style>
        .back-to-top {
            position: fixed;
            bottom: 2.5rem;
            right: 2.5rem;
            width: 48px;
            height: 48px;
            background: var(--header-bg);
            color: var(--primary);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 
                        0 8px 16px -6px rgba(0, 0, 0, 0.1),
                        inset 0 0 0 1px var(--header-border);
            z-index: 1000;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 640px) {
            .back-to-top {
                bottom: 1.5rem;
                right: 1.5rem;
                width: 42px;
                height: 42px;
                font-size: 1rem;
            }
        }
    </style>

    <script>
        // Back to Top Button
        const backToTop = document.getElementById('backToTop');
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Footer Accordion logic
        document.addEventListener('DOMContentLoaded', () => {
            const accordionHeaders = document.querySelectorAll('.footer-section[data-accordion] .footer-title');
            
            accordionHeaders.forEach(header => {
                const toggleAccordion = () => {
                    const section = header.closest('.footer-section');
                    const isExpanded = header.getAttribute('aria-expanded') === 'true';
                    
                    // Close other open accordions
                    document.querySelectorAll('.footer-section[data-accordion]').forEach(otherSection => {
                        if (otherSection !== section && otherSection.classList.contains('active')) {
                            otherSection.classList.remove('active');
                            const otherHeader = otherSection.querySelector('.footer-title');
                            if (otherHeader) {
                                otherHeader.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                    
                    // Toggle active state
                    section.classList.toggle('active');
                    header.setAttribute('aria-expanded', !isExpanded);
                };

                // Click handler
                header.addEventListener('click', toggleAccordion);
                
                // Keyboard accessibility
                header.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleAccordion();
                    }
                });
            });
        });
    </script>

    <!-- Optional: Add any additional scripts here -->
    <script src="/assets/js/ui.js"></script>

    <!-- PWA Service Worker & Install Logic -->
    <script>
        let deferredPrompt;
        const installItem = document.getElementById('pwaInstallItem');
        const installBtn = document.getElementById('pwaInstallBtn');

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('[PWA] Service Worker registered');
                    })
                    .catch((error) => {
                        console.warn('[PWA] Service Worker registration failed:', error);
                    });
            });
        }

        // Handle PWA Install Prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later.
            deferredPrompt = e;
            // Show the install button in the menu
            if (installItem) {
                installItem.style.display = 'block';
            }
            
            console.log('[PWA] beforeinstallprompt event fired');
        });

        if (installBtn) {
            installBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (!deferredPrompt) return;
                
                // Show the prompt
                deferredPrompt.prompt();
                
                // Wait for the user to respond to the prompt
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`[PWA] User response to the install prompt: ${outcome}`);
                
                // We've used the prompt, and can't use it again, throw it away
                deferredPrompt = null;
                
                // Hide the install button
                if (installItem) {
                    installItem.style.display = 'none';
                }
            });
        }

        // Check if app is already installed
        window.addEventListener('appinstalled', (evt) => {
            console.log('[PWA] App was installed');
            if (installItem) {
                installItem.style.display = 'none';
            }
        });
    </script>

</body>
</html>