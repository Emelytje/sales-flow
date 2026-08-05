<?php /** @var array|null $rep */ ?>
<div style="background:linear-gradient(150deg,#F5EBDD,#FFFDFB);min-height:100vh;display:grid;place-items:center;padding:2rem;">
    <div class="card" style="max-width:440px;text-align:center;padding:3rem 2.5rem;">
        <div class="big" style="font-size:3.5rem;">📅</div>
        <h1 style="font-size:1.6rem;margin:1rem 0 .5rem;">Aanvraag ontvangen!</h1>
        <p class="text-muted mb-4">Bedankt. <?= $rep ? e($rep['name']) : 'Onze collega' ?> bekijkt je aanvraag en je ontvangt een bevestiging per e-mail zodra de afspraak is goedgekeurd.</p>
        <a class="btn btn-outline" href="/">Terug</a>
    </div>
</div>
