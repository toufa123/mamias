import 'cap-widget';

// elemind/filament-echarts watches each chart with a ResizeObserver that calls
// chart.resize(). An observer always fires once right after observe(), and
// ECharts' resize() redraws with a zero-duration animation — so every chart's
// entry animation was cut off after a frame. Drop that first notification for
// chart elements; real resizes still reach the callback.
const NativeResizeObserver = window.ResizeObserver;
window.ResizeObserver = class extends NativeResizeObserver {
    constructor(callback) {
        const seen = new WeakSet();
        super((entries, observer) => {
            const resized = entries.filter(
                (entry) =>
                    !entry.target.classList.contains('filament-echarts-chart-object') ||
                    seen.has(entry.target) ||
                    !seen.add(entry.target),
            );
            if (resized.length) {
                callback(resized, observer);
            }
        });
    }
};

/**
 * PNG export for the dashboard graphics: the rendered chart (ECharts'
 * getDataURL, or the rasterised map) under its title, with an optional note
 * and colour-scale legend, on white. All sizes scale with `pixelRatio` so the
 * text matches a chart exported at that ratio.
 *
 * @param {{src: string, title: string, note?: string|null, scale?: {from: string, to: string, min: number, max: number, label: string}|null, file: string, pixelRatio?: number}} graphic
 */
window.mamiasExportPng = async ({ src, title, note = null, scale = null, file, pixelRatio = 2 }) => {
    const image = new Image();
    image.src = src;
    await image.decode();

    const px = (n) => n * pixelRatio;
    const font = getComputedStyle(document.body).fontFamily;
    const header = px(20) + px(18) + (note ? px(8) + px(12) : 0) + px(16);
    const footer = scale ? px(44) : px(16);

    const canvas = Object.assign(document.createElement('canvas'), {
        width: image.width + px(40),
        height: header + image.height + footer,
    });
    const context = canvas.getContext('2d');
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.textBaseline = 'top';

    context.fillStyle = '#0e2630';
    context.font = `600 ${px(18)}px ${font}`;
    context.fillText(title, px(20), px(20));

    if (note) {
        context.fillStyle = '#5f7783';
        context.font = `400 ${px(12)}px ${font}`;
        context.fillText(note, px(20), px(46));
    }

    context.drawImage(image, px(20), header);

    if (scale) {
        const top = header + image.height + px(16);
        const gradient = context.createLinearGradient(px(56), 0, px(216), 0);
        gradient.addColorStop(0, scale.from);
        gradient.addColorStop(1, scale.to);

        context.fillStyle = '#47606b';
        context.font = `400 ${px(12)}px ${font}`;
        context.textAlign = 'right';
        context.fillText(String(scale.min), px(48), top);
        context.textAlign = 'left';
        context.fillText(`${scale.max}  ${scale.label}`, px(224), top);
        context.fillStyle = gradient;
        context.fillRect(px(56), top + px(1), px(160), px(12));
    }

    Object.assign(document.createElement('a'), { href: canvas.toDataURL('image/png'), download: `${file}.png` }).click();
};

// Leaflet, as filament-leaflet's bundle left it. app.blade.php loads
// laravel-notify as a module so its own `L` cannot replace this global.
const leaflet = () => window.L;
const _L = leaflet();
if (!_L || typeof _L.map !== 'function') {
    console.warn('MODULE INIT: window.L invalid. type:', typeof _L, 'keys:', _L ? Object.keys(_L).slice(0, 15).join(',') : 'null, window keys with L:', Object.getOwnPropertyNames(window).filter(k => k.includes('L')).join(','));
}
class LeafletPMStub {
    constructor() {}
    setLang() {}
    setGlobalOptions() {}
    addControls() {}
    _initTextMarker() {}
    _createTextMarker() {}
}
function ensureLeafletPM() {
    // On the global `L` on purpose: geoman, inside the leaflet bundle, reads
    // `L.PM` from the global.
    if (window.L && !window.L.PM) {
        window.L.PM = {
            optIn: false,
            Map: LeafletPMStub,
            Edit: {
                LayerGroup: LeafletPMStub,
                Marker: LeafletPMStub,
                Text: LeafletPMStub,
                Line: LeafletPMStub,
                Polyline: LeafletPMStub,
                Polygon: LeafletPMStub,
                Rectangle: LeafletPMStub,
                Circle: LeafletPMStub,
                CircleMarker: LeafletPMStub,
                ImageOverlay: LeafletPMStub,
            },
        };
    }
}
document.addEventListener('livewire:init', ensureLeafletPM);
document.addEventListener('x-modal-opened', ensureLeafletPM);

