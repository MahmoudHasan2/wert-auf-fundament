<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal router. Placeholders: {slug} = [a-z0-9-]+, {id} = digits, {any} = one path segment.
 * Every route under /admin declares the permission it needs (null = public admin route such as login).
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:array, perm:?string, keys:array}> */
    private array $routes = [];

    public function get(string $pattern, array $handler, ?string $perm = null): void
    {
        $this->add('GET', $pattern, $handler, $perm);
    }

    public function post(string $pattern, array $handler, ?string $perm = null): void
    {
        $this->add('POST', $pattern, $handler, $perm);
    }

    private function add(string $method, string $pattern, array $handler, ?string $perm): void
    {
        $keys = [];
        $regex = preg_replace_callback('/\{([a-z][a-z0-9]*)\}/', function ($m) use (&$keys) {
            $keys[] = $m[1];
            return match ($m[1]) {
                'id', 'id2' => '(\d+)',
                'slug', 'parent', 'child' => '([a-z0-9]+(?:-[a-z0-9]+)*)',
                default => '([^/]+)',
            };
        }, $pattern);
        $this->routes[] = ['method' => $method, 'regex' => '#^' . $regex . '$#', 'handler' => $handler, 'perm' => $perm, 'keys' => $keys];
    }

    /** @return array{handler:array, params:array, perm:?string}|null|false  false = path exists but method not allowed */
    public function match(string $method, string $path): array|null|false
    {
        $pathMatched = false;
        foreach ($this->routes as $r) {
            if (!preg_match($r['regex'], $path, $m)) {
                continue;
            }
            $pathMatched = true;
            if ($r['method'] !== $method) {
                continue;
            }
            array_shift($m);
            $params = [];
            foreach ($r['keys'] as $i => $k) {
                $params[$k] = ctype_digit($m[$i]) && in_array($k, ['id', 'id2'], true) ? (int) $m[$i] : $m[$i];
            }
            return ['handler' => $r['handler'], 'params' => $params, 'perm' => $r['perm']];
        }
        return $pathMatched ? false : null;
    }
}
