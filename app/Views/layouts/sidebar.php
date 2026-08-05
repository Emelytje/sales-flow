<?php
/** @var callable $isActive */
/** @var array $user */
use App\Core\Auth;
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="logo">SF</div>
        <div class="name">Sales<span>Flow</span></div>
    </div>

    <nav class="sidebar-nav">
        <a class="nav-link <?= $isActive('/dashboard') ?>" href="/dashboard"><?= icon('dashboard') ?> Dashboard</a>

        <div class="nav-section">
            <div class="nav-section-title">Verkoop</div>
            <a class="nav-link <?= $isActive('/customers') ?>" href="/customers"><?= icon('building') ?> Klanten</a>
            <a class="nav-link <?= $isActive('/contacts') ?>" href="/contacts"><?= icon('users') ?> Contacten</a>
            <a class="nav-link <?= $isActive('/callboard') ?>" href="/callboard"><?= icon('phone-call') ?> Callboard</a>
            <a class="nav-link <?= $isActive('/emails') ?>" href="/emails"><?= icon('mail') ?> E-mail</a>
            <a class="nav-link <?= $isActive('/quotations') ?>" href="/quotations"><?= icon('file-text') ?> Offertes</a>
            <a class="nav-link <?= $isActive('/projects') ?>" href="/projects"><?= icon('briefcase') ?> Projecten</a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Planning</div>
            <a class="nav-link <?= $isActive('/agenda') ?>" href="/agenda"><?= icon('calendar') ?> Agenda</a>
            <a class="nav-link <?= $isActive('/bookings') ?>" href="/bookings"><?= icon('clock') ?> Boekingen</a>
            <a class="nav-link <?= $isActive('/map') ?>" href="/map"><?= icon('map') ?> Kaart &amp; routes</a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Inzicht</div>
            <a class="nav-link <?= $isActive('/reports') ?>" href="/reports"><?= icon('bar-chart') ?> Rapporten</a>
            <a class="nav-link <?= $isActive('/notifications') ?>" href="/notifications"><?= icon('bell') ?> Meldingen</a>
        </div>

        <?php if (Auth::isAdmin() || Auth::hasRole('manager')): ?>
        <div class="nav-section">
            <div class="nav-section-title">Beheer</div>
            <?php if (Auth::isAdmin()): ?>
                <a class="nav-link <?= $isActive('/settings/users') ?>" href="/settings/users"><?= icon('shield') ?> Gebruikers</a>
                <a class="nav-link <?= $isActive('/settings') ?>" href="/settings"><?= icon('settings') ?> Instellingen</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-foot">
        <div class="dropdown">
            <div class="user-card" data-dropdown="sideUser">
                <span class="avatar avatar-sm"><?= e(initials($user['name'] ?? 'U')) ?></span>
                <div class="meta">
                    <div class="n"><?= e($user['name'] ?? '') ?></div>
                    <div class="r"><?= e($user['role'] ?? '') ?></div>
                </div>
            </div>
            <div class="dropdown-menu" id="sideUser" style="bottom:100%;top:auto;left:0;right:0;">
                <a href="/profile"><?= icon('user', 17) ?> Profiel</a>
                <form action="/logout" method="post"><?= csrf_field() ?><button type="submit"><?= icon('log-out', 17) ?> Afmelden</button></form>
            </div>
        </div>
    </div>
</aside>
