<?php
declare(strict_types=1);

use App\Controllers\Admin as A;
use App\Controllers\Site as S;
use App\Core\Router;

/*
 * Permission values:
 *   public  – public website route
 *   guest   – admin route reachable without login (login, password reset)
 *   install – installer (self-protected: lock file + install token)
 *   auth    – any logged-in administrator
 *   content | media | enquiries | legal | settings | users | audit | backup | system – see App\Security\Auth::ROLES
 */
$r = new Router();

// ---------------------------------------------------------------- public website
$r->get('/', [S\PageController::class, 'home'], 'public');
$r->get('/sitemap.xml', [S\SeoController::class, 'sitemap'], 'public');
$r->get('/robots.txt', [S\SeoController::class, 'robots'], 'public');
$r->get('/leistungen/{parent}/{child}', [S\ServiceController::class, 'child'], 'public');
$r->get('/leistungen/{slug}', [S\ServiceController::class, 'show'], 'public');
$r->get('/referenzen/{slug}', [S\ProjectController::class, 'show'], 'public');
$r->post('/kontakt/anfrage', [S\ContactController::class, 'submit'], 'public');

// ---------------------------------------------------------------- installer & authentication
$r->get('/admin/install', [A\InstallController::class, 'form'], 'install');
$r->post('/admin/install', [A\InstallController::class, 'run'], 'install');
$r->post('/admin/install/config', [A\InstallController::class, 'config'], 'install');
$r->get('/admin/login', [A\AuthController::class, 'loginForm'], 'guest');
$r->post('/admin/login', [A\AuthController::class, 'login'], 'guest');
$r->post('/admin/logout', [A\AuthController::class, 'logout'], 'auth');
$r->get('/admin/passwort-vergessen', [A\AuthController::class, 'forgotForm'], 'guest');
$r->post('/admin/passwort-vergessen', [A\AuthController::class, 'forgot'], 'guest');
$r->get('/admin/passwort-zuruecksetzen', [A\AuthController::class, 'resetForm'], 'guest');
$r->post('/admin/passwort-zuruecksetzen', [A\AuthController::class, 'reset'], 'guest');
$r->get('/admin/konto', [A\AuthController::class, 'account'], 'auth');
$r->post('/admin/konto', [A\AuthController::class, 'changePassword'], 'auth');

// ---------------------------------------------------------------- dashboard
$r->get('/admin', [A\DashboardController::class, 'index'], 'auth');

// ---------------------------------------------------------------- pages & blocks
$r->get('/admin/pages', [A\PageAdminController::class, 'index'], 'content');
$r->get('/admin/pages/create', [A\PageAdminController::class, 'create'], 'content');
$r->post('/admin/pages', [A\PageAdminController::class, 'store'], 'content');
$r->get('/admin/pages/{id}', [A\PageAdminController::class, 'edit'], 'content');
$r->post('/admin/pages/{id}', [A\PageAdminController::class, 'update'], 'content');
$r->post('/admin/pages/{id}/delete', [A\PageAdminController::class, 'delete'], 'content');
$r->post('/admin/pages/{id}/duplicate', [A\PageAdminController::class, 'duplicate'], 'content');
$r->get('/admin/pages/{id}/preview', [A\PageAdminController::class, 'preview'], 'content');
$r->post('/admin/pages/{id}/revisions/{id2}/restore', [A\PageAdminController::class, 'restore'], 'content');
$r->get('/admin/impressum', [A\PageAdminController::class, 'imprint'], 'content');
$r->get('/admin/datenschutz', [A\PageAdminController::class, 'privacy'], 'content');

$r->post('/admin/blocks/add', [A\BlockAdminController::class, 'add'], 'content');
$r->post('/admin/blocks/reorder', [A\BlockAdminController::class, 'reorder'], 'content');
$r->get('/admin/blocks/{id}', [A\BlockAdminController::class, 'edit'], 'content');
$r->post('/admin/blocks/{id}', [A\BlockAdminController::class, 'update'], 'content');
$r->post('/admin/blocks/{id}/move', [A\BlockAdminController::class, 'move'], 'content');
$r->post('/admin/blocks/{id}/toggle', [A\BlockAdminController::class, 'toggle'], 'content');
$r->post('/admin/blocks/{id}/duplicate', [A\BlockAdminController::class, 'duplicate'], 'content');
$r->post('/admin/blocks/{id}/delete', [A\BlockAdminController::class, 'delete'], 'content');
$r->get('/admin/library', [A\BlockAdminController::class, 'library'], 'content');
$r->post('/admin/library', [A\BlockAdminController::class, 'createLibrary'], 'content');

