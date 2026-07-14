<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Throwable;

class MailerService
{
    public function sendTemplate(
        string $to,
        string $toName,
        string $template,
        array $data = [],
        ?string $userId = null,
    ): bool {
        $view = 'emails.' . $template;

        if (! View::exists($view)) {
            $this->logError("Template not found: {$template}");

            return false;
        }

        $data = $this->prepareTemplateData($template, $data);
        $subject = $data['subject'] ?? config("openshelf-mail.default_subjects.{$template}", 'Notification from OpenShelf');

        try {
            $htmlBody = view($view, $data)->render();
        } catch (Throwable $e) {
            $this->logError("Failed to render template {$template}: " . $e->getMessage());

            return false;
        }

        return $this->send($to, $toName, $subject, $htmlBody, $userId);
    }

    public function send(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        ?string $userId = null,
        array $attachments = [],
    ): bool {
        if ($userId && ! $this->checkRateLimit($userId)) {
            $this->logError("Rate limit exceeded for user: {$userId}");

            return false;
        }

        $replyTo = config('openshelf-mail.reply_to');

        try {
            Mail::html($htmlBody, function ($message) use ($to, $toName, $subject, $replyTo, $attachments, $userId) {
                $message->to($to, $toName)
                    ->subject($subject)
                    ->replyTo($replyTo['address'], $replyTo['name']);

                foreach ($attachments as $attachment) {
                    if (is_string($attachment) && file_exists($attachment)) {
                        $message->attach($attachment);
                    }
                }

                if ($userId) {
                    $message->getHeaders()->addTextHeader('X-User-ID', $userId);
                }

                $message->getHeaders()->addTextHeader('X-Mailer', 'OpenShelf-Mailer/2.0');
            });

            $this->logInfo("Email sent to {$to} - Subject: {$subject}");

            if ($userId) {
                $this->updateRateLimit($userId);
            }

            return true;
        } catch (Throwable $e) {
            $this->logError("Failed to send email to {$to}: " . $e->getMessage());

            return false;
        }
    }

    public function testConnection(): array
    {
        try {
            $transport = Mail::mailer()->getSymfonyTransport();
            if (method_exists($transport, 'start')) {
                $transport->start();
                $transport->stop();
            }

            return ['success' => true, 'message' => 'Mail transport connection successful'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function prepareTemplateData(string $template, array $data): array
    {
        $data['base_url'] = rtrim($data['base_url'] ?? config('app.url'), '/');
        $data['type'] = $data['type'] ?? 'info';

        if ($template === 'return_reminder') {
            $data['type'] = ($data['days_remaining'] ?? 0) < 0 ? 'danger' : 'warning';
        }

        return $data;
    }

    private function checkRateLimit(string $userId): bool
    {
        $config = config('openshelf-mail.rate_limit');

        if (! $config['enabled']) {
            return true;
        }

        $limits = $this->loadRateLimits();
        $now = time();
        $hourAgo = $now - 3600;
        $dayAgo = $now - 86400;

        if (! isset($limits[$userId])) {
            return true;
        }

        $userLimits = $limits[$userId];
        $hourly = count(array_filter($userLimits, fn ($timestamp) => $timestamp > $hourAgo));
        $daily = count(array_filter($userLimits, fn ($timestamp) => $timestamp > $dayAgo));

        return $hourly < $config['max_per_hour'] && $daily < $config['max_per_day'];
    }

    private function updateRateLimit(string $userId): void
    {
        $config = config('openshelf-mail.rate_limit');

        if (! $config['enabled']) {
            return;
        }

        $limits = $this->loadRateLimits();

        if (! isset($limits[$userId])) {
            $limits[$userId] = [];
        }

        $limits[$userId][] = time();

        $dayAgo = time() - 86400;
        $limits[$userId] = array_values(array_filter(
            $limits[$userId],
            fn ($timestamp) => $timestamp > $dayAgo,
        ));

        $this->saveRateLimits($limits);
    }

    private function loadRateLimits(): array
    {
        $path = config('openshelf-mail.rate_limit.storage_path');

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    private function saveRateLimits(array $limits): void
    {
        $path = config('openshelf-mail.rate_limit.storage_path');
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($limits, JSON_PRETTY_PRINT));
    }

    private function logInfo(string $message): void
    {
        $this->log('INFO', $message);
    }

    private function logError(string $message): void
    {
        $this->log('ERROR', $message);
        error_log('[Mailer] ' . $message);
    }

    private function log(string $level, string $message): void
    {
        if (! config('openshelf-mail.log.enabled')) {
            return;
        }

        $logFile = config('openshelf-mail.log.file');
        $logDir = dirname($logFile);

        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d H:i:s');
        file_put_contents($logFile, "[{$timestamp}] [{$level}] {$message}" . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
