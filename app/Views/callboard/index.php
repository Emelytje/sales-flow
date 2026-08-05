<?php
/** @var array|null $customer */
/** @var int $goal */
/** @var int $calls_today */
/** @var array $leaderboard */
?>
<div class="page-head">
    <div class="title"><h1><?= icon('phone-call', 26) ?> Callboard</h1><p>Bel je lijst af — één klant tegelijk</p></div>
    <div class="page-actions">
        <div class="chip"><?= icon('target', 16) ?> Doel: <strong id="goalCount"><?= $calls_today ?></strong>/<?= $goal ?></div>
    </div>
</div>

<div class="progress mb-6" style="height:10px;"><span id="goalBar" style="width:<?= $goal ? min(100, round($calls_today / $goal * 100)) : 0 ?>%"></span></div>

<div class="dash-grid" style="grid-template-columns:1.7fr 1fr;">
    <div class="col">
        <div class="card" id="callCard" data-goal="<?= $goal ?>">
            <div class="card-body" style="padding:2.5rem;">
                <div id="callEmpty" class="empty <?= $customer ? 'hidden' : '' ?>">
                    <?= icon('check-circle', 56) ?><h3>Alles gebeld! 🎉</h3><p>Er staan geen klanten meer in je belwachtrij.</p>
                </div>

                <div id="callBody" class="<?= $customer ? '' : 'hidden' ?>">
                    <div class="flex items-center justify-between mb-4 wrap gap-3">
                        <span class="badge badge-rose badge-dot">In de wachtrij</span>
                        <div id="stopwatch" style="font-size:1.6rem;font-weight:800;font-variant-numeric:tabular-nums;color:var(--rose-dark);">00:00</div>
                    </div>

                    <h1 id="coName" style="font-size:2.2rem;line-height:1.1;margin-bottom:.5rem;"><?= e($customer['company_name'] ?? '') ?></h1>
                    <div class="flex gap-4 wrap text-muted small mb-6">
                        <span id="coSector"><?= icon('briefcase', 14) ?> <?= e($customer['sector_name'] ?? '—') ?></span>
                        <span id="coCity"><?= icon('map-pin', 14) ?> <?= e($customer['city'] ?? '—') ?></span>
                    </div>

                    <div class="flex gap-2 wrap mb-6">
                        <a id="btnCall" class="btn btn-primary btn-lg" href="tel:<?= e($customer['phone'] ?? $customer['contact_phone'] ?? '') ?>" onclick="Callboard.startTimer()"><?= icon('phone', 20) ?> <span id="coPhone"><?= e($customer['phone'] ?? $customer['contact_phone'] ?? 'Geen nummer') ?></span></a>
                        <a id="btnMail" class="btn btn-outline btn-lg" href="mailto:<?= e($customer['email'] ?? '') ?>"><?= icon('mail', 20) ?></a>
                        <a id="btnWeb" class="btn btn-outline btn-lg" href="<?= e($customer['website'] ?? '#') ?>" target="_blank"><?= icon('globe', 20) ?></a>
                        <a id="btnMap" class="btn btn-outline btn-lg" href="https://www.google.com/maps/search/?api=1&query=<?= urlencode(($customer['city'] ?? '')) ?>" target="_blank"><?= icon('map-pin', 20) ?></a>
                        <a id="btnCard" class="btn btn-ghost btn-lg" href="/customers/<?= (int) ($customer['id'] ?? 0) ?>" target="_blank"><?= icon('external', 20) ?></a>
                    </div>

                    <div class="field"><textarea class="textarea" id="callNote" placeholder="Notitie over dit gesprek…" style="min-height:80px;"></textarea></div>

                    <h4 class="mb-2 mt-4">Resultaat</h4>
                    <div class="flex gap-2 wrap" id="outcomes">
                        <?php
                        $buttons = [
                            'called' => ['Gebeld', 'btn-outline'], 'no_answer' => ['Geen gehoor', 'btn-outline'],
                            'emailed' => ['Gemaild', 'btn-outline'], 'follow_up' => ['Opvolgen', 'btn-outline'],
                            'appointment' => ['Afspraak', 'btn-success'], 'interested' => ['Interesse', 'btn-success'],
                            'quotation' => ['Offerte', 'btn-success'], 'not_interested' => ['Geen interesse', 'btn-danger'],
                            'customer' => ['Klant! 🎉', 'btn-primary'],
                        ];
                        foreach ($buttons as $key => $b): ?>
                            <button class="btn <?= $b[1] ?>" onclick="Callboard.submit('<?= $key ?>')"><?= e($b[0]) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="callCustomerId" value="<?= (int) ($customer['id'] ?? 0) ?>">
                    <div class="mt-4"><button class="btn btn-ghost btn-sm" onclick="Callboard.skip()"><?= icon('chevron-right', 16) ?> Overslaan</button></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card"><div class="card-head"><h3><?= icon('award', 18) ?> Leaderboard</h3><span class="badge badge-rose"><?= e(date('F')) ?></span></div>
        <div class="card-body" id="lbBody">
            <?php foreach ($leaderboard as $i => $rep): ?>
                <div class="flex items-center gap-3" style="padding:9px 0;<?= $i ? 'border-top:1px solid var(--border);' : '' ?>">
                    <div style="width:22px;font-weight:800;color:var(--text-muted);text-align:center;"><?= $i + 1 ?></div>
                    <span class="avatar avatar-sm" style="background:<?= e($rep['color']) ?>"><?= e(initials($rep['name'])) ?></span>
                    <div class="flex-1" style="min-width:0;"><div style="font-weight:700;font-size:var(--fs-sm);"><?= e($rep['name']) ?></div><div class="tiny text-muted"><?= (int) $rep['calls'] ?> gesprekken</div></div>
                </div>
            <?php endforeach; ?>
        </div></div>

        <div class="card"><div class="card-body">
            <h3 class="mb-2"><?= icon('help', 18) ?> Tips</h3>
            <ul class="small text-soft" style="line-height:1.9;">
                <li>De timer start automatisch bij het bellen.</li>
                <li>Kies een resultaat om automatisch door te gaan.</li>
                <li>Opvolgingen worden automatisch ingepland.</li>
            </ul>
        </div></div>
    </div>
</div>

<script src="/assets/js/callboard.js"></script>
