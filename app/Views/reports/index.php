<?php
/** @var array $overview */ /** @var float $conversion */ /** @var array $pipeline */
/** @var array $revenue */ /** @var array $activity */ /** @var array $employees */
$stageMeta = ['lead' => ['Lead', '#9C8F98'], 'contacted' => ['Contact', '#7A6FF0'], 'qualified' => ['Gekwalificeerd', '#4C9AA6'], 'proposal' => ['Offerte', '#D98A2B'], 'won' => ['Gewonnen', '#2FA36B'], 'lost' => ['Verloren', '#D8476A']];
?>
<div class="page-head">
    <div class="title"><h1>Rapporten</h1><p>Teamprestaties · <?= e(date('F Y')) ?></p></div>
    <div class="page-actions">
        <div class="dropdown"><button class="btn btn-outline" data-dropdown="expMenu"><?= icon('download', 18) ?> Exporteren</button>
            <div class="dropdown-menu" id="expMenu">
                <a href="/reports/export/customers"><?= icon('building', 17) ?> Klanten (CSV)</a>
                <a href="/reports/export/activities"><?= icon('activity', 17) ?> Activiteiten (CSV)</a>
                <a href="/reports/export/quotations"><?= icon('file-text', 17) ?> Offertes (CSV)</a>
                <div class="dropdown-divider"></div>
                <button onclick="window.print()"><?= icon('download', 17) ?> Print / PDF</button>
            </div>
        </div>
    </div>
</div>

<div class="grid-stats mb-4">
    <?php foreach ([['Omzet (maand)', money($overview['won_value_month']), 'bg-green'], ['Pijplijnwaarde', money($overview['pipeline_value']), 'bg-rose'], ['Conversie', $conversion . '%', 'bg-violet'], ['Totaal klanten', $overview['total_customers'], 'bg-blue']] as $c): ?>
        <div class="stat"><div class="stat-label"><?= e($c[0]) ?></div><div class="stat-value"><?= e($c[1]) ?></div></div>
    <?php endforeach; ?>
</div>

<div class="dash-grid">
    <div class="col">
        <div class="card"><div class="card-head"><h3>Omzet (12 maanden)</h3></div><div class="card-body"><canvas id="revChart" data-height="260"></canvas></div></div>
        <div class="card"><div class="card-head"><h3>Activiteit (14 dagen)</h3></div><div class="card-body"><canvas id="actChart" data-height="220"></canvas></div></div>
        <div class="card"><div class="card-head"><h3>Teamprestaties</h3></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Medewerker</th><th>Gesprekken</th><th>E-mails</th><th>Afspraken</th><th>Gewonnen</th><th>Omzet</th></tr></thead>
            <tbody>
            <?php foreach ($employees as $emp): ?>
                <tr>
                    <td><div class="flex items-center gap-3"><span class="avatar avatar-sm" style="background:<?= e($emp['color']) ?>"><?= e(initials($emp['name'])) ?></span><span style="font-weight:700;"><?= e($emp['name']) ?></span></div></td>
                    <td><?= (int) $emp['calls'] ?></td><td><?= (int) $emp['emails'] ?></td><td><?= (int) $emp['meetings'] ?></td>
                    <td><span class="badge badge-success"><?= (int) $emp['won'] ?></span></td>
                    <td style="font-weight:700;"><?= money($emp['revenue']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div></div>
    </div>
    <div class="col">
        <div class="card"><div class="card-head"><h3>Pijplijn</h3></div><div class="card-body">
            <canvas id="pipeChart" data-height="200"></canvas>
            <div class="mt-4">
            <?php foreach ($stageMeta as $key => $m): [$cnt, $val] = $pipeline[$key]; ?>
                <div class="flex items-center gap-2 justify-between" style="padding:6px 0;">
                    <span class="flex items-center gap-2"><span style="width:10px;height:10px;border-radius:3px;background:<?= $m[1] ?>;"></span><?= e($m[0]) ?></span>
                    <span><strong><?= $cnt ?></strong> · <?= money($val) ?></span>
                </div>
            <?php endforeach; ?>
            </div>
        </div></div>
    </div>
</div>

<script src="/assets/js/charts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var rose = getComputedStyle(document.documentElement).getPropertyValue('--rose').trim();
    SF.renderChart('line', document.getElementById('revChart'), { labels: <?= json_encode($revenue['labels']) ?>, series: [{ data: <?= json_encode($revenue['data']) ?>, color: rose, area: true }] });
    SF.renderChart('bars', document.getElementById('actChart'), { labels: <?= json_encode($activity['labels']) ?>, series: [{ data: <?= json_encode($activity['calls']) ?>, color: rose }, { data: <?= json_encode($activity['emails']) ?>, color: '#4C82C4' }] });
    SF.renderChart('donut', document.getElementById('pipeChart'), { center: '<?= array_sum(array_map(fn($s) => $s[0], $pipeline)) ?>', centerLabel: 'klanten', data: [
        <?php foreach ($stageMeta as $key => $m): ?>{ value: <?= $pipeline[$key][0] ?>, color: '<?= $m[1] ?>' },<?php endforeach; ?>
    ] });
});
</script>
