/* =============================================================================
   SalesFlow Enterprise — Shared team calendar
   Month / week / day with time-grids, colleague filtering, owner colour-coding
   and drag-to-reschedule. Vanilla JS, backed by /agenda/events.
   ========================================================================== */
(function () {
    'use strict';
    const SF = window.SF;
    const MONTHS = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
    const DAYS = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];
    const DAY_START = 7, DAY_END = 21;         // visible hours in week/day grids
    const HOUR_PX = 48;

    const pad = (n) => String(n).padStart(2, '0');
    const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const startOfWeek = (d) => { const x = new Date(d); const day = (x.getDay() + 6) % 7; x.setDate(x.getDate() - day); x.setHours(0, 0, 0, 0); return x; };
    const parseDT = (s) => new Date(String(s).replace(' ', 'T'));

    const Agenda = window.Agenda = {
        cursor: new Date(),
        view: 'month',
        events: [],
        selected: new Set((window.SF_TEAM || []).map((t) => t.id)),

        init() {
            document.querySelectorAll('.team-cb').forEach((cb) => cb.addEventListener('change', () => {
                cb.checked ? this.selected.add(+cb.value) : this.selected.delete(+cb.value);
                this.render();
            }));
            ['Month', 'Week', 'Day'].forEach((v) => document.getElementById('v' + v).onclick = () => this.setView(v.toLowerCase()));
            this.setView(localStorage.getItem('sf-cal-view') || 'month');
        },
        allTeam(on) {
            document.querySelectorAll('.team-cb').forEach((cb) => { cb.checked = on; on ? this.selected.add(+cb.value) : this.selected.delete(+cb.value); });
            this.render();
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
            const users = [...this.selected].join(',');
            const data = await SF.api(`/agenda/events?start=${start}&end=${end}&users=${users}`);
            this.events = (data.events || []).filter((e) => this.selected.has(e.owner_id) || e.mine);
        },
        eventsOn(dateStr) {
            return this.events.filter((e) => (e.start || '').slice(0, 10) <= dateStr && (e.end || e.start).slice(0, 10) >= dateStr)
                .sort((a, b) => (a.start > b.start ? 1 : -1));
        },
        find(id) { return this.events.find((e) => e.id === id); },

        async render() {
            if (this.view === 'month') await this.renderMonth();
            else if (this.view === 'week') await this.renderWeek();
            else await this.renderDay();
        },

        /* ---- Month ---- */
        async renderMonth() {
            const c = this.cursor;
            document.getElementById('calTitle').textContent = `${MONTHS[c.getMonth()]} ${c.getFullYear()}`;
            const gridStart = startOfWeek(new Date(c.getFullYear(), c.getMonth(), 1));
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
                html += `<div class="cal-cell ${other ? 'other' : ''} ${ds === today ? 'today' : ''}" data-date="${ds}" ondragover="Agenda.dragOver(event)" ondrop="Agenda.drop(event,'${ds}')" onclick="Agenda.openCreate('${ds}')">
                    <div class="cal-num">${day.getDate()}</div>${evs.slice(0, 4).map((e) => this.chip(e)).join('')}
                    ${evs.length > 4 ? `<div class="tiny text-muted">+${evs.length - 4} meer</div>` : ''}</div>`;
            }
            document.getElementById('calendar').innerHTML = html + '</div>';
        },

        chip(e) {
            const t = (e.start || '').slice(11, 16);
            const drag = e.mine ? `draggable="true" ondragstart="Agenda.dragStart(event,${e.id})"` : '';
            const owner = !e.mine ? `<span class="cal-owner" title="${escapeHtml(e.owner || '')}" style="background:${e.owner_color}"></span>` : '';
            const priv = e.visibility === 'private' ? ' 🔒' : '';
            return `<div class="cal-ev" ${drag} style="border-left-color:${e.color}" onclick="event.stopPropagation();Agenda.openView(${e.id})">
                ${owner}${!e.allDay ? `<span class="cal-ev-time">${t}</span>` : ''}<span class="cal-ev-title">${escapeHtml(e.title)}${priv}</span></div>`;
        },

        /* ---- Week / Day time grid ---- */
        async renderWeek() {
            const ws = startOfWeek(this.cursor);
            const we = new Date(ws); we.setDate(we.getDate() + 7);
            document.getElementById('calTitle').textContent = `Week van ${ws.getDate()} ${MONTHS[ws.getMonth()]}`;
            await this.load(ymd(ws), ymd(we));
            const days = [];
            for (let i = 0; i < 7; i++) { const d = new Date(ws); d.setDate(d.getDate() + i); days.push(d); }
            this.renderTimeGrid(days);
        },
        async renderDay() {
            const d = this.cursor;
            document.getElementById('calTitle').textContent = `${DAYS[(d.getDay() + 6) % 7]} ${d.getDate()} ${MONTHS[d.getMonth()]}`;
            await this.load(ymd(d), ymd(d));
            this.renderTimeGrid([d]);
        },
        renderTimeGrid(days) {
            const today = ymd(new Date());
            const hours = [];
            for (let h = DAY_START; h <= DAY_END; h++) hours.push(h);
            let head = '<div class="tg-corner"></div>' + days.map((d) => `<div class="tg-dayhead ${ymd(d) === today ? 'today' : ''}">${DAYS[(d.getDay() + 6) % 7]}<span>${d.getDate()}</span></div>`).join('');
            let body = '<div class="tg-hours">' + hours.map((h) => `<div class="tg-hour"><span>${pad(h)}:00</span></div>`).join('') + '</div>';
            body += days.map((d) => {
                const ds = ymd(d);
                const col = `<div class="tg-col" data-date="${ds}" ondragover="Agenda.dragOver(event)" ondrop="Agenda.dropTime(event,'${ds}')" onclick="Agenda.gridClick(event,'${ds}')">`
                    + hours.map(() => '<div class="tg-slot"></div>').join('')
                    + this.eventsOn(ds).filter((e) => !e.allDay).map((e) => this.timedEvent(e, ds)).join('')
                    + (ds === today ? this.nowLine() : '')
                    + '</div>';
                return col;
            }).join('');
            document.getElementById('calendar').innerHTML =
                `<div class="tg-scroll"><div class="tg-head" style="grid-template-columns:56px repeat(${days.length},1fr)">${head}</div>
                 <div class="tg-grid" style="grid-template-columns:56px repeat(${days.length},1fr)">${body}</div></div>`;
        },
        timedEvent(e, ds) {
            const s = parseDT(e.start), en = parseDT(e.end);
            const startMin = Math.max(DAY_START * 60, s.getHours() * 60 + s.getMinutes());
            const endMin = Math.min(DAY_END * 60 + 60, en.getHours() * 60 + en.getMinutes());
            const top = (startMin - DAY_START * 60) / 60 * HOUR_PX;
            const height = Math.max(20, (endMin - startMin) / 60 * HOUR_PX);
            const drag = e.mine ? `draggable="true" ondragstart="Agenda.dragStart(event,${e.id})"` : '';
            const owner = !e.mine ? `<div class="tiny" style="opacity:.85">${escapeHtml(e.owner || '')}</div>` : '';
            return `<div class="tg-ev" ${drag} style="top:${top}px;height:${height}px;background:${hexA(e.color, .16)};border-left:3px solid ${e.color};"
                onclick="event.stopPropagation();Agenda.openView(${e.id})">
                <div class="tg-ev-t">${(e.start || '').slice(11, 16)}</div>
                <div class="tg-ev-title">${escapeHtml(e.title)}</div>${owner}</div>`;
        },
        nowLine() {
            const now = new Date();
            const min = now.getHours() * 60 + now.getMinutes();
            if (min < DAY_START * 60 || min > DAY_END * 60 + 60) return '';
            const top = (min - DAY_START * 60) / 60 * HOUR_PX;
            return `<div class="tg-now" style="top:${top}px"></div>`;
        },
        gridClick(ev, ds) {
            const rect = ev.currentTarget.getBoundingClientRect();
            const y = ev.clientY - rect.top;
            const hour = Math.max(DAY_START, Math.min(DAY_END, DAY_START + Math.floor(y / HOUR_PX)));
            this.openCreate(ds, pad(hour) + ':00');
        },

        /* ---- Drag & drop reschedule ---- */
        dragStart(ev, id) { ev.dataTransfer.setData('text/plain', id); ev.dataTransfer.effectAllowed = 'move'; },
        dragOver(ev) { ev.preventDefault(); ev.dataTransfer.dropEffect = 'move'; },
        async drop(ev, ds) {
            ev.preventDefault();
            const id = +ev.dataTransfer.getData('text/plain');
            const e = this.find(id);
            if (!e || !e.mine) return;
            const s = parseDT(e.start), en = parseDT(e.end);
            const dur = en - s;
            const ns = new Date(ds + 'T' + pad(s.getHours()) + ':' + pad(s.getMinutes()));
            await this.reschedule(id, ns, new Date(ns.getTime() + dur));
        },
        async dropTime(ev, ds) {
            ev.preventDefault();
            const id = +ev.dataTransfer.getData('text/plain');
            const e = this.find(id); if (!e || !e.mine) return;
            const rect = ev.currentTarget.getBoundingClientRect();
            const y = ev.clientY - rect.top;
            let mins = DAY_START * 60 + Math.round(y / HOUR_PX * 60 / 15) * 15;
            const dur = parseDT(e.end) - parseDT(e.start);
            const ns = new Date(ds + 'T00:00'); ns.setMinutes(mins);
            await this.reschedule(id, ns, new Date(ns.getTime() + dur));
        },
        async reschedule(id, start, end) {
            const fmt = (d) => `${ymd(d)} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
            try {
                await SF.api('/agenda/events/' + id, { method: 'PUT', body: { starts_at: fmt(start), ends_at: fmt(end) } });
                SF.toast('Afspraak verplaatst', 'success', 1500);
                this.render();
            } catch (e) { /* toast shown */ }
        },

        /* ---- View / create ---- */
        openView(id) {
            const e = this.find(id); if (!e) return;
            SF.modal.open(`
                <div class="modal-head"><h3>${escapeHtml(e.title)}</h3><button class="icon-btn" data-close>&times;</button></div>
                <div class="modal-body">
                    <div class="flex items-center gap-2 mb-3"><span class="avatar avatar-sm" style="background:${e.owner_color}">${initials(e.owner)}</span><span class="small">${escapeHtml(e.owner || '')}${e.mine ? ' (jij)' : ''}</span>${e.visibility === 'private' ? '<span class="badge badge-neutral">Privé</span>' : '<span class="badge badge-rose">Gedeeld</span>'}</div>
                    <p class="mb-2"><strong>Wanneer:</strong> ${fmtDT(e.start)} – ${fmtDT(e.end)}</p>
                    ${e.location ? `<p class="mb-2"><strong>Locatie:</strong> ${escapeHtml(e.location)}</p>` : ''}
                    ${e.company ? `<p class="mb-2"><strong>Klant:</strong> <a class="text-accent" href="/customers/${e.customer_id}">${escapeHtml(e.company)}</a></p>` : ''}
                    ${e.travel ? `<p class="mb-2"><strong>Reistijd:</strong> ${e.travel} min</p>` : ''}
                    ${e.teams ? `<a class="btn btn-primary btn-sm" href="${e.teams}" target="_blank">🎥 Deelnemen aan videocall</a>` : ''}
                    ${e.description ? `<p class="mt-2 text-soft">${escapeHtml(e.description)}</p>` : ''}
                </div>
                <div class="modal-foot">${e.mine ? `<button class="btn btn-danger btn-sm" onclick="Agenda.remove(${e.id})">Verwijderen</button>` : ''}<button class="btn btn-ghost" data-close>Sluiten</button></div>`);
        },
        openCreate(dateStr, time) {
            const d = dateStr || ymd(this.cursor);
            const t = time || '09:00';
            const endH = pad(Math.min(23, parseInt(t) + 1)) + ':00';
            const opts = (window.SF_CUSTOMERS || []).map((c) => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
            const m = SF.modal.open(`
                <div class="modal-head"><h3>Nieuwe afspraak</h3><button class="icon-btn" data-close>&times;</button></div>
                <form id="evf" action="/agenda/events" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="_csrf" value="${SF.csrf()}">
                        <div class="field"><label class="label">Titel</label><input class="input" name="title" required autofocus></div>
                        <div class="grid gap-3" style="grid-template-columns:1fr 1fr;">
                            <div class="field"><label class="label">Start</label><input class="input" type="datetime-local" name="starts_at" value="${d}T${t}" required></div>
                            <div class="field"><label class="label">Einde</label><input class="input" type="datetime-local" name="ends_at" value="${d}T${endH}" required></div>
                            <div class="field"><label class="label">Type</label><select class="select" name="type"><option value="meeting">Afspraak</option><option value="call">Gesprek</option><option value="task">Taak</option><option value="visit">Bezoek</option><option value="vacation">Verlof</option></select></div>
                            <div class="field"><label class="label">Reistijd (min)</label><input class="input" type="number" name="travel_minutes" value="0"></div>
                        </div>
                        <div class="field"><label class="label">Klant</label><select class="select" name="customer_id"><option value="">—</option>${opts}</select></div>
                        <div class="field"><label class="label">Locatie</label><input class="input" name="location"></div>
                        <div class="field"><textarea class="textarea" name="description" placeholder="Omschrijving…"></textarea></div>
                        <div class="flex gap-4 wrap">
                            <label class="flex items-center gap-2 small"><input type="checkbox" name="visibility" value="private"> Privé (alleen jij)</label>
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
        remove(id) {
            SF.confirm('Deze afspraak verwijderen?', async () => {
                await SF.api('/agenda/events/' + id, { method: 'DELETE' });
                SF.modal.close(); this.render();
            }, { danger: true, confirmText: 'Verwijderen' });
        }
    };

    function escapeHtml(s) { return String(s || '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }
    function initials(n) { return String(n || '?').split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase(); }
    function fmtDT(s) { if (!s) return ''; return parseDT(s).toLocaleString('nl-BE', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }); }
    function hexA(hex, a) { hex = (hex || '#E98CAB').replace('#', ''); if (hex.length === 3) hex = hex.split('').map((c) => c + c).join(''); const n = parseInt(hex, 16); return `rgba(${(n >> 16) & 255},${(n >> 8) & 255},${n & 255},${a})`; }

    document.addEventListener('DOMContentLoaded', () => { if (document.getElementById('calendar')) Agenda.init(); });
})();
