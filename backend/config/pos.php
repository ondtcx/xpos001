<?php

return [
    /*
    |--------------------------------------------------------------------------
    | POS v2 feature flag
    |--------------------------------------------------------------------------
    |
    | When `enabled` is true, the new 2-column POS view is served and the
    | controller accepts AJAX submissions. When false, the legacy sidebar
    | view is served and the controller still performs form-submit
    | processing (caja check + redirect). Default is `false` in PR 2
    | (foundation), flipped to `true` in PR 3 (cutover).
    |
    */

    'enabled' => env('POS_V2_ENABLED', false),
];
