<?php

return [
    /*
     * Bearer token expected from site instances when posting install status.
     * Set LARAUPDATER_STATUS_REPORT_TOKEN in .env (empty = no auth required).
     */
    'status_report_token' => env('LARAUPDATER_STATUS_REPORT_TOKEN', ''),
];
