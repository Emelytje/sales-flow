<?php
/** @var array $quotation */ /** @var array $items */ /** @var array $company */
$q = $quotation;
$cur = '€';
?>
<div class="qdoc">
    <div class="qdoc-head">
        <div>
            <div class="qdoc-logo">SF</div>
            <h2><?= e($company['company_name'] ?? 'SalesFlow') ?></h2>
            <div class="qdoc-muted">
                <?= e($company['company_address'] ?? '') ?><br>
                <?php if (!empty($company['company_vat'])): ?>BTW: <?= e($company['company_vat']) ?><br><?php endif; ?>
                <?php if (!empty($company['company_email'])): ?><?= e($company['company_email']) ?><?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <h1 style="color:#B33B62;">OFFERTE</h1>
            <div class="qdoc-muted">
                <strong><?= e($q['number']) ?></strong><br>
                Datum: <?= e(date_nl($q['created_at'])) ?><br>
                Geldig tot: <?= e(date_nl($q['valid_until'])) ?>
            </div>
        </div>
    </div>

    <div class="qdoc-parties">
        <div>
            <div class="qdoc-label">Voor</div>
            <strong><?= e($q['company_name']) ?></strong><br>
            <span class="qdoc-muted">
                <?= e($q['address'] ?? '') ?><?= $q['address'] ? '<br>' : '' ?>
                <?= e(trim(($q['postal_code'] ?? '') . ' ' . ($q['city'] ?? ''))) ?><br>
                <?php if (!empty($q['vat_number'])): ?>BTW: <?= e($q['vat_number']) ?><?php endif; ?>
            </span>
        </div>
        <div>
            <div class="qdoc-label">Contact</div>
            <?= e($q['rep_name'] ?? '') ?><br>
            <span class="qdoc-muted"><?= e($q['rep_email'] ?? '') ?><br><?= e($q['rep_phone'] ?? '') ?></span>
        </div>
    </div>

    <h3 style="margin:24px 0 6px;"><?= e($q['title']) ?></h3>
    <?php if (!empty($q['intro'])): ?><p class="qdoc-muted" style="margin-bottom:16px;"><?= nl2br(e($q['intro'])) ?></p><?php endif; ?>

    <table class="qdoc-table">
        <thead><tr><th>Omschrijving</th><th class="r">Aantal</th><th class="r">Prijs</th><th class="r">Korting</th><th class="r">Totaal</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e($it['description']) ?></td>
                <td class="r"><?= rtrim(rtrim(number_format((float) $it['quantity'], 2, ',', '.'), '0'), ',') ?></td>
                <td class="r"><?= money($it['unit_price']) ?></td>
                <td class="r"><?= (float) $it['discount'] ? number_format((float) $it['discount'], 0) . '%' : '—' ?></td>
                <td class="r"><?= money($it['line_total']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="qdoc-totals">
        <div><span>Subtotaal</span><span><?= money($q['subtotal']) ?></span></div>
        <?php if ((float) $q['discount'] > 0): ?><div><span>Korting</span><span>- <?= money($q['discount']) ?></span></div><?php endif; ?>
        <div><span>BTW (<?= rtrim(rtrim(number_format((float) $q['tax_rate'], 2, ',', '.'), '0'), ',') ?>%)</span><span><?= money($q['tax_amount']) ?></span></div>
        <div class="qdoc-grand"><span>Totaal</span><span><?= money($q['total']) ?></span></div>
    </div>

    <?php if (!empty($q['terms'])): ?>
        <div class="qdoc-terms"><div class="qdoc-label">Voorwaarden</div><p class="qdoc-muted"><?= nl2br(e($q['terms'])) ?></p></div>
    <?php endif; ?>

    <?php if (!empty($q['signed_at'])): ?>
        <div class="qdoc-signed">
            <div class="qdoc-label">Digitaal ondertekend</div>
            <img src="<?= e($q['signature_data']) ?>" alt="handtekening" style="max-height:80px;">
            <div><strong><?= e($q['signer_name']) ?></strong> · <?= e(date_nl($q['signed_at'], 'd/m/Y H:i')) ?></div>
        </div>
    <?php endif; ?>
</div>

<style>
    .qdoc { max-width: 800px; margin: 0 auto; background: #fff; color: #2C2230; padding: 48px; font-size: 14px; line-height: 1.55; }
    .qdoc-head { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 24px; border-bottom: 3px solid #E98CAB; }
    .qdoc-logo { width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg, #E98CAB, #B33B62); color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 20px; margin-bottom: 10px; }
    .qdoc-head h1 { font-size: 30px; letter-spacing: 2px; } .qdoc-head h2 { font-size: 18px; }
    .qdoc-muted { color: #6B5E68; font-size: 13px; }
    .qdoc-parties { display: flex; justify-content: space-between; gap: 40px; margin-top: 24px; }
    .qdoc-label { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #B33B62; font-weight: 700; margin-bottom: 4px; }
    .qdoc-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
    .qdoc-table th { text-align: left; padding: 10px; background: #F5EBDD; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; }
    .qdoc-table th.r, .qdoc-table td.r { text-align: right; }
    .qdoc-table td { padding: 10px; border-bottom: 1px solid #EFE3D8; }
    .qdoc-totals { margin-left: auto; width: 300px; margin-top: 16px; }
    .qdoc-totals div { display: flex; justify-content: space-between; padding: 6px 0; }
    .qdoc-grand { border-top: 2px solid #2C2230; margin-top: 6px; font-size: 18px; font-weight: 800; color: #B33B62; }
    .qdoc-terms { margin-top: 32px; }
    .qdoc-signed { margin-top: 32px; padding: 16px; background: #E5F5EC; border-radius: 12px; }
    @media print { .qdoc { padding: 0; } body { background: #fff; } }
</style>
