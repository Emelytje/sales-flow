<?php
/** @var array $messages */ /** @var array $templates */ /** @var array $customers */
$statusMeta = ['sent' => ['Verzonden', 'info'], 'failed' => ['Mislukt', 'danger'], 'draft' => ['Concept', 'neutral'], 'queued' => ['Wachtrij', 'warning'], 'scheduled' => ['Gepland', 'warning']];
?>
<div class="page-head">
    <div class="title"><h1>E-mail</h1><p>Verstuur met open- &amp; klik-tracking · gratis via SMTP</p></div>
    <div class="page-actions">
        <a class="btn btn-outline" href="/emails/inbox"><?= icon('mail', 18) ?> Inbox</a>
        <button class="btn btn-outline" onclick="tplModal()"><?= icon('file-text', 18) ?> Sjabloon</button>
        <button class="btn btn-primary" onclick="composeModal()"><?= icon('send', 18) ?> Nieuwe e-mail</button>
    </div>
</div>

<div class="dash-grid" style="grid-template-columns:2fr 1fr;">
    <div class="col">
        <div class="card"><div class="card-head"><h3><?= icon('send', 18) ?> Verzonden</h3></div>
        <?php if (!$messages): ?>
            <div class="empty"><?= icon('mail', 56) ?><h3>Nog geen e-mails</h3><p>Verstuur je eerste getrackte e-mail.</p></div>
        <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Ontvanger</th><th>Onderwerp</th><th>Status</th><th>Opens</th><th>Kliks</th><th>Datum</th></tr></thead>
            <tbody>
            <?php foreach ($messages as $m): [$lbl, $col] = $statusMeta[$m['status']] ?? ['—', 'neutral']; ?>
                <tr>
                    <td><div style="font-weight:600;font-size:var(--fs-sm);"><?= e($m['to_email']) ?></div><?php if ($m['company_name']): ?><div class="tiny text-muted"><?= e($m['company_name']) ?></div><?php endif; ?></td>
                    <td class="small"><?= e($m['subject']) ?></td>
                    <td><span class="badge badge-<?= $col ?> badge-dot"><?= e($lbl) ?></span></td>
                    <td><span class="badge <?= $m['open_count'] ? 'badge-success' : 'badge-neutral' ?>"><?= (int) $m['open_count'] ?></span></td>
                    <td><span class="badge <?= $m['click_count'] ? 'badge-rose' : 'badge-neutral' ?>"><?= (int) $m['click_count'] ?></span></td>
                    <td class="small text-muted"><?= e(time_ago($m['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
        </div>
    </div>

    <div class="col">
        <div class="card"><div class="card-head"><h3><?= icon('file-text', 18) ?> Sjablonen</h3></div><div class="card-body">
            <?php if (!$templates): ?><p class="text-muted small">Nog geen sjablonen.</p><?php endif; ?>
            <?php foreach ($templates as $t): ?>
                <div class="flex items-center gap-3" style="padding:8px 0;border-bottom:1px solid var(--border);">
                    <div class="flex-1"><div style="font-weight:700;font-size:var(--fs-sm);"><?= e($t['name']) ?></div><div class="tiny text-muted"><?= e($t['subject']) ?></div></div>
                    <button class="btn btn-outline btn-sm" onclick='composeModal(<?= json_encode(["subject" => $t["subject"], "body" => $t["body"]], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Gebruik</button>
                    <button class="icon-btn" style="width:30px;height:30px;" onclick="delTpl(<?= (int) $t['id'] ?>)"><?= icon('trash', 15) ?></button>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>

<script>
window.SF_MAIL_CUSTOMERS = <?= json_encode(array_map(static fn ($c) => ['id' => (int) $c['id'], 'name' => $c['company_name'], 'email' => $c['email']], $customers)) ?>;
function composeModal(tpl) {
    tpl = tpl || {};
    var opts = window.SF_MAIL_CUSTOMERS.map(c => `<option value="${c.id}" data-email="${c.email||''}">${esc(c.name)}</option>`).join('');
    var m = SF.modal.open(`
        <div class="modal-head"><h3>Nieuwe e-mail</h3><button class="icon-btn" data-close>&times;</button></div>
        <form id="mf" action="/emails/send" method="post">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="${SF.csrf()}">
                <div class="field"><label class="label">Klant (optioneel)</label>
                    <select class="select" name="customer_id" onchange="var o=this.selectedOptions[0];if(o.dataset.email)document.querySelector('[name=to_email]').value=o.dataset.email;">
                        <option value="">— Handmatig —</option>${opts}</select></div>
                <div class="field"><label class="label">Aan</label><input class="input" type="email" name="to_email" required></div>
                <div class="field"><label class="label">Onderwerp</label><input class="input" name="subject" value="${esc(tpl.subject||'')}" required></div>
                <div class="field"><label class="label">Bericht</label><textarea class="textarea" name="body" style="min-height:180px;" required>${esc(tpl.body||'')}</textarea></div>
                <p class="tiny text-muted">Opens en kliks worden automatisch gemeten. Je handtekening wordt toegevoegd.</p>
            </div>
            <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuleren</button><button class="btn btn-primary">Verzenden</button></div>
        </form>`, { large: true });
    SF.bindForm(m.querySelector('#mf'), () => { SF.modal.close(); SF.toast('E-mail verzonden', 'success'); setTimeout(()=>location.reload(), 800); });
}
function tplModal() {
    var m = SF.modal.open(`
        <div class="modal-head"><h3>Nieuw sjabloon</h3><button class="icon-btn" data-close>&times;</button></div>
        <form id="tf" action="/emails/templates" method="post">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="${SF.csrf()}">
                <div class="field"><label class="label">Naam</label><input class="input" name="name" required></div>
                <div class="field"><label class="label">Onderwerp</label><input class="input" name="subject" required></div>
                <div class="field"><label class="label">Bericht</label><textarea class="textarea" name="body" style="min-height:160px;" required></textarea></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuleren</button><button class="btn btn-primary">Opslaan</button></div>
        </form>`, { large: true });
    SF.bindForm(m.querySelector('#tf'), () => { SF.modal.close(); SF.toast('Sjabloon opgeslagen', 'success'); setTimeout(()=>location.reload(), 700); });
}
function delTpl(id) { SF.confirm('Sjabloon verwijderen?', () => SF.api('/emails/templates/' + id, { method: 'DELETE' }).then(()=>location.reload()), { danger: true, confirmText: 'Verwijderen' }); }
function esc(s){return String(s||'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
</script>
