<?php
/** @var string $content */
/** @var string $title */
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="theme-color" content="#E98CAB">
    <title><?= e($title ?? 'Aanmelden') ?> · SalesFlow Enterprise</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/assets/icons/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/design-system.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <script>
        (function () { var t = localStorage.getItem('sf-theme'); if (t && t !== 'system') document.documentElement.setAttribute('data-theme', t); })();
    </script>
    <style>
        .auth-wrap { min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; }
        .auth-aside { position: relative; overflow: hidden; color: #fff;
            background: linear-gradient(150deg, #E98CAB 0%, #B33B62 100%); padding: 3rem; display: flex; flex-direction: column; justify-content: space-between; }
        .auth-aside::before, .auth-aside::after { content: ''; position: absolute; border-radius: 50%; background: rgba(255,255,255,0.12); }
        .auth-aside::before { width: 460px; height: 460px; right: -160px; top: -140px; }
        .auth-aside::after { width: 320px; height: 320px; left: -120px; bottom: -120px; background: rgba(255,255,255,0.08); }
        .auth-aside .content { position: relative; z-index: 1; max-width: 420px; margin: auto 0; }
        .auth-aside h1 { font-size: 2.4rem; line-height: 1.15; margin-bottom: 1rem; }
        .auth-aside p { opacity: 0.92; font-size: 1.05rem; }
        .auth-brand { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 1.2rem; position: relative; z-index: 1; }
        .auth-brand .logo { width: 42px; height: 42px; border-radius: 12px; background: rgba(255,255,255,0.2); display: grid; place-items: center; font-size: 20px; }
        .auth-features { position: relative; z-index: 1; display: grid; gap: 14px; }
        .auth-feature { display: flex; align-items: center; gap: 12px; font-weight: 600; }
        .auth-feature svg { width: 22px; height: 22px; }
        .auth-main { display: grid; place-items: center; padding: 2rem; background: var(--bg); }
        .auth-card { width: 100%; max-width: 400px; animation: fadeUp .5s var(--ease); }
        .auth-card h2 { font-size: 1.7rem; margin-bottom: 4px; }
        @media (max-width: 900px) { .auth-wrap { grid-template-columns: 1fr; } .auth-aside { display: none; } }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-aside">
        <div class="auth-brand"><div class="logo">SF</div> SalesFlow Enterprise</div>
        <div class="content">
            <h1>Verkoop slimmer.<br>Groei sneller.</h1>
            <p>Eén platform voor je klanten, gesprekken, offertes en agenda — met live inzicht in je pijplijn.</p>
        </div>
        <div class="auth-features">
            <div class="auth-feature"><?= icon('phone-call') ?> Geoptimaliseerd callboard</div>
            <div class="auth-feature"><?= icon('calendar') ?> Slimme agenda &amp; boekingen</div>
            <div class="auth-feature"><?= icon('trending-up') ?> Realtime pijplijn &amp; rapporten</div>
        </div>
    </div>
    <div class="auth-main">
        <div class="auth-card">
            <?= $content ?>
        </div>
    </div>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
