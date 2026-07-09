import './bootstrap';

import Alpine from 'alpinejs';
import { registerPosSidebarStore } from './pos-sidebar-store';
import { registerPosStore } from './pos-store';

window.Alpine = Alpine;

// Old store: still in use by the legacy pos/index.blade.php while the new
// view is behind a feature flag. Removed in PR 3.
registerPosSidebarStore(Alpine, window.__POS_INITIAL__);

// New store: introduced in PR 1 (foundation), used by the v2 view in PR 2.
registerPosStore(Alpine, window.__POS_INITIAL_V2__);

Alpine.start();
