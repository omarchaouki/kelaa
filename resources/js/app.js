import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import directoryMap from './components/directory-map';
import locationPicker from './components/location-picker';
import geolocate from './components/geolocate';
import { track, share } from './components/contact';
import cookieConsent from './components/cookie-consent';

Alpine.data('directoryMap', directoryMap);
Alpine.data('locationPicker', locationPicker);
Alpine.data('geolocate', geolocate);
Alpine.data('cookieConsent', cookieConsent);

// Available to Alpine expressions: x-on:click="$track(...)".
Alpine.magic('track', () => track);
Alpine.magic('share', () => share);

Livewire.start();

// Offline page and static asset cache only (see public/sw.js). HTTPS (production)
// only: on a local dev server, restarts would show the offline page.
if ('serviceWorker' in navigator && location.protocol === 'https:') {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
