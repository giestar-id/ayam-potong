<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ETagMiddleware
{
    /**
     * Handle an incoming request and support conditional HTTP caching (ETag / 304 Not Modified).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only evaluate ETags for successful GET / HEAD responses
        if (!in_array($request->method(), ['GET', 'HEAD']) || $response->getStatusCode() !== 200) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return $response;
        }

        // Generate ETag hash based on output payload
        $etag = '"' . md5($content) . '"';
        $response->headers->set('ETag', $etag);

        // Check if client sent If-None-Match header
        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch) {
            $clientEtags = array_map('trim', explode(',', $ifNoneMatch));
            if (in_array($etag, $clientEtags, true) || in_array(trim($etag, '"'), $clientEtags, true) || in_array('*', $clientEtags, true)) {
                // Return 304 Not Modified with empty body
                $response->setNotModified();
            }
        }

        return $response;
    }
}
