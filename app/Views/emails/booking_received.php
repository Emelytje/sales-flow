<?php /** @var string $name */ /** @var string $rep */ /** @var string $when */
$body = '<h1 style="font-size:22px;margin:0 0 16px;">Aanvraag ontvangen</h1>
    <p style="font-size:15px;line-height:1.6;">Hallo ' . e($name) . ',</p>
    <p style="font-size:15px;line-height:1.6;color:#6B5E68;">Bedankt voor je afspraakaanvraag met <strong>' . e($rep) . '</strong> op <strong>' . e($when) . '</strong>. Je ontvangt een bevestiging zodra de afspraak is goedgekeurd.</p>';
echo \App\Core\View::renderPartial('emails/layout', ['title' => 'Aanvraag ontvangen', 'body' => $body]);
