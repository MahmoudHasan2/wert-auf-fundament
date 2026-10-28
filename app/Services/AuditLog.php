<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\ErrorHandler;
use App\Core\Request;
use App\Security\Auth;

/** Lightweight audit trail. Never pass passwords, tokens or enquiry contents in $summary. */
final class AuditLog
{
    public const LABELS = [
        'login.success' => 'Anmeldung', 'login.failed' => 'Anmeldung fehlgeschlagen', 'login.throttled' => 'Anmeldung gesperrt',
        'logout' => 'Abmeldung', 'password.changed' => 'Passwort geändert', 'password.reset' => 'Passwort zurückgesetzt',
        'install' => 'Installation',
    ];

    public static function record(string $action, string $entityType = '', ?int $entityId = null, string $summary = '', ?Request $request = null, string $actorName = ''): void
    {
        try {
            $user = Auth::user();
            DB::insert('audit_log', [
                'admin_id'    => $user['id'] ?? null,
                'admin_name'  => mb_substr($user['name'] ?? $actorName, 0, 120),
                'action'      => mb_substr($action, 0, 60),
                'entity_type' => mb_substr($entityType, 0, 40),
                'entity_id'   => $entityId,
                'summary'     => mb_substr($summary, 0, 255),
                'ip_anon'     => $request ? anon_ip($request->ip()) : '',
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
        }
    }
}
