<?php /** @var string $content */ /** @var string $title */ ?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'SalesFlow Enterprise') ?></title>
    <link rel="stylesheet" href="/assets/css/design-system.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <script>(function(){var t=localStorage.getItem('sf-theme');if(t&&t!=='system')document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>
    <?= $content ?>
</body>
</html>
