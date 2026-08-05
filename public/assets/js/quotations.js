/* =============================================================================
   SalesFlow Enterprise — Quotation editor (dynamic line items + live totals)
   ========================================================================== */
(function () {
    'use strict';
    const money = (n) => '€ ' + Number(n || 0).toLocaleString('nl-BE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const Quote = window.Quote = {
        addLine(item) {
            item = item || { description: '', quantity: 1, unit_price: 0, discount: 0 };
            const idx = document.querySelectorAll('#lineBody tr').length;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input class="input" name="items[${idx}][description]" value="${escapeAttr(item.description)}" placeholder="Omschrijving"></td>
                <td><input class="input line-q" name="items[${idx}][quantity]" type="number" step="0.01" value="${item.quantity}" style="width:80px" oninput="Quote.recalc()"></td>
                <td><input class="input line-p" name="items[${idx}][unit_price]" type="number" step="0.01" value="${item.unit_price}" style="width:110px" oninput="Quote.recalc()"></td>
                <td><input class="input line-d" name="items[${idx}][discount]" type="number" step="0.01" value="${item.discount}" style="width:80px" oninput="Quote.recalc()"></td>
                <td class="line-total" style="font-weight:700;white-space:nowrap;">€ 0,00</td>
                <td><button type="button" class="icon-btn" style="width:32px;height:32px;" onclick="this.closest('tr').remove();Quote.recalc()">&times;</button></td>`;
            document.getElementById('lineBody').appendChild(tr);
            this.recalc();
        },
        recalc() {
            let sub = 0;
            document.querySelectorAll('#lineBody tr').forEach((tr) => {
                const q = parseFloat(tr.querySelector('.line-q').value) || 0;
                const p = parseFloat(tr.querySelector('.line-p').value) || 0;
                const d = parseFloat(tr.querySelector('.line-d').value) || 0;
                const total = q * p * (1 - d / 100);
                tr.querySelector('.line-total').textContent = money(total);
                sub += total;
            });
            const disc = parseFloat(document.getElementById('discount').value) || 0;
            const taxRate = parseFloat(document.getElementById('taxRate').value) || 0;
            const afterDisc = Math.max(0, sub - disc);
            const tax = afterDisc * taxRate / 100;
            document.getElementById('sumSub').textContent = money(sub);
            document.getElementById('sumTax').textContent = money(tax);
            document.getElementById('sumTotal').textContent = money(afterDisc + tax);
        }
    };

    function escapeAttr(s) { return String(s || '').replace(/"/g, '&quot;'); }

    document.addEventListener('DOMContentLoaded', () => {
        const items = window.SF_ITEMS || [];
        if (items.length) items.forEach((i) => Quote.addLine(i));
        else Quote.addLine();
    });
})();
