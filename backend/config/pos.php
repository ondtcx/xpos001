<?php

return [
    /*
    |--------------------------------------------------------------------------
    | POS v2 feature flag
    |--------------------------------------------------------------------------
    |
<<<<<<< HEAD
    | When `enabled` is true, the new 2-column POS view is served and the
    | controller accepts AJAX submissions. When false, the legacy sidebar
    | view is served and the controller still performs form-submit
    | processing (caja check + redirect). Default is `false` in PR 2
    | (foundation), flipped to `true` in PR 3 (cutover).
    |
    */

    'enabled' => env('POS_V2_ENABLED', false),
=======
    | When `enabled` is true (the default since PR 3 cutover), the new
    | 2-column POS view is served and the controller accepts AJAX
    | submissions only (no caja abierta check, no form-submit redirect).
    | Set `POS_V2_ENABLED=false` to temporarily fall back to the legacy
    | sidebar flow (used by integration tests that still target the
    | legacy markup).
    |
    */

    'enabled' => env('POS_V2_ENABLED', true),
>>>>>>> feat/pos-v2-ui-replacement
];