// The UNEP/MAP basemap (config/filament-leaflet.php) is cached in EPSG:4326, not
// Leaflet's default web mercator, so every map, minimap included, uses that CRS.
// Every map also opens on the whole Mediterranean, fitted to its own size. Maps
// that fit their markers afterwards still do (those are Mediterranean too). A map
// still hidden at load (in a modal) is fitted on its first resize instead.
const MEDITERRANEAN = [[30, -6], [46, 36.5]];
function useBasemapCrs() {
    const L = leaflet();
    if (!L?.Map || L.Map._mamiasBasemap) return;
    L.Map._mamiasBasemap = true;
    L.Map.mergeOptions({ crs: L.CRS.EPSG4326, mediterraneanView: true });
    L.Map.addInitHook(function () {
        if (!this.options.mediterraneanView) return;
        this.once('load', () => {
            const fit = () => this.fitBounds(MEDITERRANEAN, { animate: false });
            this.getSize().x ? fit() : this.once('resize', fit);
        });
    });
}
useBasemapCrs();

function addMinimap(map, L) {
    if (!map || map._myMiniMap) return;

    let tileUrl = null;
    map.eachLayer(function (l) {
        if (l._url && !tileUrl) tileUrl = l._url;
    });
    if (!tileUrl) return;

    const container = map.getContainer();
    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'position:absolute;bottom:10px;left:10px;width:150px;height:150px;border:2px solid rgba(0,0,0,.3);border-radius:4px;overflow:hidden;cursor:default;z-index:1000';
    container.appendChild(wrapper);

    const mini = L.map(wrapper, {
        mediterraneanView: false, // follows the main map instead
        zoomControl: false,
        attributionControl: false,
        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        touchZoom: false,
        keyboard: false,
    });
    L.tileLayer(tileUrl, { minZoom: 0, maxZoom: 10 }).addTo(mini);

    map.on('move', function () {
        try {
            const c = map.getCenter();
            mini.setView([c.lat, c.lng], Math.max(2, map.getZoom() - 3), { animate: false });
        } catch (e) { /* ignore */ }
    });

    map._myMiniMap = mini;
}

function addMousePosition(map, L) {
    if (!map || map._myMousePos) return;
    const container = map.getContainer();
    const div = L.DomUtil.create('div', '');
    div.style.cssText = 'position:absolute;bottom:10px;right:10px;background:white;padding:2px 7px;font:11px/1.4 monospace;border:2px solid rgba(0,0,0,.2);background-clip:padding-box;border-radius:4px;cursor:default;z-index:1000';
    div.innerHTML = '–';
    container.appendChild(div);
    map.on('mousemove', function (e) {
        div.innerHTML = Number(e.latlng.lat).toFixed(5) + ', ' + Number(e.latlng.lng).toFixed(5);
    });
    map._myMousePos = div;
}

function addMapControls(map, L) {
    if (!map) return;
    try { addMinimap(map, L); } catch (e) { console.warn('minimap error:', e); }
    try { addMousePosition(map, L); } catch (e) { console.warn('mousepos error:', e); }
}

// Patch leafletMapEntry so the infolist pick marker binds popup/tooltip from the
// marker data configured by getPickMarkerData() in SpeciesLocationsMapEntry.
document.addEventListener('livewire:init', () => {
    setTimeout(() => {
        const original = window.leafletMapEntry;
        if (!original) return;

        window.leafletMapEntry = function ($wire, config) {
            const base = original($wire, config);
            const origSetup = base.setupPickMarker.bind(base);

            base.setupPickMarker = function () {
                origSetup();
                const L = _L || leaflet();
                if (!L || typeof L.map !== 'function') {
                    console.warn('entry window.L invalid. keys:', L ? Object.keys(L).slice(0, 15).join(',') : 'null');
                }
                addMapControls(this.mapCore?.map, L);
                if (!this.pickMarker || !this.mapCore) return;

                const options = this.config.state.pickMarker;
                if (options.popup) {
                    this.mapCore.bindPopup(this.pickMarker, options.popup);
                }
                if (options.tooltip) {
                    this.mapCore.bindTooltip(this.pickMarker, options.tooltip);
                }
            };

            return base;
        };
    }, 0);
});

