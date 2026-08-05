<?php
/** @var string $name */
/** @var string $link */
$body = '
    <h1 style="font-size:22px;margin:0 0 16px;">Herstel je wachtwoord</h1>
    <p style="font-size:15px;line-height:1.6;margin:0 0 20px;">Hallo ' . e($name) . ',</p>
    <p style="font-size:15px;line-height:1.6;margin:0 0 24px;color:#6B5E68;">
        We ontvingen een verzoek om je wachtwoord te herstellen. Klik op de knop hieronder om een
        nieuw wachtwoord in te stellen. Deze link verloopt binnen 1 uur.
    </p>
    <a href="' . e($link) . '" style="display:inline-block;background:linear-gradient(135deg,#E98CAB,#B33B62);color:#fff;text-decoration:none;font-weight:700;padding:14px 28px;border-radius:12px;font-size:15px;">Wachtwoord herstellen</a>
    <p style="font-size:13px;line-height:1.6;margin:28px 0 0;color:#9C8F98;">
        Heb je dit niet aangevraagd? Dan hoef je niets te doen en blijft je wachtwoord ongewijzigd.
    </p>';

echo \App\Core\View::renderPartial('emails/layout', ['title' => 'Herstel je wachtwoord', 'body' => $body]);
