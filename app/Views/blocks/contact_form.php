<?php
/** @var array $d @var array $block @var array $ctx */
use App\Core\View;
use App\Repositories\PageRepository;
use App\Security\FormToken;
use App\Services\Settings;

$state = View::shared('contactForm', ['errors' => [], 'old' => []]);
$errors = $state['errors'];
$old = $state['old'] + ['name' => '', 'email' => '', 'phone' => '', 'project_type' => '', 'preferred_contact' => '', 'location' => '', 'message' => '', 'privacy' => false];
$request = $ctx['request'] ?? null;
$sent = $request && ($request->query['gesendet'] ?? '') === '1' && !$errors;
$types = Settings::lines('contact_project_types');
if ($old['project_type'] === '' && $request && is_string($request->query['leistung'] ?? null) && in_array($request->query['leistung'], $types, true)) {
    $old['project_type'] = $request->query['leistung'];
}
$privacy = PageRepository::system('privacy');
$fieldError = function (string $name) use ($errors): string {
    return isset($errors[$name]) ? '<p class="field__error" id="err-' . e($name) . '">' . icon('triangle-alert', 'icon icon--xs') . ' ' . e($errors[$name]) . '</p>' : '';
};
$aria = fn (string $name): string => isset($errors[$name]) ? ' aria-invalid="true" aria-describedby="err-' . $name . '"' : '';
?>
<section class="section section--sand contact-form" id="kontaktformular">
    <div class="container contact-form__grid<?= $d['aside'] ? '' : ' contact-form__grid--single' ?>">
        <div class="contact-form__main">
            <?= View::partial('components/section-head', ['d' => $d]) ?>

            <?php if ($sent): ?>
                <div class="alert alert--success" role="status" tabindex="-1" data-autofocus>
                    <?= icon('circle-check') ?>
                    <div><strong>Vielen Dank für Ihre Anfrage.</strong><p><?= nl2br(e(setting('contact_success', 'Wir haben Ihre Nachricht erhalten und melden uns zeitnah bei Ihnen.'))) ?></p></div>
                </div>
            <?php else: ?>
                <?php if ($errors): ?>
                    <div class="alert alert--error" role="alert" tabindex="-1" data-autofocus>
                        <?= icon('triangle-alert') ?>
                        <div><strong>Bitte prüfen Sie Ihre Angaben.</strong>
                            <p><?= e($errors['_form'] ?? 'Einige Felder sind unvollständig oder fehlerhaft.') ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                <form class="form" method="post" action="<?= e(url('/kontakt/anfrage')) ?>" novalidate data-contact-form>
                    <input type="hidden" name="_token" value="<?= e(FormToken::issue('contact')) ?>">
                    <input type="hidden" name="_page" value="<?= (int) ($ctx['page']['id'] ?? 0) ?>">
                    <div class="hp" aria-hidden="true">
                        <label for="cf-website">Website (bitte leer lassen)</label>
                        <input type="text" id="cf-website" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="form__row">
                        <div class="field">
                            <label for="cf-name">Name <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="cf-name" name="name" required maxlength="120" autocomplete="name" value="<?= e($old['name']) ?>"<?= $aria('name') ?>>
                            <?= $fieldError('name') ?>
                        </div>
                        <div class="field">
                            <label for="cf-email">E-Mail-Adresse <span class="req" aria-hidden="true">*</span></label>
                            <input type="email" id="cf-email" name="email" required maxlength="190" autocomplete="email" value="<?= e($old['email']) ?>"<?= $aria('email') ?>>
                            <?= $fieldError('email') ?>
                        </div>
                    </div>
                    <div class="form__row">
                        <div class="field">
                            <label for="cf-phone">Telefon <span class="optional">(optional)</span></label>
                            <input type="tel" id="cf-phone" name="phone" maxlength="40" autocomplete="tel" value="<?= e($old['phone']) ?>"<?= $aria('phone') ?>>
                            <?= $fieldError('phone') ?>
                        </div>
                        <div class="field">
                            <label for="cf-location">Ort des Vorhabens <span class="optional">(optional)</span></label>
                            <input type="text" id="cf-location" name="location" maxlength="120" value="<?= e($old['location']) ?>">
                        </div>
                    </div>
                    <div class="form__row">
                        <?php if ($types): ?>
                        <div class="field">
                            <label for="cf-type">Art des Vorhabens <span class="optional">(optional)</span></label>
                            <select id="cf-type" name="project_type">
                                <option value="">Bitte wählen …</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= e($t) ?>"<?= $old['project_type'] === $t ? ' selected' : '' ?>><?= e($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <fieldset class="field field--choice">
                            <legend>Bevorzugte Kontaktaufnahme <span class="optional">(optional)</span></legend>
                            <div class="choice-row">
                                <label class="choice"><input type="radio" name="preferred_contact" value="email"<?= $old['preferred_contact'] === 'email' ? ' checked' : '' ?>> E-Mail</label>
                                <label class="choice"><input type="radio" name="preferred_contact" value="phone"<?= $old['preferred_contact'] === 'phone' ? ' checked' : '' ?>> Telefon</label>
                            </div>
                        </fieldset>
                    </div>
                    <div class="field">
                        <label for="cf-message">Ihre Nachricht <span class="req" aria-hidden="true">*</span></label>
                        <textarea id="cf-message" name="message" rows="7" required maxlength="5000"<?= $aria('message') ?> placeholder="Beschreiben Sie kurz Ihr Vorhaben, z. B. Umfang, gewünschter Zeitraum und Besonderheiten."><?= e($old['message']) ?></textarea>
                        <?= $fieldError('message') ?>
                    </div>
                    <div class="field field--check">
                        <label class="choice">
                            <input type="checkbox" name="privacy" value="1" required<?= $old['privacy'] ? ' checked' : '' ?><?= $aria('privacy') ?>>
                            <span>Ich habe die <?php if ($privacy): ?><a href="<?= e(url('/' . $privacy['slug'])) ?>" target="_blank" rel="noopener">Datenschutzhinweise</a><?php else: ?>Datenschutzhinweise<?php endif; ?> zur Kenntnis genommen. <span class="req" aria-hidden="true">*</span></span>
                        </label>
                        <?= $fieldError('privacy') ?>
                    </div>
                    <?php if (setting('contact_privacy_note') !== ''): ?>
                        <p class="form__note"><?= nl2br(e(setting('contact_privacy_note'))) ?></p>
                    <?php endif; ?>
                    <p class="form__required"><span aria-hidden="true">*</span> Pflichtfelder</p>
                    <button class="btn btn--primary btn--lg" type="submit" data-submit>Anfrage senden <?= icon('send') ?></button>
                </form>
            <?php endif; ?>
        </div>
        <?php if ($d['aside']): ?>
            <aside class="contact-form__aside" aria-label="Kontaktdaten">
                <?= View::partial('components/contact-cards', ['d' => ['show_registered' => '1', 'show_office' => '1', 'show_hours' => '1'], 'stacked' => true]) ?>
            </aside>
        <?php endif; ?>
    </div>
</section>
