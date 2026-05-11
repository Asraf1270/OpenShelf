<?php
/**
 * OpenShelf - Report Page
 * Allows users to report issues, bugs, or user misconduct.
 */

session_start();
require_once __DIR__ . '/includes/db.php';

// Success/Error messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Get user data if logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'other';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $email = $_POST['email'] ?? $userEmail;
    $name = $_POST['name'] ?? $userName;
    $userId = $_SESSION['user_id'] ?? 'guest';

    if (empty($subject) || empty($message) || empty($email)) {
        $error = "Please fill in all required fields.";
    } else {
        // Prepare report data
        $reportData = [
            'id' => uniqid('rep_'),
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'type' => $type,
            'subject' => $subject,
            'message' => $message,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Save to data/reports/ directory as JSON for now (fallback since no table exists)
        $reportsDir = __DIR__ . '/data/reports/';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }
        
        $filePath = $reportsDir . $reportData['id'] . '.json';
        if (file_put_contents($filePath, json_encode($reportData, JSON_PRETTY_PRINT))) {
            header('Location: report.php?success=Your report has been submitted. Thank you for helping us improve.');
            exit;
        } else {
            $error = "Failed to save the report. Please try again later.";
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
        font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif;
        transition: background 0.3s ease;
    }

    .report-container {
        max-width: 700px;
        margin: 6rem auto;
        padding: 0 1.5rem;
    }

    .report-header {
        text-align: center;
        margin-bottom: 4rem;
    }

    .report-header h1 {
        font-size: clamp(2.5rem, 6vw, 3.5rem);
        font-weight: 850;
        letter-spacing: -0.03em;
        margin-bottom: 1.5rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .report-card {
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

    @media (max-width: 640px) {
        .report-card { padding: 2.5rem 1.5rem; border-radius: 24px; }
        .report-header h1 { font-size: 2.25rem; }
    }
</style>

<main class="report-container">
    <div class="report-header">
        <h1>Report an Issue</h1>
        <p style="color: var(--text-muted);">Help us keep OpenShelf safe and functional for everyone.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="report-card">
        <form action="report.php" method="POST">
            <div class="form-group">
                <label for="name">Your Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($userName); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userEmail); ?>" required>
            </div>

            <div class="form-group">
                <label for="type">Report Type</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="bug">Technical Bug / Error</option>
                    <option value="user">User Misconduct / Harassment</option>
                    <option value="book">Inaccurate Book Information</option>
                    <option value="suggestion">Feature Suggestion</option>
                    <option value="other">Other Issue</option>
                </select>
            </div>

            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" class="form-control" placeholder="Briefly describe the issue" required>
            </div>

            <div class="form-group">
                <label for="message">Detailed Description</label>
                <textarea id="message" name="message" class="form-control" rows="5" placeholder="Please provide as much detail as possible..." required></textarea>
            </div>

            <button type="submit" class="btn-submit">Submit Report</button>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
