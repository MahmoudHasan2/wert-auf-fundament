<?php
declare(strict_types=1);

namespace App\Core;

use App\Security\Auth;
use App\Services\Installer;

/**
 * Request lifecycle. Access control is enforced HERE, centrally, for every admin route:
 *  - every non-GET request under /admin must carry a valid CSRF token
 *  - every admin route declares a permission; "guest" routes are the only ones without login
 */
final class Kernel
{
    public function handle(Request $request): Response
    {
        try {
            $response = $this->dispatch($request);
        } catch (HttpException $e) {
            $response = $this->errorResponse($request, $e->status);
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            $response = $this->errorResponse($request, 500);
        }
        $this->securityHeaders($request, $response);
        return $response;
    }

    private function dispatch(Request $request): Response
    {
        if (config('app.force_https') && !$request->isHttps() && PHP_SAPI !== 'cli-server' && PHP_SAPI !== 'cli') {
            return new Response('', 301, ['Location' => abs_url($request->path) . (empty($request->query) ? '' : '?' . http_build_query($request->query))]);
        }

        // Canonical paths: lowercase, no trailing slash (Request already trims).
        $router = require BASE_PATH . '/app/routes.php';

        if ($request->isAdmin()) {
            Session::start($request);
        }

        if (!Installer::isInstalled() && !str_starts_with($request->path, '/admin/install')) {
            if ($request->isAdmin()) {
                return Response::redirect('/admin/install');
            }
            return Response::html(View::render('errors/not-installed'), 503);
        }

        $match = $router->match($request->method, $request->path);
        if ($match === null) {
            throw HttpException::notFound();
        }
        if ($match === false) {
            throw new HttpException(405, 'Method Not Allowed');
        }

        if ($request->isAdmin()) {
            if ($request->method !== 'GET' && !Csrf::verify($request)) {
                if ($request->wantsJson()) {
                    return Response::json(['error' => 'Sicherheitstoken ungültig. Bitte Seite neu laden.'], 419);
                }
                Session::flash('error', 'Ihre Sitzung ist abgelaufen oder das Formular ist ungültig. Bitte versuchen Sie es erneut.');
                return Response::redirect($this->safeBack($request));
            }
            $perm = $match['perm'];
            if ($perm === null) {
                throw HttpException::forbidden(); // misconfigured route: deny by default
            }
            if ($perm !== 'guest' && $perm !== 'install') {
                $user = Auth::user();
                if ($user === null) {
                    if ($request->wantsJson()) {
                        return Response::json(['error' => 'Nicht angemeldet.'], 401);
                    }
                    if ($request->method === 'GET') {
                        Session::set('_intended', $request->path);
                    }
                    return Response::redirect('/admin/login');
                }
                if ($perm !== 'auth' && !Auth::can($perm)) {
                    throw HttpException::forbidden();
                }
            }
        } elseif ($match['perm'] !== 'public') {
            throw HttpException::notFound();
        }

        [$class, $method] = $match['handler'];
        $controller = new $class($request);
        $response = $controller->$method(...array_values($match['params']));
        return $response instanceof Response ? $response : Response::html((string) $response);
    }

    private function safeBack(Request $request): string
    {
        $ref = $request->header('Referer');
        $path = (string) (parse_url($ref, PHP_URL_PATH) ?? '');
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $host = parse_url($ref, PHP_URL_HOST);
        if ($host && $host !== ($request->server['HTTP_HOST'] ?? '')) {
            return '/admin';
        }
        return str_starts_with($path, '/admin') ? $path : '/admin';
    }

    private function errorResponse(Request $request, int $status): Response
    {
        $status = in_array($status, [403, 404, 405, 419, 429, 500, 503], true) ? $status : 500;
        try {
            if ($request->isAdmin()) {
                $html = View::render('admin/error', ['status' => $status, 'user' => Installer::isInstalled() ? Auth::user() : null]);
            } else {
                $html = \App\Controllers\Site\SiteController::renderError($request, $status);
            }
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            $html = View::partial('errors/500-static');
        }
        return Response::html($html, $status);
    }

    private function securityHeaders(Request $request, Response $response): void
    {
        $csp = "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; "
             . "connect-src 'self'; frame-src 'none'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";
        $response->header('Content-Security-Policy', $csp)
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()')
            ->header('Cross-Origin-Opener-Policy', 'same-origin');
        if (config('app.hsts') && $request->isHttps()) {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        if ($request->isAdmin()) {
            $response->header('X-Robots-Tag', 'noindex, nofollow')
                ->header('Cache-Control', 'no-store, max-age=0');
        }
    }
}
