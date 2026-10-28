<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorHandler;

/**
 * Plain-text mailer without external dependencies.
 * Transports: "smtp" (SSL or STARTTLS, AUTH LOGIN), "mail" (PHP mail()), "none".
 * Credentials come exclusively from env.php.
 */
final class Mailer
{
    public static function transport(): string
    {
        $t = (string) config('mail.transport', 'none');
        return in_array($t, ['smtp', 'mail'], true) ? $t : 'none';
    }

    public static function isConfigured(): bool
    {
        $t = self::transport();
        if ($t === 'none' || !filter_var((string) config('mail.from_address'), FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return $t === 'mail' || (string) config('mail.host') !== '';
    }

    /**
     * @throws \RuntimeException with a generic, safe message on failure (details go to the log)
     */
    public static function send(string $to, string $subject, string $body, ?string $replyTo = null): void
    {
        if (!self::isConfigured()) {
            throw new \RuntimeException('E-Mail-Versand ist nicht konfiguriert.');
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Ungültige Empfängeradresse.');
        }
        if ($replyTo !== null && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $replyTo = null;
        }
        $from = (string) config('mail.from_address');
        $fromName = self::clean((string) config('mail.from_name', ''));
        $subject = self::clean($subject);

        $headers = [
            'Date' => date('r'),
            'From' => ($fromName !== '' ? mb_encode_mimeheader($fromName, 'UTF-8', 'Q') . ' ' : '') . '<' . $from . '>',
            'Message-ID' => '<' . bin2hex(random_bytes(12)) . '@' . (substr(strrchr($from, '@') ?: '@localhost', 1)) . '>',
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => 'quoted-printable',
        ];
        if ($replyTo) {
            $headers['Reply-To'] = '<' . $replyTo . '>';
        }
        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'Q');
        $encodedBody = quoted_printable_encode(str_replace(["\r\n", "\r"], "\n", $body));
        $encodedBody = str_replace("\n", "\r\n", str_replace("\r\n", "\n", $encodedBody));

        try {
            if (self::transport() === 'mail') {
                $h = '';
                foreach ($headers as $k => $v) {
                    if ($k !== 'Date') {
                        $h .= $k . ': ' . $v . "\r\n";
                    }
                }
                if (!mail($to, $encodedSubject, $encodedBody, rtrim($h), '-f' . $from)) {
                    throw new \RuntimeException('mail() returned false');
                }
                return;
            }
            self::smtp($from, $to, $headers + ['To' => '<' . $to . '>', 'Subject' => $encodedSubject], $encodedBody);
        } catch (\Throwable $e) {
            ErrorHandler::log('Mail delivery failed: ' . $e->getMessage(), 'WARNING');
            throw new \RuntimeException('Die E-Mail konnte nicht versendet werden. Details stehen im Fehlerprotokoll.');
        }
    }

    private static function clean(string $v): string
    {
        return trim(str_replace(["\r", "\n", "\0"], ' ', $v));
    }

    private static function smtp(string $from, string $to, array $headers, string $body): void
    {
        $host = (string) config('mail.host');
        $port = (int) config('mail.port', 465);
        $enc = (string) config('mail.encryption', 'ssl');
        $timeout = (int) config('mail.timeout', 15);
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
        $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");
        }
        stream_set_timeout($fp, $timeout);
        try {
            self::expect($fp, 220);
            $ehloHost = preg_replace('/[^a-z0-9.-]/i', '', (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost'));
            self::cmd($fp, 'EHLO ' . $ehloHost, 250);
            if ($enc === 'tls') {
                self::cmd($fp, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new \RuntimeException('STARTTLS failed');
                }
                self::cmd($fp, 'EHLO ' . $ehloHost, 250);
            }
            $user = (string) config('mail.username');
            if ($user !== '') {
                self::cmd($fp, 'AUTH LOGIN', 334);
                self::cmd($fp, base64_encode($user), 334);
                self::cmd($fp, base64_encode((string) config('mail.password')), 235, true);
            }
            self::cmd($fp, 'MAIL FROM:<' . $from . '>', 250);
            self::cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::cmd($fp, 'DATA', 354);
            $data = '';
            foreach ($headers as $k => $v) {
                $data .= $k . ': ' . $v . "\r\n";
            }
            $data .= "\r\n" . preg_replace('/^\./m', '..', $body) . "\r\n.";
            self::cmd($fp, $data, 250);
            self::cmd($fp, 'QUIT', 221);
        } finally {
            fclose($fp);
        }
    }

    private static function cmd($fp, string $line, int|array $expect, bool $secret = false): void
    {
        fwrite($fp, $line . "\r\n");
        self::expect($fp, $expect, $secret ? '[credentials]' : substr($line, 0, 20));
    }

    private static function expect($fp, int|array $codes, string $context = 'greeting'): void
    {
        $codes = (array) $codes;
        $response = '';
        while (($line = fgets($fp, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException("SMTP unexpected response after $context: " . trim($response));
        }
    }
}
