<?php
/** @var string $content */
/** @var string $title */
use App\Core\Auth;
use App\Core\Database;

$user = Auth::user();
$path = $_SERVER['REQUEST_URI'] ?? '/';
$isActive = static fn (string $prefix): string =>
    ($prefix === '/dashboard' ? ($path === '/' || str_starts_with($path, '/dashboard')) : str_starts_with($path, $prefix))
        ? 'active' : '';

$unread = 0;
try {
    $unread = (int) Database::instance()->scalar(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL',
        [Auth::id()]
    );
} catch (\Throwable) {
}
?>
<!doctype html>
<html lang="nl" data-theme-init>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?php $vapid = (string) getenv('VAPID_PUBLIC_KEY'); if ($vapid !== ''): ?>
    <meta name="vapid-key" content="<?= e($vapid) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#E98CAB">
    <title><?= e($title ?? 'SalesFlow Enterprise') ?> · SalesFlow</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/assets/icons/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="/assets/css/design-system.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <script>
        (function () {
            var t = localStorage.getItem('sf-theme');
            if (t && t !== 'system') document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
<!-- Core runtime loaded before page content so window.SF is available to view scripts. -->
<script src="/assets/js/app.js"></script>
<div class="app">
    <?= \App\Core\View::renderPartial('layouts/sidebar', ['isActive' => $isActive, 'user' => $user]) ?>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn menu-toggle" aria-label="Menu"><?= icon('menu') ?></button>
            <form class="search-bar" action="/search" method="get" role="search">
                <?= icon('search') ?>
                <input class="input" type="search" name="q" placeholder="Zoek klanten, contacten, offertes…" autocomplete="off">
            </form>
            <div class="topbar-actions">
                <a class="icon-btn" href="/callboard" title="Callboard"><?= icon('phone-call') ?></a>
                <a class="icon-btn" href="/notifications" title="Meldingen">
                    <?= icon('bell') ?>
                    <span class="dot notif-dot <?= $unread ? '' : 'hidden' ?>"></span>
                </a>
                <button class="icon-btn" data-theme-toggle title="Thema wisselen"><?= icon('moon') ?></button>
                <div class="dropdown">
                    <button class="user-card" data-dropdown="userMenu" style="padding-right:10px;">
                        <span class="avatar avatar-sm"><?= e(initials($user['name'] ?? 'U')) ?></span>
                    </button>
                    <div class="dropdown-menu" id="userMenu">
                        <a href="/profile"><?= icon('user', 17) ?> Mijn profiel</a>
                        <?php if (Auth::isAdmin()): ?>
                            <a href="/settings"><?= icon('settings', 17) ?> Instellingen</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <form action="/logout" method="post">
                            <?= csrf_field() ?>
                            <button type="submit"><?= icon('log-out', 17) ?> Afmelden</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="page">
            <?php if ($msg = \App\Core\Session::flash('success')): ?>
                <script>document.addEventListener('DOMContentLoaded',()=>SF.toast(<?= json_encode($msg) ?>,'success'));</script>
            <?php endif; ?>
            <?php if ($msg = \App\Core\Session::flash('error')): ?>
                <script>document.addEventListener('DOMContentLoaded',()=>SF.toast(<?= json_encode($msg) ?>,'error'));</script>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<?php if (!empty($scripts ?? [])): foreach ($scripts as $s): ?>
    <script src="<?= e($s) ?>"></script>
<?php endforeach; endif; ?>
<?= $inlineScript ?? '' ?>
</body>
</html>
