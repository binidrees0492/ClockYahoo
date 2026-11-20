<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Throwable;

class EmailService
{
    /**
     * Log an outgoing or failed email.
     */
    public static function logEmail(
        string $recipient,
        string $subject,
        ?string $body = null,
        string $status = 'sent',
        ?string $errorMessage = null,
        ?int $userId = null,
        ?string $sender = null
    ): EmailLog {
        if (!$userId) {
            $user = User::where('email', $recipient)->first();
            if ($user) {
                $userId = $user->id;
            }
        }

        return EmailLog::create([
            'user_id'       => $userId,
            'recipient'     => $recipient,
            'sender'        => $sender ?: config('mail.from.address'),
            'subject'       => $subject,
            'body'          => $body,
            'status'        => $status,
            'error_message' => $errorMessage,
            'mailer_type'   => 'smtp',
            'sent_at'       => $status === 'sent' ? now() : null,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Send email on behalf of user (using custom SMTP or system fallback).
     */
    public static function sendUserEmail(User $user, string $recipient, string $subject, string $body, array $attachments = []): array
    {
        $sender = ($user->use_custom_email && $user->smtp_from_address)
            ? $user->smtp_from_address
            : ($user->email ?: config('mail.from.address'));

        try {
            Mail::raw($body, function ($message) use ($recipient, $subject, $sender, $user, $attachments) {
                $message->to($recipient)
                    ->subject($subject)
                    ->from($sender, $user->smtp_from_name ?: $user->name);

                foreach ($attachments as $att) {
                    if (is_string($att) && file_exists($att)) {
                        $message->attach($att);
                    } elseif (is_array($att) && isset($att['path']) && file_exists($att['path'])) {
                        $message->attach($att['path'], ['as' => $att['name'] ?? basename($att['path'])]);
                    }
                }
            });

            $attMeta = !empty($attachments) ? array_map(function ($a) {
                if (is_array($a)) {
                    return [
                        'name' => $a['name'] ?? basename($a['path'] ?? 'file'),
                        'path' => $a['path'] ?? '',
                        'url'  => $a['url'] ?? null,
                        'size' => $a['size'] ?? null,
                    ];
                }
                return [
                    'name' => basename($a),
                    'path' => $a,
                    'url'  => null,
                    'size' => file_exists($a) ? filesize($a) : null,
                ];
            }, $attachments) : null;

            $log = EmailLog::create([
                'user_id'       => $user->id,
                'recipient'     => $recipient,
                'sender'        => $sender,
                'subject'       => $subject,
                'body'          => $body,
                'folder'        => 'sent',
                'status'        => 'sent',
                'sent_at'       => now(),
                'last_attempt_at' => now(),
                'attachments'   => $attMeta,
            ]);

            return ['success' => true, 'message' => 'Email verified and sent successfully.', 'log' => $log];
        } catch (Throwable $e) {
            $attMeta = !empty($attachments) ? array_map(function ($a) {
                if (is_array($a)) {
                    return [
                        'name' => $a['name'] ?? basename($a['path'] ?? 'file'),
                        'path' => $a['path'] ?? '',
                        'url'  => $a['url'] ?? null,
                        'size' => $a['size'] ?? null,
                    ];
                }
                return [
                    'name' => basename($a),
                    'path' => $a,
                    'url'  => null,
                    'size' => file_exists($a) ? filesize($a) : null,
                ];
            }, $attachments) : null;

            $log = EmailLog::create([
                'user_id'       => $user->id,
                'recipient'     => $recipient,
                'sender'        => $sender,
                'subject'       => $subject,
                'body'          => $body,
                'folder'        => 'drafts',
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at'       => null,
                'last_attempt_at' => now(),
                'attachments'   => $attMeta,
            ]);

            return ['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage(), 'log' => $log];
        }
    }

    /**
     * Dynamically apply SMTP credentials from CompanySetting or parameters to Laravel Mail runtime.
     */
    public static function applyRuntimeMailConfig(?array $customConfig = null): void
    {
        try {
            $host = $customConfig['host'] ?? \App\Models\CompanySetting::get('mail_host');
            if (empty($host)) {
                return;
            }

            $port = (int) ($customConfig['port'] ?? \App\Models\CompanySetting::get('mail_port', 587));
            $encryption = strtolower(trim((string) ($customConfig['encryption'] ?? \App\Models\CompanySetting::get('mail_encryption', 'tls'))));
            $isSsl = ($encryption === 'ssl' || $port === 465);
            $scheme = $isSsl ? 'smtps' : null;

            $username = $customConfig['username'] ?? \App\Models\CompanySetting::get('mail_username');
            $password = $customConfig['password'] ?? \App\Models\CompanySetting::get('mail_password');
            $fromAddress = $customConfig['from_address'] ?? \App\Models\CompanySetting::get('mail_from_address') ?: config('mail.from.address');
            $fromName = $customConfig['from_name'] ?? \App\Models\CompanySetting::get('mail_from_name') ?: config('mail.from.name');

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.encryption' => $encryption,
                'mail.mailers.smtp.scheme' => $scheme,
                'mail.mailers.smtp.username' => $username,
                'mail.mailers.smtp.password' => $password,
                'mail.from.address' => $fromAddress,
                'mail.from.name' => $fromName,
            ]);

            app()->forgetInstance('mail.manager');
            Mail::purge('smtp');
        } catch (Throwable $e) {
            Log::warning('EmailService: Failed applying runtime mail config: ' . $e->getMessage());
        }
    }

    /**
     * Safely synchronize mail values into .env file if it exists and is writable.
     */
    public static function updateEnvFile(array $values): bool
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath) || !is_writable($envPath)) {
            return false;
        }

