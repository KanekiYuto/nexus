<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetClientIpFromFastly
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // fastly-client-ip 由 Fastly 在入口处写入真实客户端 IP，不可被客户端伪造
        if ($clientIp = $request->header('fastly-client-ip')) {
            $request->server->set('REMOTE_ADDR', $clientIp);
        }

        return $next($request);
    }
}
