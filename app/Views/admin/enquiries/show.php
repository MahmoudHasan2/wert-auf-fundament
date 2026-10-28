<?php
/** @var array $e @var array $notes */
use App\Controllers\Admin\EnquiryAdminController;
?>
<p class="breadcrumb-admin"><a href="<?= e(url('/admin/enquiries')) ?>"><?= icon('arrow-left') ?> Alle Anfragen</a></p>
<div class="editor-grid">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <h2 class="card__title"><?= e($e['name']) ?></h2>
                <span class="pill pill--<?= e($e['status']) ?>"><?= e(status_label($e['status'])) ?></span>
            </div>
            <dl class="dl dl--grid">
                <div><dt>Eingang</dt><dd><?= e(format_date($e['created_at'], true)) ?></dd></div>
                <div><dt>E-Mail</dt><dd><a href="mailto:<?= e($e['email']) ?>"><?= e($e['email']) ?></a></dd></div>
                <div><dt>Telefon</dt><dd><?= $e['phone'] !== '' ? '<a href="' . e(tel_href($e['phone'])) . '">' . e($e['phone']) . '</a>' : '–' ?></dd></div>
                <div><dt>Bevorzugter Kontakt</dt><dd><?= e(['email' => 'E-Mail', 'phone' => 'Telefon'][$e['preferred_contact']] ?? '–') ?></dd></div>
                <div><dt>Art des Vorhabens</dt><dd><?= e($e['project_type'] ?: '–') ?></dd></div>
                <div><dt>Ort des Vorhabens</dt><dd><?= e($e['location'] ?: '–') ?></dd></div>
                <div><dt>Datenschutzhinweis bestätigt</dt><dd><?= e(format_date($e['privacy_ack_at'], true)) ?></dd></div>
                <div><dt>E-Mail-Benachrichtigung</dt><dd><?= (int) $e['mail_sent'] ? 'versendet' : 'nicht versendet' ?></dd></div>
            </dl>
            <h3 class="card__subtitle">Nachricht</h3>
            <div class="message"><?= nl2br(e($e['message'])) ?></div>
            <p class="btn-row">
                <a class="btn btn--primary" href="mailto:<?= e($e['email']) ?>?subject=<?= rawurlencode('Ihre Anfrage bei ' . setting('company_name')) ?>"><?= icon('mail') ?> Per E-Mail antworten</a>
                <?php if ($e['phone'] !== ''): ?><a class="btn btn--secondary" href="<?= e(tel_href($e['phone'])) ?>"><?= icon('phone') ?> Anrufen</a><?php endif; ?>
            </p>
        </section>
        <section class="card" id="notizen">
            <h2 class="card__title">Interne Notizen</h2>
            <?php if (!$notes): ?><p class="empty">Noch keine Notizen.</p><?php else: ?>
                <ul class="notes">
                    <?php foreach ($notes as $n): ?><li><p><?= nl2br(e($n['note'])) ?></p><span class="muted small"><?= e($n['admin_name'] ?? '–') ?> · <?= e(format_date($n['created_at'], true)) ?></span></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/admin/enquiries/' . $e['id'] . '/notes')) ?>" class="form-stack">
                <?= csrf_field() ?>
                <label class="sr-only" for="en-note">Neue Notiz</label>
                <textarea id="en-note" name="note" rows="3" maxlength="3000" placeholder="Notiz hinzufügen, z. B. „Rückruf am … vereinbart“" required></textarea>
                <button class="btn btn--secondary" type="submit"><?= icon('plus') ?> Notiz speichern</button>
            </form>
        </section>
    </div>
    <aside class="stack">
        <form method="post" action="<?= e(url('/admin/enquiries/' . $e['id'] . '/status')) ?>" class="card form-stack">
            <?= csrf_field() ?>
            <h2 class="card__title">Status</h2>
            <?php foreach (EnquiryAdminController::STATUSES as $k => $label): ?>
                <label class="check"><input type="radio" name="status" value="<?= $k ?>"<?= $e['status'] === $k ? ' checked' : '' ?>> <?= e($label) ?></label>
            <?php endforeach; ?>
            <button class="btn btn--primary" type="submit">Status speichern</button>
        </form>
        <section class="card">
            <h2 class="card__title">Löschen</h2>
            <p class="muted small">Personenbezogene Daten nur so lange speichern, wie sie für die Bearbeitung benötigt werden.</p>
            <form method="post" action="<?= e(url('/admin/enquiries/' . $e['id'] . '/delete')) ?>" data-confirm="Anfrage inkl. Notizen endgültig löschen?"><?= csrf_field() ?><button class="btn btn--danger btn--block" type="submit"><?= icon('trash-2') ?> Anfrage löschen</button></form>
        </section>
    </aside>
</div>
