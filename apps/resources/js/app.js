import 'cap-widget';
const _L = window.L;
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
        zoomControl: false,
        attributionControl: false,
        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        touchZoom: false,
        keyboard: false,
    });
    L.tileLayer(tileUrl, { minZoom: 0, maxZoom: 13 }).addTo(mini);

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
                const L = _L || window.L;
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
                if (!window.L || typeof window.L.map !== 'function') {
                    console.warn('window.L invalid at init. keys:', window.L ? Object.keys(window.L).slice(0, 15).join(',') : 'null/undef');
                }
                addMapControls(map, _L || window.L);

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
