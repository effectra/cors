<?php

namespace Effectra\Cors;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    /** @var CorsService $cors */
    protected $cors;


    protected array $paths = [];

    public function __construct(CorsService $cors, array $paths = [])
    {
        $this->cors = $cors;
        $this->paths = $paths;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check if we're dealing with CORS and if we should handle it
        if (!$this->shouldRun($request)) {
            return $handler->handle($request);
        }

        // For Preflight, return the Preflight response
        if ($this->cors->isPreflightRequest($request)) {
            $response = $this->cors->handlePreflightRequest($request);

            $this->cors->varyHeader($response, 'Access-Control-Request-Method');

            return $response;
        }

        // Handle the request
        $response = $handler->handle($request);

        if ($request->getMethod() === 'OPTIONS') {
            $this->cors->varyHeader($response, 'Access-Control-Request-Method');
        }

        return $this->addHeaders($request, $response);
    }

    /**
     * Add the headers to the Response, if they don't exist yet.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function addHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$response->hasHeader('Access-Control-Allow-Origin')) {
            // Add the CORS headers to the Response
            $response = $this->cors->addActualRequestHeaders($response, $request);
        }

        return $response;
    }

    /**
     * Determine if the request has a URI that should pass through the CORS flow.
     *
     * @param  ServerRequestInterface  $request
     * @return bool
     */
    protected function shouldRun(ServerRequestInterface $request): bool
    {
        return $this->isMatchingPath($request);
    }

    /**
     * The path from the config, to see if the CORS Service should run
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isMatchingPath(ServerRequestInterface $request): bool
    {
        // Get the paths from the config or the middleware
        $paths = $this->getPathsByHost($request->getUri()->getHost());

        foreach ($paths as $path) {
            if ($path !== '/') {
                $path = trim($path, '/');
            }

            if ($request->getUri()->getPath() === $path || $request->getUri()->getPath() === $path) {
                return true;
            }
        }

        return false;
    }

    /**
     * Paths by given host or string values in config by default
     *
     * @param string $host
     * @return array
     */
    protected function getPathsByHost(string $host): array
    {
        // If there are paths by the given host
        if (isset($this->paths[$host])) {
            return  $this->paths[$host];
        }
        // Defaults
        return array_filter($this->paths, function ($path) {
            return is_string($path);
        });
    }
}
