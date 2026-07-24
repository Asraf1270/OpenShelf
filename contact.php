<?php
/**
 * OpenShelf Contact Page
 * Contact form for inquiries and support
 */

session_start();
require_once __DIR__ . '/includes/db.php';

$message = '';
$error = '';

// Get user data if logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    $userId = $_SESSION['user_id'] ?? null;
    
    if (empty($name) || empty($email) || empty($subject) || empty($messageText)) {
        $error = 'দয়া করে সব ক্ষেত্র পূরণ করুন।';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'দয়া করে একটি সঠিক ইমেইল ঠিকানা দিন।';
    } else {
        try {
            $db = getDB();
            $msgId = uniqid('msg_');
            
            $stmt = $db->prepare("INSERT INTO contact_messages (id, user_id, name, email, subject, message, status, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, 'unread', NOW())");
            $stmt->execute([$msgId, $userId, $name, $email, $subject, $messageText]);
            
            $message = 'আপনার মেসেজ সফলভাবে পাঠানো হয়েছে! আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।';
        } catch (PDOException $e) {
            error_log("Contact message error: " . $e->getMessage());
            $error = 'মেসেজ পাঠাতে ব্যর্থ হয়েছে। দয়া করে পরে আবার চেষ্টা করুন।';
        }
    }
}

include 'includes/header.php';
?>

<style>
    :root {
        --primary: #2C3E50;
        --secondary: #4C9F8A;
        --accent: #3A7B6B;
        --bg: #F8F9FA;
        --surface: #ffffff;
        --text-main: #0F172A;
        --text-muted: #5A6C7D;
        --border: #E2E8F0;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
        --shadow-md: 0 10px 30px -5px rgba(44, 62, 80, 0.08);
        --radius-lg: 24px;
        --radius-md: 16px;
    }

    [data-theme="dark"] {
        --bg: #0F172A;
        --surface: #1E293B;
        --text-main: #F8F9FA;
        --text-muted: #94A3B8;
        --border: #334155;
        --shadow-md: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
    }

    body {
        background-color: var(--bg);
        color: var(--text-main);
        font-family: 'Hind Siliguri', 'Outfit', 'Inter', system-ui, -apple-system, sans-serif;
        transition: background 0.3s ease;
    }

    .contact-container {
        max-width: 1100px;
        margin: 6rem auto;
        padding: 0 1.5rem;
    }

    .hero-section {
        text-align: center;
        margin-bottom: 5rem;
    }

    .hero-section h1 {
        font-size: clamp(2.5rem, 6vw, 3.5rem);
        font-weight: 850;
        letter-spacing: -0.03em;
        margin-bottom: 1.5rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 4rem;
    }

    .info-card {
        background: var(--surface);
        padding: 3.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
        height: fit-content;
    }

    .info-item {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .info-item:last-child {
        margin-bottom: 0;
    }

    .info-icon {
        width: 54px;
        height: 54px;
        background: rgba(76, 159, 138, 0.1);
        color: var(--secondary);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }

    .info-item:hover .info-icon {
        background: var(--secondary);
        color: white;
        transform: scale(1.1);
    }

    .info-content h3 {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--text-muted);
        margin-bottom: 0.75rem;
        font-weight: 750;
    }

    .info-content p {
        font-weight: 700;
        font-size: 1.15rem;
        margin: 0;
        color: var(--text-main);
    }

    .form-card {
        background: var(--surface);
        padding: 3.5rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
    }

    .form-group {
        margin-bottom: 2rem;
    }

    .form-group label {
        display: block;
        font-weight: 700;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
        color: var(--text-main);
    }

    .form-control {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        background: var(--bg);
        color: var(--text-main);
        font-family: inherit;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 4px rgba(76, 159, 138, 0.1);
        background: var(--surface);
    }

    .btn-submit {
        width: 100%;
        padding: 1.15rem;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 1.05rem;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 1rem;
        letter-spacing: 0.5px;
    }

    .btn-submit:hover {
        background: var(--secondary);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(76, 159, 138, 0.4);
    }

    .alert {
        padding: 1.25rem;
        border-radius: 12px;
        margin-bottom: 2.5rem;
        font-weight: 600;
    }

    .alert-success { 
        background: rgba(16, 185, 129, 0.1); 
        color: #059669; 
        border: 1px solid rgba(16, 185, 129, 0.2); 
    }
    
    .alert-error { 
        background: rgba(239, 68, 68, 0.1); 
        color: #dc2626; 
        border: 1px solid rgba(239, 68, 68, 0.2); 
    }

    @media (max-width: 900px) {
        .contact-grid { grid-template-columns: 1fr; gap: 3rem; }
        .hero-section { margin-bottom: 3rem; }
        .contact-container { margin: 4rem auto; }
        .info-card, .form-card { padding: 2.5rem 1.5rem; }
    }
</style>

<main class="contact-container">
    <section class="hero-section">
        <h1>যোগাযোগ করুন</h1>
        <p style="color: var(--text-muted); font-size: 1.2rem;">আপনার কি কোনো প্রশ্ন আছে? আমরা সাহায্য করতে প্রস্তুত।</p>
    </section>

    <div class="contact-grid">
        <div class="info-card">
            <div class="info-item">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <div class="info-content">
                    <h3>ইমেইলে লিখুন</h3>
                    <p>support@duopenshelf.top</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon"><i class="fab fa-whatsapp"></i></div>
                <div class="info-content">
                    <h3>হোয়াটসঅ্যাপ</h3>
                    <p>+৮৮০ ১৯৮৭ ৯৭১২৭০</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="info-content">
                    <h3>অবস্থান</h3>
                    <p>ক্যাম্পাস হাব, ঢাকা বিশ্ববিদ্যালয়</p>
                </div>
            </div>
        </div>

        <div class="form-card">
            <?php if ($message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>আপনার নাম</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($userName); ?>" required>
                </div>
                <div class="form-group">
                    <label>ইমেইল ঠিকানা</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userEmail); ?>" required>
                </div>
                <div class="form-group">
                    <label>বিষয়</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>মেসেজ</label>
                    <textarea name="message" class="form-control" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn-submit">মেসেজ পাঠান</button>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>