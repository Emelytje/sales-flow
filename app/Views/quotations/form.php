<?php
/** @var array|null $quotation */ /** @var array $items */ /** @var array $customers */
/** @var float $taxRate */ /** @var int $preselect */
$isEdit = !empty($quotation['id']);
$action = $isEdit ? '/quotations/' . (int) $quotation['id'] : '/quotations';
?>
<div class="breadcrumb"><a href="/quotations">Offertes</a> <?= icon('chevron-right', 14) ?> <?= $isEdit ? e($quotation['number']) : 'Nieuw' ?></div>
<div class="page-head"><div class="title"><h1><?= $isEdit ? 'Offerte bewerken' : 'Nieuwe offerte' ?></h1></div></div>

<form action="<?= e($action) ?>" method="post" id="quoteForm">
    <?= csrf_field() ?><?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <div class="dash-grid" style="grid-template-columns:2fr 1fr;">
        <div class="col">
            <div class="card"><div class="card-body">
                <div class="grid gap-4" style="grid-template-columns:2fr 1fr;">
                    <div class="field"><label class="label">Titel</label><input class="input" name="title" value="<?= e($quotation['title'] ?? 'Offerte') ?>" required></div>
                    <div class="field"><label class="label">Status</label><select class="select" name="status">
                        <?php foreach (['draft' => 'Concept', 'sent' => 'Verzonden', 'accepted' => 'Geaccepteerd', 'rejected' => 'Afgewezen'] as $k => $v): ?><option value="<?= $k ?>" <?= ($quotation['status'] ?? 'draft') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
                    </select></div>
                </div>
                <div class="field"><label class="label">Klant</label><select class="select" name="customer_id" required>
                    <option value="">— Kies klant —</option>
                    <?php foreach ($customers as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) ($quotation['customer_id'] ?? $preselect) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['company_name']) ?></option><?php endforeach; ?>
                </select></div>
                <div class="field"><label class="label">Introductie</label><textarea class="textarea" name="intro" placeholder="Beste klant, hierbij onze offerte…"><?= e($quotation['intro'] ?? '') ?></textarea></div>
            </div></div>

            <div class="card"><div class="card-head"><h3>Offertelijnen</h3><button type="button" class="btn btn-outline btn-sm" onclick="Quote.addLine()"><?= icon('plus', 16) ?> Regel</button></div>
            <div class="card-body">
                <div class="table-wrap"><table class="table" id="lineTable">
                    <thead><tr><th style="width:44%">Omschrijving</th><th>Aantal</th><th>Prijs</th><th>Korting %</th><th>Totaal</th><th></th></tr></thead>
                    <tbody id="lineBody"></tbody>
                </table></div>
            </div></div>

            <div class="card"><div class="card-body">
                <div class="field"><label class="label">Voorwaarden</label><textarea class="textarea" name="terms" placeholder="Betaling binnen 30 dagen…"><?= e($quotation['terms'] ?? '') ?></textarea></div>
            </div></div>
        </div>

        <div class="col">
            <div class="card"><div class="card-body">
                <h3 class="mb-4">Overzicht</h3>
                <div class="flex justify-between mb-2"><span class="text-muted">Subtotaal</span><strong id="sumSub">€ 0,00</strong></div>
                <div class="flex justify-between items-center mb-2"><span class="text-muted">Korting (€)</span><input class="input" type="number" step="0.01" name="discount" id="discount" value="<?= (float) ($quotation['discount'] ?? 0) ?>" style="width:110px;text-align:right;" oninput="Quote.recalc()"></div>
                <div class="flex justify-between items-center mb-2"><span class="text-muted">BTW (%)</span><input class="input" type="number" step="0.01" name="tax_rate" id="taxRate" value="<?= (float) $taxRate ?>" style="width:110px;text-align:right;" oninput="Quote.recalc()"></div>
                <div class="flex justify-between mb-2"><span class="text-muted">BTW-bedrag</span><strong id="sumTax">€ 0,00</strong></div>
                <hr class="divider">
                <div class="flex justify-between" style="font-size:var(--fs-lg);"><strong>Totaal</strong><strong id="sumTotal" style="color:var(--rose-dark);">€ 0,00</strong></div>
                <button class="btn btn-primary btn-block btn-lg mt-6" type="submit"><?= icon('check', 18) ?> <?= $isEdit ? 'Opslaan' : 'Aanmaken' ?></button>
            </div></div>
        </div>
    </div>
</form>

<script>window.SF_ITEMS = <?= json_encode(array_map(static fn ($i) => ['description' => $i['description'], 'quantity' => (float) $i['quantity'], 'unit_price' => (float) $i['unit_price'], 'discount' => (float) $i['discount']], $items)) ?>;</script>
<script src="/assets/js/quotations.js"></script>
