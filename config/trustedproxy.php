<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | This setting defines which proxies should be trusted. Set this to '*' to
    | trust all proxies, or provide an array of specific proxy IP addresses.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', '*'),

    /*
    |--------------------------------------------------------------------------
    | Trusted Headers
    |--------------------------------------------------------------------------
    |
    | This setting defines which headers should be trusted from the proxies.
    | These headers are used to determine the original client IP, host, port,
    | and protocol.
    |
    */

    'headers' => Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
        | Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
        | Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
        | Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        | Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX
        | Illuminate\Http\Request::HEADER_FORWARDED,

];
