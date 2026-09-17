<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting middleware configuration
    |
    */

    'api' => '60:1', // 60 requests per minute for API
    'web' => '60:1', // 60 requests per minute for web routes
    'login' => '5:1', // 5 login attempts per minute
    'upload' => '10:1', // 10 file uploads per minute

];
