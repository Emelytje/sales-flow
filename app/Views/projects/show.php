<?php /** @var array $project */ /** @var array $templates */ /** @var int $customers */
$p = $project;
?>
<div class="breadcrumb"><a href="/projects">Projecten</a> <?= icon('chevron-right', 14) ?> <?= e($p['name']) ?></div>
<div class="page-head">
    <div class="title"><h1><?= e($p['name']) ?></h1><p><?= $customers ?> gekoppelde klanten</p></div>
    <div class="page-actions"><a class="btn btn-outline" href="/customers?project=<?= (int) $p['id'] ?>"><?= icon('building', 18) ?> Klanten</a></div>
</div>

<form action="/projects/<?= (int) $p['id'] ?>" method="post">
    <?= csrf_field() ?><input type="hidden" name="_method" value="PUT">
    <div class="dash-grid" style="grid-template-columns:1fr 1fr;">
        <div class="col">
            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('briefcase', 18) ?> Details</h3>
                <div class="field"><label class="label">Naam</label><input class="input" name="name" value="<?= e($p['name']) ?>"></div>
                <div class="field"><label class="label">Omschrijving</label><textarea class="textarea" name="description"><?= e($p['description']) ?></textarea></div>
                <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                    <div class="field"><label class="label">Kleur</label><input class="input" type="color" name="color" value="<?= e($p['color']) ?>" style="height:44px;"></div>
                    <div class="field"><label class="label">Status</label><select class="select" name="status"><option value="active" <?= $p['status'] === 'active' ? 'selected' : '' ?>>Actief</option><option value="archived" <?= $p['status'] === 'archived' ? 'selected' : '' ?>>Gearchiveerd</option></select></div>
                </div>
                <div class="field"><label class="label">Demovideo (URL)</label><input class="input" name="demo_video_url" value="<?= e($p['demo_video_url']) ?>"></div>
            </div></div>
        </div>
        <div class="col">
            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('phone-call', 18) ?> Belscript</h3>
                <div class="field"><textarea class="textarea" name="call_script" style="min-height:160px;" placeholder="Script voor het verkoopgesprek…"><?= e($p['call_script']) ?></textarea></div>
            </div></div>
            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('help', 18) ?> FAQ</h3>
                <div class="field"><textarea class="textarea" name="faq" style="min-height:120px;" placeholder="Veelgestelde vragen…"><?= e($p['faq']) ?></textarea></div>
            </div></div>
        </div>
    </div>
    <div class="flex justify-between mt-4"><span></span><button class="btn btn-primary btn-lg"><?= icon('check', 18) ?> Opslaan</button></div>
</form>
