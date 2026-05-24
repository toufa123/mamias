import 'cap-widget';

// Patch leafletMapField after the vendor bundle registers it to add a WoRMS popup
// on the pick marker when the user clicks the map to set coordinates.
document.addEventListener('livewire:init', () => {
    // Defer so the vendor livewire:init listener runs first.
    setTimeout(() => {
        const original = window.leafletMapField;
        if (!original) return;

        window.leafletMapField = function ($wire, config) {
            const base = original($wire, config);
            const origUpdate = base.updatePickMarker.bind(base);

            base.updatePickMarker = function () {
                origUpdate();
                if (!base.pickMarker) return;

                const marker = Alpine.raw(base.pickMarker);
                const coords = base.getState();
                if (!coords) return;

                // Derive sibling field paths from the map's own statePath.
                const prefix = config.state.statePath.replace(/\.[^.]+$/, '');
                const aphiaId = $wire.get(prefix + '.aphia_id');
                const name    = $wire.get(prefix + '.suggested_scientific_name');
                const auth    = $wire.get(prefix + '.authority');

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
                html += '<span style="color:#555;font-size:12px;">📍 ' + lat + ', ' + lng + '</span>';
                html += '</div>';

                marker.bindPopup(html, { maxWidth: 320 }).openPopup();
            };

            return base;
        };
    }, 0);
});
