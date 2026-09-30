import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import directoryMap from './components/directory-map';
import geolocate from './components/geolocate';
import { track, share } from './components/contact';

Alpine.data('directoryMap', directoryMap);
Alpine.data('geolocate', geolocate);

// Available to Alpine expressions: x-on:click="track(...)".
Alpine.magic('track', () => track);
Alpine.magic('share', () => share);

Livewire.start();
