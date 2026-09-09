import L from 'leaflet';
import 'leaflet.markercluster';

window.L = L;

export const categoryConfig = {
    SD: { sector: 'pendidikan', color: '#10b981', emoji: '🎓', label: 'SD' },
    SMP: { sector: 'pendidikan', color: '#2563eb', emoji: '🎓', label: 'SMP' },
    polsek: { sector: 'ketertiban', color: '#2563eb', emoji: '🚓', label: 'Polsek' },
    kelurahan: { sector: 'ketertiban', color: '#059669', emoji: '🏘️', label: 'Kelurahan/Desa' },
    pasar: { sector: 'ketertiban', color: '#f59e0b', emoji: '🏪', label: 'Pasar' },
    poskamling: { sector: 'ketertiban', color: '#f97316', emoji: '🛡️', label: 'Poskamling' },
    tipkamtikmas: { sector: 'ketertiban', color: '#14b8a6', emoji: '🪖', label: 'Tipkamtikmas' },
    Puskesmas: { sector: 'kesehatan', color: '#10b981', emoji: '🏥', label: 'Puskesmas' },
    Pustu: { sector: 'kesehatan', color: '#6366f1', emoji: '🏬', label: 'Pustu' },
    'Rumah Sakit': { sector: 'kesehatan', color: '#dc2626', emoji: '🏨', label: 'Rumah Sakit' },
    Posyandu: { sector: 'kesehatan', color: '#3b82f6', emoji: '👶', label: 'Posyandu' },
    sos: { sector: 'ketertiban', color: '#dc2626', emoji: '🆘', label: 'SOS Darurat' },
    responder: { sector: 'ketertiban', color: '#2563eb', emoji: '🚓', label: 'Petugas' },
    'data-publik-pendidikan': { sector: 'pendidikan', color: '#0d9488', emoji: '📊', label: 'Data Publik' },
    'data-publik-kesehatan': { sector: 'kesehatan', color: '#f97316', emoji: '📊', label: 'Data Publik' },
    'data-publik-keamanan': { sector: 'ketertiban', color: '#7c3aed', emoji: '📊', label: 'Data Publik' },
};

function iconFor(category, size = 28) {
    const cfg = categoryConfig[category] ?? { color: '#64748b', emoji: '📍', label: category };
    const html = `<div style="background:${cfg.color};color:#fff;width:${size}px;height:${size}px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:${Math.round(size * 0.52)}px;box-shadow:0 2px 6px rgba(0,0,0,0.3);border:2px solid #fff">${cfg.emoji}</div>`;
    return L.divIcon({
        html,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        className: '',
    });
}

export function popupHtml(marker) {
    const cfg = categoryConfig[marker.category] ?? { label: marker.category };
    let details = '';
    for (const [k, v] of Object.entries(marker.details ?? {})) {
        details += `<div><span style="color:#6b7280">${k}:</span> <strong>${v}</strong></div>`;
    }

    let statusLine = '';
    if (marker.kecamatan) {
        statusLine = `<div style="color:#6b7280;font-size:12px">Kecamatan: <strong>${marker.kecamatan}</strong></div>`;
    }

    let syncLine = '';
    if (marker.lastSeen) {
        syncLine = `<div style="color:#9ca3af;font-size:11px;margin-top:4px">Terakhir: ${marker.lastSeen}</div>`;
    }

    let actions = '';
    if (marker.sourceUrl) {
        actions += `<a href="${marker.sourceUrl}" target="_blank" rel="noopener" style="display:inline-block;margin-top:8px;margin-right:6px;padding:4px 10px;border-radius:8px;background:#f3f4f6;color:#6b7280;font-size:12px;font-weight:700;text-decoration:none">Data Sumber</a>`;
    }
    if (marker.detailUrl) {
        actions += `<a href="${marker.detailUrl}" style="display:inline-block;margin-top:8px;padding:4px 10px;border-radius:8px;background:#047857;color:#fff;font-size:12px;font-weight:700;text-decoration:none">Lihat Detail</a>`;
    }

    return `
        <div style="min-width:180px">
            <div style="font-weight:800;color:#047857;margin-bottom:6px;font-size:14px">${marker.name}</div>
            <div style="color:#4b5563;font-size:12px;line-height:1.6">
                <span style="display:inline-block;background:#d1fae5;color:#047857;padding:1px 8px;border-radius:10px;font-weight:700;margin-bottom:6px">${cfg.label}</span>
                ${statusLine}
                ${details}
                ${syncLine}
                ${actions}
            </div>
        </div>
    `;
}

function buildLayers(map, markers, options) {
    const clusterOptions = options.cluster === false ? null : { maxClusterRadius: 40, showCoverageOnHover: false };
    const clusters = {};
    const bounds = [];

    markers.forEach((marker) => {
        const lat = Number(marker.latitude);
        const lng = Number(marker.longitude);
        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            return;
        }

        if (options.legendOnly?.length && !options.legendOnly.includes(marker.category)) {
            return;
        }

        const cfg = categoryConfig[marker.category] ?? { sector: 'other' };
        const layer = L.marker([lat, lng], { icon: iconFor(marker.category, options.size ?? 28) });
        layer.bindPopup(popupHtml(marker));
        bounds.push([lat, lng]);

        if (clusterOptions) {
            const key = options.legend ? marker.category : cfg.sector;
            if (!clusters[key]) {
                clusters[key] = L.markerClusterGroup(clusterOptions);
                clusters[key].addTo(map);
            }
            clusters[key].addLayer(layer);
        } else {
            layer.addTo(map);
        }
    });

    if (bounds.length && options.fitBounds !== false) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: options.maxZoom ?? 11 });
    }

    return { clusters };
}

