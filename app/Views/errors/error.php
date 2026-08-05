<?php
/** @var int $code */
/** @var string $heading */
/** @var string $message */
?>
<div style="min-height:100vh;display:grid;place-items:center;padding:2rem;">
    <div class="card" style="max-width:460px;text-align:center;padding:3rem 2.5rem;">
        <div style="font-size:5rem;font-weight:800;letter-spacing:-.04em;background:linear-gradient(135deg,var(--rose),var(--rose-dark));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;line-height:1;">
            <?= e($code) ?>
        </div>
        <h2 style="margin:1rem 0 .5rem;"><?= e($heading) ?></h2>
        <p class="text-muted" style="margin-bottom:1.5rem;"><?= e($message) ?></p>
        <a class="btn btn-primary" href="/dashboard"><?= icon('dashboard', 18) ?> Terug naar dashboard</a>
    </div>
</div>
