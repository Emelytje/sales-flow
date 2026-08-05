<?php
/** @var array $customers */
/** @var int $missing */
$mapData = array_values(array_filter(array_map(static fn ($c) => [
    'id'    => (int) $c['id'],
    'name'  => $c['company_name'],
    'city'  => $c['city'],
    'phone' => $c['phone'],
    'stage' => $c['pipeline_stage'],
    'lat'   => $c['latitude'] !== null ? (float) $c['latitude'] : null,
    'lon'   => $c['longitude'] !== null ? (float) $c['longitude'] : null,
], $customers), static fn ($c) => $c['lat'] !== null));
?>
<div class="page-head">
    <div class="title"><h1>Kaart &amp; routes</h1><p><?= count($mapData) ?> klanten op de kaart · gratis via OpenStreetMap</p></div>
    <div class="page-actions">
        <?php if ($missing > 0): ?><span class="chip"><?= $missing ?> zonder locatie</span><?php endif; ?>
        <button class="btn btn-outline" onclick="SFMap.locate()"><?= icon('map-pin', 18) ?> Mijn locatie</button>
    </div>
</div>

<div class="dash-grid" style="grid-template-columns:1fr 340px;">
    <div class="card" style="overflow:hidden;"><div id="map" style="height:640px;width:100%;"></div></div>
    <div class="col">
        <div class="card"><div class="card-head"><h3>Klanten</h3></div>
        <div class="card-body" style="max-height:600px;overflow-y:auto;" id="mapList"></div></div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
window.SF_MAP_DATA = <?= json_encode($mapData) ?>;
const SFMap = window.SFMap = {
    map: null, markers: [],
    stageColor: { lead:'#9C8F98', contacted:'#7A6FF0', qualified:'#4C9AA6', proposal:'#D98A2B', won:'#2FA36B', lost:'#D8476A' },
    init() {
        const data = window.SF_MAP_DATA;
        const center = data.length ? [data[0].lat, data[0].lon] : [50.85, 4.35]; // Brussels
        this.map = L.map('map', { zoomControl: true }).setView(center, data.length ? 9 : 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap'
        }).addTo(this.map);

        const list = document.getElementById('mapList');
        const bounds = [];
        data.forEach((c) => {
            const color = this.stageColor[c.stage] || '#E98CAB';
            const marker = L.circleMarker([c.lat, c.lon], { radius: 9, fillColor: color, color: '#fff', weight: 2, fillOpacity: 0.9 }).addTo(this.map);
            marker.bindPopup(`<strong>${esc(c.name)}</strong><br>${esc(c.city||'')}<br>
                <a href="/customers/${c.id}">Open klantkaart</a>${c.phone ? ` · <a href="tel:${esc(c.phone)}">Bellen</a>` : ''}<br>
                <a href="https://www.google.com/maps/dir/?api=1&destination=${c.lat},${c.lon}" target="_blank">Navigeer</a>`);
            this.markers.push(marker);
            bounds.push([c.lat, c.lon]);

            const row = document.createElement('div');
            row.className = 'flex items-center gap-3';
            row.style.cssText = 'padding:9px 0;border-bottom:1px solid var(--border);cursor:pointer;';
            row.innerHTML = `<span style="width:10px;height:10px;border-radius:50%;background:${color};flex-shrink:0;"></span>
                <div class="flex-1" style="min-width:0;"><div style="font-weight:700;font-size:var(--fs-sm);">${esc(c.name)}</div><div class="tiny text-muted">${esc(c.city||'—')}</div></div>`;
            row.onclick = () => { this.map.setView([c.lat, c.lon], 14); marker.openPopup(); };
            list.appendChild(row);
        });
        if (bounds.length > 1) this.map.fitBounds(bounds, { padding: [40, 40] });
    },
    locate() {
        if (!navigator.geolocation) return SF.toast('Geolocatie niet beschikbaar', 'error');
        navigator.geolocation.getCurrentPosition((pos) => {
            const p = [pos.coords.latitude, pos.coords.longitude];
            L.circleMarker(p, { radius: 10, fillColor: '#B33B62', color: '#fff', weight: 3, fillOpacity: 1 }).addTo(this.map).bindPopup('Jij bent hier').openPopup();
            this.map.setView(p, 12);
        });
    }
};
function esc(s){return String(s||'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
document.addEventListener('DOMContentLoaded', () => SFMap.init());
</script>
