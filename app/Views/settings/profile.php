<?php
/** @var array $user */
/** @var string $secret */
/** @var string $otpUri */
?>
<div class="page-head"><div class="title"><h1>Mijn profiel</h1><p>Beheer je gegevens en beveiliging</p></div></div>

<div class="dash-grid" style="grid-template-columns:1.4fr 1fr;">
    <div class="col">
        <form action="/profile" method="post">
            <?= csrf_field() ?><input type="hidden" name="_method" value="PUT">
            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('user', 18) ?> Persoonlijke gegevens</h3>
                <div class="flex items-center gap-4 mb-4">
                    <span class="avatar avatar-lg" style="background:<?= e($user['color']) ?>"><?= e(initials($user['name'])) ?></span>
                    <div><div style="font-weight:700;"><?= e($user['name']) ?></div><div class="small text-muted"><?= e(ucfirst($user['role'])) ?></div></div>
                </div>
                <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                    <div class="field"><label class="label">Naam</label><input class="input" name="name" value="<?= e($user['name']) ?>" required></div>
                    <div class="field"><label class="label">E-mail</label><input class="input" type="email" name="email" value="<?= e($user['email']) ?>" required></div>
                    <div class="field"><label class="label">Telefoon</label><input class="input" name="phone" value="<?= e($user['phone']) ?>"></div>
                    <div class="field"><label class="label">Functie</label><input class="input" name="job_title" value="<?= e($user['job_title']) ?>"></div>
                    <div class="field"><label class="label">Thema</label><select class="select" name="theme">
                        <?php foreach (['system' => 'Systeem', 'light' => 'Licht', 'dark' => 'Donker'] as $k => $v): ?><option value="<?= $k ?>" <?= $user['theme'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="field"><label class="label">Dagelijks beldoel</label><input class="input" type="number" name="daily_call_goal" value="<?= (int) $user['daily_call_goal'] ?>"></div>
                </div>
                <div class="field"><label class="label">E-mailhandtekening</label><textarea class="textarea" name="signature"><?= e($user['signature']) ?></textarea></div>
                <button class="btn btn-primary"><?= icon('check', 18) ?> Opslaan</button>
            </div></div>
        </form>
    </div>

    <div class="col">
        <form action="/profile/password" method="post">
            <?= csrf_field() ?><input type="hidden" name="_method" value="PUT">
            <div class="card"><div class="card-body">
                <h3 class="mb-4"><?= icon('shield', 18) ?> Wachtwoord</h3>
                <div class="field"><label class="label">Huidig wachtwoord</label><input class="input" type="password" name="current_password"></div>
                <div class="field"><label class="label">Nieuw wachtwoord</label><input class="input" type="password" name="password"></div>
                <div class="field"><label class="label">Bevestig</label><input class="input" type="password" name="password_confirmation"></div>
                <button class="btn btn-primary btn-sm"><?= icon('check', 16) ?> Wachtwoord wijzigen</button>
            </div></div>

            <div class="card"><div class="card-body">
                <h3 class="mb-2"><?= icon('shield', 18) ?> Tweestapsverificatie</h3>
                <?php if ($user['two_factor_enabled']): ?>
                    <span class="badge badge-success badge-dot">Ingeschakeld</span>
                    <p class="small text-muted mt-2">2FA beschermt je account met een authenticator-app.</p>
                <?php else: ?>
                    <p class="small text-muted mb-2">Scan met Google/Microsoft Authenticator of voer de sleutel handmatig in:</p>
                    <div class="chip" style="font-family:monospace;word-break:break-all;"><?= e(chunk_split($secret, 4, ' ')) ?></div>
                    <div class="field mt-4"><label class="label">Verificatiecode</label><input class="input" name="two_factor_code" placeholder="000000" inputmode="numeric" maxlength="6"></div>
                    <p class="tiny text-muted">Vul je huidige wachtwoord hierboven in én een code om 2FA in te schakelen.</p>
                <?php endif; ?>
            </div></div>
        </form>
    </div>
</div>
