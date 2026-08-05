<?php
/** @var array $customer */
/** @var array $contacts */
/** @var array $timeline */
/** @var array $notes */
/** @var string $mapsKey */
$c = $customer;
$stages = ['lead' => 'Lead', 'contacted' => 'Contact', 'qualified' => 'Gekwalificeerd', 'proposal' => 'Offerte', 'won' => 'Gewonnen', 'lost' => 'Verloren'];
$typeIcon = ['call' => ['phone-call', 'bg-green'], 'email' => ['mail', 'bg-blue'], 'meeting' => ['calendar', 'bg-violet'], 'note' => ['file-text', 'bg-amber'], 'quotation' => ['file-text', 'bg-rose'], 'status_change' => ['activity', 'bg-teal'], 'task' => ['check', 'bg-amber'], 'system' => ['zap', 'bg-teal']];
$mapsQuery = urlencode(trim(($c['address'] ?? '') . ' ' . ($c['postal_code'] ?? '') . ' ' . ($c['city'] ?? '')));
?>
<div class="breadcrumb"><a href="/customers">Klanten</a> <?= icon('chevron-right', 14) ?> <?= e($c['company_name']) ?></div>

<div class="card mb-4"><div class="card-body">
    <div class="flex items-center gap-4 wrap">
        <span class="avatar avatar-lg" style="border-radius:16px;background:<?= e($c['owner_color'] ?: '#E98CAB') ?>;"><?= e(initials($c['company_name'])) ?></span>
        <div class="flex-1" style="min-width:200px;">
            <div class="flex items-center gap-3 wrap">
                <h1 style="font-size:var(--fs-xl);"><?= e($c['company_name']) ?></h1>
                <span class="stage-tag stage-<?= e($c['pipeline_stage']) ?>"><?= e($stages[$c['pipeline_stage']] ?? '') ?></span>
                <span class="badge badge-<?= $c['priority'] === 'high' ? 'danger' : ($c['priority'] === 'medium' ? 'warning' : 'neutral') ?>">Prioriteit: <?= e($c['priority']) ?></span>
            </div>
            <div class="flex gap-4 wrap text-muted small mt-2">
                <?php if ($c['sector_name']): ?><span><?= icon('briefcase', 14) ?> <?= e($c['sector_name']) ?></span><?php endif; ?>
                <?php if ($c['city']): ?><span><?= icon('map-pin', 14) ?> <?= e($c['city']) ?></span><?php endif; ?>
                <?php if ($c['owner_name']): ?><span><?= icon('user', 14) ?> <?= e($c['owner_name']) ?></span><?php endif; ?>
                <span><?= icon('target', 14) ?> <?= money($c['estimated_value']) ?></span>
            </div>
        </div>
        <div class="flex gap-2 wrap">
            <?php if ($c['phone']): ?><a class="btn btn-primary" href="tel:<?= e($c['phone']) ?>"><?= icon('phone', 18) ?> Bellen</a><?php endif; ?>
            <?php if ($c['email']): ?><a class="btn btn-outline" href="mailto:<?= e($c['email']) ?>"><?= icon('mail', 18) ?></a><?php endif; ?>
            <?php if ($c['website']): ?><a class="btn btn-outline" href="<?= e(str_starts_with($c['website'], 'http') ? $c['website'] : 'https://' . $c['website']) ?>" target="_blank"><?= icon('globe', 18) ?></a><?php endif; ?>
            <?php if ($mapsQuery): ?><a class="btn btn-outline" href="https://www.google.com/maps/search/?api=1&query=<?= $mapsQuery ?>" target="_blank"><?= icon('map-pin', 18) ?></a><?php endif; ?>
            <?php if (can('customers.edit')): ?><a class="btn btn-outline" href="/customers/<?= (int) $c['id'] ?>/edit"><?= icon('edit', 18) ?></a><?php endif; ?>
            <?php if (can('customers.delete')): ?>
                <button class="btn btn-ghost" onclick="SF.confirm('Deze klant en alle gekoppelde gegevens verwijderen?',()=>SF.api('/customers/<?= (int) $c['id'] ?>',{method:'DELETE'}).then(()=>location.href='/customers'),{danger:true,confirmText:'Verwijderen'})"><?= icon('trash', 18) ?></button>
            <?php endif; ?>
        </div>
    </div>
</div></div>

