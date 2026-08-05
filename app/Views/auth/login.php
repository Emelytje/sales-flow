<h2>Welkom terug</h2>
<p class="text-muted mb-6">Meld je aan op je SalesFlow-account.</p>

<form action="/login" method="post" autocomplete="on">
    <?= csrf_field() ?>

    <div class="field">
        <label class="label" for="email">E-mailadres</label>
        <div class="input-group">
            <span class="input-icon"><?= icon('mail', 18) ?></span>
            <input class="input <?= error('email') ? 'error' : '' ?>" type="email" id="email" name="email"
                   value="<?= e(old('email')) ?>" placeholder="jij@bedrijf.be" required autofocus>
        </div>
        <?php if ($m = error('email')): ?><div class="field-error"><?= e($m) ?></div><?php endif; ?>
    </div>

    <div class="field">
        <label class="label" for="password">Wachtwoord</label>
        <div class="input-group">
            <span class="input-icon"><?= icon('shield', 18) ?></span>
            <input class="input <?= error('password') ? 'error' : '' ?>" type="password" id="password" name="password"
                   placeholder="••••••••••" required>
        </div>
        <?php if ($m = error('password')): ?><div class="field-error"><?= e($m) ?></div><?php endif; ?>
    </div>

    <div class="flex items-center justify-between mb-4">
        <label class="flex items-center gap-2" style="cursor:pointer;font-size:var(--fs-sm);">
            <input type="checkbox" name="remember" value="1"> Onthoud mij
        </label>
        <a href="/forgot-password" class="small text-accent" style="font-weight:600;">Wachtwoord vergeten?</a>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg"><?= icon('log-out', 18) ?> Aanmelden</button>
</form>

<p class="text-muted small text-center mt-6">
    Beveiligd met versleuteling &amp; sessiebescherming.
</p>
