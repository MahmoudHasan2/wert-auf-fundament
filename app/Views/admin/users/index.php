<?php
/** @var array $users @var array $user */
use App\Security\Auth;
?>
<div class="toolbar">
    <p class="muted">Administratoren haben alle Rechte. Redakteure können Inhalte, Medien und Anfragen bearbeiten, aber keine Rechtstexte, Einstellungen, Benutzer oder Backups.</p>
    <a class="btn btn--primary" href="<?= e(url('/admin/users/create')) ?>"><?= icon('plus') ?> Neuer Benutzer</a>
</div>
<div class="card card--flush">
    <table class="table">
        <thead><tr><th>Name</th><th>Rolle</th><th>Status</th><th>Letzte Anmeldung</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td data-label="Name"><strong><?= e($u['name']) ?></strong><?= (int) $u['id'] === (int) $user['id'] ? ' <span class="badge">Sie</span>' : '' ?><br><span class="muted small"><?= e($u['email']) ?></span></td>
                <td data-label="Rolle"><?= e(explode(' (', Auth::ROLE_LABELS[$u['role']] ?? $u['role'])[0]) ?></td>
                <td data-label="Status"><span class="pill pill--<?= (int) $u['is_active'] ? 'published' : 'archived' ?>"><?= (int) $u['is_active'] ? 'Aktiv' : 'Deaktiviert' ?></span></td>
                <td data-label="Letzte Anmeldung"><?= e(format_date($u['last_login_at'], true)) ?></td>
                <td class="table__actions">
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/users/' . $u['id'])) ?>"><?= icon('pencil') ?> Bearbeiten</a>
                    <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                        <form method="post" action="<?= e(url('/admin/users/' . $u['id'] . '/delete')) ?>" data-confirm="Benutzer „<?= e($u['name']) ?>“ löschen?"><?= csrf_field() ?><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= icon('trash-2') ?> Löschen</button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
