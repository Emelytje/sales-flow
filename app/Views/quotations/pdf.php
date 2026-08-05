<?php /** @var array $quotation */ /** @var array $items */ /** @var array $company */ ?>
<div style="background:#F5EBDD;min-height:100vh;padding:24px 0;">
    <div class="no-print" style="max-width:800px;margin:0 auto 16px;display:flex;gap:8px;justify-content:flex-end;padding:0 16px;">
        <a class="btn btn-ghost" href="/quotations/<?= (int) $quotation['id'] ?>">Terug</a>
        <button class="btn btn-primary" onclick="window.print()"><?= icon('download', 18) ?> Opslaan als PDF</button>
    </div>
    <div style="max-width:800px;margin:0 auto;box-shadow:0 8px 30px rgba(120,60,90,.14);border-radius:8px;overflow:hidden;">
        <?= \App\Core\View::renderPartial('partials/quote_document', ['quotation' => $quotation, 'items' => $items, 'company' => $company]) ?>
    </div>
</div>
<style>@media print { .no-print { display: none !important; } body { background: #fff !important; } }</style>
<script>window.addEventListener('load', () => { if (location.hash === '#print') window.print(); });</script>
