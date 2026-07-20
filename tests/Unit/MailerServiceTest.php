<?php

namespace Tests\Unit;

use App\Services\MailerService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MailerServiceTest extends TestCase
{
    public function test_it_falls_back_to_log_mailer_when_smtp_transport_fails(): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', '127.0.0.1');
        Config::set('mail.mailers.smtp.port', 1);
        Config::set('mail.mailers.smtp.username', 'test');
        Config::set('mail.mailers.smtp.password', 'test');
        Config::set('mail.mailers.smtp.encryption', 'tls');

        Config::set('openshelf-mail.fallback_to_log', true);
        Config::set('openshelf-mail.log.enabled', true);
        Config::set('openshelf-mail.log.file', storage_path('logs/test-mail.log'));

        @unlink(storage_path('logs/test-mail.log'));

        $service = new MailerService();

        $result = $service->send('test@example.com', 'Test User', 'Fallback Test', '<p>Fallback body</p>');

        $this->assertTrue($result);
        $this->assertFileExists(storage_path('logs/test-mail.log'));
        $this->assertStringContainsString('Fallback Test', file_get_contents(storage_path('logs/test-mail.log')));
    }
}
