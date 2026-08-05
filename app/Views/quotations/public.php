<?php /** @var array $quotation */ /** @var array $items */ /** @var array $company */
$q = $quotation;
$signed = !empty($q['signed_at']);
?>
<div style="background:#F5EBDD;min-height:100vh;padding:24px 12px;">
    <div style="max-width:800px;margin:0 auto;">
        <div class="card" style="overflow:hidden;box-shadow:0 12px 40px rgba(120,60,90,.16);">
            <?= \App\Core\View::renderPartial('partials/quote_document', ['quotation' => $q, 'items' => $items, 'company' => $company]) ?>
        </div>

        <?php if (!$signed): ?>
        <div class="card mt-4"><div class="card-body">
            <h3 class="mb-2"><?= icon('edit', 18) ?> Digitaal ondertekenen</h3>
            <p class="text-muted small mb-4">Teken hieronder om deze offerte te accepteren. Dit is juridisch bindend.</p>
            <div class="field"><label class="label">Naam</label><input class="input" id="signerName" placeholder="Voor- en achternaam"></div>
            <label class="label">Handtekening</label>
            <canvas id="sigPad" style="width:100%;height:180px;border:2px dashed var(--border-strong);border-radius:12px;touch-action:none;background:#fff;"></canvas>
            <div class="flex gap-2 mt-3">
                <button class="btn btn-ghost btn-sm" onclick="Sig.clear()">Wissen</button>
                <button class="btn btn-primary flex-1" onclick="Sig.submit()"><?= icon('check', 18) ?> Accepteren &amp; ondertekenen</button>
            </div>
        </div></div>
        <?php else: ?>
        <div class="card mt-4"><div class="card-body" style="text-align:center;">
            <div class="badge badge-success badge-dot" style="font-size:14px;padding:8px 16px;"><?= icon('check-circle', 16) ?> Ondertekend op <?= e(date_nl($q['signed_at'], 'd/m/Y')) ?></div>
        </div></div>
        <?php endif; ?>
        <p class="text-center tiny text-muted mt-4">Aangedreven door SalesFlow Enterprise</p>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<?php if (!$signed): ?>
<script>
const Sig = {
    canvas: null, ctx: null, drawing: false, empty: true,
    init() {
        this.canvas = document.getElementById('sigPad');
        const ratio = window.devicePixelRatio || 1;
        this.canvas.width = this.canvas.offsetWidth * ratio;
        this.canvas.height = this.canvas.offsetHeight * ratio;
        this.ctx = this.canvas.getContext('2d');
        this.ctx.scale(ratio, ratio);
        this.ctx.lineWidth = 2.5; this.ctx.lineCap = 'round'; this.ctx.strokeStyle = '#2C2230';
        const pos = (e) => { const r = this.canvas.getBoundingClientRect(); const t = e.touches ? e.touches[0] : e; return { x: t.clientX - r.left, y: t.clientY - r.top }; };
        const start = (e) => { this.drawing = true; this.empty = false; const p = pos(e); this.ctx.beginPath(); this.ctx.moveTo(p.x, p.y); e.preventDefault(); };
        const move = (e) => { if (!this.drawing) return; const p = pos(e); this.ctx.lineTo(p.x, p.y); this.ctx.stroke(); e.preventDefault(); };
        const end = () => { this.drawing = false; };
        ['mousedown', 'touchstart'].forEach((ev) => this.canvas.addEventListener(ev, start));
        ['mousemove', 'touchmove'].forEach((ev) => this.canvas.addEventListener(ev, move));
        ['mouseup', 'touchend', 'mouseleave'].forEach((ev) => this.canvas.addEventListener(ev, end));
    },
    clear() { this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height); this.empty = true; },
    async submit() {
        const name = document.getElementById('signerName').value.trim();
        if (!name) return SF.toast('Vul je naam in', 'error');
        if (this.empty) return SF.toast('Plaats je handtekening', 'error');
        try {
            await SF.api('<?= "/q/" . e($q["public_token"]) . "/sign" ?>', { method: 'POST', body: { signer_name: name, signature_data: this.canvas.toDataURL('image/png') } });
            SF.toast('Bedankt! Offerte ondertekend ✓', 'success');
            setTimeout(() => location.reload(), 1200);
        } catch (e) {}
    }
};
document.addEventListener('DOMContentLoaded', () => Sig.init());
</script>
<?php endif; ?>