<div class="dash-grid" style="grid-template-columns:1.6fr 1fr;">
    <div class="col">
        <div class="card"><div class="card-body">
            <h3 class="mb-4"><?= icon('zap', 18) ?> Activiteit loggen</h3>
            <form action="/customers/<?= (int) $c['id'] ?>/activities" method="post">
                <?= csrf_field() ?>
                <div class="flex gap-2 mb-4 wrap">
                    <?php foreach (['call' => 'Gesprek', 'email' => 'E-mail', 'meeting' => 'Afspraak', 'note' => 'Notitie'] as $k => $lbl): ?>
                        <label class="pill"><input type="radio" name="type" value="<?= $k ?>" <?= $k === 'call' ? 'checked' : '' ?> style="margin-right:6px;"><?= e($lbl) ?></label>
                    <?php endforeach; ?>
                </div>
                <div class="field"><input class="input" name="subject" placeholder="Onderwerp (bijv. 'Opvolging offerte')"></div>
                <div class="field"><textarea class="textarea" name="body" placeholder="Wat is er besproken?"></textarea></div>
                <button class="btn btn-primary"><?= icon('check', 18) ?> Loggen</button>
            </form>
        </div></div>

        <div class="card"><div class="card-head"><h3><?= icon('activity', 18) ?> Tijdlijn</h3></div><div class="card-body">
            <?php if (!$timeline): ?><p class="text-muted small">Nog geen activiteit gelogd.</p><?php else: ?>
                <div class="timeline">
                    <?php foreach ($timeline as $t): [$ic, $bg] = $typeIcon[$t['type']] ?? ['activity', 'bg-teal']; ?>
                        <div class="tl-item">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="feed-icon <?= $bg ?>" style="width:28px;height:28px;"><?= icon($ic, 14) ?></span>
                                <strong style="font-size:var(--fs-sm);"><?= e($t['subject'] ?: ucfirst($t['type'])) ?></strong>
                                <span class="tl-time" style="margin-left:auto;"><?= e(time_ago($t['occurred_at'])) ?></span>
                            </div>
                            <?php if ($t['body']): ?><p class="small text-soft" style="margin-left:36px;"><?= nl2br(e($t['body'])) ?></p><?php endif; ?>
                            <?php if ($t['user_name']): ?><div class="tiny text-muted" style="margin-left:36px;">door <?= e($t['user_name']) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div></div>
    </div>

    <div class="col">
        <div class="card"><div class="card-head"><h3><?= icon('users', 18) ?> Contacten</h3></div><div class="card-body">
            <?php if (!$contacts): ?><p class="text-muted small mb-4">Nog geen contactpersonen.</p><?php else: foreach ($contacts as $ct): ?>
                <div class="flex items-center gap-3" style="padding:8px 0;<?= '' ?>">
                    <span class="avatar avatar-sm"><?= e(initials($ct['first_name'] . ' ' . $ct['last_name'])) ?></span>
                    <div class="flex-1" style="min-width:0;">
                        <div style="font-weight:700;font-size:var(--fs-sm);"><?= e(trim($ct['first_name'] . ' ' . $ct['last_name'])) ?> <?php if ($ct['is_primary']): ?><span class="badge badge-rose">Primair</span><?php endif; ?></div>
                        <div class="tiny text-muted"><?= e($ct['job_title'] ?: '—') ?></div>
                    </div>
                    <?php if ($ct['phone'] || $ct['mobile']): ?><a class="icon-btn" href="tel:<?= e($ct['mobile'] ?: $ct['phone']) ?>" style="width:32px;height:32px;"><?= icon('phone', 16) ?></a><?php endif; ?>
                    <?php if ($ct['email']): ?><a class="icon-btn" href="mailto:<?= e($ct['email']) ?>" style="width:32px;height:32px;"><?= icon('mail', 16) ?></a><?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
            <button class="btn btn-outline btn-sm btn-block mt-2" onclick="openContactModal(<?= (int) $c['id'] ?>)"><?= icon('plus', 16) ?> Contact toevoegen</button>
        </div></div>

        <div class="card"><div class="card-head"><h3><?= icon('file-text', 18) ?> Notities</h3></div><div class="card-body">
            <form action="/customers/<?= (int) $c['id'] ?>/notes" method="post" class="mb-4">
                <?= csrf_field() ?>
                <div class="field" style="margin-bottom:8px;"><textarea class="textarea" name="body" placeholder="Nieuwe notitie…" style="min-height:70px;"></textarea></div>
                <button class="btn btn-primary btn-sm"><?= icon('plus', 16) ?> Opslaan</button>
            </form>
            <?php foreach ($notes as $n): ?>
                <div style="padding:10px;background:var(--surface-2);border-radius:var(--r-md);margin-bottom:8px;">
                    <p class="small"><?= nl2br(e($n['body'])) ?></p>
                    <div class="tiny text-muted mt-2"><?= e($n['user_name'] ?? '') ?> · <?= e(time_ago($n['created_at'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>

<script>
function openContactModal(customerId) {
    var m = SF.modal.open(`
        <div class="modal-head"><h3>Contact toevoegen</h3><button class="icon-btn" data-close>&times;</button></div>
        <form id="contactForm" action="/contacts" method="post">
            <div class="modal-body">
                <input type="hidden" name="customer_id" value="${customerId}">
                <input type="hidden" name="_csrf" value="${SF.csrf()}">
                <div class="grid gap-3" style="grid-template-columns:1fr 1fr;">
                    <div class="field"><label class="label">Voornaam *</label><input class="input" name="first_name" required></div>
                    <div class="field"><label class="label">Achternaam</label><input class="input" name="last_name"></div>
                </div>
                <div class="field"><label class="label">Functie</label><input class="input" name="job_title"></div>
                <div class="grid gap-3" style="grid-template-columns:1fr 1fr;">
                    <div class="field"><label class="label">E-mail</label><input class="input" type="email" name="email"></div>
                    <div class="field"><label class="label">GSM</label><input class="input" name="mobile"></div>
                </div>
                <label class="flex items-center gap-2 small"><input type="checkbox" name="is_primary" value="1"> Primair contact</label>
            </div>
            <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuleren</button><button class="btn btn-primary">Toevoegen</button></div>
        </form>`, { large: false });
    SF.bindForm(m.querySelector('#contactForm'));
}
</script>
