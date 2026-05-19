<?php
/**
 * OpenShelf Help & Support Page
 * Handles FAQs and guides inside /settings/help/
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/settings/help/';
    header('Location: /login/');
    exit;
}

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    :root {
        --help-bg: #f8fafc;
        --help-card-bg: rgba(255, 255, 255, 0.9);
        --help-border: rgba(44, 62, 80, 0.12);
        --help-active-teal: #4C9F8A;
        --help-radius: 24px;
        --help-inner-radius: 12px;
        --help-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        --help-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    :root[data-theme="dark"] {
        --help-bg: #0f172a;
        --help-card-bg: #1e293b;
        --help-border: #334155;
        --help-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .help-page-wrapper {
        min-height: calc(100vh - 140px);
        background: var(--help-bg);
        color: var(--text-primary);
        padding: 2.5rem 1rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        transition: var(--help-transition);
    }

    .help-container {
        width: 100%;
        max-width: 720px;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        animation: helpEntrance 0.5s ease-out;
    }

    @keyframes helpEntrance {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Header Nav (Back to Hub) */
    .help-header-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .back-hub-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-secondary);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.92rem;
        transition: var(--help-transition);
    }

    .back-hub-btn:hover {
        color: var(--primary);
        transform: translateX(-2px);
    }

    .help-card {
        background: var(--help-card-bg);
        border: 1px solid var(--help-border);
        border-radius: var(--help-radius);
        padding: 2.25rem;
        box-shadow: var(--help-shadow);
        backdrop-filter: blur(10px);
    }

    /* FAQ Accordion */
    .faq-accordion-list {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        margin-top: 1.5rem;
    }

    .faq-accordion-item {
        border: 1px solid var(--help-border);
        border-radius: var(--help-inner-radius);
        overflow: hidden;
        background: var(--settings-input-bg, #ffffff);
        transition: var(--help-transition);
    }

    :root[data-theme="dark"] .faq-accordion-item {
        background: #0f172a;
    }

    .faq-header-btn {
        width: 100%;
        padding: 1.1rem 1.4rem;
        background: transparent;
        border: none;
        outline: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        font-size: 0.95rem;
        text-align: left;
        color: var(--text-primary);
        cursor: pointer;
        transition: var(--help-transition);
    }

    .faq-header-btn:hover {
        background: var(--surface-hover);
    }

    .faq-header-btn i {
        color: var(--text-tertiary);
        transition: transform 0.3s ease;
    }

    .faq-body-text {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s cubic-bezier(0, 1, 0, 1);
        padding: 0 1.4rem;
        color: var(--text-secondary);
        font-size: 0.88rem;
        line-height: 1.6;
    }

    .faq-accordion-item.open .faq-body-text {
        max-height: 500px;
        padding: 0 1.4rem 1.1rem;
        transition: max-height 0.3s cubic-bezier(1, 0, 1, 0);
    }

    .faq-accordion-item.open .faq-header-btn i {
        transform: rotate(180deg);
        color: var(--help-active-teal);
    }

    /* Help Cards Grid */
    .help-links-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
        margin-top: 2.25rem;
    }

    @media (min-width: 580px) {
        .help-links-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .help-info-card {
        border: 1px dashed var(--help-border);
        border-radius: var(--help-inner-radius);
        padding: 1.5rem;
        background: var(--settings-input-bg, #ffffff);
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }

    :root[data-theme="dark"] .help-info-card {
        background: #0f172a;
    }

    .help-info-card i {
        font-size: 1.6rem;
        color: var(--help-active-teal);
        margin-top: 3px;
    }

    .help-info-title {
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 0.3rem;
    }

    .help-info-desc {
        font-size: 0.82rem;
        color: var(--text-secondary);
        line-height: 1.4;
    }

    .help-info-link {
        display: inline-block;
        font-size: 0.85rem;
        color: var(--help-active-teal);
        text-decoration: none;
        margin-top: 0.6rem;
        font-weight: 600;
    }

    .help-info-link:hover {
        text-decoration: underline;
    }
</style>

<div class="help-page-wrapper">
    <div class="help-container">
        <!-- Back Link -->
        <div class="help-header-nav">
            <a href="/settings/" class="back-hub-btn">
                <i class="fas fa-arrow-left"></i> Back to Settings
            </a>
        </div>

        <div class="help-card">
            <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--help-border); padding-bottom: 1rem;">
                <h2 style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">Help & Support</h2>
                <p style="font-size: 0.88rem; color: var(--text-secondary);">Browse FAQs, read the community policies, or get in touch with moderation team.</p>
            </div>

            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">Frequently Asked Questions</h3>
            
            <div class="faq-accordion-list">
                <!-- FAQ 1 -->
                <div class="faq-accordion-item">
                    <button class="faq-header-btn" onclick="toggleAccordion(this)">
                        How does sharing books work on OpenShelf?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-body-text">
                        OpenShelf is a community-driven peer-to-peer library. You can add books you own to your profile. Other users living in the same residential halls can browse these books and submit a request to borrow them. You can approve or decline the request and coordinate the physical swap.
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="faq-accordion-item">
                    <button class="faq-header-btn" onclick="toggleAccordion(this)">
                        Who is allowed to borrow my books?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-body-text">
                        Only verified, registered users of the OpenShelf platform can request your books. During the borrow request phase, you can see their name, department, session, and residential hall details to make a safe and informed decision.
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="faq-accordion-item">
                    <button class="faq-header-btn" onclick="toggleAccordion(this)">
                        What should I do if a book is returned damaged or late?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-body-text">
                        We encourage open communication between borrowers and owners. If a user returns a book late or damaged, you can rate the return session poorly and leave feedback. Repeat offenders may have their accounts suspended by platform administrators.
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="faq-accordion-item">
                    <button class="faq-header-btn" onclick="toggleAccordion(this)">
                        How do I request admin support?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-body-text">
                        If you run into technical issues or need to report a user, you can use the report form or send an email directly to our support desk.
                    </div>
                </div>
            </div>

            <!-- Support Links Section -->
            <div class="help-links-grid">
                <div class="help-info-card">
                    <i class="fas fa-circle-question"></i>
                    <div>
                        <h4 class="help-info-title">Platform FAQs & Rules</h4>
                        <p class="help-info-desc">Read comprehensive platform regulations, borrowing limits, and security guidelines.</p>
                        <a href="/faq.php" class="help-info-link">Visit FAQ &rarr;</a>
                    </div>
                </div>

                <div class="help-info-card">
                    <i class="fas fa-envelope-open-text"></i>
                    <div>
                        <h4 class="help-info-title">Email support desk</h4>
                        <p class="help-info-desc">Need manual intervention? Send us a ticket and our mods will help you.</p>
                        <a href="/contact.php" class="help-info-link">Contact Support &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleAccordion(btn) {
        const item = btn.parentElement;
        const isOpen = item.classList.contains('open');

        // Close all items first
        document.querySelectorAll('.faq-accordion-item').forEach(el => {
            el.classList.remove('open');
        });

        if (!isOpen) {
            item.classList.add('open');
        }
    }
</script>

<?php
require_once dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
