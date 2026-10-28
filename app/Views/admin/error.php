<?php
/** @var int $status @var ?array $user */
$texts = [
    403 => ['Kein Zugriff', 'Für diesen Bereich fehlen Ihnen die Berechtigungen. Bitte wenden Sie sich an einen Administrator.'],
    404 => ['Nicht gefunden', 'Die angeforderte Seite oder der Datensatz existiert nicht (mehr).'],
    405 => ['Nicht erlaubt', 'Diese Aktion ist so nicht möglich.'],
    419 => ['Sitzung abgelaufen', 'Bitte laden Sie die Seite neu und versuchen Sie es erneut.'],
    500 => ['Fehler', 'Ein unerwarteter Fehler ist aufgetreten. Details stehen im Fehlerprotokoll (storage/logs).'],
];
[$title, $text] = $texts[$status] ?? $texts[500];
?>
<!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title><?= e($title) ?> – Verwaltung</title><link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>"></head>
<body class="admin admin--guest">
<main class="auth">
    <div class="auth__card">
        <p class="auth__code"><?= (int) $status ?></p>
        <h1 class="auth__title"><?= e($title) ?></h1>
        <p><?= e($text) ?></p>
        <p class="btn-row"><a class="btn btn--primary" href="<?= e(url($user ? '/admin' : '/admin/login')) ?>">Zur Übersicht</a></p>
    </div>
</main>
</body>
</html>
