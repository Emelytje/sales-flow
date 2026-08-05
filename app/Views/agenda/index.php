<?php /** @var array $customers */ /** @var array $team */ /** @var int $me */ ?>
<div class="page-head">
    <div class="title"><h1>Gedeelde agenda</h1><p>Afspraken, taken en bezoeken van het hele team</p></div>
    <div class="page-actions">
        <div class="flex gap-1" style="background:var(--surface-2);border-radius:var(--r-md);padding:3px;">
            <button class="btn btn-sm btn-ghost" data-view="month" id="vMonth">Maand</button>
            <button class="btn btn-sm btn-ghost" data-view="week" id="vWeek">Week</button>
            <button class="btn btn-sm btn-ghost" data-view="day" id="vDay">Dag</button>
        </div>
        <button class="btn btn-primary" onclick="Agenda.openCreate()"><?= icon('plus', 18) ?> Afspraak</button>
    </div>
</div>

<div class="dash-grid" style="grid-template-columns:220px 1fr;align-items:start;">
    <div class="card"><div class="card-body">
        <h3 class="mb-4" style="font-size:var(--fs-md);"><?= icon('users', 18) ?> Collega's</h3>
        <div id="teamFilter" class="flex" style="flex-direction:column;gap:2px;">
            <?php foreach ($team as $t): ?>
                <label class="flex items-center gap-2" style="padding:7px 8px;border-radius:var(--r-md);cursor:pointer;font-size:var(--fs-sm);font-weight:600;">
                    <input type="checkbox" class="team-cb" value="<?= (int) $t['id'] ?>" checked>
                    <span style="width:12px;height:12px;border-radius:4px;background:<?= e($t['color']) ?>;flex-shrink:0;"></span>
                    <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($t['name']) ?><?= (int) $t['id'] === $me ? ' (jij)' : '' ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <hr class="divider">
        <div class="flex gap-2">
            <button class="btn btn-outline btn-sm flex-1" onclick="Agenda.allTeam(true)">Allen</button>
            <button class="btn btn-outline btn-sm flex-1" onclick="Agenda.allTeam(false)">Geen</button>
        </div>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="flex items-center justify-between mb-4 wrap gap-3">
            <div class="flex items-center gap-2">
                <button class="icon-btn" onclick="Agenda.prev()"><?= icon('chevron-left', 20) ?></button>
                <button class="btn btn-outline btn-sm" onclick="Agenda.today()">Vandaag</button>
                <button class="icon-btn" onclick="Agenda.next()"><?= icon('chevron-right', 20) ?></button>
            </div>
            <h2 id="calTitle" style="font-size:var(--fs-lg);"></h2>
            <span class="tiny text-muted">Sleep een eigen afspraak om te verplaatsen</span>
        </div>
        <div id="calendar"></div>
    </div></div>
</div>

<script>
window.SF_CUSTOMERS = <?= json_encode(array_map(static fn ($c) => ['id' => (int) $c['id'], 'name' => $c['company_name']], $customers)) ?>;
window.SF_TEAM = <?= json_encode(array_map(static fn ($t) => ['id' => (int) $t['id'], 'name' => $t['name'], 'color' => $t['color']], $team)) ?>;
window.SF_ME = <?= $me ?>;
</script>
<script src="/assets/js/calendar.js"></script>
