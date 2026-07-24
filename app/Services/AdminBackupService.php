<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class AdminBackupService
{
    private const TABLES = [
        'users',
        'books',
        'borrow_requests',
        'announcements',
        'categories',
        'admins',
        'announcement_read_status',
        'login_otps',
        'notifications',
        'contact_messages',
        'wishlist',
        'reports',
        'remember_tokens',
        'support_us',
        'transactions',
    ];

    public function backupPath(): string
    {
        return base_path('backups');
    }

    public function dataPath(): string
    {
        return base_path('data');
    }

    public function usersPath(): string
    {
        return base_path('users');
    }

    public function createBackup(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = $this->backupPath() . DIRECTORY_SEPARATOR . $timestamp;

        File::ensureDirectoryExists($backupDir);

        if (is_dir($this->dataPath())) {
            File::copyDirectory($this->dataPath(), $backupDir . DIRECTORY_SEPARATOR . 'data');
        }

        if (is_dir($this->usersPath())) {
            File::copyDirectory($this->usersPath(), $backupDir . DIRECTORY_SEPARATOR . 'users');
        }

        $dbBackupDir = $backupDir . DIRECTORY_SEPARATOR . 'database';
        File::ensureDirectoryExists($dbBackupDir);

        foreach (self::TABLES as $table) {
            try {
                $data = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
                file_put_contents(
                    $dbBackupDir . DIRECTORY_SEPARATOR . $table . '.json',
                    json_encode($data, JSON_PRETTY_PRINT),
                );
            } catch (Throwable $e) {
                file_put_contents(
                    $dbBackupDir . DIRECTORY_SEPARATOR . $table . '_error.txt',
                    $e->getMessage(),
                );
            }
        }

        return $timestamp;
    }

    public function listBackups(): array
    {
        $backupPath = $this->backupPath();

        if (! is_dir($backupPath)) {
            return [];
        }

        $backups = [];

        foreach (glob($backupPath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            $timestampPart = substr($name, 0, 19);
            $dateObj = \DateTime::createFromFormat('Y-m-d_H-i-s', $timestampPart);

            $backups[] = [
                'name' => $name,
                'date' => $dateObj ? $dateObj->format('M j, Y H:i:s') : 'Unknown',
                'size' => round($this->directorySize($dir) / 1024, 2),
                'path' => $dir,
                'is_auto' => str_contains($name, '_auto'),
            ];
        }

        usort($backups, fn ($a, $b) => strcmp($b['name'], $a['name']));

        return $backups;
    }

    public function deleteBackup(string $name): bool
    {
        if ($name === '' || str_contains($name, '..')) {
            return false;
        }

        $path = $this->backupPath() . DIRECTORY_SEPARATOR . $name;

        if (! is_dir($path)) {
            return false;
        }

        File::deleteDirectory($path);

        return true;
    }

    public function restoreBackup(string $name): string
    {
        if ($name === '' || str_contains($name, '..')) {
            throw new \InvalidArgumentException('Invalid backup');
        }

        $backupDir = $this->backupPath() . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;

        if (! is_dir($backupDir)) {
            throw new \InvalidArgumentException('Invalid backup');
        }

        $autoTimestamp = $this->createAutoBackup();

        if (is_dir($backupDir . 'data')) {
            File::ensureDirectoryExists($this->dataPath());
            File::copyDirectory($backupDir . 'data', $this->dataPath());
        }

        if (is_dir($backupDir . 'users')) {
            File::ensureDirectoryExists($this->usersPath());
            File::copyDirectory($backupDir . 'users', $this->usersPath());
        } else {
            $legacyFiles = glob($backupDir . '*.json') ?: [];

            if ($legacyFiles !== []) {
                File::ensureDirectoryExists($this->dataPath());

                foreach ($legacyFiles as $file) {
                    File::copy($file, $this->dataPath() . DIRECTORY_SEPARATOR . basename($file));
                }
            }
        }

        $dbBackupDir = $backupDir . 'database' . DIRECTORY_SEPARATOR;

        if (is_dir($dbBackupDir)) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                foreach (self::TABLES as $table) {
                    $jsonFile = $dbBackupDir . $table . '.json';

                    if (! file_exists($jsonFile)) {
                        continue;
                    }

                    $data = json_decode(file_get_contents($jsonFile), true);

                    if (! is_array($data)) {
                        continue;
                    }

                    DB::table($table)->truncate();

                    if ($data !== []) {
                        foreach (array_chunk($data, 100) as $chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    }
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        return $autoTimestamp;
    }

    private function createAutoBackup(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s') . '_auto';
        $backupDir = $this->backupPath() . DIRECTORY_SEPARATOR . $timestamp;

        File::ensureDirectoryExists($backupDir);

        if (is_dir($this->dataPath())) {
            File::copyDirectory($this->dataPath(), $backupDir . DIRECTORY_SEPARATOR . 'data');
        }

        if (is_dir($this->usersPath())) {
            File::copyDirectory($this->usersPath(), $backupDir . DIRECTORY_SEPARATOR . 'users');
        }

        $dbBackupDir = $backupDir . DIRECTORY_SEPARATOR . 'database';
        File::ensureDirectoryExists($dbBackupDir);

        foreach (self::TABLES as $table) {
            try {
                $data = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
                file_put_contents(
                    $dbBackupDir . DIRECTORY_SEPARATOR . $table . '.json',
                    json_encode($data, JSON_PRETTY_PRINT),
                );
            } catch (Throwable) {
                // Ignore missing tables during auto-backup.
            }
        }

        return $timestamp;
    }

    private function directorySize(string $dir): int
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) ?: [] as $file) {
            $size += is_file($file) ? filesize($file) : $this->directorySize($file);
        }

        return (int) $size;
    }
}
