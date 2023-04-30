<?php

namespace Aval\Cors;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Cors
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        // Get origin and headers from request
        $origin = $request->getHeaderLine('Origin');
        $headers = $request->getHeaderLine('Access-Control-Request-Headers');

        // Set headers for response
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withHeader('Access-Control-Allow-Headers', $headers);
        $response = $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

        // Handle preflight request
        if ($request->getMethod() === 'OPTIONS') {
            return $response;
        }

        // Pass request and response to next middleware
        return $next($request, $response);
    }
}
