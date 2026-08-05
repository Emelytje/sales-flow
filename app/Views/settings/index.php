<?php
/** @var array $settings */
/** @var string $appUrl */
$s = static fn (string $k, $d = '') => e($settings[$k] ?? $d);
?>
<div class="page-head"><div class="title"><h1>Instellingen</h1><p>Bedrijf, offertes en telefonie</p></div>
    <div class="page-actions">
        <a class="btn btn-outline" href="/settings/users"><?= icon('users', 18) ?> Gebruikers</a>
        <a class="btn btn-outline" href="/settings/integrations"><?= icon('link', 18) ?> Integraties</a>
        <a class="btn btn-outline" href="/settings/audit"><?= icon('shield', 18) ?> Audit log</a>
    </div>
</div>

<form action="/settings" method="post">
    <?= csrf_field() ?><input type="hidden" name="_method" value="PUT">
    <div class="dash-grid" style="grid-template-columns:1fr 1fr;">
        <div class="col">
            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('building', 18) ?> Bedrijfsgegevens</h3>
                <div class="field"><label class="label">Bedrijfsnaam</label><input class="input" name="company_name" value="<?= $s('company_name') ?>"></div>
                <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                    <div class="field"><label class="label">E-mail</label><input class="input" name="company_email" value="<?= $s('company_email') ?>"></div>
                    <div class="field"><label class="label">Telefoon</label><input class="input" name="company_phone" value="<?= $s('company_phone') ?>"></div>
                </div>
                <div class="field"><label class="label">Adres</label><input class="input" name="company_address" value="<?= $s('company_address') ?>"></div>
                <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                    <div class="field"><label class="label">BTW-nummer</label><input class="input" name="company_vat" value="<?= $s('company_vat') ?>"></div>
                    <div class="field"><label class="label">IBAN</label><input class="input" name="company_iban" value="<?= $s('company_iban') ?>"></div>
                </div>
            </div></div>

            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('file-text', 18) ?> Offertes</h3>
                <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                    <div class="field"><label class="label">Nummerprefix</label><input class="input" name="quotation_prefix" value="<?= $s('quotation_prefix', 'OFF-') ?>"></div>
                    <div class="field"><label class="label">Geldig (dagen)</label><input class="input" type="number" name="quotation_valid_days" value="<?= $s('quotation_valid_days', '30') ?>"></div>
                    <div class="field"><label class="label">BTW-tarief (%)</label><input class="input" type="number" step="0.01" name="default_tax_rate" value="<?= $s('default_tax_rate', '21') ?>"></div>
                    <div class="field"><label class="label">Valuta</label><input class="input" name="currency" value="<?= $s('currency', 'EUR') ?>"></div>
                </div>
            </div></div>
        </div>

        <div class="col">
            <div class="card"><div class="card-body">
                <h3 class="mb-2"><?= icon('phone-call', 18) ?> Telefonie / CTI</h3>
                <p class="small text-muted mb-4">Nummers voor de scherm-pop bij inkomende oproepen. Zie <a href="/settings/integrations" class="text-accent">integraties</a> voor de webhook-URL.</p>
                <div class="field"><label class="label">Algemeen nummer</label><input class="input" name="general_number" value="<?= $s('general_number') ?>" placeholder="+3231234567"></div>
                <div class="field"><label class="label">Sales-nummer</label><input class="input" name="sales_number" value="<?= $s('sales_number') ?>" placeholder="oproepen hierheen → sales-team"></div>
                <div class="field"><label class="label">CTI webhook-secret</label><input class="input" name="cti_webhook_secret" value="<?= $s('cti_webhook_secret') ?>" placeholder="een geheim voor de webhook"></div>
                <div class="chip" style="word-break:break-all;font-family:monospace;font-size:12px;"><?= e($appUrl) ?>/api/v1/cti/incoming</div>
            </div></div>

            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('star', 18) ?> Huisstijl</h3>
                <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                    <div class="field"><label class="label">Primaire kleur</label><input class="input" type="color" name="primary_color" value="<?= $s('primary_color', '#E98CAB') ?>" style="height:44px;"></div>
                    <div class="field"><label class="label">Accentkleur</label><input class="input" type="color" name="accent_color" value="<?= $s('accent_color', '#B33B62') ?>" style="height:44px;"></div>
                </div>
            </div></div>
        </div>
    </div>
    <div class="flex justify-between mt-4"><span></span><button class="btn btn-primary btn-lg"><?= icon('check', 18) ?> Instellingen opslaan</button></div>
</form>
