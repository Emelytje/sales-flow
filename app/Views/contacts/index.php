<?php
/** @var array $result */
/** @var string $search */
?>
<div class="page-head">
    <div class="title"><h1>Contacten</h1><p><?= (int) $result['total'] ?> contactpersonen</p></div>
</div>

<form class="list-toolbar" method="get" action="/contacts">
    <div class="search-bar"><?= icon('search') ?><input class="input" type="search" name="q" value="<?= e($search) ?>" placeholder="Zoek contact of bedrijf…"></div>
    <button class="btn btn-outline"><?= icon('search', 18) ?> Zoeken</button>
</form>

<div class="card">
    <?php if (!$result['data']): ?>
        <div class="empty"><?= icon('users', 56) ?><h3>Geen contacten</h3><p>Voeg contactpersonen toe via een klantkaart.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Naam</th><th>Bedrijf</th><th>Functie</th><th>E-mail</th><th>Telefoon</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($result['data'] as $ct): ?>
                <tr>
                    <td><div class="flex items-center gap-3"><span class="avatar avatar-sm"><?= e(initials($ct['first_name'] . ' ' . $ct['last_name'])) ?></span>
                        <span style="font-weight:700;"><?= e(trim($ct['first_name'] . ' ' . $ct['last_name'])) ?> <?php if ($ct['is_primary']): ?><span class="badge badge-rose">Primair</span><?php endif; ?></span></div></td>
                    <td><a href="/customers/<?= (int) $ct['customer_id'] ?>" class="text-accent" style="font-weight:600;"><?= e($ct['company_name']) ?></a></td>
                    <td class="small"><?= e($ct['job_title'] ?: '—') ?></td>
                    <td class="small"><?= $ct['email'] ? '<a href="mailto:' . e($ct['email']) . '">' . e($ct['email']) . '</a>' : '—' ?></td>
                    <td class="small"><?= $ct['mobile'] || $ct['phone'] ? '<a href="tel:' . e($ct['mobile'] ?: $ct['phone']) . '">' . e($ct['mobile'] ?: $ct['phone']) . '</a>' : '—' ?></td>
                    <td><a class="icon-btn" href="/customers/<?= (int) $ct['customer_id'] ?>" style="width:34px;height:34px;"><?= icon('chevron-right', 17) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>

<?= \App\Core\View::renderPartial('partials/pagination', ['result' => $result, 'baseQuery' => array_filter(['q' => $search])]) ?>
