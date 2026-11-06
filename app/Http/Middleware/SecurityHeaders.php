<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (app()->isProduction() && ! $request->isSecure()) {
            $response = redirect()->to(rtrim((string) config('app.url'), '/').'/'.ltrim($request->getRequestUri(), '/'), 301);
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction()) {
            $response->headers->set('Content-Security-Policy', "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self' https:; img-src 'self' data: blob: https:; font-src 'self' data: https://fonts.bunny.net; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self' https: wss:");
        }

        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
