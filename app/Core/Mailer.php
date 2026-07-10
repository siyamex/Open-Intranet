<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Hand-rolled SMTP mailer (STARTTLS or implicit TLS on port 465) with
 * fallback to PHP mail(). Every message is also appended to
 * storage/logs/mail.log so local development works without a mail server.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $html, ?string $text = null): bool
    {
        $fromRaw = (string) Config::env('SMTP_FROM', 'OpenIntranet <no-reply@localhost>');
        [$fromName, $fromEmail] = self::parseAddress($fromRaw);
        $text ??= trim(strip_tags(preg_replace('#<br\s*/?>#i', "\n", $html) ?? $html));

        $boundary = 'b' . bin2hex(random_bytes(12));
        $headers = [
            'MIME-Version: 1.0',
            'From: ' . self::encodeHeader($fromName) . ' <' . $fromEmail . '>',
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . (parse_url((string) Config::env('APP_URL', 'localhost'), PHP_URL_HOST) ?: 'localhost') . '>',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        $body = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
            . $text . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
            . $html . "\r\n"
            . "--{$boundary}--\r\n";

        self::logMail($to, $subject, $text);

        $host = (string) Config::env('SMTP_HOST', '');
        if ($host !== '') {
            try {
                self::smtpSend($fromEmail, $to, "Subject: " . self::encodeHeader($subject) . "\r\nTo: {$to}\r\n" . implode("\r\n", $headers) . "\r\n\r\n" . $body);
                return true;
            } catch (\Throwable $e) {
                error_log('[mailer] SMTP send failed: ' . $e->getMessage());
                // fall through to mail()
            }
        }

        $mailHeaders = implode("\r\n", $headers);
        if (function_exists('mail') && @mail($to, self::encodeHeader($subject), $body, $mailHeaders)) {
            return true;
        }

        // No transport available — in local development treat the mail.log
        // entry as delivery so flows remain testable.
        return Config::env('APP_ENV', 'production') === 'local';
    }

    private static function smtpSend(string $from, string $to, string $data): void
    {
        $host = (string) Config::env('SMTP_HOST', '');
        $port = (int) Config::env('SMTP_PORT', 587);
        $user = (string) Config::env('SMTP_USER', '');
        $pass = (string) Config::env('SMTP_PASS', '');

        $remote = ($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $fp = stream_socket_client($remote, $errno, $errstr, 10);
        if ($fp === false) {
            throw new \RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
        }
        stream_set_timeout($fp, 10);

        $expect = static function (array $codes) use ($fp): string {
            $response = '';
            do {
                $line = fgets($fp, 515);
                if ($line === false) {
                    throw new \RuntimeException('SMTP: connection dropped.');
                }
                $response .= $line;
            } while (isset($line[3]) && $line[3] === '-');
            $code = (int) substr($response, 0, 3);
            if (!in_array($code, $codes, true)) {
                throw new \RuntimeException('SMTP unexpected response: ' . trim($response));
            }
            return $response;
        };
        $say = static function (string $cmd) use ($fp): void {
            fwrite($fp, $cmd . "\r\n");
        };

        $hostname = parse_url((string) Config::env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
        $expect([220]);
        $say('EHLO ' . $hostname);
        $expect([250]);

        if ($port !== 465) {
            $say('STARTTLS');
            $expect([220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('SMTP: STARTTLS negotiation failed.');
            }
            $say('EHLO ' . $hostname);
            $expect([250]);
        }

        if ($user !== '') {
            $say('AUTH LOGIN');
            $expect([334]);
            $say(base64_encode($user));
            $expect([334]);
            $say(base64_encode($pass));
            $expect([235]);
        }

        $say('MAIL FROM:<' . $from . '>');
        $expect([250]);
        $say('RCPT TO:<' . $to . '>');
        $expect([250, 251]);
        $say('DATA');
        $expect([354]);
        // dot-stuffing
        $data = preg_replace('/^\./m', '..', $data) ?? $data;
        fwrite($fp, $data . "\r\n.\r\n");
        $expect([250]);
        $say('QUIT');
        fclose($fp);
    }

    /**
     * @return array{0: string, 1: string} [name, email]
     */
    private static function parseAddress(string $raw): array
    {
        if (preg_match('/^\s*(.*?)\s*<([^>]+)>\s*$/', $raw, $m)) {
            return [trim($m[1], " \"'"), trim($m[2])];
        }
        return ['', trim($raw)];
    }

    private static function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    private static function logMail(string $to, string $subject, string $text): void
    {
        $line = sprintf(
            "[%s] to=%s subject=%s\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $text,
            str_repeat('-', 60)
        );
        @file_put_contents(BASE_PATH . '/storage/logs/mail.log', $line, FILE_APPEND | LOCK_EX);
    }
}
