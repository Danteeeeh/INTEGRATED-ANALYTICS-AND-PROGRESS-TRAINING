<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    protected $proxies = '*';

    protected $headers = Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO
        | Request::HEADER_X_FORWARDED_PREFIX
        | Request::HEADER_FORWARDED;

    protected function setTrustedProxyIpAddresses(Request $request): void
    {
        // Trust all proxies when behind a CDN/Load Balancer
        $this->proxies = '*';
        parent::setTrustedProxyIpAddresses($request);
    }
}
