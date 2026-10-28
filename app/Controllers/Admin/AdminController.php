<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Security\Auth;
use App\Services\AuditLog;
use App\Support\Paginator;

/**
 * Base class for all admin controllers. Authentication, permission and CSRF checks
 * are already enforced centrally in App\Core\Kernel before a controller is reached.
 */
abstract class AdminController
{
    protected ?array $user;

    public function __construct(protected Request $request)
    {
        $this->user = Auth::user();
    }

    protected function view(string $template, array $data = [], string $title = ''): Response
    {
        $old = Session::pullOld();
        return Response::html(View::render('admin/' . $template, $data + [
            'title' => $title,
            'user' => $this->user,
            'flash' => Session::pullFlash(),
            'old' => $old['old'],
            'errors' => $old['errors'],
            'request' => $this->request,
            'newEnquiries' => $this->user ? (int) DB::value("SELECT COUNT(*) FROM enquiries WHERE status = 'new'") : 0,
        ], 'admin/layout'));
    }

    protected function redirect(string $path, ?string $type = null, ?string $message = null): Response
    {
        if ($type !== null && $message !== null) {
            Session::flash($type, $message);
        }
        return Response::redirect($path);
    }

    /** Redirect back to a form with input and validation errors. */
    protected function invalid(string $path, array $errors, ?string $message = null): Response
    {
        Session::flashInput($this->request->post, $errors);
        Session::flash('error', $message ?? 'Bitte prüfen Sie die markierten Felder.');
        return Response::redirect($path);
    }

    protected function audit(string $action, string $type, ?int $id, string $summary): void
    {
        AuditLog::record($action, $type, $id, $summary, $this->request);
    }

    protected function paginator(int $total, int $perPage = 25): Paginator
    {
        return new Paginator($total, max(1, $this->request->qInt('seite', 1)), $perPage);
    }

    protected function requirePerm(string $perm): void
    {
        if (!Auth::can($perm)) {
            throw HttpException::forbidden();
        }
    }

    protected function findOr404(?array $row): array
    {
        if (!$row) {
            throw HttpException::notFound();
        }
        return $row;
    }

    /** Normalises a datetime-local input ("2026-10-20T08:00") to SQL or null. */
    protected function dateInput(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
