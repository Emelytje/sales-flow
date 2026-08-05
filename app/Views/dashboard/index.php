<?php
/** @var array $overview */
/** @var float $conversion */
/** @var array $pipeline */
/** @var array $revenue */
/** @var array $activity */
/** @var array $leaderboard */
/** @var array $feed */
/** @var array $schedule */
/** @var array $tasks */
/** @var bool $isManager */
use App\Core\Auth;

$stageMeta = [
    'lead' => ['Lead', 'var(--stage-lead)'],
    'contacted' => ['Contact', 'var(--stage-contacted)'],
    'qualified' => ['Gekwalificeerd', 'var(--stage-qualified)'],
    'proposal' => ['Offerte', 'var(--stage-proposal)'],
    'won' => ['Gewonnen', 'var(--stage-won)'],
    'lost' => ['Verloren', 'var(--stage-lost)'],
];
$feedIcon = ['call' => ['phone-call', 'bg-green'], 'email' => ['mail', 'bg-blue'], 'meeting' => ['calendar', 'bg-violet'], 'note' => ['file-text', 'bg-amber'], 'quotation' => ['file-text', 'bg-rose'], 'status_change' => ['activity', 'bg-teal'], 'task' => ['check', 'bg-amber'], 'system' => ['zap', 'bg-teal']];
?>
<div class="page-head">
    <div class="title">
        <h1>Goededag, <?= e(explode(' ', Auth::user()['name'] ?? '')[0]) ?> 👋</h1>
        <p><?= $isManager ? 'Teamoverzicht' : 'Jouw overzicht' ?> · <?= e(date_nl(date('Y-m-d'), 'l j F Y')) ?></p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline" href="/callboard"><?= icon('phone-call', 18) ?> Callboard</a>
        <a class="btn btn-primary" href="/customers/create"><?= icon('plus', 18) ?> Nieuwe klant</a>
    </div>
</div>

