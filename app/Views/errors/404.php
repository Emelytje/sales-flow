<?= \App\Core\View::renderPartial('errors/error', [
    'code' => 404,
    'heading' => 'Pagina niet gevonden',
    'message' => 'De pagina die je zoekt bestaat niet of is verplaatst.',
]) ?>
