<?php
/**
 * OpenShelf Contact Page
 * Contact form for inquiries and support
 */

session_start();
include 'includes/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($messageText)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Send email (logic placeholder)
        $message = 'Thank you for contacting us! We will get back to you soon.';
    }
}
?>

<style>
    :root {
        --primary: #6366f1;
        --bg: #f8fafc;
        --surface: #ffffff;
        --text: #0f172a;
        --text-muted: #64748b;
        --border: #e2e8f0;
        --radius: 20px;
        --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
    }

    [data-theme="dark"] {
        --bg: #0f172a;
        --surface: #1e293b;
        --text: #f8fafc;
        --text-muted: #94a3b8;
        --border: #334155;
        --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
    }

    body {
        background-color: var(--bg);
        color: var(--text);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .contact-container {
        max-width: 1100px;
        margin: 4rem auto;
        padding: 0 1.5rem;
    }

    .hero-section {
        text-align: center;
        margin-bottom: 4rem;
    }

    .hero-section h1 {
        font-size: clamp(2.5rem, 6vw, 3.5rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, var(--primary), #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 3rem;
    }

    .info-card {
        background: var(--surface);
        padding: 3rem;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        height: fit-content;
    }

    .info-item {
        display: flex;
        gap: 1.25rem;
        margin-bottom: 2.5rem;
    }

    .info-item:last-child {
        margin-bottom: 0;
    }

    .info-icon {
        width: 48px;
        height: 48px;
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .info-content h3 {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
    }

    .info-content p {
        font-weight: 600;
        font-size: 1.1rem;
        margin: 0;
    }

    .form-card {
        background: var(--surface);
        padding: 3rem;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        background: var(--bg);
        color: var(--text);
        font-family: inherit;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .btn-submit {
        width: 100%;
        padding: 1rem;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-top: 1rem;
    }

    .btn-submit:hover {
        background: #4f46e5;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
    }

    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        font-weight: 500;
    }

    .alert-success { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); }
    .alert-error { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); }

    @media (max-width: 900px) {
        .contact-grid { grid-template-columns: 1fr; }
        .hero-section { margin-bottom: 2rem; }
    }
</style>

<main class="contact-container">
    <section class="hero-section">
        <h1>Get in Touch</h1>
        <p style="color: var(--text-muted); font-size: 1.2rem;">Have questions? We're here to help.</p>
    </section>

    <div class="contact-grid">
        <div class="info-card">
            <div class="info-item">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <div class="info-content">
                    <h3>Email Us</h3>
                    <p>support@openshelf.com</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon"><i class="fab fa-whatsapp"></i></div>
                <div class="info-content">
                    <h3>WhatsApp</h3>
                    <p>+880 1234 56789</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="info-content">
                    <h3>Location</h3>
                    <p>Campus Hub, Dhaka</p>
                </div>
            </div>
        </div>

        <div class="form-card">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Your Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" class="form-control" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn-submit">Send Message</button>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>