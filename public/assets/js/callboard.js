/* =============================================================================
   SalesFlow Enterprise — Callboard
   Auto stopwatch, outcome logging, auto-advance to the next customer.
   ========================================================================== */
(function () {
    'use strict';
    const SF = window.SF;

    const Callboard = window.Callboard = {
        seconds: 0,
        timer: null,
        goal: parseInt(document.getElementById('callCard')?.dataset.goal || '40', 10),

        startTimer() {
            if (this.timer) return;
            this.seconds = 0;
            this.render();
            this.timer = setInterval(() => { this.seconds++; this.render(); }, 1000);
        },
        stopTimer() {
            clearInterval(this.timer);
            this.timer = null;
        },
        render() {
            const m = String(Math.floor(this.seconds / 60)).padStart(2, '0');
            const s = String(this.seconds % 60).padStart(2, '0');
            const el = document.getElementById('stopwatch');
            if (el) el.textContent = `${m}:${s}`;
        },

        async submit(outcome) {
            const id = parseInt(document.getElementById('callCustomerId').value, 10);
            if (!id) return;
            const duration = this.seconds;
            this.stopTimer();
            try {
                const res = await SF.api('/callboard/outcome', {
                    method: 'POST',
                    body: {
                        customer_id: id,
                        outcome: outcome,
                        duration: duration,
                        note: document.getElementById('callNote').value
                    }
                });
                this.updateGoal(res.calls_today);
                SF.toast('Resultaat opgeslagen', 'success', 1500);
                this.load(res.next);
            } catch (e) { /* toast already shown */ }
        },

        async skip() {
            const id = parseInt(document.getElementById('callCustomerId').value, 10);
            this.stopTimer();
            const res = await SF.api('/callboard/next?exclude=' + id);
            this.load(res.customer);
        },

        updateGoal(count) {
            if (typeof count !== 'number') return;
            const gc = document.getElementById('goalCount');
            const gb = document.getElementById('goalBar');
            if (gc) gc.textContent = count;
            if (gb) gb.style.width = Math.min(100, Math.round(count / this.goal * 100)) + '%';
            if (count === this.goal) SF.toast('🎯 Dagdoel bereikt! Top werk!', 'success', 5000);
        },

        load(customer) {
            this.seconds = 0;
            this.render();
            const body = document.getElementById('callBody');
            const empty = document.getElementById('callEmpty');
            if (!customer) {
                body.classList.add('hidden');
                empty.classList.remove('hidden');
                return;
            }
            empty.classList.add('hidden');
            body.classList.remove('hidden');

            const phone = customer.phone || customer.contact_phone || '';
            document.getElementById('coName').textContent = customer.company_name;
            document.getElementById('coSector').innerHTML = document.getElementById('coSector').innerHTML.replace(/<\/svg>.*/, '</svg> ' + (customer.sector_name || '—'));
            document.getElementById('coCity').innerHTML = document.getElementById('coCity').innerHTML.replace(/<\/svg>.*/, '</svg> ' + (customer.city || '—'));
            document.getElementById('coPhone').textContent = phone || 'Geen nummer';
            document.getElementById('btnCall').href = phone ? 'tel:' + phone : '#';
            document.getElementById('btnMail').href = customer.email ? 'mailto:' + customer.email : '#';
            document.getElementById('btnWeb').href = customer.website || '#';
            document.getElementById('btnMap').href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(customer.city || '');
            document.getElementById('btnCard').href = '/customers/' + customer.id;
            document.getElementById('callCustomerId').value = customer.id;
            document.getElementById('callNote').value = '';
        }
    };
})();