        try {
            $content = file_get_contents($envPath);
            foreach ($values as $key => $val) {
                $valStr = (string) $val;
                if (preg_match('/\s/', $valStr) || str_contains($valStr, '#')) {
                    $valStr = '"' . addcslashes($valStr, '"') . '"';
                }
                if (preg_match("/^{$key}=.*/m", $content)) {
                    $content = preg_replace("/^{$key}=.*/m", "{$key}={$valStr}", $content);
                } else {
                    $content .= "\n{$key}={$valStr}";
                }
            }
            file_put_contents($envPath, $content);
            return true;
        } catch (Throwable $e) {
            Log::warning('EmailService: Failed updating .env file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test SMTP connection.
     *
     * @param array{host: string, port: int|string, username?: string, password?: string, encryption?: string} $config
     * @return array{success: bool, message: string}
     */
    public static function testSmtpConnection(array $config): array
    {
        $rawHost = trim($config['host'] ?? '');
        $host = $rawHost;
        $autocorrectNote = '';

        if (preg_match('/^(stmp|smpt)\./i', $host)) {
            $host = preg_replace('/^(stmp|smpt)\./i', 'smtp.', $host);
            $autocorrectNote = " (Auto-corrected host from '{$rawHost}' to '{$host}')";
        }

        $port = (int) ($config['port'] ?? 587);
        $username = trim($config['username'] ?? '');
        $password = $config['password'] ?? '';
        $encryption = strtolower(trim($config['encryption'] ?? 'tls'));

        if (empty($host)) {
            return ['success' => false, 'message' => 'SMTP Host is required.'];
        }

        if ($port <= 0 || $port > 65535) {
            return ['success' => false, 'message' => 'Invalid SMTP Port.'];
        }

        try {
            $isSsl = ($encryption === 'ssl' || $port === 465);
            $protocol = $isSsl ? 'ssl://' : 'tcp://';
            $timeout = 8;
            $errno = 0;
            $errstr = '';

            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);

            $fp = @stream_socket_client(
                $protocol . $host . ':' . $port,
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$fp) {
                $typoTip = '';
                if (preg_match('/^(stmp|smpt)\./i', $rawHost)) {
                    $typoTip = " Tip: '{$rawHost}' contains a typo ('stmp' instead of 'smtp').";
                } elseif (str_contains($errstr, 'getaddrinfo') || str_contains($errstr, 'Name or service not known')) {
                    $typoTip = " Tip: Host '{$host}' could not be resolved via DNS. Check spelling.";
                }
                return [
                    'success' => false,
                    'message' => "Could not connect to SMTP server {$host}:{$port} - Error: {$errstr} ({$errno}).{$typoTip} Check if host/port is correct and firewall allows outgoing SMTP."
                ];
            }

            // Read banner
            stream_set_timeout($fp, 5);
            $banner = fgets($fp, 512);

            // Send EHLO
            fputs($fp, "EHLO localhost\r\n");
            $response = '';
            while ($line = fgets($fp, 512)) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }

            fputs($fp, "QUIT\r\n");
            fclose($fp);

            if (empty($username)) {
                return [
                    'success' => true,
                    'message' => "Connected successfully to SMTP server {$host}:{$port}! Server banner: " . trim($banner)
                ];
            }

            // If credentials provided, attempt full transport authentication
            // In Symfony EsmtpTransport: $tls = true for implicit TLS (SMTPS on port 465/ssl), null for STARTTLS (port 587/tls)
            $tls = $isSsl ? true : ($encryption === 'none' ? false : null);
            $transport = new EsmtpTransport($host, $port, $tls);
            if (!empty($username)) {
                $transport->setUsername($username);
                $transport->setPassword($password);
            }

            $transport->start();
            $transport->stop();

            return [
                'success' => true,
                'message' => "SMTP connection & authentication verified successfully for {$username}@{$host}:{$port}!{$autocorrectNote}"
            ];
        } catch (Throwable $e) {
            $errMsg = $e->getMessage();
            if (str_contains(strtolower($errMsg), 'authentication failed') || str_contains(strtolower($errMsg), '535')) {
                $errMsg .= " (Hostinger Tip: Use your full email address e.g. info@yourdomain.com as username, not just the prefix)";
            }
            return [
                'success' => false,
                'message' => 'SMTP Connection test failed: ' . $errMsg
            ];
        }
    }

