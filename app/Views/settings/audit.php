<?php /** @var array $logs */ /** @var array $result */ ?>
<div class="breadcrumb"><a href="/settings">Instellingen</a> <?= icon('chevron-right', 14) ?> Audit log</div>
<div class="page-head"><div class="title"><h1>Audit log</h1><p><?= (int) $result['total'] ?> gebeurtenissen</p></div></div>

<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Tijd</th><th>Gebruiker</th><th>Actie</th><th>Entiteit</th><th>IP</th></tr></thead>
    <tbody>
    <?php if (!$logs): ?><tr><td colspan="5" class="text-center text-muted" style="padding:2rem;">Nog geen gebeurtenissen.</td></tr><?php endif; ?>
    <?php foreach ($logs as $l): ?>
        <tr>
            <td class="small text-muted" style="white-space:nowrap;"><?= e(date_nl($l['created_at'], 'd/m/Y H:i')) ?></td>
            <td class="small"><?= e($l['user_name'] ?? 'Systeem') ?></td>
            <td><span class="badge badge-neutral" style="font-family:monospace;"><?= e($l['action']) ?></span></td>
            <td class="small text-muted"><?= e($l['entity_type'] ? $l['entity_type'] . ' #' . $l['entity_id'] : '—') ?></td>
            <td class="tiny text-muted"><?= e($l['ip_address'] ?? '—') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>

<?= \App\Core\View::renderPartial('partials/pagination', ['result' => $result, 'baseQuery' => []]) ?>
