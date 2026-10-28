<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $files,
        public readonly array $server,
    ) {
    }

    public static function capture(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?? '/'));
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
        );
    }

    public function input(string $key, mixed $default = ''): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function str(string $key, int $max = 2000): string
    {
        $v = $this->post[$key] ?? '';
        if (!is_string($v)) {
            return '';
        }
        $v = trim(str_replace("\0", '', $v));
        return mb_substr($v, 0, $max);
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->post[$key] ?? null;
        return is_numeric($v) ? (int) $v : $default;
    }

    public function bool(string $key): bool
    {
        return !empty($this->post[$key]);
    }

    public function arr(string $key): array
    {
        $v = $this->post[$key] ?? [];
        return is_array($v) ? $v : [];
    }

    public function q(string $key, string $default = ''): string
    {
        $v = $this->query[$key] ?? $default;
        return is_string($v) ? mb_substr(trim($v), 0, 200) : $default;
    }

    public function qInt(string $key, int $default = 0): int
    {
        $v = $this->query[$key] ?? null;
        return is_numeric($v) ? (int) $v : $default;
    }

    public function file(string $key): ?array
    {
        $f = $this->files[$key] ?? null;
        return is_array($f) && isset($f['error']) ? $f : null;
    }

    /** Normalises multi-file uploads into a list of single file arrays. */
    public function fileList(string $key): array
    {
        $f = $this->files[$key] ?? null;
        if (!is_array($f) || !isset($f['name'])) {
            return [];
        }
        if (!is_array($f['name'])) {
            return [$f];
        }
        $out = [];
        foreach ($f['name'] as $i => $name) {
            $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
        return $out;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isHttps(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || (($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($this->server['SERVER_PORT'] ?? '') === '443');
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return mb_substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return (string) ($this->server[$key] ?? '');
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept'), 'application/json');
    }

    public function isAdmin(): bool
    {
        return $this->path === '/admin' || str_starts_with($this->path, '/admin/');
    }
}
