<?php
/** @var array $flash */
$icons = ['success' => 'circle-check', 'error' => 'triangle-alert', 'warning' => 'triangle-alert', 'info' => 'info'];
foreach ($flash as $type => $messages):
    foreach ((array) $messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
        <?= icon($icons[$type] ?? 'info') ?><p><?= e($msg) ?></p>
    </div>
<?php endforeach; endforeach; ?>
