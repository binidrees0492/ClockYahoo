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
     * Test SMTP connection.
     *
     * @param array{host: string, port: int|string, username?: string, password?: string, encryption?: string} $config
     * @return array{success: bool, message: string}
     */
    public static function testSmtpConnection(array $config): array
    {
        $host = trim($config['host'] ?? '');
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
            // First check socket reachability
            $protocol = ($encryption === 'ssl') ? 'ssl://' : 'tcp://';
            $timeout = 5;
            $errno = 0;
            $errstr = '';

            $fp = @stream_socket_client(
                $protocol . $host . ':' . $port,
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT
            );

            if (!$fp) {
                return [
                    'success' => false,
                    'message' => "Could not connect to SMTP server {$host}:{$port} - Error: {$errstr} ({$errno})"
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
            $tls = in_array($encryption, ['tls', 'starttls']);
            $transport = new EsmtpTransport($host, $port, $tls);
            if (!empty($username)) {
                $transport->setUsername($username);
                $transport->setPassword($password);
            }

            $transport->start();
            $transport->stop();

            return [
                'success' => true,
                'message' => "SMTP connection & authentication verified successfully for {$username}@{$host}:{$port}!"
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'SMTP Connection test failed: ' . $e->getMessage()
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
        $host = trim($config['host'] ?? '');
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
