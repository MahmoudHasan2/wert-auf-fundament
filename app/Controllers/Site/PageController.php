<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\HttpException;
use App\Core\Response;
use App\Repositories\PageRepository;

final class PageController extends SiteController
{
    public function home(): Response
    {
        $page = PageRepository::system('home');
        if (!$page || !PageRepository::isLive($page)) {
            throw HttpException::notFound();
        }
        return self::renderPage($page, $this->request);
    }

    public function show(string $slug): Response
    {
        $page = PageRepository::findPublishedBySlug($slug);
        if (!$page) {
            throw HttpException::notFound();
        }
        if ($page['system_key'] === 'home') {
            return Response::redirect('/', 301);
        }
        return self::renderPage($page, $this->request);
    }
}
