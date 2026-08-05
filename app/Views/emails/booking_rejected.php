<?php /** @var string $name */
$body = '<h1 style="font-size:22px;margin:0 0 16px;">Over je afspraakaanvraag</h1>
    <p style="font-size:15px;line-height:1.6;">Hallo ' . e($name) . ',</p>
    <p style="font-size:15px;line-height:1.6;color:#6B5E68;">Helaas kunnen we je aanvraag op het gekozen moment niet inplannen. Neem gerust contact op voor een alternatief.</p>';
echo \App\Core\View::renderPartial('emails/layout', ['title' => 'Afspraakaanvraag', 'body' => $body]);
