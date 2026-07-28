<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class HealthChecker
{
    /**
     * @return array{status: string, checks: array<string, array{status: string, message?: string}>}
     */
    public function check(): array
    {
        $checks = [
            'app' => $this->ok('Application is running'),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'mail' => $this->checkMail(),
            'ai' => $this->checkAi(),
        ];

        return [
            'status' => $this->aggregateStatus($checks),
            'checks' => $checks,
        ];
    }

    /**
     * @param array<string, array{status: string, message?: string}> $checks
     */
    private function aggregateStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('down', $statuses, true)) {
            return 'down';
        }

        if (in_array('degraded', $statuses, true)) {
            return 'degraded';
        }

        return 'ok';
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return $this->ok('Database connection successful');
        } catch (Throwable $e) {
            return $this->down('Database unavailable: ' . $e->getMessage());
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkCache(): array
    {
        try {
            $key = 'health:ping:' . uniqid('', true);
            Cache::put($key, 'pong', 5);
            $value = Cache::pull($key);

            if ($value !== 'pong') {
                return $this->degraded('Cache write/read mismatch');
            }

            return $this->ok('Cache is available (' . config('cache.default') . ')');
        } catch (Throwable $e) {
            return $this->degraded('Cache unavailable: ' . $e->getMessage());
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkMail(): array
    {
        $mailer = (string)config('mail.default');
        $ownerEmail = config('contact.owner_email');

        if (!is_string($ownerEmail) || $ownerEmail === '') {
            return $this->degraded('CONTACT_OWNER_EMAIL is not configured');
        }

        if (in_array($mailer, ['log', 'array'], true)) {
            return $this->degraded('Mailer "' . $mailer . '" does not deliver to real inboxes');
        }

        if ($mailer === 'smtp') {
            $host = (string)config('mail.mailers.smtp.host');
            $port = (int)config('mail.mailers.smtp.port');

            try {
                $connection = @fsockopen($host, $port, $errno, $errstr, 1.5);

                if ($connection === false) {
                    return $this->degraded("SMTP {$host}:{$port} unreachable ({$errstr})");
                }

                fclose($connection);

                return $this->ok("SMTP {$host}:{$port} reachable");
            } catch (Throwable $e) {
                return $this->degraded('SMTP check failed: ' . $e->getMessage());
            }
        }

        return $this->ok('Mailer configured: ' . $mailer);
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkAi(): array
    {
        $provider = (string)config('ai.provider');

        if ($provider === '') {
            return $this->degraded('AI provider is not configured');
        }

        if ($provider === 'groq') {
            $key = config('ai.groq.key');
            $url = config('ai.groq.url');
            $model = config('ai.groq.model');

            if (!is_string($key) || $key === '' || str_contains($key, 'your_free_key')) {
                return $this->degraded('GROQ_API_KEY is missing or placeholder');
            }

            if (!is_string($url) || $url === '' || !is_string($model) || $model === '') {
                return $this->degraded('Groq URL or model is not configured');
            }

            return $this->ok('Groq provider configured');
        }

        return $this->degraded('Unsupported AI provider: ' . $provider);
    }

    /**
     * @return array{status: string, message: string}
     */
    private function ok(string $message): array
    {
        return ['status' => 'ok', 'message' => $message];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function degraded(string $message): array
    {
        return ['status' => 'degraded', 'message' => $message];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function down(string $message): array
    {
        return ['status' => 'down', 'message' => $message];
    }
}
