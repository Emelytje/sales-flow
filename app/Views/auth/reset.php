<?php /** @var string $token */ /** @var string $email */ ?>
<h2>Nieuw wachtwoord</h2>
<p class="text-muted mb-6">Kies een sterk wachtwoord van minstens 10 tekens.</p>

<form action="/reset-password" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <input type="hidden" name="email" value="<?= e($email) ?>">

    <div class="field">
        <label class="label" for="password">Nieuw wachtwoord</label>
        <div class="input-group">
            <span class="input-icon"><?= icon('shield', 18) ?></span>
            <input class="input" type="password" id="password" name="password" placeholder="••••••••••" required autofocus>
        </div>
        <?php if ($m = error('password')): ?><div class="field-error"><?= e($m) ?></div><?php endif; ?>
    </div>

    <div class="field">
        <label class="label" for="password_confirmation">Bevestig wachtwoord</label>
        <div class="input-group">
            <span class="input-icon"><?= icon('check', 18) ?></span>
            <input class="input" type="password" id="password_confirmation" name="password_confirmation" placeholder="••••••••••" required>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg"><?= icon('check', 18) ?> Wachtwoord opslaan</button>
</form>