// ---------------------------------------------------------------- services
$r->get('/admin/services', [A\ServiceAdminController::class, 'index'], 'content');
$r->get('/admin/services/create', [A\ServiceAdminController::class, 'create'], 'content');
$r->post('/admin/services', [A\ServiceAdminController::class, 'store'], 'content');
$r->get('/admin/services/{id}', [A\ServiceAdminController::class, 'edit'], 'content');
$r->post('/admin/services/{id}', [A\ServiceAdminController::class, 'update'], 'content');
$r->post('/admin/services/{id}/move', [A\ServiceAdminController::class, 'move'], 'content');
$r->post('/admin/services/{id}/delete', [A\ServiceAdminController::class, 'delete'], 'content');
$r->get('/admin/services/{id}/preview', [A\ServiceAdminController::class, 'preview'], 'content');

// ---------------------------------------------------------------- projects
$r->get('/admin/projects', [A\ProjectAdminController::class, 'index'], 'content');
$r->get('/admin/projects/create', [A\ProjectAdminController::class, 'create'], 'content');
$r->post('/admin/projects', [A\ProjectAdminController::class, 'store'], 'content');
$r->get('/admin/projects/{id}', [A\ProjectAdminController::class, 'edit'], 'content');
$r->post('/admin/projects/{id}', [A\ProjectAdminController::class, 'update'], 'content');
$r->post('/admin/projects/{id}/move', [A\ProjectAdminController::class, 'move'], 'content');
$r->post('/admin/projects/{id}/delete', [A\ProjectAdminController::class, 'delete'], 'content');
$r->get('/admin/projects/{id}/preview', [A\ProjectAdminController::class, 'preview'], 'content');

// ---------------------------------------------------------------- media & galleries
$r->get('/admin/media', [A\MediaAdminController::class, 'index'], 'media');
$r->get('/admin/media/picker', [A\MediaAdminController::class, 'picker'], 'media');
$r->post('/admin/media/upload', [A\MediaAdminController::class, 'upload'], 'media');
$r->get('/admin/media/{id}', [A\MediaAdminController::class, 'edit'], 'media');
$r->post('/admin/media/{id}', [A\MediaAdminController::class, 'update'], 'media');
$r->post('/admin/media/{id}/replace', [A\MediaAdminController::class, 'replace'], 'media');
$r->post('/admin/media/{id}/delete', [A\MediaAdminController::class, 'delete'], 'media');

$r->get('/admin/galleries', [A\GalleryAdminController::class, 'index'], 'content');
$r->post('/admin/galleries', [A\GalleryAdminController::class, 'store'], 'content');
$r->get('/admin/galleries/{id}', [A\GalleryAdminController::class, 'edit'], 'content');
$r->post('/admin/galleries/{id}', [A\GalleryAdminController::class, 'update'], 'content');
$r->post('/admin/galleries/{id}/items', [A\GalleryAdminController::class, 'addItems'], 'content');
$r->post('/admin/galleries/{id}/items/{id2}/move', [A\GalleryAdminController::class, 'moveItem'], 'content');
$r->post('/admin/galleries/{id}/items/{id2}/delete', [A\GalleryAdminController::class, 'removeItem'], 'content');
$r->post('/admin/galleries/{id}/delete', [A\GalleryAdminController::class, 'delete'], 'content');

// ---------------------------------------------------------------- navigation
$r->get('/admin/navigation', [A\NavigationAdminController::class, 'index'], 'content');
$r->post('/admin/navigation', [A\NavigationAdminController::class, 'store'], 'content');
$r->get('/admin/navigation/{id}', [A\NavigationAdminController::class, 'edit'], 'content');
$r->post('/admin/navigation/{id}', [A\NavigationAdminController::class, 'update'], 'content');
$r->post('/admin/navigation/{id}/move', [A\NavigationAdminController::class, 'move'], 'content');
$r->post('/admin/navigation/{id}/delete', [A\NavigationAdminController::class, 'delete'], 'content');

