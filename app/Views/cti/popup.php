<?php
/** @var string $number */
/** @var array $match */
$found = !empty($match['found']);
?>
<div style="min-height:100vh;display:grid;place-items:center;padding:1.5rem;background:var(--bg);">
    <div class="card anim-in" style="max-width:420px;width:100%;text-align:center;padding:2.5rem 2rem;">
        <div class="avatar avatar-lg" style="margin:0 auto 1rem;width:72px;height:72px;font-size:1.6rem;
            <?= $found ? '' : 'background:linear-gradient(135deg,#9C8F98,#6B5E68);' ?>">
            <?= $found ? e(initials($match['name'] ?? '?')) : '?' ?>
        </div>

        <div class="badge badge-rose badge-dot" style="margin-bottom:.75rem;">Inkomende oproep</div>

        <?php if ($found): ?>
            <h2 style="margin-bottom:.25rem;"><?= e($match['name']) ?></h2>
            <?php if (($match['type'] ?? '') === 'contact' && !empty($match['company'])): ?>
                <p class="text-muted"><?= e($match['company']) ?></p>
            <?php endif; ?>
            <p class="text-soft" style="margin:.5rem 0 1.5rem;font-weight:600;letter-spacing:.02em;"><?= e($number) ?></p>
            <a class="btn btn-primary btn-block btn-lg" href="<?= e($match['url']) ?>" target="_blank">
                <?= icon('external', 18) ?> Open klantkaart
            </a>
        <?php else: ?>
            <h2 style="margin-bottom:.25rem;">Onbekende beller</h2>
            <p class="text-soft" style="margin:.5rem 0 1.5rem;font-weight:600;letter-spacing:.02em;"><?= e($number) ?></p>
            <a class="btn btn-primary btn-block btn-lg" href="/customers/create?phone=<?= urlencode($number) ?>" target="_blank">
                <?= icon('plus', 18) ?> Nieuwe klant aanmaken
            </a>
        <?php endif; ?>

        <p class="tiny text-muted" style="margin-top:1.25rem;">SalesFlow CTI · scherm-pop</p>
    </div>
</div>
<link rel="stylesheet" href="/assets/css/design-system.css">