// Patch leafletMapField for multi-marker support: store an array of coordinates,
// render all markers on the map, and listen for Geoman draw events.
document.addEventListener('livewire:init', () => {
    setTimeout(() => {
        const original = window.leafletMapField;
        if (!original) return;

        window.leafletMapField = function ($wire, config) {
            const base = original($wire, config);
            const origInit = base.init.bind(base);

            base.pickMarkers = [];

            base.siblingMarkers = [];

            base.clearSiblingMarkers = function () {
                if (!base.mapCore?.map) return;
                const map = Alpine.raw(base.mapCore.map);
                base.siblingMarkers.forEach(m => map.removeLayer(Alpine.raw(m)));
                base.siblingMarkers = [];
            };

            base.renderSiblingMarkers = async function (speciesName) {
                base.clearSiblingMarkers();
                if (!speciesName || !base.mapCore?.map) return;

                const coordsList = await $wire.call('getSpeciesLocations', speciesName);
                if (!Array.isArray(coordsList) || coordsList.length === 0) return;

                const map = Alpine.raw(base.mapCore.map);

                coordsList.forEach(({ lat, lng }) => {
                    const marker = base.mapCore.createMarker({
                        coords: [lat, lng],
                        icon: { color: '#9ca3af' },
                        draggable: false,
                    });
                    marker.addTo(map);
                    base.siblingMarkers.push(marker);
                });
            };

            base.getState = function () {
                if (!this.config.state) return [];
                const val = this.$wire.get(this.config.state.statePath);
                if (!val) return [];
                if (Array.isArray(val)) return val;
                if (val.lat !== undefined && val.lng !== undefined) return [val];
                return [];
            };

            base.setState = function (lat, lng) {
                if (!this.config.state) return;
                const current = this.getState();
                this.$wire.set(this.config.state.statePath, [...current, { lat, lng }]);
            };

            base.removeMarker = function (index) {
                const current = this.getState();
                current.splice(index, 1);
                this.$wire.set(this.config.state.statePath, current);
            };

            base.buildMarkerPopupHtml = function (coords) {
                const prefix = config.state.statePath.replace(/\.[^.]+$/, '');
                const aphiaId = $wire.get(prefix + '.aphia_id');
                const name = $wire.get(prefix + '.suggested_scientific_name');
                const auth = $wire.get(prefix + '.authority');
                const lat = Number(coords.lat).toFixed(5);
                const lng = Number(coords.lng).toFixed(5);

                let html = '<div style="font-size:13px;line-height:1.9;min-width:180px;">';
                if (aphiaId && name) {
                    const url = 'https://www.marinespecies.org/aphia.php?p=taxdetails&id=' + aphiaId;
                    html += '<a href="' + url + '" target="_blank" rel="noopener noreferrer"'
                        + ' style="font-weight:600;color:#005f98;text-decoration:none;">'
                        + name + (auth ? ' <em>' + auth + '</em>' : '')
                        + '</a><br>';
                } else {
                    html += '<em style="color:#999;">No species selected</em><br>';
                }
                html += '<span style="color:#555;font-size:12px;">&#x1F4CD; ' + lat + ', ' + lng + '</span>';
                html += '</div>';
                return html;
            };

            base.renderPickMarkers = function () {
                base.pickMarkers.forEach(m => {
                    if (base.mapCore?.map) {
                        Alpine.raw(base.mapCore.map).removeLayer(Alpine.raw(m));
                    }
                });
                base.pickMarkers = [];

                const coordsList = this.getState();
                if (coordsList.length === 0) return;

                let markerOptions = this.config.state.pickMarker;

                coordsList.forEach((coords) => {
                    const opts = { ...markerOptions, coords: [coords.lat, coords.lng] };
                    const marker = this.mapCore.createMarker(opts);
                    Alpine.raw(marker).addTo(Alpine.raw(this.mapCore.map));
                    base.pickMarkers.push(marker);

                    marker.bindPopup(this.buildMarkerPopupHtml(coords), { maxWidth: 320 });
                });

                if (coordsList.length > 0) {
                    const last = Alpine.raw(base.pickMarkers[base.pickMarkers.length - 1]);
                    if (last && typeof last.openPopup === 'function') {
                        last.openPopup();
                    }
                }
            };

            base.init = function () {
                origInit();
                const map = this.mapCore?.map;
                if (!leaflet() || typeof leaflet().map !== 'function') {
                    console.warn('window.L invalid at init. keys:', leaflet() ? Object.keys(leaflet()).slice(0, 15).join(',') : 'null/undef');
                }
                addMapControls(map, _L || leaflet());

                const prefix = config.state.statePath.replace(/\.[^.]+$/, '');
                const namePath = prefix + '.suggested_scientific_name';

                base.renderSiblingMarkers($wire.get(namePath));
                $wire.watch(namePath, (name) => base.renderSiblingMarkers(name));

                if (map) {
                    Alpine.raw(map).on('pm:create', (e) => {
                        if (e.shape === 'Marker') {
                            const latlng = e.layer.getLatLng();
                            Alpine.raw(map).removeLayer(e.layer);
                            base.setState(latlng.lat, latlng.lng);
                        }
                    });
                }
            };

            base.updatePickMarker = function () {
                this.renderPickMarkers();
            };

            return base;
        };
    }, 0);
});
