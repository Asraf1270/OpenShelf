<?php
/**
 * OpenShelf Modern Footer
 * Responsive footer with all links and social media
 */
?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <!-- Footer Top -->
            <div class="footer-top">
                <!-- Brand Column -->
                <div class="footer-section">
                    <div class="footer-logo">
                        <a href="/">
                            <img src="/assets/images/logo-full.svg" alt="OpenShelf" height="40" style="filter: brightness(0) invert(1) sepia(1) saturate(5) hue-rotate(130deg);">
                        </a>
                    </div>
                    <p class="footer-tagline">
                        Share books, share knowledge. Join our community of book lovers and start sharing today!
                    </p>
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

                <!-- Quick Links -->
                <div class="footer-section">
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="/"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="/books/"><i class="fas fa-chevron-right"></i> Browse Books</a></li>
                        <li><a href="/feed/"><i class="fas fa-chevron-right"></i> Activity Feed</a></li>
                        <li><a href="/about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="/contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="footer-section">
                    <h3 class="footer-title">Support</h3>
                    <ul class="footer-links">
                        <li><a href="/faq.php"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                        <li><a href="/guidelines.php"><i class="fas fa-chevron-right"></i> Community Guidelines</a></li>
                        <li><a href="/terms.php"><i class="fas fa-chevron-right"></i> Terms of Service</a></li>
                        <li><a href="/privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li>
                        <li><a href="/report.php"><i class="fas fa-chevron-right"></i> Report Issue</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="footer-section">
                    <h3 class="footer-title">Contact Us</h3>
                    <ul class="footer-contact">
                        <li>
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:support@openshelf.com">support@openshelf.com</a>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <a href="tel:+880123456789">+880 1234 56789</a>
                        </li>
                        <li>
                            <i class="fab fa-whatsapp"></i>
                            <a href="https://wa.me/880123456789" target="_blank">WhatsApp Support</a>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Dhaka University, Bangladesh</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p class="footer-heart">Made with <i class="fas fa-heart" style="color: #ef4444;"></i> for book lovers</p>
            </div>
        </div>
    </footer>

    <style>
        /* ========================================
           MODERN FOOTER STYLES
        ======================================== */
        
        .footer {
            background: #2C3E50;
            color: #cbd5e1;
            border-top: 1px solid #1a252f;
            margin-top: 6rem;
            padding: 5rem 0 2rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .footer-top {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 4rem;
            margin-bottom: 4rem;
        }

        .footer-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
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
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .footer-tagline {
            color: #cbd5e1;
            font-size: 0.875rem;
            line-height: 1.6;
            margin: 0;
        }

        .footer-title {
            color: #ffffff;
            font-size: 1.15rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: 0.5px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-title::after {
            content: '';
            width: 24px;
            height: 3px;
            background: #4C9F8A;
            border-radius: 10px;
            position: absolute;
            bottom: -8px;
            left: 0;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.6rem;
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
            gap: 1rem;
            margin-bottom: 1rem;
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
            padding-top: 2rem;
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

        /* Responsive */
        @media (max-width: 768px) {
            .footer {
                padding: 2rem 0 1rem;
            }
            
            .footer-top {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                text-align: center;
            }
            
            .footer-logo {
                justify-content: center;
            }
            
            .footer-links a {
                justify-content: center;
            }
            
            .footer-contact li {
                justify-content: center;
            }
            
            .footer-social {
                justify-content: center;
            }
        }
    </style>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <style>
        .back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #2C3E50, #4C9F8A);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
            z-index: 1000;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        @media (max-width: 640px) {
            .back-to-top {
                bottom: 1rem;
                right: 1rem;
                width: 40px;
                height: 40px;
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