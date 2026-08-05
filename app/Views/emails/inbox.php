<?php /** @var bool $configured */ /** @var bool $connected */ /** @var array $messages */ ?>
<div class="page-head">
    <div class="title"><h1>Inbox</h1><p>Je Gmail-inbox, live in SalesFlow</p></div>
    <div class="page-actions">
        <a class="btn btn-outline" href="/emails"><?= icon('send', 18) ?> Verzonden</a>
        <?php if ($connected): ?>
            <form action="/oauth/google/disconnect" method="post" onsubmit="return confirm('Gmail ontkoppelen?')"><?= csrf_field() ?><button class="btn btn-ghost"><?= icon('x', 18) ?> Ontkoppelen</button></form>
        <?php endif; ?>
    </div>
</div>

<?php if (!$configured): ?>
    <div class="card"><div class="empty">
        <?= icon('mail', 56) ?>
        <h3>Gmail nog niet geconfigureerd</h3>
        <p>Een beheerder moet eerst <code>GOOGLE_CLIENT_ID</code> en <code>GOOGLE_CLIENT_SECRET</code> in <code>.env</code> invullen.<br>Uitgaande e-mail met tracking werkt intussen gewoon via SMTP.</p>
        <a class="btn btn-outline mt-4" href="/settings/integrations">Naar integraties</a>
    </div></div>
<?php elseif (!$connected): ?>
    <div class="card"><div class="empty">
        <?= icon('mail', 56) ?>
        <h3>Koppel je Gmail</h3>
        <p>Verbind je Google-account om je inbox hier te lezen (alleen-lezen &amp; verzenden).</p>
        <a class="btn btn-primary mt-4" href="/oauth/google/connect"><?= icon('link', 18) ?> Gmail koppelen</a>
    </div></div>
<?php else: ?>
    <div class="card"><div class="card-body" style="padding:0;">
        <?php if (!$messages): ?>
            <div class="empty"><?= icon('mail', 56) ?><h3>Inbox leeg of niet bereikbaar</h3><p>Er zijn geen recente berichten opgehaald.</p></div>
        <?php else: foreach ($messages as $m): ?>
            <div class="flex items-center gap-3 inbox-row" style="padding:12px 18px;border-bottom:1px solid var(--border);cursor:pointer;<?= $m['unread'] ? 'background:rgba(233,140,171,0.06);' : '' ?>"
                 onclick="readMsg('<?= e($m['id']) ?>')">
                <span class="avatar avatar-sm"><?= e(initials(preg_replace('/<.*/', '', $m['from']))) ?></span>
                <div class="flex-1" style="min-width:0;">
                    <div class="flex items-center gap-2">
                        <strong style="font-size:var(--fs-sm);<?= $m['unread'] ? '' : 'font-weight:600;' ?>white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px;"><?= e(preg_replace('/\s*<.*/', '', $m['from'])) ?></strong>
                        <?php if ($m['unread']): ?><span class="badge badge-rose">nieuw</span><?php endif; ?>
                    </div>
                    <div style="font-size:var(--fs-sm);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($m['subject']) ?></div>
                    <div class="tiny text-muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($m['snippet']) ?></div>
                </div>
                <div class="tiny text-muted" style="white-space:nowrap;"><?= e(date_nl($m['date'], 'd/m')) ?></div>
            </div>
        <?php endforeach; endif; ?>
    </div></div>
<?php endif; ?>

<script>
async function readMsg(id) {
    try {
        const m = await SF.api('/emails/message/' + id);
        SF.modal.open(`
            <div class="modal-head"><h3 style="font-size:var(--fs-md);">${esc(m.subject)}</h3><button class="icon-btn" data-close>&times;</button></div>
            <div class="modal-body">
                <div class="small text-muted mb-3"><strong>${esc(m.from)}</strong><br>${esc(m.date)}</div>
                <hr class="divider">
                <div style="max-height:60vh;overflow:auto;">${m.body || '<em>Geen inhoud</em>'}</div>
            </div>
            <div class="modal-foot"><button class="btn btn-ghost" data-close>Sluiten</button></div>`, { large: true });
    } catch (e) {}
}
function esc(s){return String(s||'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
</script>
