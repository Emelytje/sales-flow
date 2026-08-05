<?php /** @var string $name */ /** @var string $when */ /** @var string $link */
$body = '<h1 style="font-size:22px;margin:0 0 16px;">Je afspraak is bevestigd ✅</h1>
    <p style="font-size:15px;line-height:1.6;">Hallo ' . e($name) . ',</p>
    <p style="font-size:15px;line-height:1.6;color:#6B5E68;">Je afspraak op <strong>' . e($when) . '</strong> is bevestigd.</p>
    ' . ($link ? '<a href="' . e($link) . '" style="display:inline-block;background:linear-gradient(135deg,#E98CAB,#B33B62);color:#fff;text-decoration:none;font-weight:700;padding:14px 28px;border-radius:12px;">Deelnemen aan videocall</a>' : '') . '';
echo \App\Core\View::renderPartial('emails/layout', ['title' => 'Afspraak bevestigd', 'body' => $body]);