export function createMap(containerId, markers, options = {}) {
    const container = document.getElementById(containerId);
    if (!container) {
        return null;
    }

    const map = options.map ?? L.map(containerId, { scrollWheelZoom: true }).setView(options.center ?? [-3.25, 121.85], options.zoom ?? 9);
    const store = (map._mjccStore = map._mjccStore || {});

    if (!options.map) {
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);
    }

    buildLayers(map, markers, options);

    if (options.resize) {
        setTimeout(() => map.invalidateSize(), 120);
    }

    store.markers = markers;
    store.options = options;
    return map;
}

export function renderMarkers(map, markers, options = {}) {
    if (!map) {
        return;
    }
    const store = map._mjccStore || (map._mjccStore = {});
    const opts = { ...(store.options ?? {}), ...options };

    // remove all non-base layers (cluster groups + individual markers)
    map.eachLayer((layer) => {
        if (layer instanceof L.MarkerClusterGroup || layer instanceof L.Marker) {
            map.removeLayer(layer);
        }
    });

    buildLayers(map, markers, opts);

    store.markers = markers;
    store.options = opts;
    return map;
}

export function findFocus(markers, focus) {
    if (!focus) {
        return null;
    }
    const [slug, id] = focus.split(':');
    return markers.find((m) => m.slug === slug && String(m.id) === String(id)) ?? null;
}

export function legendItems(categories) {
    return categories.map((category) => ({
        category,
        ...(categoryConfig[category] ?? { color: '#64748b', label: category, emoji: '📍' }),
    }));
}

/**
 * Renders petugas (responder) markers plus a road route from each petugas
 * back toward the requested location. The route is fetched on-demand from the
 * public OSRM demo server and drawn as a dashed polyline, mirroring the
 * "versi live" tracking in the mobile app.
 */
export function renderResponders(map, sender, responders, options = {}) {
    if (!map || !Array.isArray(responders)) {
        return;
    }

    const store = map._mjccStore || (map._mjccStore = {});
    store.responderMarkers = store.responderMarkers || [];
    store.routeLayers = store.routeLayers || [];
    store.routeFetches = store.routeFetches || {};

    // drop the previous responder markers + route lines
    store.responderMarkers.forEach((m) => {
        try {
            map.removeLayer(m);
        } catch (e) { /* already removed */ }
    });
    store.routeLayers.forEach((l) => {
        try {
            map.removeLayer(l);
        } catch (e) { /* already removed */ }
    });
    store.responderMarkers = [];
    store.routeLayers = [];

    if (!sender) {
        return;
    }

    const from = { lat: Number(sender.latitude), lng: Number(sender.longitude) };
    if (!Number.isFinite(from.lat) || !Number.isFinite(from.lng)) {
        return;
    }

    responders.forEach((r) => {
        const to = { lat: Number(r.latitude), lng: Number(r.longitude) };
        if (!Number.isFinite(to.lat) || !Number.isFinite(to.lng)) {
            return;
        }

        const marker = L.marker([to.lat, to.lng], { icon: iconFor('responder', options.size ?? 30) });
        marker.bindPopup(popupHtml(r));
        marker.addTo(map);
        store.responderMarkers.push(marker);

        const cacheKey = `${r.id ?? ''}:${to.lat.toFixed(5)},${to.lng.toFixed(5)}`;
        const cached = store.routeFetches[cacheKey];

        const addLine = (points) => {
            const line = L.polyline(points, {
                color: options.routeColor ?? '#2563eb',
                weight: options.routeWeight ?? 4,
                opacity: options.routeOpacity ?? 0.75,
                dashArray: '8 10',
            }).addTo(map);
            store.routeLayers.push(line);
        };

        // Already fetched earlier: re-draw from cache without hitting OSRM again.
        if (Array.isArray(cached)) {
            addLine(cached);
            return;
        }
        if (cached === true) {
            return; // a fetch is in flight; it will draw when it resolves
        }

        store.routeFetches[cacheKey] = true;

        // OSRM: geometry in GeoJSON. Route from petugas -> requester.
        const url =
            'https://router.project-osrm.org/route/v1/driving/' +
            `${to.lng},${to.lat};${from.lng},${from.lat}?overview=full&geometries=geojson&steps=false`;

        fetch(url)
            .then((res) => (res.ok ? res.json() : null))
            .then((data) => {
                const coords = data?.routes?.[0]?.geometry?.coordinates;
                if (!Array.isArray(coords) || coords.length < 2) {
                    return;
                }
                const points = coords.map(([lng, lat]) => [lat, lng]);
                store.routeFetches[cacheKey] = points;
                addLine(points);

                const distanceKm = ((data.routes[0].distance ?? 0) / 1000).toFixed(1);
                const etaMin = Math.round((data.routes[0].duration ?? 0) / 60);
                if (typeof options.onRoute === 'function') {
                    options.onRoute({ distanceKm, etaMin, responder: r });
                }
            })
            .catch(() => { /* routing unavailable: keep markers */ });
    });
}
