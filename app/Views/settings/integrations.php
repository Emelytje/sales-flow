<?php
/** @var bool $google */ /** @var bool $maps */ /** @var bool $ms */ /** @var bool $push */
/** @var array $settings */ /** @var string $appUrl */
$badge = static fn (bool $on): string => $on
    ? '<span class="badge badge-success badge-dot">Verbonden</span>'
    : '<span class="badge badge-neutral badge-dot">Niet geconfigureerd</span>';
$items = [
    ['Google Workspace', 'mail', 'Gmail, Agenda & Places — voor e-mailkoppeling en synchronisatie.', $google],
    ['Google Maps', 'map', 'Kaart, routes en Places-lookup op de kaartpagina.', $maps],
    ['Microsoft 365', 'video', 'Teams-vergaderingen en Outlook-agenda via Microsoft Graph.', $ms],
    ['Web Push', 'bell', 'Pushmeldingen naar de PWA op desktop en mobiel.', $push],
];
?>
<div class="breadcrumb"><a href="/settings">Instellingen</a> <?= icon('chevron-right', 14) ?> Integraties</div>
<div class="page-head"><div class="title"><h1>Integraties</h1><p>Koppelingen met externe diensten</p></div></div>

<div class="grid-stats" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
    <?php foreach ($items as $it): ?>
        <div class="card"><div class="card-body">
            <div class="flex items-center justify-between mb-3">
                <span class="stat-icon bg-rose" style="margin:0;width:42px;height:42px;"><?= icon($it[1], 20) ?></span>
                <?= $badge($it[3]) ?>
            </div>
            <h3 style="font-size:var(--fs-md);"><?= e($it[0]) ?></h3>
            <p class="small text-muted mt-1"><?= e($it[2]) ?></p>
        </div></div>
    <?php endforeach; ?>
</div>

<?php $gConnected = \App\Services\GoogleService::connected((int) (auth()['id'] ?? 0)); ?>
<div class="card mt-4"><div class="card-body">
    <div class="flex items-center justify-between wrap gap-3">
        <div>
            <h3 class="mb-1"><?= icon('mail', 18) ?> Gmail-koppeling</h3>
            <p class="small text-muted">Lees je inbox in SalesFlow. <?= $google ? '' : 'Vul eerst GOOGLE_CLIENT_ID/SECRET in <code>.env</code> in.' ?></p>
        </div>
        <div>
            <?php if (!$google): ?>
                <span class="badge badge-neutral">Niet geconfigureerd</span>
            <?php elseif ($gConnected): ?>
                <span class="badge badge-success badge-dot" style="margin-right:8px;">Gekoppeld</span>
                <form action="/oauth/google/disconnect" method="post" style="display:inline;"><?= csrf_field() ?><button class="btn btn-ghost btn-sm">Ontkoppelen</button></form>
            <?php else: ?>
                <a class="btn btn-primary btn-sm" href="/oauth/google/connect"><?= icon('link', 16) ?> Gmail koppelen</a>
            <?php endif; ?>
        </div>
    </div>
</div></div>

<div class="card mt-4"><div class="card-body">
    <h3 class="mb-2"><?= icon('phone-call', 18) ?> CTI / Telefonie webhook</h3>
    <p class="small text-muted mb-3">Stel deze URL in bij je telefooncentrale (3CX, Zadarma, Twilio) voor scherm-pop bij inkomende oproepen. Zie <code>docs/CTI-SETUP.md</code>.</p>
    <table class="table"><tbody>
        <tr><td style="font-weight:600;">Webhook (POST)</td><td style="font-family:monospace;word-break:break-all;"><?= e($appUrl) ?>/api/v1/cti/incoming</td></tr>
        <tr><td style="font-weight:600;">Reverse lookup (GET)</td><td style="font-family:monospace;word-break:break-all;"><?= e($appUrl) ?>/api/v1/cti/lookup?number=%number%</td></tr>
        <tr><td style="font-weight:600;">MicroSIP screen-pop</td><td style="font-family:monospace;word-break:break-all;"><?= e($appUrl) ?>/cti/popup?number=%number%</td></tr>
        <tr><td style="font-weight:600;">Secret ingesteld</td><td><?= ($settings['cti_webhook_secret'] ?? '') !== '' ? '<span class="badge badge-success">Ja</span>' : '<span class="badge badge-warning">Nee — stel er één in bij Instellingen</span>' ?></td></tr>
    </tbody></table>
</div></div>
