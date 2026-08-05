<?php /** @var array $rep */ /** @var array $slots */ ?>
<div style="background:linear-gradient(150deg,#F5EBDD,#FFFDFB);min-height:100vh;padding:24px 12px;">
    <div style="max-width:760px;margin:0 auto;">
        <div class="card" style="overflow:hidden;">
            <div style="background:linear-gradient(135deg,#E98CAB,#B33B62);padding:32px;color:#fff;display:flex;align-items:center;gap:16px;">
                <span class="avatar avatar-lg" style="background:rgba(255,255,255,.2);"><?= e(initials($rep['name'])) ?></span>
                <div><h1 style="font-size:1.6rem;">Afspraak met <?= e($rep['name']) ?></h1><p style="opacity:.9;"><?= e($rep['job_title'] ?: 'Sales') ?> · 30 minuten</p></div>
            </div>
            <div class="card-body" style="padding:2rem;">
                <?php if (!$slots): ?>
                    <div class="empty"><?= icon('calendar', 48) ?><h3>Geen vrije momenten</h3><p>Er zijn momenteel geen tijdslots beschikbaar. Probeer later opnieuw.</p></div>
                <?php else: ?>
                <div id="step1">
                    <h3 class="mb-4">Kies een moment</h3>
                    <div id="dayTabs" class="flex gap-2 wrap mb-4"></div>
                    <div id="slotGrid" class="grid gap-2" style="grid-template-columns:repeat(auto-fill,minmax(90px,1fr));"></div>
                </div>
                <form id="bookForm" class="hidden" action="/book/<?= e($rep['booking_slug']) ?>" method="post">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="slot" id="slotInput">
                    <div class="chip mb-4" id="chosenSlot" style="font-weight:700;"></div>
                    <div class="grid gap-3" style="grid-template-columns:1fr 1fr;">
                        <div class="field"><label class="label">Naam *</label><input class="input" name="guest_name" required></div>
                        <div class="field"><label class="label">E-mail *</label><input class="input" type="email" name="guest_email" required></div>
                        <div class="field"><label class="label">Telefoon</label><input class="input" name="guest_phone"></div>
                        <div class="field"><label class="label">Bedrijf</label><input class="input" name="guest_company"></div>
                    </div>
                    <div class="field"><label class="label">Onderwerp</label><input class="input" name="subject" value="Kennismaking"></div>
                    <div class="field"><label class="label">Bericht</label><textarea class="textarea" name="message"></textarea></div>
                    <div class="flex gap-2"><button type="button" class="btn btn-ghost" onclick="Booking.back()">← Terug</button><button class="btn btn-primary flex-1"><?= icon('check', 18) ?> Afspraak aanvragen</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <p class="text-center tiny text-muted mt-4">Aangedreven door SalesFlow Enterprise</p>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<script>
window.SF_SLOTS = <?= json_encode($slots) ?>;
const Booking = {
    init() {
        const days = Object.keys(SF_SLOTS);
        const tabs = document.getElementById('dayTabs');
        days.forEach((d, i) => {
            const b = document.createElement('button');
            b.className = 'pill' + (i === 0 ? ' active' : '');
            const dt = new Date(d + 'T00:00');
            b.textContent = dt.toLocaleDateString('nl-BE', { weekday: 'short', day: 'numeric', month: 'short' });
            b.onclick = () => { tabs.querySelectorAll('.pill').forEach(p => p.classList.remove('active')); b.classList.add('active'); this.renderSlots(d); };
            tabs.appendChild(b);
        });
        if (days.length) this.renderSlots(days[0]);
    },
    renderSlots(day) {
        const grid = document.getElementById('slotGrid');
        grid.innerHTML = '';
        SF_SLOTS[day].forEach(t => {
            const b = document.createElement('button');
            b.className = 'btn btn-outline';
            b.textContent = t;
            b.onclick = () => this.choose(day, t);
            grid.appendChild(b);
        });
    },
    choose(day, time) {
        document.getElementById('slotInput').value = day + ' ' + time;
        const dt = new Date(day + 'T00:00');
        document.getElementById('chosenSlot').textContent = '📅 ' + dt.toLocaleDateString('nl-BE', { weekday: 'long', day: 'numeric', month: 'long' }) + ' om ' + time;
        document.getElementById('step1').classList.add('hidden');
        document.getElementById('bookForm').classList.remove('hidden');
    },
    back() { document.getElementById('bookForm').classList.add('hidden'); document.getElementById('step1').classList.remove('hidden'); }
};
document.addEventListener('DOMContentLoaded', () => { Booking.init(); const f = document.getElementById('bookForm'); if (f) SF.bindForm(f); });
</script>
