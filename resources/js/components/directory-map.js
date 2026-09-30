import L from 'leaflet';
import 'leaflet.markercluster';

/**
 * Leaflet map of businesses. Marker data comes from a JSON attribute and
 * popups are built with DOM APIs (textContent), never innerHTML, so
 * business names cannot inject markup.
 *
 * Usage: <div x-data="directoryMap(config)" data-markers='[...]'>
 */
export default (config) => ({
    map: null,
    cluster: null,

    init() {
        this.map = L.map(this.$refs.canvas, {
            scrollWheelZoom: false,
            attributionControl: true,
        }).setView([config.center.lat, config.center.lng], config.zoom);

        L.tileLayer(config.tiles.url, {
            attribution: config.tiles.attribution,
            maxZoom: config.tiles.maxZoom,
        }).addTo(this.map);

        this.cluster = L.markerClusterGroup({ showCoverageOnHover: false });
        this.map.addLayer(this.cluster);

        this.render(JSON.parse(this.$el.dataset.markers || '[]'));

        // Livewire re-renders (filters, pagination) update the markers in place.
        new MutationObserver(() => this.render(JSON.parse(this.$el.dataset.markers || '[]')))
            .observe(this.$el, { attributes: true, attributeFilter: ['data-markers'] });
    },

    render(markers) {
        this.cluster.clearLayers();

        const bounds = [];

        for (const marker of markers) {
            if (!Number.isFinite(marker.lat) || !Number.isFinite(marker.lng)) {
                continue;
            }

            const icon = L.divIcon({
                className: marker.sponsored ? 'map-pin map-pin--sponsored' : 'map-pin',
                iconSize: [28, 28],
                iconAnchor: [14, 28],
            });

            const pin = L.marker([marker.lat, marker.lng], { icon, title: marker.name });
            pin.bindPopup(() => this.popup(marker));
            this.cluster.addLayer(pin);
            bounds.push([marker.lat, marker.lng]);
        }

        if (bounds.length > 0) {
            this.map.fitBounds(bounds, { padding: [24, 24], maxZoom: 16 });
        }
    },

    popup(marker) {
        const wrapper = document.createElement('div');
        wrapper.className = 'map-popup';

        const link = document.createElement('a');
        // URLs are built server-side with route(); refuse anything off-site anyway.
        const url = new URL(marker.url, window.location.origin);
        link.href = url.origin === window.location.origin ? url.href : '#';
        link.textContent = marker.name;
        link.className = 'map-popup__name';
        wrapper.appendChild(link);

        if (marker.sponsored) {
            const badge = document.createElement('span');
            badge.className = 'map-popup__sponsored';
            badge.textContent = config.labels.sponsored;
            wrapper.appendChild(badge);
        }

        if (marker.status) {
            const status = document.createElement('p');
            status.textContent = marker.status;
            wrapper.appendChild(status);
        }

        return wrapper;
    },

    destroy() {
        this.map?.remove();
    },
});
