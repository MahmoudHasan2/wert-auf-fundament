<?php /** @var int $status @var string $title @var string $text */ ?>
<section class="section error-page">
    <div class="container narrow">
        <p class="error-page__code" aria-hidden="true"><?= (int) $status ?></p>
        <h1 class="page-title"><?= e($title) ?></h1>
        <p class="lead"><?= e($text) ?></p>
        <div class="btn-row">
            <a class="btn btn--primary" href="<?= e(url('/')) ?>">Zur Startseite</a>
            <a class="btn btn--outline" href="<?= e(url('/kontakt')) ?>">Kontakt aufnehmen</a>
        </div>
    </div>
</section>
