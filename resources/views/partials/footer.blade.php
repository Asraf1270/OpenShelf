<footer class="footer">
    <div class="footer-container">
        <!-- Footer Top -->
        <div class="footer-top">
            <!-- Brand Column -->
            <div class="footer-section">
                <div class="footer-logo">
                    <a href="/">
                        <img src="{{ asset('assets/images/logo-wordmark.svg') }}" alt="OpenShelf" height="28" style="filter: brightness(0) invert(1) sepia(1) saturate(5) hue-rotate(130deg);">
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
                        <li><a href="/books"><i class="fas fa-chevron-right"></i> বই ব্রাউজ করুন</a></li>
                        <li><a href="/feed"><i class="fas fa-chevron-right"></i> কার্যকলাপ ফিড</a></li>
                        <li><a href="/about"><i class="fas fa-chevron-right"></i> আমাদের সম্পর্কে</a></li>
                        <li><a href="/contact"><i class="fas fa-chevron-right"></i> যোগাযোগ</a></li>
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
                        <li><a href="/faq"><i class="fas fa-chevron-right"></i> প্রায়শই জিজ্ঞাসিত প্রশ্ন</a></li>
                        <li><a href="/guidelines"><i class="fas fa-chevron-right"></i> সম্প্রদায়ের নির্দেশিকা</a></li>
                        <li><a href="/terms"><i class="fas fa-chevron-right"></i> সেবার শর্তাবলী</a></li>
                        <li><a href="/privacy"><i class="fas fa-chevron-right"></i> গোপনীয়তা নীতি</a></li>
                        <li><a href="/report"><i class="fas fa-chevron-right"></i> সমস্যা রিপোর্ট করুন</a></li>
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
                
                <!-- Social icons section -->
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
            <p>&copy; {{ date('Y') }} OpenShelf. সর্বাধিকার সংরক্ষিত।</p>
            <p class="footer-heart">বই প্রেমীদের জন্য <i class="fas fa-heart" style="color: #ef4444;"></i> দিয়ে তৈরি</p>
        </div>
    </div>
</footer>

<style>
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
        cursor: pointer;
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

    /* Accordion styles */
    .footer-accordion-content {
        display: block;
    }

    .footer-chevron {
        display: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .footer {
            padding: 2rem 0 1rem;
        }
        
        .footer-top {
            grid-template-columns: 1fr;
        }

        .footer-section[data-accordion] {
            padding-right: 0;
            border-right: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 1rem;
        }

        .footer-section[data-accordion]:last-of-type {
            border-bottom: none;
        }

        .footer-title {
            cursor: pointer;
        }

        .footer-chevron {
            display: block;
        }

        .footer-accordion-content {
            display: none;
            margin-top: 0.75rem;
        }

        .footer-section[data-accordion].active .footer-accordion-content {
            display: block;
        }

        .footer-section[data-accordion].active .footer-chevron i {
            transform: rotate(180deg);
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Accordion functionality for mobile
    const accordions = document.querySelectorAll('[data-accordion]');
    
    accordions.forEach(accordion => {
        const title = accordion.querySelector('.footer-title');
        if (!title) return;

        title.addEventListener('click', () => {
            accordion.classList.toggle('active');
        });
    });
});
</script>
