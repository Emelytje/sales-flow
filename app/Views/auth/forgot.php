<h2>Wachtwoord vergeten</h2>
<p class="text-muted mb-6">Vul je e-mailadres in en we sturen je een herstellink.</p>

<form action="/forgot-password" method="post">
    <?= csrf_field() ?>
    <div class="field">
        <label class="label" for="email">E-mailadres</label>
        <div class="input-group">
            <span class="input-icon"><?= icon('mail', 18) ?></span>
            <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>"
                   placeholder="jij@bedrijf.be" required autofocus>
        </div>
        <?php if ($m = error('email')): ?><div class="field-error"><?= e($m) ?></div><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary btn-block btn-lg"><?= icon('send', 18) ?> Verstuur herstellink</button>
</form>

<p class="text-center mt-6"><a href="/login" class="small text-accent" style="font-weight:600;"><?= icon('chevron-left', 14) ?> Terug naar aanmelden</a></p>
