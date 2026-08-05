/* =============================================================================
   SalesFlow Enterprise — Internal chat
   Channels + DMs, short-polling messages, read receipts, typing indicator,
   file sharing. InfinityFree-safe (no long-lived connections).
   ========================================================================== */
(function () {
    'use strict';
    const SF = window.SF;

    const Chat = window.Chat = {
        channel: null,
        lastId: 0,
        timer: null,
        typingTimer: null,

        async open(id, name, color) {
            this.channel = id;
            this.lastId = 0;
            document.getElementById('chatEmpty').classList.add('hidden');
            document.getElementById('chatRoom').classList.remove('hidden');
            document.getElementById('chatTitle').textContent = name;
            const av = document.getElementById('chatAvatar');
            av.textContent = initials(name);
            av.style.background = color || '#B33B62';
            document.getElementById('chatMessages').innerHTML = '';
            document.querySelectorAll('.chat-ch').forEach((c) => c.classList.toggle('active', +c.dataset.ch === id));
            await this.poll();
            clearInterval(this.timer);
            this.timer = setInterval(() => this.poll(), 2500);
            document.getElementById('chatBody').focus();
        },

        async dm(userId, name) {
            const res = await SF.api('/chat/channels', { method: 'POST', body: { user_id: userId } });
            this.open(res.id, name);
        },
        newGroup() {
            const m = SF.modal.open(`
                <div class="modal-head"><h3>Nieuw kanaal</h3><button class="icon-btn" data-close>&times;</button></div>
                <form id="cf"><div class="modal-body"><input type="hidden" name="_csrf" value="${SF.csrf()}">
                    <div class="field"><label class="label">Kanaalnaam</label><input class="input" name="name" placeholder="bv. Sales-team" required autofocus></div>
                    <p class="tiny text-muted">Alle actieve teamleden worden toegevoegd.</p></div>
                <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuleren</button><button class="btn btn-primary">Aanmaken</button></div></form>`);
            m.querySelector('#cf').onsubmit = async (e) => {
                e.preventDefault();
                const res = await SF.api('/chat/channels', { method: 'POST', body: { name: m.querySelector('[name=name]').value } });
                SF.modal.close(); location.href = '/chat';
            };
        },

        async poll() {
            if (!this.channel) return;
            let data;
            try { data = await SF.api(`/chat/channels/${this.channel}/messages?after=${this.lastId}`); } catch (e) { return; }
            const box = document.getElementById('chatMessages');
            const atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;
            (data.messages || []).forEach((m) => {
                this.lastId = Math.max(this.lastId, m.id);
                box.appendChild(this.bubble(m));
            });
            if (atBottom || (data.messages || []).some((m) => m.mine)) box.scrollTop = box.scrollHeight;
            const ti = document.getElementById('typingInd');
            if ((data.typing || []).length) { ti.textContent = data.typing.join(', ') + ' is aan het typen…'; ti.classList.remove('hidden'); }
            else ti.classList.add('hidden');
        },

        bubble(m) {
            const div = document.createElement('div');
            div.className = 'chat-msg' + (m.mine ? ' mine' : '');
            let content = m.body ? escapeHtml(m.body).replace(/\n/g, '<br>') : '';
            if (m.attachment) content += `<a href="/chat/file?m=${m.id}" class="chat-file" target="_blank">📎 Bestand</a>`;
            div.innerHTML = (m.mine ? '' : `<span class="avatar avatar-sm" style="background:${m.color}">${initials(m.user)}</span>`) +
                `<div class="chat-bubble"><div class="chat-bubble-in">${content}</div><div class="chat-meta">${m.mine ? '' : escapeHtml(m.user) + ' · '}${fmtTime(m.at)}</div></div>`;
            return div;
        },

        async send() {
            const input = document.getElementById('chatBody');
            const fileInput = document.getElementById('chatFile');
            const body = input.value.trim();
            if (!body && !fileInput.files.length) return;
            const fd = new FormData();
            fd.append('_csrf', SF.csrf());
            fd.append('body', body);
            if (fileInput.files.length) fd.append('file', fileInput.files[0]);
            input.value = ''; fileInput.value = '';
            try { await SF.api(`/chat/channels/${this.channel}/messages`, { method: 'POST', body: fd }); await this.poll(); }
            catch (e) { input.value = body; }
        },

        pingTyping() {
            if (!this.channel || this.typingTimer) return;
            SF.api(`/chat/channels/${this.channel}/typing`, { method: 'POST', body: { _csrf: SF.csrf() } }).catch(() => {});
            this.typingTimer = setTimeout(() => { this.typingTimer = null; }, 3000);
        }
    };

    function escapeHtml(s) { return String(s || '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }
    function initials(n) { return String(n || '?').split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase(); }
    function fmtTime(s) { if (!s) return ''; const d = new Date(String(s).replace(' ', 'T')); return d.toLocaleTimeString('nl-BE', { hour: '2-digit', minute: '2-digit' }); }

    document.addEventListener('DOMContentLoaded', () => {
        const body = document.getElementById('chatBody');
        if (body) {
            body.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); Chat.send(); } else { Chat.pingTyping(); } });
        }
        const file = document.getElementById('chatFile');
        if (file) file.addEventListener('change', () => Chat.send());
        // Auto-open channel from ?c=
        const c = new URLSearchParams(location.search).get('c');
        if (c) { const el = document.querySelector(`.chat-ch[data-ch="${c}"]`); if (el) el.click(); }
    });
})();
