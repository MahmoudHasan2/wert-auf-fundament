<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Response;
use App\Security\Auth;
use App\Services\Compliance;
use App\Services\Mailer;

final class DashboardController extends AdminController
{
    public function index(): Response
    {
        $now = now();
        $stats = [
            ['Veröffentlichte Seiten', (int) DB::value("SELECT COUNT(*) FROM pages WHERE status = 'published' AND (published_at IS NULL OR published_at <= ?)", [$now]), '/admin/pages', 'file-text'],
            ['Veröffentlichte Leistungen', (int) DB::value("SELECT COUNT(*) FROM services WHERE status = 'published'"), '/admin/services', 'hard-hat'],
            ['Veröffentlichte Referenzen', (int) DB::value("SELECT COUNT(*) FROM projects WHERE status = 'published'"), '/admin/projects', 'building-2'],
            ['Neue Anfragen', (int) DB::value("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"), '/admin/enquiries?status=new', 'inbox'],
        ];
        return $this->view('dashboard', [
            'stats' => $stats,
            'enquiries' => Auth::can('enquiries') ? DB::all('SELECT id, name, project_type, status, created_at FROM enquiries ORDER BY created_at DESC LIMIT 5') : [],
            'activity' => DB::all("SELECT admin_name, action, entity_type, summary, created_at FROM audit_log WHERE action NOT LIKE 'login.%' ORDER BY id DESC LIMIT 8"),
            'checks' => Compliance::checks(),
            'mailOk' => Mailer::isConfigured(),
        ], 'Übersicht');
    }
}
