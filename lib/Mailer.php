<?php
/**
 * OpenShelf Mailer Class
 * 
 * Wrapper for PHPMailer with template support and rate limiting
 */

// Include PHPMailer (you need to install via composer)
// composer require phpmailer/phpmailer

require_once __DIR__ . '/../vendor/autoload.php';

// Now you can use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    
    private $config;
    private $mailer;
    private $rateLimitFile;
    private $logFile;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->config = include __DIR__ . '/../config/mail.php';
        $this->rateLimitFile = __DIR__ . '/../data/mail_rate_limit.json';
        $this->logFile = $this->config['log']['file'];
        
        $this->initializeMailer();
    }
    
    /**
     * Initialize PHPMailer with configuration
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            if ($this->config['smtp']['debug'] > 0) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp']['host'];
            $this->mailer->SMTPAuth = $this->config['smtp']['auth'];
            $this->mailer->Username = $this->config['smtp']['username'];
            $this->mailer->Password = $this->config['smtp']['password'];
            $this->mailer->SMTPSecure = $this->config['smtp']['secure'];
            $this->mailer->Port = $this->config['smtp']['port'];
            $this->mailer->Timeout = $this->config['smtp']['timeout'];
            
            // Recipient settings
            $this->mailer->setFrom(
                $this->config['email']['from']['address'],
                $this->config['email']['from']['name']
            );
            
            $this->mailer->addReplyTo(
                $this->config['email']['reply_to']['address'],
                $this->config['email']['reply_to']['name']
            );
            
            // Content settings
            $this->mailer->CharSet = $this->config['email']['charset'];
            $this->mailer->Encoding = $this->config['email']['encoding'];
            $this->mailer->isHTML(true);
            $this->mailer->WordWrap = $this->config['email']['wordwrap'];
            
        } catch (Exception $e) {
            $errorMsg = "Mailer initialization failed: " . $e->getMessage();
            $this->logError($errorMsg);
            error_log(" [Mailer] " . $errorMsg);
        }
    }
    
    /**
     * Send email
     * 
     * @param string $to Recipient email
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $htmlBody HTML content
     * @param string $textBody Plain text content (optional)
     * @param array $attachments Array of file paths
     * @param string $userId User ID for rate limiting (optional)
     * @return bool Success status
     */
    public function send($to, $toName, $subject, $htmlBody, $textBody = '', $attachments = [], $userId = null) {
        try {
            // Check rate limit
            if ($userId && !$this->checkRateLimit($userId)) {
                throw new Exception("Rate limit exceeded for user: {$userId}");
            }
            
            // Reset mailer for new message
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();
            
            // Recipient
            $this->mailer->addAddress($to, $toName);
            
            // Content
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->buildHTMLTemplate($htmlBody, $toName);
            
            if (!empty($textBody)) {
                $this->mailer->AltBody = strip_tags($textBody);
            } else {
                $this->mailer->AltBody = strip_tags($htmlBody);
            }
            
            // Add tracking headers
            $this->mailer->addCustomHeader('X-Mailer', 'OpenShelf-Mailer/1.0');
            $this->mailer->addCustomHeader('X-User-ID', $userId ?? 'system');
            
            // Attachments
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $this->mailer->addAttachment($attachment);
                }
            }
            
            // Send
            $sent = $this->mailer->send();
            
            // Log success
            $this->logInfo("Email sent to {$to} - Subject: {$subject}");
            
            // Update rate limit
            if ($userId) {
                $this->updateRateLimit($userId);
            }
            
            return true;
            
        } catch (Exception $e) {
            $errorMsg = "Failed to send email to {$to}: " . $e->getMessage();
            $this->logError($errorMsg);
            error_log(" [Mailer] " . $errorMsg);
            return false;
        }
    }
    
    /**
     * Send email using template
     * 
     * @param string $to Recipient email
     * @param string $toName Recipient name
     * @param string $template Template name
     * @param array $data Template data
     * @param string $userId User ID for rate limiting
     * @return bool Success status
     */
    public function sendTemplate($to, $toName, $template, $data = [], $userId = null) {
        $templateFile = $this->config['templates'] . $template . '.php';
        
        if (!file_exists($templateFile)) {
            $errorMsg = "Template not found: {$template}";
            $this->logError($errorMsg);
            error_log(" [Mailer] " . $errorMsg);
            return false;
        }
        
        // Extract data for template
        extract($data);
        
        // Start output buffering
        ob_start();
        include $templateFile;
        $htmlBody = ob_get_clean();
        
        // Extract subject from template or use default
        $subject = $data['subject'] ?? 'Notification from OpenShelf';
        
        return $this->send($to, $toName, $subject, $htmlBody, '', [], $userId);
    }
    
    /**
     * Build HTML email template with header/footer
     */
    private function buildHTMLTemplate($content, $recipientName) {
        $year = date('Y');
        
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    background-color: #f8f9fa;
                }
                .email-wrapper {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                }
                .email-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    padding: 30px 20px;
                    text-align: center;
                }
                .email-header h1 {
                    color: white;
                    margin: 0;
                    font-size: 28px;
                }
                .email-header .logo {
                    font-size: 40px;
                    color: white;
                    margin-bottom: 10px;
                }
                .email-body {
                    padding: 30px 20px;
                }
                .email-footer {
                    padding: 20px;
                    text-align: center;
                    background-color: #f8f9fa;
                    color: #8898aa;
                    font-size: 14px;
                    border-top: 1px solid #e9ecef;
                }
                .button {
                    display: inline-block;
                    padding: 12px 30px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-decoration: none;
                    border-radius: 25px;
                    font-weight: 600;
                    margin: 20px 0;
                }
                .otp-box {
                    background: #f8f9fa;
                    border: 2px dashed #667eea;
                    padding: 20px;
                    text-align: center;
                    font-size: 36px;
                    font-weight: bold;
                    letter-spacing: 10px;
                    color: #667eea;
                    border-radius: 10px;
                    margin: 20px 0;
                }
                .warning {
                    color: #f5365c;
                    font-size: 14px;
                }
                .text-muted {
                    color: #8898aa;
                }
                @media only screen and (max-width: 600px) {
                    .email-wrapper {
                        width: 100%;
                        border-radius: 0;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="email-header">
                    <div class="logo">📚</div>
                    <h1>OpenShelf</h1>
                </div>
                <div class="email-body">
                    <p>Hello <strong>{$recipientName}</strong>,</p>
                    {$content}
                    <p style="margin-top: 30px;">
                        Best regards,<br>
                        <strong>The OpenShelf Team</strong>
                    </p>
                </div>
                <div class="email-footer">
                    <p>&copy; {$year} OpenShelf. All rights reserved.</p>
                    <p style="font-size: 12px; margin-top: 10px;">
                        This is an automated message, please do not reply to this email.<br>
                        <a href="#" style="color: #667eea; text-decoration: none;">Privacy Policy</a> | 
                        <a href="#" style="color: #667eea; text-decoration: none;">Terms of Service</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
    
    /**
     * Check rate limit for user
     */
    private function checkRateLimit($userId) {
        if (!$this->config['rate_limit']['enabled']) {
            return true;
        }
        
        $limits = $this->loadRateLimits();
        $now = time();
        $hourAgo = $now - 3600;
        $dayAgo = $now - 86400;
        
        if (!isset($limits[$userId])) {
            return true;
        }
        
        $userLimits = $limits[$userId];
        
        // Count emails in last hour
        $hourly = count(array_filter($userLimits, function($timestamp) use ($hourAgo) {
            return $timestamp > $hourAgo;
        }));
        
        // Count emails in last day
        $daily = count(array_filter($userLimits, function($timestamp) use ($dayAgo) {
            return $timestamp > $dayAgo;
        }));
        
        return $hourly < $this->config['rate_limit']['max_per_hour'] && 
               $daily < $this->config['rate_limit']['max_per_day'];
    }
    
    /**
     * Update rate limit counter for user
     */
    private function updateRateLimit($userId) {
        if (!$this->config['rate_limit']['enabled']) {
            return;
        }
        
        $limits = $this->loadRateLimits();
        
        if (!isset($limits[$userId])) {
            $limits[$userId] = [];
        }
        
        $limits[$userId][] = time();
        
        // Clean old entries (older than 24 hours)
        $dayAgo = time() - 86400;
        $limits[$userId] = array_filter($limits[$userId], function($timestamp) use ($dayAgo) {
            return $timestamp > $dayAgo;
        });
        
        $this->saveRateLimits($limits);
    }
    
    /**
     * Load rate limits from file
     */
    private function loadRateLimits() {
        if (!file_exists($this->rateLimitFile)) {
            return [];
        }
        return json_decode(file_get_contents($this->rateLimitFile), true) ?? [];
    }
    
    /**
     * Save rate limits to file
     */
    private function saveRateLimits($limits) {
        file_put_contents(
            $this->rateLimitFile,
            json_encode($limits, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Log info message
     */
    private function logInfo($message) {
        $this->log('INFO', $message);
    }
    
    /**
     * Log error message
     */
    private function logError($message) {
        $this->log('ERROR', $message);
    }
    
    /**
     * Write to log file
     */
    private function log($level, $message) {
        if (!$this->config['log']['enabled']) {
            return;
        }
        
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Test SMTP connection
     */
    public function testConnection() {
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            return ['success' => true, 'message' => 'SMTP connection successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}