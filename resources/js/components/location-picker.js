import L from 'leaflet';

/**
 * Pick a business position: tap the map or drag the pin. Coordinates are
 * written to Livewire properties (latitude / longitude) and validated server-side.
 */
export default (config) => ({
    map: null,
    marker: null,

    init() {
        const start = [config.lat ?? config.center.lat, config.lng ?? config.center.lng];

        this.map = L.map(this.$refs.canvas, { scrollWheelZoom: false }).setView(start, config.zoom);

        L.tileLayer(config.tiles.url, {
            attribution: config.tiles.attribution,
            maxZoom: config.tiles.maxZoom,
        }).addTo(this.map);

        const icon = L.divIcon({ className: 'map-pin', iconSize: [28, 28], iconAnchor: [14, 28] });
        this.marker = L.marker(start, { draggable: true, icon }).addTo(this.map);

        this.marker.on('dragend', () => this.update(this.marker.getLatLng()));
        this.map.on('click', (event) => {
            this.marker.setLatLng(event.latlng);
            this.update(event.latlng);
        });

        if (config.lat === null || config.lat === undefined) {
            this.update({ lat: start[0], lng: start[1] });
        }
    },

    update({ lat, lng }) {
        const round = (value) => Math.round(value * 1e6) / 1e6;
        this.$wire.set('latitude', round(lat), false);
        this.$wire.set('longitude', round(lng), false);
    },

    destroy() {
        this.map?.remove();
    },
});
