<?php
/** @var array $page @var array $blocks @var array $revisions @var array $navRefs @var array $old @var array $errors */
use App\Repositories\PageRepository;

$status = PageRepository::displayStatus($page);
?>
<div class="page-head">
    <div>
        <p class="page-head__meta">
            <span class="pill pill--<?= e($status) ?>"><?= e(status_label($status)) ?></span>
            <code><?= e(PageRepository::path($page)) ?></code>
            <?php if ($page['system_key']): ?><span class="badge">Systemseite</span><?php endif; ?>
        </p>
    </div>
    <div class="btn-row">
        <a class="btn btn--secondary" href="<?= e(url('/admin/pages/' . $page['id'] . '/preview')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Vorschau</a>
        <?php if (PageRepository::isLive($page)): ?><a class="btn btn--ghost" href="<?= e(url(PageRepository::path($page))) ?>" target="_blank" rel="noopener"><?= icon('external-link') ?> Live ansehen</a><?php endif; ?>
    </div>
</div>

<div class="editor-grid">
    <section class="card" id="bloecke" aria-labelledby="blocks-title">
        <div class="card__head">
            <h2 class="card__title" id="blocks-title">Inhaltsblöcke</h2>
            <span class="muted small">Reihenfolge per Pfeil oder Ziehen ändern</span>
        </div>
        <?= \App\Core\View::partial('admin/partials/blocks', ['blocks' => $blocks, 'owner' => 'page', 'ownerId' => (int) $page['id']]) ?>
    </section>

    <aside class="stack">
        <form method="post" action="<?= e(url('/admin/pages/' . $page['id'])) ?>" class="card form-stack">
            <?= csrf_field() ?>
            <h2 class="card__title">Seiteneinstellungen</h2>
            <?= \App\Core\View::partial('admin/pages/settings-fields', ['page' => $page, 'old' => $old, 'errors' => $errors]) ?>
            <button class="btn btn--primary btn--block" type="submit"><?= icon('save') ?> Einstellungen speichern</button>
        </form>

        <section class="card">
            <h2 class="card__title">Versionen</h2>
            <?php if (!$revisions): ?>
                <p class="empty">Noch keine Versionen.</p>
            <?php else: ?>
                <p class="muted small">Jede Änderung erzeugt eine Version (die letzten <?= \App\Services\Revisions::KEEP ?> bleiben erhalten).</p>
                <ul class="mini-list mini-list--scroll">
                    <?php foreach ($revisions as $i => $rev): ?>
                        <li>
                            <span><strong><?= e($rev['note'] ?: 'Änderung') ?></strong><span><?= e(format_date($rev['created_at'], true)) ?> · <?= e($rev['admin_name'] ?? '–') ?></span></span>
                            <?php if ($i > 0): ?>
                                <form method="post" action="<?= e(url('/admin/pages/' . $page['id'] . '/revisions/' . $rev['id'] . '/restore')) ?>" data-confirm="Diese Version wiederherstellen? Der aktuelle Stand wird vorher als Version gesichert.">
                                    <?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit"><?= icon('rotate-ccw') ?> Wiederherstellen</button>
                                </form>
                            <?php else: ?><span class="badge badge--green">Aktuell</span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2 class="card__title">Weitere Aktionen</h2>
            <form method="post" action="<?= e(url('/admin/pages/' . $page['id'] . '/duplicate')) ?>">
                <?= csrf_field() ?><button class="btn btn--secondary btn--block" type="submit"><?= icon('copy') ?> Seite duplizieren</button>
            </form>
            <?php if ($page['system_key'] === null): ?>
                <?php if ($navRefs): ?>
                    <p class="muted small">Löschen nicht möglich: Die Seite ist im Menü verlinkt (<?= e(implode(', ', array_column($navRefs, 'label'))) ?>).</p>
                <?php else: ?>
                    <form method="post" action="<?= e(url('/admin/pages/' . $page['id'] . '/delete')) ?>" data-confirm="Seite „<?= e($page['title']) ?>“ mit allen Inhaltsblöcken endgültig löschen?">
                        <?= csrf_field() ?><button class="btn btn--danger btn--block" type="submit"><?= icon('trash-2') ?> Seite löschen</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p class="muted small">Systemseiten können nicht gelöscht werden.</p>
            <?php endif; ?>
        </section>
    </aside>
</div>
