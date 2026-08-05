<?php
/**
 * Dependency-free SMTP mailer with STARTTLS support and a mail() fallback.
 *
 * Supports plain-text + HTML multipart messages. On shared hosting without a
 * configured SMTP relay it falls back to PHP's mail(). Real credentials are
 * read from config('mail').
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Mailer
{
    /**
     * @param array<int, array{path:string, name:string}> $attachments
     */
    public static function send(string $to, string $subject, string $htmlBody, ?string $textBody = null, array $attachments = []): bool
    {
        $driver = (string) Config::get('mail.driver', 'smtp');
        $host = (string) Config::get('mail.host', '');

        try {
            if ($driver === 'smtp' && $host !== '' && $host !== 'localhost') {
                return (new self())->smtpSend($to, $subject, $htmlBody, $textBody, $attachments);
            }
            return self::mailFallback($to, $subject, $htmlBody, $textBody);
        } catch (\Throwable $e) {
            Logger::error('Mail send failed: ' . $e->getMessage(), ['to' => $to]);
            return false;
        }
    }

    private static function mailFallback(string $to, string $subject, string $html, ?string $text): bool
    {
        $fromEmail = (string) Config::get('mail.from_email');
        $fromName = (string) Config::get('mail.from_name');
        $boundary = 'sf_' . bin2hex(random_bytes(8));

        $headers = [
            'MIME-Version: 1.0',
            'From: ' . self::encodeHeader($fromName) . " <{$fromEmail}>",
            'Reply-To: ' . $fromEmail,
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $body = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
            . ($text ?? strip_tags($html)) . "\r\n\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
            . $html . "\r\n\r\n"
            . "--{$boundary}--";

        return mail($to, self::encodeHeader($subject), $body, implode("\r\n", $headers));
    }

    /**
     * @param array<int, array{path:string, name:string}> $attachments
     */
    private function smtpSend(string $to, string $subject, string $html, ?string $text, array $attachments): bool
    {
        $host = (string) Config::get('mail.host');
        $port = (int) Config::get('mail.port', 587);
        $user = (string) Config::get('mail.username');
        $pass = (string) Config::get('mail.password');
        $enc = (string) Config::get('mail.encryption', 'tls');
        $fromEmail = (string) Config::get('mail.from_email');
        $fromName = (string) Config::get('mail.from_name');

        $transport = $enc === 'ssl' ? "ssl://{$host}" : $host;
        $socket = @stream_socket_client("{$transport}:{$port}", $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            throw new RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
        }
        stream_set_timeout($socket, 20);

        $read = function () use ($socket): string {
            $data = '';
            while ($line = fgets($socket, 515)) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $cmd = function (string $command, array $expect) use ($socket, $read): string {
            fwrite($socket, $command . "\r\n");
            $response = $read();
            $code = (int) substr($response, 0, 3);
            if (!in_array($code, $expect, true)) {
                throw new RuntimeException("SMTP error after '{$command}': {$response}");
            }
            return $response;
        };

        $read();
        $ehloHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $cmd("EHLO {$ehloHost}", [250]);

        if ($enc === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                throw new RuntimeException('STARTTLS negotiation failed.');
            }
            $cmd("EHLO {$ehloHost}", [250]);
        }

        if ($user !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode($user), [334]);
            $cmd(base64_encode($pass), [235]);
        }

        $cmd("MAIL FROM:<{$fromEmail}>", [250]);
        $cmd("RCPT TO:<{$to}>", [250, 251]);
        $cmd('DATA', [354]);

        $message = $this->buildMime($to, $fromEmail, $fromName, $subject, $html, $text, $attachments);
        // Dot-stuffing for SMTP.
        $message = preg_replace('/^\./m', '..', $message);
        fwrite($socket, $message . "\r\n.\r\n");
        $response = $read();
        if ((int) substr($response, 0, 3) !== 250) {
            throw new RuntimeException("SMTP DATA rejected: {$response}");
        }

        $cmd('QUIT', [221]);
        fclose($socket);
        return true;
    }

    /**
     * @param array<int, array{path:string, name:string}> $attachments
     */
    private function buildMime(string $to, string $fromEmail, string $fromName, string $subject, string $html, ?string $text, array $attachments): string
    {
        $boundary = 'sf_alt_' . bin2hex(random_bytes(8));
        $mixed = 'sf_mix_' . bin2hex(random_bytes(8));
        $date = date('r');
        $messageId = '<' . bin2hex(random_bytes(12)) . '@' . ($_SERVER['SERVER_NAME'] ?? 'salesflow') . '>';

        $headers = "Date: {$date}\r\n"
            . 'From: ' . self::encodeHeader($fromName) . " <{$fromEmail}>\r\n"
            . "To: <{$to}>\r\n"
            . 'Subject: ' . self::encodeHeader($subject) . "\r\n"
            . "Message-ID: {$messageId}\r\n"
            . "MIME-Version: 1.0\r\n";

        $alt = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text ?? strip_tags($html))) . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html)) . "\r\n"
            . "--{$boundary}--\r\n";

        if ($attachments === []) {
            return $headers . $alt;
        }

        $body = "Content-Type: multipart/mixed; boundary=\"{$mixed}\"\r\n\r\n"
            . "--{$mixed}\r\n" . $alt;
        foreach ($attachments as $att) {
            if (!is_file($att['path'])) {
                continue;
            }
            $body .= "--{$mixed}\r\n"
                . 'Content-Type: application/octet-stream; name="' . $att['name'] . "\"\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . 'Content-Disposition: attachment; filename="' . $att['name'] . "\"\r\n\r\n"
                . chunk_split(base64_encode((string) file_get_contents($att['path']))) . "\r\n";
        }
        $body .= "--{$mixed}--\r\n";
        return $headers . $body;
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
