<?= \App\Core\View::renderPartial('errors/error', [
    'code' => 500,
    'heading' => 'Er ging iets mis',
    'message' => 'Er trad een onverwachte fout op. Ons team is op de hoogte gebracht.',
]) ?>
