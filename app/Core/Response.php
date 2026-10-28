<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    private array $headers = [];

    public function __construct(
        public string $body = '',
        public int $status = 200,
        array $headers = [],
    ) {
        $this->headers = $headers + ['Content-Type' => 'text/html; charset=UTF-8'];
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function text(string $body, string $type = 'text/plain', int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => $type . '; charset=UTF-8']);
    }

    /**
     * Redirect to an INTERNAL path only (open-redirect protection).
     * Paths are relative to the application base ("/admin/pages").
     */
    public static function redirect(string $path, int $status = 303): self
    {
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || str_contains($path, '\\')) {
            $path = '/';
        }
        return new self('', $status, ['Location' => url($path)]);
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . str_replace(["\r", "\n"], '', $value));
            }
        }
        echo $this->body;
    }
}
