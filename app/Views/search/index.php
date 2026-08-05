<?php
/** @var string $q */
/** @var array $results */
$total = count($results['customers']) + count($results['contacts']) + count($results['quotations']);
?>
<div class="page-head"><div class="title"><h1>Zoeken</h1><p><?= $q ? $total . ' resultaten voor "' . e($q) . '"' : 'Zoek in je hele CRM' ?></p></div></div>

<form class="search-bar mb-6" method="get" action="/search" style="max-width:600px;">
    <?= icon('search') ?><input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Zoek klanten, contacten, offertes…" autofocus>
</form>

<?php if ($q === ''): ?>
    <div class="card"><div class="empty"><?= icon('search', 56) ?><h3>Begin met typen</h3><p>Doorzoek klanten, contactpersonen en offertes ineens.</p></div></div>
<?php elseif ($total === 0): ?>
    <div class="card"><div class="empty"><?= icon('search', 56) ?><h3>Geen resultaten</h3><p>Probeer een andere zoekterm.</p></div></div>
<?php else: ?>
    <?php if ($results['customers']): ?>
    <div class="card mb-4"><div class="card-head"><h3><?= icon('building', 18) ?> Klanten</h3><span class="badge badge-neutral"><?= count($results['customers']) ?></span></div><div class="card-body">
        <?php foreach ($results['customers'] as $c): ?>
            <a class="flex items-center gap-3" href="/customers/<?= (int) $c['id'] ?>" style="padding:9px 0;text-decoration:none;">
                <span class="avatar avatar-sm" style="border-radius:10px;"><?= e(initials($c['company_name'])) ?></span>
                <div class="flex-1"><div style="font-weight:700;font-size:var(--fs-sm);"><?= e($c['company_name']) ?></div><div class="tiny text-muted"><?= e($c['city'] ?: '—') ?></div></div>
                <span class="stage-tag stage-<?= e($c['pipeline_stage']) ?>"><?= e($c['pipeline_stage']) ?></span>
            </a>
        <?php endforeach; ?>
    </div></div>
    <?php endif; ?>

    <?php if ($results['contacts']): ?>
    <div class="card mb-4"><div class="card-head"><h3><?= icon('users', 18) ?> Contacten</h3><span class="badge badge-neutral"><?= count($results['contacts']) ?></span></div><div class="card-body">
        <?php foreach ($results['contacts'] as $ct): ?>
            <a class="flex items-center gap-3" href="/customers/<?= (int) $ct['customer_id'] ?>" style="padding:9px 0;text-decoration:none;">
                <span class="avatar avatar-sm"><?= e(initials($ct['first_name'] . ' ' . $ct['last_name'])) ?></span>
                <div class="flex-1"><div style="font-weight:700;font-size:var(--fs-sm);"><?= e(trim($ct['first_name'] . ' ' . $ct['last_name'])) ?></div><div class="tiny text-muted"><?= e($ct['company_name']) ?></div></div>
            </a>
        <?php endforeach; ?>
    </div></div>
    <?php endif; ?>

    <?php if ($results['quotations']): ?>
    <div class="card"><div class="card-head"><h3><?= icon('file-text', 18) ?> Offertes</h3><span class="badge badge-neutral"><?= count($results['quotations']) ?></span></div><div class="card-body">
        <?php foreach ($results['quotations'] as $qo): ?>
            <a class="flex items-center gap-3" href="/quotations/<?= (int) $qo['id'] ?>" style="padding:9px 0;text-decoration:none;">
                <span class="feed-icon bg-rose" style="width:34px;height:34px;"><?= icon('file-text', 15) ?></span>
                <div class="flex-1"><div style="font-weight:700;font-size:var(--fs-sm);"><?= e($qo['number']) ?> · <?= e($qo['title']) ?></div><div class="tiny text-muted"><?= e($qo['company_name']) ?></div></div>
                <strong><?= money($qo['total']) ?></strong>
            </a>
        <?php endforeach; ?>
    </div></div>
    <?php endif; ?>
<?php endif; ?>
