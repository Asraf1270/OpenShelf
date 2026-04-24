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
        --primary: #6366f1;
        --bg: #f8fafc;
        --surface: #ffffff;
        --text: #0f172a;
        --text-muted: #64748b;
        --border: #e2e8f0;
        --radius: 16px;
        --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }

    [data-theme="dark"] {
        --bg: #0f172a;
        --surface: #1e293b;
        --text: #f8fafc;
        --text-muted: #94a3b8;
        --border: #334155;
        --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
    }

    body {
        background-color: var(--bg);
        color: var(--text);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .report-container {
        max-width: 700px;
        margin: 4rem auto;
        padding: 0 1.5rem;
    }

    .report-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .report-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, var(--primary), #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .report-card {
        background: var(--surface);
        padding: 2.5rem;
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
        .report-card {
            padding: 1.5rem;
        }
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
