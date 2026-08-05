<?php /** @var array $customers */ ?>
<div class="page-head">
    <div class="title"><h1>Agenda</h1><p>Afspraken, taken en bezoeken</p></div>
    <div class="page-actions">
        <div class="flex gap-1" style="background:var(--surface-2);border-radius:var(--r-md);padding:3px;">
            <button class="btn btn-sm btn-ghost" data-view="month" id="vMonth">Maand</button>
            <button class="btn btn-sm btn-ghost" data-view="week" id="vWeek">Week</button>
            <button class="btn btn-sm btn-ghost" data-view="day" id="vDay">Dag</button>
        </div>
        <button class="btn btn-primary" onclick="Agenda.openCreate()"><?= icon('plus', 18) ?> Afspraak</button>
    </div>
</div>

<div class="card"><div class="card-body">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <button class="icon-btn" onclick="Agenda.prev()"><?= icon('chevron-left', 20) ?></button>
            <button class="btn btn-outline btn-sm" onclick="Agenda.today()">Vandaag</button>
            <button class="icon-btn" onclick="Agenda.next()"><?= icon('chevron-right', 20) ?></button>
        </div>
        <h2 id="calTitle" style="font-size:var(--fs-lg);"></h2>
        <div class="flex gap-3 small text-muted wrap">
            <span class="stage-tag" style="color:var(--text-soft)"><span style="width:9px;height:9px;border-radius:3px;background:#7A6FF0;display:inline-block;margin-right:4px;"></span>Afspraak</span>
            <span class="stage-tag" style="color:var(--text-soft)"><span style="width:9px;height:9px;border-radius:3px;background:#2FA36B;display:inline-block;margin-right:4px;"></span>Gesprek</span>
            <span class="stage-tag" style="color:var(--text-soft)"><span style="width:9px;height:9px;border-radius:3px;background:#D98A2B;display:inline-block;margin-right:4px;"></span>Taak</span>
        </div>
    </div>
    <div id="calendar"></div>
</div></div>

<script>window.SF_CUSTOMERS = <?= json_encode(array_map(static fn ($c) => ['id' => (int) $c['id'], 'name' => $c['company_name']], $customers)) ?>;</script>
<script src="/assets/js/calendar.js"></script>
