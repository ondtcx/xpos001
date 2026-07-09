import './bootstrap';

import Alpine from 'alpinejs';

import { registerPosSidebarStore } from './pos-sidebar-store';

window.Alpine = Alpine;

registerPosSidebarStore(Alpine, window.__POS_INITIAL__ ?? {});

Alpine.start();