// ---------------------------------------------------------------- FAQ
$r->get('/admin/faq', [A\FaqAdminController::class, 'index'], 'content');
$r->get('/admin/faq/create', [A\FaqAdminController::class, 'create'], 'content');
$r->post('/admin/faq', [A\FaqAdminController::class, 'store'], 'content');
$r->get('/admin/faq/{id}', [A\FaqAdminController::class, 'edit'], 'content');
$r->post('/admin/faq/{id}', [A\FaqAdminController::class, 'update'], 'content');
$r->post('/admin/faq/{id}/move', [A\FaqAdminController::class, 'move'], 'content');
$r->post('/admin/faq/{id}/delete', [A\FaqAdminController::class, 'delete'], 'content');
$r->post('/admin/faq-categories', [A\FaqAdminController::class, 'storeCategory'], 'content');
$r->post('/admin/faq-categories/{id}', [A\FaqAdminController::class, 'updateCategory'], 'content');
$r->post('/admin/faq-categories/{id}/delete', [A\FaqAdminController::class, 'deleteCategory'], 'content');

// ---------------------------------------------------------------- enquiries
$r->get('/admin/enquiries', [A\EnquiryAdminController::class, 'index'], 'enquiries');
$r->get('/admin/enquiries/export', [A\EnquiryAdminController::class, 'export'], 'settings');
$r->post('/admin/enquiries/purge', [A\EnquiryAdminController::class, 'purge'], 'enquiries');
$r->get('/admin/enquiries/{id}', [A\EnquiryAdminController::class, 'show'], 'enquiries');
$r->post('/admin/enquiries/{id}/status', [A\EnquiryAdminController::class, 'status'], 'enquiries');
$r->post('/admin/enquiries/{id}/notes', [A\EnquiryAdminController::class, 'note'], 'enquiries');
$r->post('/admin/enquiries/{id}/delete', [A\EnquiryAdminController::class, 'delete'], 'enquiries');

// ---------------------------------------------------------------- settings, users, audit, backups, system
$r->get('/admin/settings', [A\SettingsAdminController::class, 'edit'], 'settings');
$r->post('/admin/settings', [A\SettingsAdminController::class, 'update'], 'settings');

$r->get('/admin/users', [A\UserAdminController::class, 'index'], 'users');
$r->get('/admin/users/create', [A\UserAdminController::class, 'create'], 'users');
$r->post('/admin/users', [A\UserAdminController::class, 'store'], 'users');
$r->get('/admin/users/{id}', [A\UserAdminController::class, 'edit'], 'users');
$r->post('/admin/users/{id}', [A\UserAdminController::class, 'update'], 'users');
$r->post('/admin/users/{id}/delete', [A\UserAdminController::class, 'delete'], 'users');

$r->get('/admin/audit', [A\SystemAdminController::class, 'auditLog'], 'audit');

$r->get('/admin/backups', [A\BackupAdminController::class, 'index'], 'backup');
$r->post('/admin/backups/database', [A\BackupAdminController::class, 'database'], 'backup');
$r->post('/admin/backups/media', [A\BackupAdminController::class, 'media'], 'backup');
$r->get('/admin/backups/download/{any}', [A\BackupAdminController::class, 'download'], 'backup');
$r->post('/admin/backups/delete', [A\BackupAdminController::class, 'delete'], 'backup');
$r->post('/admin/backups/restore', [A\BackupAdminController::class, 'restore'], 'backup');

$r->get('/admin/system', [A\SystemAdminController::class, 'index'], 'system');
$r->post('/admin/system/migrate', [A\SystemAdminController::class, 'migrate'], 'system');
$r->post('/admin/system/test-mail', [A\SystemAdminController::class, 'testMail'], 'system');

// ---------------------------------------------------------------- CMS pages (catch-all, must stay last)
$r->get('/{slug}', [S\PageController::class, 'show'], 'public');

return $r;
