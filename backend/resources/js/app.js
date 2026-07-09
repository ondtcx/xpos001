import './bootstrap';

import Alpine from 'alpinejs';
import { registerPosStore } from './pos-store';

window.Alpine = Alpine;

registerPosStore(Alpine, window.__POS_INITIAL_V2__);

Alpine.start();