    /**
     * Test IMAP connection.
     *
     * @param array{host: string, port: int|string, username?: string, password?: string, encryption?: string} $config
     * @return array{success: bool, message: string}
     */
    public static function testImapConnection(array $config): array
    {
        $rawHost = trim($config['host'] ?? '');
        $host = $rawHost;
        $autocorrectNote = '';

        if (preg_match('/^(ipam|impa|imap1)\./i', $host)) {
            $host = preg_replace('/^(ipam|impa|imap1)\./i', 'imap.', $host);
            $autocorrectNote = " (Auto-corrected host from '{$rawHost}' to '{$host}')";
        }
        $port = (int) ($config['port'] ?? 993);
        $username = trim($config['username'] ?? '');
        $password = $config['password'] ?? '';
        $encryption = strtolower(trim($config['encryption'] ?? 'ssl'));

        if (empty($host)) {
            return ['success' => false, 'message' => 'IMAP Host is required.'];
        }

        if ($port <= 0 || $port > 65535) {
            return ['success' => false, 'message' => 'Invalid IMAP Port.'];
        }

        try {
            $prefix = ($encryption === 'ssl' || $port === 993) ? 'ssl://' : 'tcp://';
            $timeout = 5;
            $errno = 0;
            $errstr = '';

            $socket = @stream_socket_client(
                $prefix . $host . ':' . $port,
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT
            );

            if (!$socket) {
                return [
                    'success' => false,
                    'message' => "Could not reach IMAP server at {$host}:{$port} - Error: {$errstr} ({$errno})"
                ];
            }

            stream_set_timeout($socket, 5);
            $greeting = fgets($socket, 1024);

            if (!str_starts_with(trim($greeting), '* OK')) {
                fclose($socket);
                return [
                    'success' => false,
                    'message' => "Unexpected IMAP server greeting: " . trim($greeting)
                ];
            }

            if (!empty($username) && !empty($password)) {
                // Send IMAP LOGIN command
                $loginCmd = sprintf('A001 LOGIN "%s" "%s"', addcslashes($username, '"\\'), addcslashes($password, '"\\'));
                fputs($socket, $loginCmd . "\r\n");

                $authResponse = '';
                while ($resp = fgets($socket, 1024)) {
                    $authResponse .= $resp;
                    if (str_starts_with($resp, 'A001 ')) {
                        break;
                    }
                }

                // Send LOGOUT
                fputs($socket, "A002 LOGOUT\r\n");
                fclose($socket);

                if (str_contains($authResponse, 'A001 OK')) {
                    return [
                        'success' => true,
                        'message' => "IMAP login and mailbox verified successfully for {$username}@{$host}:{$port}!"
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => "IMAP authentication rejected: " . trim($authResponse)
                    ];
                }
            }

            fclose($socket);
            return [
                'success' => true,
                'message' => "IMAP server connection established successfully at {$host}:{$port}."
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'IMAP Connection error: ' . $e->getMessage()
            ];
        }
    }
}
