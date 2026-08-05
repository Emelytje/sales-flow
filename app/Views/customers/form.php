<?php
/** @var array $customer */
/** @var array $lookups */
$isEdit = !empty($customer['id']);
$action = $isEdit ? '/customers/' . (int) $customer['id'] : '/customers';
$val = static fn (string $k, $d = '') => e($customer[$k] ?? old($k, $d));
$stages = ['lead' => 'Lead', 'contacted' => 'Contact', 'qualified' => 'Gekwalificeerd', 'proposal' => 'Offerte', 'won' => 'Gewonnen', 'lost' => 'Verloren'];
?>
<div class="breadcrumb"><a href="/customers">Klanten</a> <?= icon('chevron-right', 14) ?> <?= $isEdit ? 'Bewerken' : 'Nieuw' ?></div>
<div class="page-head">
    <div class="title"><h1><?= $isEdit ? 'Klant bewerken' : 'Nieuwe klant' ?></h1></div>
</div>

<form action="<?= e($action) ?>" method="post">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <div class="dash-grid" style="grid-template-columns:2fr 1fr;">
        <div class="col">
            <div class="card"><div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <h3><?= icon('building', 18) ?> Bedrijfsgegevens</h3>
                    <button type="button" class="btn btn-outline btn-sm" onclick="kboLookup()"><?= icon('search', 15) ?> KBO/BTW opzoeken</button>
                </div>
                <div class="field">
                    <label class="label">Bedrijfsnaam *</label>
                    <input class="input <?= error('company_name') ? 'error' : '' ?>" name="company_name" id="f_company_name" value="<?= $val('company_name') ?>" required>
                    <?php if ($m = error('company_name')): ?><div class="field-error"><?= e($m) ?></div><?php endif; ?>
                </div>
                <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                    <div class="field"><label class="label">E-mail</label><input class="input" type="email" name="email" value="<?= $val('email') ?>"></div>
                    <div class="field"><label class="label">Telefoon</label><input class="input" name="phone" value="<?= $val('phone') ?>"></div>
                    <div class="field"><label class="label">Website</label><input class="input" name="website" value="<?= $val('website') ?>" placeholder="https://"></div>
                    <div class="field"><label class="label">LinkedIn</label><input class="input" name="linkedin" value="<?= $val('linkedin') ?>"></div>
                    <div class="field"><label class="label">BTW-nummer</label><input class="input" name="vat_number" id="f_vat_number" value="<?= $val('vat_number') ?>" placeholder="BE0123.456.789"></div>
                    <div class="field"><label class="label">KBO-nummer</label><input class="input" name="kbo_number" id="f_kbo_number" value="<?= $val('kbo_number') ?>" placeholder="0123.456.789"></div>
                </div>
            </div></div>

            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('map-pin', 18) ?> Adres</h3>
                <div class="field"><label class="label">Straat en nummer</label><input class="input" name="address" id="f_address" value="<?= $val('address') ?>"></div>
                <div class="grid gap-4" style="grid-template-columns:1fr 2fr 1fr;">
                    <div class="field"><label class="label">Postcode</label><input class="input" name="postal_code" id="f_postal_code" value="<?= $val('postal_code') ?>"></div>
                    <div class="field"><label class="label">Plaats</label><input class="input" name="city" id="f_city" value="<?= $val('city') ?>"></div>
                    <div class="field"><label class="label">Land</label><input class="input" name="country" value="<?= $val('country', 'België') ?>"></div>
                </div>
                <input type="hidden" name="latitude" value="<?= $val('latitude') ?>">
                <input type="hidden" name="longitude" value="<?= $val('longitude') ?>">
            </div></div>
        </div>

        <div class="col">
            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('target', 18) ?> Verkoop</h3>
                <div class="field">
                    <label class="label">Fase</label>
                    <select class="select" name="pipeline_stage">
                        <?php foreach ($stages as $k => $lbl): ?><option value="<?= $k ?>" <?= ($customer['pipeline_stage'] ?? 'lead') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="label">Status</label>
                    <select class="select" name="status">
                        <?php foreach (['prospect' => 'Prospect', 'customer' => 'Klant', 'inactive' => 'Inactief'] as $k => $lbl): ?><option value="<?= $k ?>" <?= ($customer['status'] ?? 'prospect') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="label">Prioriteit</label>
                    <select class="select" name="priority">
                        <?php foreach (['low' => 'Laag', 'medium' => 'Gemiddeld', 'high' => 'Hoog'] as $k => $lbl): ?><option value="<?= $k ?>" <?= ($customer['priority'] ?? 'medium') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label class="label">Geschatte waarde (€)</label><input class="input" type="number" step="0.01" name="estimated_value" value="<?= $val('estimated_value', '0') ?>"></div>
                <div class="field"><label class="label">Lead score (0–100)</label><input class="input" type="number" min="0" max="100" name="lead_score" value="<?= $val('lead_score', '0') ?>"></div>
            </div></div>

            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('tag', 18) ?> Toewijzing</h3>
                <div class="field">
                    <label class="label">Eigenaar</label>
                    <select class="select" name="owner_id">
                        <option value="">— Ikzelf —</option>
                        <?php foreach ($lookups['owners'] as $o): ?><option value="<?= (int) $o['id'] ?>" <?= (string) ($customer['owner_id'] ?? '') === (string) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="label">Sector</label>
                    <select class="select" name="sector_id">
                        <option value="">—</option>
                        <?php foreach ($lookups['sectors'] as $s): ?><option value="<?= (int) $s['id'] ?>" <?= (string) ($customer['sector_id'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="label">Project</label>
                    <select class="select" name="project_id">
                        <option value="">—</option>
                        <?php foreach ($lookups['projects'] as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (string) ($customer['project_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div></div>
        </div>
    </div>

    <div class="flex gap-2 justify-between mt-4">
        <a class="btn btn-ghost" href="<?= $isEdit ? '/customers/' . (int) $customer['id'] : '/customers' ?>">Annuleren</a>
        <button class="btn btn-primary btn-lg" type="submit"><?= icon('check', 18) ?> <?= $isEdit ? 'Wijzigingen opslaan' : 'Klant aanmaken' ?></button>
    </div>
</form>

<script>
async function kboLookup() {
    var num = (document.getElementById('f_kbo_number').value || document.getElementById('f_vat_number').value || '').trim();
    var name = document.getElementById('f_company_name').value.trim();
    if (!num && !name) { SF.toast('Vul een KBO/BTW-nummer of bedrijfsnaam in', 'error'); return; }

    // With a number: direct lookup. Without: search by name and let the user pick.
    if (num) {
        try {
            var res = await SF.api('/lookup/kbo?number=' + encodeURIComponent(num));
            if (res.found) return fill(res.company);
            SF.toast('Geen gegevens gevonden voor dit nummer', 'error');
        } catch (e) {}
        return;
    }
    try {
        var data = await SF.api('/lookup/kbo/search?q=' + encodeURIComponent(name));
        if (!data.configured) { SF.toast('KBO-zoeken op naam vereist een cbeapi.be-sleutel', 'info', 5000); return; }
        if (!data.results.length) { SF.toast('Geen bedrijven gevonden', 'error'); return; }
        var rows = data.results.map(function (c, i) {
            return '<div class="flex items-center gap-3" style="padding:9px 0;border-bottom:1px solid var(--border);cursor:pointer;" onclick="window.__kbo(' + i + ')">' +
                '<div class="flex-1"><div style="font-weight:700;font-size:var(--fs-sm);">' + esc(c.company_name) + '</div>' +
                '<div class="tiny text-muted">' + esc([c.postal_code, c.city].filter(Boolean).join(' ')) + (c.kbo_number ? ' · ' + esc(c.kbo_number) : '') + '</div></div>' +
                '<span class="text-accent">Kies</span></div>';
        }).join('');
        var m = SF.modal.open('<div class="modal-head"><h3>Kies een bedrijf</h3><button class="icon-btn" data-close>&times;</button></div><div class="modal-body">' + rows + '</div>');
        window.__kbo = function (i) { SF.modal.close(); fill(data.results[i]); };
    } catch (e) {}
}
function fill(c) {
    var map = { company_name: 'f_company_name', vat_number: 'f_vat_number', kbo_number: 'f_kbo_number', address: 'f_address', postal_code: 'f_postal_code', city: 'f_city' };
    Object.keys(map).forEach(function (k) { if (c[k]) { var el = document.getElementById(map[k]); if (el && !el.value) el.value = c[k]; else if (el && c[k]) el.value = c[k]; } });
    SF.toast('Gegevens ingevuld via ' + (c.source || 'KBO'), 'success');
}
function esc(s){return String(s||'').replace(/[&<>"]/g,function(x){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[x];});}
</script>
