<?php /** @var array $projects */ ?>
<div class="page-head"><div class="title"><h1>Projecten</h1><p><?= count($projects) ?> projecten</p></div>
    <div class="page-actions"><?php if (can('projects.manage')): ?><button class="btn btn-primary" onclick="projectModal()"><?= icon('plus', 18) ?> Nieuw project</button><?php endif; ?></div>
</div>

<?php if (!$projects): ?>
    <div class="card"><div class="empty"><?= icon('briefcase', 56) ?><h3>Geen projecten</h3><p>Groepeer klanten, scripts en templates per project of product.</p></div></div>
<?php else: ?>
<div class="grid-stats" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));">
    <?php foreach ($projects as $p): ?>
        <a class="card" href="/projects/<?= (int) $p['id'] ?>" style="text-decoration:none;"><div class="card-body">
            <div class="flex items-center gap-3 mb-3">
                <span class="stat-icon" style="margin:0;width:44px;height:44px;background:<?= e($p['color']) ?>;"><?= icon('briefcase', 20) ?></span>
                <div><h3 style="font-size:var(--fs-md);"><?= e($p['name']) ?></h3><?php if ($p['status'] === 'archived'): ?><span class="badge badge-neutral">Gearchiveerd</span><?php endif; ?></div>
            </div>
            <p class="small text-muted" style="min-height:38px;"><?= e($p['description'] ? mb_substr($p['description'], 0, 90) : 'Geen omschrijving') ?></p>
            <div class="flex gap-4 mt-3 small">
                <span><strong><?= (int) $p['customer_count'] ?></strong> klanten</span>
                <span><strong><?= (int) $p['quote_count'] ?></strong> offertes</span>
            </div>
        </div></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function projectModal() {
    var m = SF.modal.open(`
        <div class="modal-head"><h3>Nieuw project</h3><button class="icon-btn" data-close>&times;</button></div>
        <form id="pf" action="/projects" method="post">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="${SF.csrf()}">
                <div class="field"><label class="label">Naam</label><input class="input" name="name" required></div>
                <div class="field"><label class="label">Omschrijving</label><textarea class="textarea" name="description"></textarea></div>
                <div class="field"><label class="label">Kleur</label><input class="input" type="color" name="color" value="#B33B62" style="height:44px;width:80px;"></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuleren</button><button class="btn btn-primary">Aanmaken</button></div>
        </form>`);
    SF.bindForm(m.querySelector('#pf'));
}
</script>