<div class="grid-stats">
    <?php
    $cards = [
        ['Omzet deze maand', money($overview['won_value_month']), 'trending-up', 'bg-green'],
        ['Gesprekken vandaag', $overview['calls_today'], 'phone-call', 'bg-rose'],
        ['E-mails vandaag', $overview['emails_today'], 'mail', 'bg-blue'],
        ['Afspraken vandaag', $overview['meetings_today'], 'calendar', 'bg-violet'],
        ['Open offertes', $overview['open_quotes'], 'file-text', 'bg-amber'],
        ['Nieuwe klanten', $overview['new_customers'], 'building', 'bg-teal'],
        ['Pijplijnwaarde', money($overview['pipeline_value']), 'target', 'bg-rose'],
        ['Conversie', $conversion . '%', 'award', 'bg-green'],
    ];
    foreach ($cards as $i => $c): ?>
        <div class="stat" data-anim style="animation-delay:<?= $i * 0.05 ?>s">
            <div class="stat-icon <?= $c[3] ?>"><?= icon($c[2], 22) ?></div>
            <div class="stat-label"><?= e($c[0]) ?></div>
            <div class="stat-value"><?= e($c[1]) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="dash-grid">
    <div class="col">
        <div class="card">
            <div class="card-head">
                <h3>Omzettrend</h3>
                <span class="badge badge-neutral">Laatste 6 maanden</span>
            </div>
            <div class="card-body"><canvas id="revenueChart" data-height="240"></canvas></div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Activiteit</h3>
                <div class="flex gap-3 small">
                    <span class="stage-tag" style="color:var(--text-soft)"><span style="width:10px;height:10px;border-radius:3px;background:var(--rose);display:inline-block;margin-right:4px;"></span>Gesprekken</span>
                    <span class="stage-tag" style="color:var(--text-soft)"><span style="width:10px;height:10px;border-radius:3px;background:#4C82C4;display:inline-block;margin-right:4px;"></span>E-mails</span>
                </div>
            </div>
            <div class="card-body"><canvas id="activityChart" data-height="220"></canvas></div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Pijplijn</h3><a href="/customers" class="small text-accent" style="font-weight:600;">Alles bekijken</a></div>
            <div class="card-body">
                <div class="pipeline">
                    <?php foreach ($stageMeta as $key => $meta): [$cnt, $val] = $pipeline[$key]; ?>
                        <div class="pipe-col">
                            <h4><span class="stage-tag stage-<?= $key ?>"><?= e($meta[0]) ?></span><span class="count"><?= $cnt ?></span></h4>
                            <div class="tiny text-muted mb-2"><?= money($val) ?></div>
                            <div class="progress"><span style="width:<?= $cnt ? min(100, $cnt * 12) : 2 ?>%;background:<?= $meta[1] ?>;"></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <?php if ($isManager): ?>
        <div class="card">
            <div class="card-head"><h3><?= icon('award', 18) ?> Leaderboard</h3><span class="badge badge-rose"><?= e(date('F')) ?></span></div>
            <div class="card-body">
                <?php if (!$leaderboard): ?><p class="text-muted small">Nog geen data deze maand.</p><?php endif; ?>
                <?php foreach ($leaderboard as $i => $rep): ?>
                    <div class="flex items-center gap-3" style="padding:10px 0;<?= $i ? 'border-top:1px solid var(--border);' : '' ?>">
                        <div style="width:24px;font-weight:800;color:var(--text-muted);text-align:center;"><?= $i + 1 ?></div>
                        <span class="avatar avatar-sm" style="background:<?= e($rep['color']) ?>"><?= e(initials($rep['name'])) ?></span>
                        <div class="flex-1" style="min-width:0;">
                            <div style="font-weight:700;font-size:var(--fs-sm);"><?= e($rep['name']) ?></div>
                            <div class="tiny text-muted"><?= (int) $rep['calls'] ?> gesprekken · <?= (int) $rep['meetings'] ?> afspraken</div>
                        </div>
                        <div style="font-weight:800;color:var(--rose-dark);"><?= money($rep['revenue']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-head"><h3><?= icon('calendar', 18) ?> Vandaag</h3><a href="/agenda" class="small text-accent" style="font-weight:600;">Agenda</a></div>
            <div class="card-body">
                <?php if (!$schedule): ?>
                    <p class="text-muted small">Geen afspraken vandaag. Tijd om te bellen! 📞</p>
                <?php else: foreach ($schedule as $ev): ?>
                    <div class="flex gap-3" style="padding:9px 0;">
                        <div style="font-weight:700;color:var(--rose-dark);font-size:var(--fs-sm);white-space:nowrap;"><?= e(date_nl($ev['starts_at'], 'H:i')) ?></div>
                        <div class="flex-1">
                            <div style="font-weight:600;font-size:var(--fs-sm);"><?= e($ev['title']) ?></div>
                            <?php if ($ev['company_name']): ?><div class="tiny text-muted"><?= e($ev['company_name']) ?></div><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3><?= icon('check-circle', 18) ?> Taken</h3></div>
            <div class="card-body">
                <?php if (!$tasks): ?>
                    <p class="text-muted small">Geen openstaande taken.</p>
                <?php else: foreach ($tasks as $t): ?>
                    <div class="flex items-center gap-3" style="padding:8px 0;">
                        <span class="badge badge-<?= $t['priority'] === 'high' ? 'danger' : ($t['priority'] === 'medium' ? 'warning' : 'neutral') ?>"><?= e($t['priority']) ?></span>
                        <div class="flex-1" style="font-size:var(--fs-sm);font-weight:600;"><?= e($t['title']) ?></div>
                        <div class="tiny text-muted"><?= e(date_nl($t['due_at'], 'd/m')) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3><?= icon('activity', 18) ?> Activiteitenfeed</h3></div>
            <div class="card-body" style="max-height:340px;overflow-y:auto;">
                <?php if (!$feed): ?>
                    <p class="text-muted small">Nog geen activiteit.</p>
                <?php else: foreach ($feed as $f): [$ic, $bg] = $feedIcon[$f['type']] ?? ['activity', 'bg-teal']; ?>
                    <div class="feed-item">
                        <div class="feed-icon <?= $bg ?>"><?= icon($ic, 16) ?></div>
                        <div class="txt">
                            <b><?= e($f['user_name'] ?? 'Systeem') ?></b>
                            <?= e($f['subject'] ?? ucfirst($f['type'])) ?>
                            <?php if ($f['company_name']): ?>· <span class="text-accent"><?= e($f['company_name']) ?></span><?php endif; ?>
                        </div>
                        <div class="when"><?= e(time_ago($f['occurred_at'])) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/charts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var revenue = <?= json_encode($revenue) ?>;
        var activity = <?= json_encode($activity) ?>;
        var rose = getComputedStyle(document.documentElement).getPropertyValue('--rose').trim();
        SF.renderChart('line', document.getElementById('revenueChart'), {
            labels: revenue.labels,
            series: [{ data: revenue.data, color: rose, area: true }]
        });
        SF.renderChart('bars', document.getElementById('activityChart'), {
            labels: activity.labels,
            series: [
                { data: activity.calls, color: rose },
                { data: activity.emails, color: '#4C82C4' }
            ]
        });
    });
</script>
