<?= \App\Core\View::renderPartial('errors/error', [
    'code' => 403,
    'heading' => 'Geen toegang',
    'message' => 'Je hebt niet de juiste rechten om deze pagina te bekijken.',
]) ?>
