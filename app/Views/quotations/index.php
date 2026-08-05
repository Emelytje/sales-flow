<?php
/** @var array $result */ /** @var string $search */ /** @var string|null $status */
$statuses = ['draft' => ['Concept', 'neutral'], 'sent' => ['Verzonden', 'info'], 'viewed' => ['Bekeken', 'warning'], 'accepted' => ['Geaccepteerd', 'success'], 'rejected' => ['Afgewezen', 'danger'], 'expired' => ['Verlopen', 'neutral']];
?>
<div class="page-head"><div class="title"><h1>Offertes</h1><p><?= (int) $result['total'] ?> offertes</p></div>
    <div class="page-actions"><?php if (can('quotations.create')): ?><a class="btn btn-primary" href="/quotations/create"><?= icon('plus', 18) ?> Nieuwe offerte</a><?php endif; ?></div>
</div>

<div class="filter-pills mb-4">
    <a class="pill <?= !$status ? 'active' : '' ?>" href="/quotations">Alle</a>
    <?php foreach ($statuses as $k => $v): ?><a class="pill <?= $status === $k ? 'active' : '' ?>" href="/quotations?status=<?= $k ?>"><?= e($v[0]) ?></a><?php endforeach; ?>
</div>

<div class="card">
    <?php if (!$result['data']): ?>
        <div class="empty"><?= icon('file-text', 56) ?><h3>Geen offertes</h3><p>Maak je eerste offerte aan.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Nummer</th><th>Klant</th><th>Titel</th><th>Bedrag</th><th>Status</th><th>Geldig tot</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($result['data'] as $q): [$lbl, $col] = $statuses[$q['status']] ?? ['—', 'neutral']; ?>
            <tr onclick="location.href='/quotations/<?= (int) $q['id'] ?>'" style="cursor:pointer;">
                <td style="font-weight:700;font-family:monospace;"><?= e($q['number']) ?></td>
                <td><?= e($q['company_name']) ?></td>
                <td class="small"><?= e($q['title']) ?></td>
                <td style="font-weight:700;"><?= money($q['total']) ?></td>
                <td><span class="badge badge-<?= $col ?> badge-dot"><?= e($lbl) ?></span></td>
                <td class="small text-muted"><?= e(date_nl($q['valid_until'])) ?></td>
                <td><a class="icon-btn" href="/quotations/<?= (int) $q['id'] ?>" style="width:34px;height:34px;"><?= icon('chevron-right', 17) ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?= \App\Core\View::renderPartial('partials/pagination', ['result' => $result, 'baseQuery' => array_filter(['q' => $search, 'status' => $status])]) ?>
