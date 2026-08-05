<?php
/** @var array $quotation */ /** @var array $items */ /** @var string $appUrl */
$q = $quotation;
$statuses = ['draft' => ['Concept', 'neutral'], 'sent' => ['Verzonden', 'info'], 'viewed' => ['Bekeken', 'warning'], 'accepted' => ['Geaccepteerd', 'success'], 'rejected' => ['Afgewezen', 'danger'], 'expired' => ['Verlopen', 'neutral']];
[$lbl, $col] = $statuses[$q['status']] ?? ['—', 'neutral'];
$publicLink = $appUrl . '/q/' . $q['public_token'];
?>
<div class="breadcrumb"><a href="/quotations">Offertes</a> <?= icon('chevron-right', 14) ?> <?= e($q['number']) ?></div>
<div class="page-head">
    <div class="title"><h1><?= e($q['number']) ?> <span class="badge badge-<?= $col ?> badge-dot"><?= e($lbl) ?></span></h1><p><?= e($q['title']) ?> · <?= e($q['company_name']) ?></p></div>
    <div class="page-actions">
        <button class="btn btn-outline" onclick="navigator.clipboard.writeText('<?= e($publicLink) ?>').then(()=>SF.toast('Klantlink gekopieerd','success'))"><?= icon('link', 18) ?> Deel-link</button>
        <a class="btn btn-outline" href="/quotations/<?= (int) $q['id'] ?>/pdf" target="_blank"><?= icon('download', 18) ?> PDF</a>
        <?php if (can('quotations.edit')): ?><a class="btn btn-outline" href="/quotations/<?= (int) $q['id'] ?>/edit"><?= icon('edit', 18) ?></a><?php endif; ?>
        <a class="btn btn-primary" href="<?= e($publicLink) ?>" target="_blank"><?= icon('external', 18) ?> Klantweergave</a>
    </div>
</div>

<div class="card">
    <?= \App\Core\View::renderPartial('partials/quote_document', ['quotation' => $q, 'items' => $items, 'company' => $company]) ?>
</div>
