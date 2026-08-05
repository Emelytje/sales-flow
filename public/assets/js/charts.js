/* =============================================================================
   SalesFlow Enterprise — Tiny canvas chart library
   Line (area), grouped bars and donut. Retina-aware, theme-aware, no deps.
   ========================================================================== */
(function () {
    'use strict';
    const SF = window.SF = window.SF || {};

    function css(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }
    function setup(canvas) {
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        const w = rect.width || canvas.parentElement.clientWidth || 600;
        const h = parseInt(canvas.dataset.height || '220', 10);
        canvas.width = w * dpr; canvas.height = h * dpr;
        canvas.style.height = h + 'px';
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        return { ctx, w, h };
    }
    const fmt = (n) => {
        if (Math.abs(n) >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (Math.abs(n) >= 1000) return (n / 1000).toFixed(1) + 'k';
        return String(Math.round(n));
    };

    SF.charts = {
        line(canvas, cfg) {
            const { ctx, w, h } = setup(canvas);
            const pad = { t: 16, r: 12, b: 26, l: 40 };
            const series = cfg.series;
            const labels = cfg.labels;
            const all = series.flatMap((s) => s.data);
            const max = Math.max(1, ...all) * 1.15;
            const gw = w - pad.l - pad.r, gh = h - pad.t - pad.b;
            const x = (i) => pad.l + (labels.length <= 1 ? gw / 2 : (gw * i) / (labels.length - 1));
            const y = (v) => pad.t + gh - (gh * v) / max;

            ctx.clearRect(0, 0, w, h);
            ctx.strokeStyle = css('--border'); ctx.lineWidth = 1;
            ctx.fillStyle = css('--text-muted'); ctx.font = '11px Inter, sans-serif';
            for (let g = 0; g <= 4; g++) {
                const gy = pad.t + (gh * g) / 4;
                ctx.beginPath(); ctx.moveTo(pad.l, gy); ctx.lineTo(w - pad.r, gy); ctx.stroke();
                ctx.textAlign = 'right'; ctx.fillText(fmt(max - (max * g) / 4), pad.l - 8, gy + 3);
            }
            ctx.textAlign = 'center';
            labels.forEach((lb, i) => ctx.fillText(lb, x(i), h - 8));

            series.forEach((s) => {
                const color = s.color || css('--rose');
                if (s.area) {
                    const grad = ctx.createLinearGradient(0, pad.t, 0, h - pad.b);
                    grad.addColorStop(0, hexA(color, 0.28));
                    grad.addColorStop(1, hexA(color, 0));
                    ctx.beginPath(); ctx.moveTo(x(0), y(s.data[0]));
                    s.data.forEach((v, i) => ctx.lineTo(x(i), y(v)));
                    ctx.lineTo(x(s.data.length - 1), h - pad.b); ctx.lineTo(x(0), h - pad.b);
                    ctx.closePath(); ctx.fillStyle = grad; ctx.fill();
                }
                ctx.beginPath(); ctx.lineWidth = 2.5; ctx.strokeStyle = color;
                ctx.lineJoin = 'round';
                s.data.forEach((v, i) => i ? ctx.lineTo(x(i), y(v)) : ctx.moveTo(x(i), y(v)));
                ctx.stroke();
                s.data.forEach((v, i) => {
                    ctx.beginPath(); ctx.arc(x(i), y(v), 3.5, 0, 7); ctx.fillStyle = css('--surface');
                    ctx.fill(); ctx.lineWidth = 2; ctx.strokeStyle = color; ctx.stroke();
                });
            });
        },

        bars(canvas, cfg) {
            const { ctx, w, h } = setup(canvas);
            const pad = { t: 16, r: 12, b: 26, l: 40 };
            const labels = cfg.labels, series = cfg.series;
            const max = Math.max(1, ...series.flatMap((s) => s.data)) * 1.15;
            const gw = w - pad.l - pad.r, gh = h - pad.t - pad.b;
            const group = gw / labels.length;
            const bw = Math.min(22, (group * 0.7) / series.length);

            ctx.clearRect(0, 0, w, h);
            ctx.strokeStyle = css('--border'); ctx.fillStyle = css('--text-muted');
            ctx.font = '11px Inter, sans-serif';
            for (let g = 0; g <= 4; g++) {
                const gy = pad.t + (gh * g) / 4;
                ctx.beginPath(); ctx.moveTo(pad.l, gy); ctx.lineTo(w - pad.r, gy); ctx.stroke();
                ctx.textAlign = 'right'; ctx.fillText(fmt(max - (max * g) / 4), pad.l - 8, gy + 3);
            }
            labels.forEach((lb, i) => {
                const cx = pad.l + group * i + group / 2;
                ctx.textAlign = 'center'; ctx.fillStyle = css('--text-muted');
                ctx.fillText(lb, cx, h - 8);
                series.forEach((s, si) => {
                    const v = s.data[i];
                    const bh = (gh * v) / max;
                    const bx = cx - (bw * series.length) / 2 + si * bw + 1;
                    const by = pad.t + gh - bh;
                    ctx.fillStyle = s.color || css('--rose');
                    roundRect(ctx, bx, by, bw - 2, bh, 4); ctx.fill();
                });
            });
        },

        donut(canvas, cfg) {
            const { ctx, w, h } = setup(canvas);
            const cx = w / 2, cy = h / 2, r = Math.min(w, h) / 2 - 10, ir = r * 0.62;
            const total = cfg.data.reduce((a, b) => a + b.value, 0) || 1;
            let start = -Math.PI / 2;
            ctx.clearRect(0, 0, w, h);
            cfg.data.forEach((d) => {
                const angle = (d.value / total) * Math.PI * 2;
                ctx.beginPath(); ctx.moveTo(cx, cy);
                ctx.arc(cx, cy, r, start, start + angle); ctx.closePath();
                ctx.fillStyle = d.color; ctx.fill();
                start += angle;
            });
            ctx.beginPath(); ctx.arc(cx, cy, ir, 0, Math.PI * 2);
            ctx.fillStyle = css('--surface'); ctx.fill();
            ctx.fillStyle = css('--text'); ctx.textAlign = 'center';
            ctx.font = '700 22px Inter, sans-serif';
            ctx.fillText(cfg.center || fmt(total), cx, cy + 2);
            if (cfg.centerLabel) {
                ctx.font = '11px Inter, sans-serif'; ctx.fillStyle = css('--text-muted');
                ctx.fillText(cfg.centerLabel, cx, cy + 20);
            }
        }
    };

    function roundRect(ctx, x, y, w, h, r) {
        if (h < r) r = h;
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, 0);
        ctx.arcTo(x, y + h, x, y, 0);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }
    function hexA(hex, a) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex.split('').map((c) => c + c).join('');
        const n = parseInt(hex, 16);
        return `rgba(${(n >> 16) & 255},${(n >> 8) & 255},${n & 255},${a})`;
    }

    // Re-render registered charts on theme change / resize.
    SF._chartRegistry = [];
    SF.renderChart = function (type, canvas, cfg) {
        SF.charts[type](canvas, cfg);
        SF._chartRegistry.push(() => SF.charts[type](canvas, cfg));
    };
    let rt;
    window.addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(() => SF._chartRegistry.forEach((f) => f()), 150); });
    new MutationObserver(() => SF._chartRegistry.forEach((f) => f()))
        .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
})();
