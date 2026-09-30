/**
 * "Use my location": asks the browser, then hands rounded coordinates to the
 * Livewire component. Never required, never stored in the URL.
 */
export default () => ({
    locating: false,
    error: null,

    locate() {
        if (!('geolocation' in navigator)) {
            this.error = 'unsupported';

            return;
        }

        this.locating = true;
        this.error = null;

        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.locating = false;
                // ~110 m precision is enough for "near me" and protects privacy.
                const round = (value) => Math.round(value * 1000) / 1000;
                this.$wire.setLocation(round(position.coords.latitude), round(position.coords.longitude));
            },
            (error) => {
                this.locating = false;
                this.error = error.code === error.PERMISSION_DENIED ? 'denied' : 'unavailable';
            },
            { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 },
        );
    },
});
