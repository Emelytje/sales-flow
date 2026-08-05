<?php
/** @var array $result */
/** @var string $search */
/** @var array $filters */
/** @var array $owners */
/** @var array $sectors */
$stages = ['lead' => 'Lead', 'contacted' => 'Contact', 'qualified' => 'Gekwalificeerd', 'proposal' => 'Offerte', 'won' => 'Gewonnen', 'lost' => 'Verloren'];
$qs = static function (array $extra) use ($search, $filters): string {
    return '?' . http_build_query(array_filter(array_merge(['q' => $search], $filters, $extra)));
};
?>
<div class="page-head">
    <div class="title">
        <h1>Klanten</h1>
        <p><?= (int) $result['total'] ?> bedrijven in je CRM</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline" href="/map"><?= icon('map', 18) ?> Kaart</a>
        <?php if (can('customers.create')): ?>
            <a class="btn btn-primary" href="/customers/create"><?= icon('plus', 18) ?> Nieuwe klant</a>
        <?php endif; ?>
    </div>
</div>

<form class="list-toolbar" method="get" action="/customers">
    <div class="search-bar">
        <?= icon('search') ?>
        <input class="input" type="search" name="q" value="<?= e($search) ?>" placeholder="Zoek op naam, e-mail, stad, BTW…">
    </div>
    <select class="select" name="owner" style="max-width:180px" onchange="this.form.submit()">
        <option value="">Alle eigenaars</option>
        <?php foreach ($owners as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (string) $filters['owner_id'] === (string) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="select" name="sector" style="max-width:180px" onchange="this.form.submit()">
        <option value="">Alle sectoren</option>
        <?php foreach ($sectors as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= (string) $filters['sector_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline"><?= icon('filter', 18) ?> Filter</button>
</form>

<div class="filter-pills mb-4">
    <a class="pill <?= empty($filters['pipeline_stage']) ? 'active' : '' ?>" href="<?= e($qs(['stage' => null])) ?>">Alle fases</a>
    <?php foreach ($stages as $key => $label): ?>
        <a class="pill <?= ($filters['pipeline_stage'] ?? '') === $key ? 'active' : '' ?>" href="<?= e($qs(['stage' => $key])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <?php if (!$result['data']): ?>
        <div class="empty">
            <?= icon('building', 56) ?>
            <h3>Geen klanten gevonden</h3>
            <p>Pas je filters aan of voeg je eerste klant toe.</p>
            <?php if (can('customers.create')): ?><a class="btn btn-primary mt-4" href="/customers/create"><?= icon('plus', 18) ?> Nieuwe klant</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th>Bedrijf</th><th>Fase</th><th>Eigenaar</th><th>Plaats</th>
                    <th>Waarde</th><th>Laatste contact</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($result['data'] as $c): ?>
                    <tr onclick="location.href='/customers/<?= (int) $c['id'] ?>'" style="cursor:pointer;">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="avatar avatar-sm" style="border-radius:10px;background:<?= e($c['owner_color'] ?: '#E98CAB') ?>;"><?= e(initials($c['company_name'])) ?></span>
                                <div>
                                    <div style="font-weight:700;"><?= e($c['company_name']) ?></div>
                                    <div class="tiny text-muted"><?= e($c['sector_name'] ?? '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="stage-tag stage-<?= e($c['pipeline_stage']) ?>"><?= e($stages[$c['pipeline_stage']] ?? $c['pipeline_stage']) ?></span></td>
                        <td>
                            <?php if ($c['owner_name']): ?>
                                <div class="flex items-center gap-2"><span class="avatar avatar-sm" style="background:<?= e($c['owner_color']) ?>"><?= e(initials($c['owner_name'])) ?></span><span class="small"><?= e($c['owner_name']) ?></span></div>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td class="small"><?= e($c['city'] ?: '—') ?></td>
                        <td style="font-weight:700;"><?= money($c['estimated_value']) ?></td>
                        <td class="small text-muted"><?= e(time_ago($c['last_contact_at'])) ?></td>
                        <td onclick="event.stopPropagation();">
                            <div class="flex gap-1">
                                <?php if ($c['phone']): ?><a class="icon-btn" href="tel:<?= e($c['phone']) ?>" title="Bellen" style="width:34px;height:34px;"><?= icon('phone', 17) ?></a><?php endif; ?>
                                <a class="icon-btn" href="/customers/<?= (int) $c['id'] ?>" style="width:34px;height:34px;"><?= icon('chevron-right', 17) ?></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?= \App\Core\View::renderPartial('partials/pagination', ['result' => $result, 'baseQuery' => array_filter(array_merge(['q' => $search], $filters))]) ?>
