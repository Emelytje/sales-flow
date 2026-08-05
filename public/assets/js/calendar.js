/* =============================================================================
   SalesFlow Enterprise — Calendar (month / week / day)
   Vanilla JS, fetches /agenda/events, create/edit/delete via modals.
   ========================================================================== */
(function () {
    'use strict';
    const SF = window.SF;
    const MONTHS = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
    const DAYS = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];

    const pad = (n) => String(n).padStart(2, '0');
    const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const startOfWeek = (d) => { const x = new Date(d); const day = (x.getDay() + 6) % 7; x.setDate(x.getDate() - day); x.setHours(0, 0, 0, 0); return x; };

    const Agenda = window.Agenda = {
        cursor: new Date(),
        view: 'month',
        events: [],

        init() {
            this.setView(localStorage.getItem('sf-cal-view') || 'month');
            ['Month', 'Week', 'Day'].forEach((v) => {
                document.getElementById('v' + v).onclick = () => this.setView(v.toLowerCase());
            });
        },
        setView(v) {
            this.view = v;
            localStorage.setItem('sf-cal-view', v);
            ['month', 'week', 'day'].forEach((x) => document.getElementById('v' + x[0].toUpperCase() + x.slice(1)).classList.toggle('btn-primary', x === v));
            this.render();
        },
        prev() { this.shift(-1); },
        next() { this.shift(1); },
        today() { this.cursor = new Date(); this.render(); },
        shift(dir) {
            if (this.view === 'month') this.cursor.setMonth(this.cursor.getMonth() + dir);
            else if (this.view === 'week') this.cursor.setDate(this.cursor.getDate() + 7 * dir);
            else this.cursor.setDate(this.cursor.getDate() + dir);
            this.render();
        },

        async load(start, end) {
            const data = await SF.api(`/agenda/events?start=${start}&end=${end}`);
            this.events = data.events || [];
        },
        eventsOn(dateStr) {
            return this.events.filter((e) => (e.start || '').slice(0, 10) <= dateStr && (e.end || e.start).slice(0, 10) >= dateStr)
                .sort((a, b) => (a.start > b.start ? 1 : -1));
        },

        async render() {
            if (this.view === 'month') await this.renderMonth();
            else if (this.view === 'week') await this.renderWeek();
            else await this.renderDay();
        },

        async renderMonth() {
            const c = this.cursor;
            document.getElementById('calTitle').textContent = `${MONTHS[c.getMonth()]} ${c.getFullYear()}`;
            const first = new Date(c.getFullYear(), c.getMonth(), 1);
            const gridStart = startOfWeek(first);
            const end = new Date(gridStart); end.setDate(end.getDate() + 42);
            await this.load(ymd(gridStart), ymd(end));

            let html = '<div class="cal-grid cal-month">';
            DAYS.forEach((d) => html += `<div class="cal-dow">${d}</div>`);
            const today = ymd(new Date());
            for (let i = 0; i < 42; i++) {
                const day = new Date(gridStart); day.setDate(day.getDate() + i);
                const ds = ymd(day);
                const other = day.getMonth() !== c.getMonth();
                const evs = this.eventsOn(ds);
                html += `<div class="cal-cell ${other ? 'other' : ''} ${ds === today ? 'today' : ''}" onclick="Agenda.openCreate('${ds}')">
                    <div class="cal-num">${day.getDate()}</div>${evs.slice(0, 4).map((e) => this.chip(e)).join('')}
                    ${evs.length > 4 ? `<div class="tiny text-muted">+${evs.length - 4} meer</div>` : ''}</div>`;
            }
            html += '</div>';
            document.getElementById('calendar').innerHTML = html;
        },

        async renderWeek() {
            const ws = startOfWeek(this.cursor);
            const we = new Date(ws); we.setDate(we.getDate() + 7);
            document.getElementById('calTitle').textContent = `Week van ${ws.getDate()} ${MONTHS[ws.getMonth()]}`;
            await this.load(ymd(ws), ymd(we));
            let html = '<div class="cal-grid cal-week">';
            for (let i = 0; i < 7; i++) {
                const day = new Date(ws); day.setDate(day.getDate() + i);
                const ds = ymd(day);
                const evs = this.eventsOn(ds);
                html += `<div class="cal-col"><div class="cal-dow">${DAYS[i]} ${day.getDate()}</div>
                    <div class="cal-daycol" onclick="Agenda.openCreate('${ds}')">${evs.map((e) => this.chip(e, true)).join('')}</div></div>`;
            }
            html += '</div>';
            document.getElementById('calendar').innerHTML = html;
        },

        async renderDay() {
            const d = this.cursor; const ds = ymd(d);
            document.getElementById('calTitle').textContent = `${DAYS[(d.getDay() + 6) % 7]} ${d.getDate()} ${MONTHS[d.getMonth()]}`;
            await this.load(ds, ds);
            const evs = this.eventsOn(ds);
            let html = '<div class="cal-day">';
            if (!evs.length) html += '<p class="text-muted" style="padding:2rem;text-align:center;">Geen afspraken. Klik om er één toe te voegen.</p>';
            evs.forEach((e) => html += this.chip(e, true));
            html += `<button class="btn btn-outline btn-block mt-4" onclick="Agenda.openCreate('${ds}')">+ Afspraak toevoegen</button></div>`;
            document.getElementById('calendar').innerHTML = html;
        },

        chip(e, big) {
            const t = (e.start || '').slice(11, 16);
            return `<div class="cal-ev ${big ? 'big' : ''}" style="border-left-color:${e.color}" onclick="event.stopPropagation();Agenda.openView(${e.id})">
                ${!e.allDay ? `<span class="cal-ev-time">${t}</span>` : ''}<span class="cal-ev-title">${escapeHtml(e.title)}</span></div>`;
        },

        find(id) { return this.events.find((e) => e.id === id); },

        openView(id) {
            const e = this.find(id);
            if (!e) return;
            const body = `
                <div class="modal-head"><h3>${escapeHtml(e.title)}</h3><button class="icon-btn" data-close>&times;</button></div>
                <div class="modal-body">
                    <p class="mb-2"><strong>Wanneer:</strong> ${fmt(e.start)} – ${fmt(e.end)}</p>
                    ${e.location ? `<p class="mb-2"><strong>Locatie:</strong> ${escapeHtml(e.location)}</p>` : ''}
                    ${e.company ? `<p class="mb-2"><strong>Klant:</strong> <a class="text-accent" href="/customers/${e.customer_id}">${escapeHtml(e.company)}</a></p>` : ''}
                    ${e.travel ? `<p class="mb-2"><strong>Reistijd:</strong> ${e.travel} min</p>` : ''}
                    ${e.teams ? `<a class="btn btn-primary btn-sm" href="${e.teams}" target="_blank">🎥 Deelnemen aan videocall</a>` : ''}
                    ${e.description ? `<p class="mt-2 text-soft">${escapeHtml(e.description)}</p>` : ''}
                </div>
                <div class="modal-foot">
                    ${e.mine ? `<button class="btn btn-danger btn-sm" onclick="Agenda.remove(${e.id})">Verwijderen</button>` : ''}
                    <button class="btn btn-ghost" data-close>Sluiten</button>
                </div>`;
            SF.modal.open(body);
        },

        openCreate(dateStr) {
            const d = dateStr || ymd(this.cursor);
            const opts = (window.SF_CUSTOMERS || []).map((c) => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
            const m = SF.modal.open(`
                <div class="modal-head"><h3>Nieuwe afspraak</h3><button class="icon-btn" data-close>&times;</button></div>
                <form id="evf" action="/agenda/events" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="_csrf" value="${SF.csrf()}">
                        <div class="field"><label class="label">Titel</label><input class="input" name="title" required></div>
                        <div class="grid gap-3" style="grid-template-columns:1fr 1fr;">
                            <div class="field"><label class="label">Start</label><input class="input" type="datetime-local" name="starts_at" value="${d}T09:00" required></div>
                            <div class="field"><label class="label">Einde</label><input class="input" type="datetime-local" name="ends_at" value="${d}T10:00" required></div>
                            <div class="field"><label class="label">Type</label><select class="select" name="type">
                                <option value="meeting">Afspraak</option><option value="call">Gesprek</option><option value="task">Taak</option><option value="visit">Bezoek</option><option value="vacation">Verlof</option></select></div>
                            <div class="field"><label class="label">Reistijd (min)</label><input class="input" type="number" name="travel_minutes" value="0"></div>
                        </div>
                        <div class="field"><label class="label">Klant</label><select class="select" name="customer_id"><option value="">—</option>${opts}</select></div>
                        <div class="field"><label class="label">Locatie</label><input class="input" name="location"></div>
                        <div class="field"><textarea class="textarea" name="description" placeholder="Omschrijving…"></textarea></div>
                        <div class="flex gap-4 wrap">
                            <label class="flex items-center gap-2 small"><input type="checkbox" name="visibility" value="private"> Privé</label>
                            <label class="flex items-center gap-2 small"><input type="checkbox" name="create_meeting" value="1"> Videovergadering (gratis)</label>
                        </div>
                    </div>
                    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuleren</button><button class="btn btn-primary">Opslaan</button></div>
                </form>`);
            SF.bindForm(m.querySelector('#evf'), (res) => {
                SF.modal.close();
                if (res.conflict) SF.toast('Let op: overlapt met een andere afspraak.', 'info', 5000);
                else SF.toast('Afspraak opgeslagen', 'success');
                this.render();
            });
        },

        async remove(id) {
            SF.confirm('Deze afspraak verwijderen?', async () => {
                await SF.api('/agenda/events/' + id, { method: 'DELETE' });
                SF.modal.close();
                this.render();
            }, { danger: true, confirmText: 'Verwijderen' });
        }
    };

    function escapeHtml(s) { return String(s || '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }
    function fmt(s) { if (!s) return ''; const d = new Date(s.replace(' ', 'T')); return d.toLocaleString('nl-BE', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }); }

    document.addEventListener('DOMContentLoaded', () => { if (document.getElementById('calendar')) Agenda.init(); });
})();
