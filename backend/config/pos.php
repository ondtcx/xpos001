<?php

return [
    /*
    |--------------------------------------------------------------------------
    | POS v2 feature flag
    |--------------------------------------------------------------------------
    |
    | When `enabled` is true (the default since PR 3 cutover), the new
    | 2-column POS view is served and the controller accepts AJAX
    | submissions only (no caja abierta check, no form-submit redirect).
    | Set `POS_V2_ENABLED=false` to temporarily fall back to the legacy
    | sidebar flow (used by integration tests that still target the
    | legacy markup).
    |
    */

    'enabled' => env('POS_V2_ENABLED', true),
];
