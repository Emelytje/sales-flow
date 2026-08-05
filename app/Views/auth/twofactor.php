<h2>Tweestapsverificatie</h2>
<p class="text-muted mb-6">Voer de 6-cijferige code in uit je authenticator-app.</p>

<form action="/2fa" method="post">
    <?= csrf_field() ?>
    <div class="field">
        <label class="label" for="code">Verificatiecode</label>
        <input class="input" type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*"
               maxlength="6" placeholder="000000" required autofocus autocomplete="one-time-code"
               style="letter-spacing:.5em;text-align:center;font-size:1.4rem;font-weight:700;">
    </div>
    <button type="submit" class="btn btn-primary btn-block btn-lg"><?= icon('check-circle', 18) ?> Verifiëren</button>
</form>

<p class="text-center mt-6"><a href="/login" class="small text-accent" style="font-weight:600;">Annuleren</a></p>
