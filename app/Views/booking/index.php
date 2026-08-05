<?php /** @var array $bookings */
$statuses = ['pending' => ['In afwachting', 'warning'], 'approved' => ['Goedgekeurd', 'success'], 'rejected' => ['Afgewezen', 'danger'], 'cancelled' => ['Geannuleerd', 'neutral']];
?>
<div class="page-head"><div class="title"><h1>Boekingen</h1><p>Afspraakaanvragen via je boekingspagina</p></div>
    <div class="page-actions"><span class="chip"><?= icon('link', 16) ?> jouwdomein.tld/book/<span class="text-accent"><?= e(auth()['booking_slug'] ?? 'jouw-slug') ?></span></span></div>
</div>

<div class="card">
    <?php if (!$bookings): ?>
        <div class="empty"><?= icon('clock', 56) ?><h3>Nog geen boekingen</h3><p>Deel je boekingslink en klanten plannen zelf een afspraak in.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Wanneer</th><th>Gast</th><th>Onderwerp</th><th>Rep</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $b): [$lbl, $col] = $statuses[$b['status']] ?? ['—', 'neutral']; ?>
            <tr>
                <td><div style="font-weight:700;"><?= e(date_nl($b['starts_at'], 'd/m')) ?> <?= e(date_nl($b['starts_at'], 'H:i')) ?></div></td>
                <td><div style="font-weight:600;"><?= e($b['guest_name']) ?></div><div class="tiny text-muted"><?= e($b['guest_email']) ?><?= $b['guest_company'] ? ' · ' . e($b['guest_company']) : '' ?></div></td>
                <td class="small"><?= e($b['subject']) ?></td>
                <td class="small"><?= e($b['rep_name']) ?></td>
                <td><span class="badge badge-<?= $col ?> badge-dot"><?= e($lbl) ?></span></td>
                <td>
                    <?php if ($b['status'] === 'pending'): ?>
                        <div class="flex gap-1">
                            <button class="btn btn-success btn-sm" onclick="bk('approve',<?= (int) $b['id'] ?>)"><?= icon('check', 15) ?></button>
                            <button class="btn btn-danger btn-sm" onclick="bk('reject',<?= (int) $b['id'] ?>)"><?= icon('x', 15) ?></button>
                        </div>
                    <?php elseif ($b['teams_join_url']): ?>
                        <a class="btn btn-outline btn-sm" href="<?= e($b['teams_join_url']) ?>" target="_blank">🎥 Link</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

<script>
function bk(action, id) {
    SF.api('/bookings/' + id + '/' + action, { method: 'POST' }).then(() => {
        SF.toast(action === 'approve' ? 'Afspraak goedgekeurd — bevestiging verstuurd' : 'Aanvraag afgewezen', 'success');
        setTimeout(() => location.reload(), 900);
    });
}
</script>
