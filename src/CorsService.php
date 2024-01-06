<?php

namespace Effectra\Cors;

use Effectra\Http\Message\Response;
use Psr\Http\Message\RequestInterface;
 use Psr\Http\Message\ResponseInterface;

class CorsService
{
    /** @var string[]  */
    private array $allowedOrigins = [];
    /** @var string[] */
    private array $allowedOriginsPatterns = [];
    /** @var string[] */
    private array $allowedMethods = [];
    /** @var string[] */
    private array $allowedHeaders = [];
    /** @var string[] */
    private array $exposedHeaders = [];
    private bool $supportsCredentials = false;
    private ?int $maxAge = 0;

    private bool $allowAllOrigins = false;
    private bool $allowAllMethods = false;
    private bool $allowAllHeaders = false;

    
    public function __construct(array $options = [])
    {
        if ($options) {
            $this->setOptions($options);
        }
    }

    
    public function setOptions(array $options): void
    {
        $this->allowedOrigins = $options['allowedOrigins'] ?? $options['allowed_origins'] ?? $this->allowedOrigins;
        $this->allowedOriginsPatterns =
            $options['allowedOriginsPatterns'] ?? $options['allowed_origins_patterns'] ?? $this->allowedOriginsPatterns;
        $this->allowedMethods = $options['allowedMethods'] ?? $options['allowed_methods'] ?? $this->allowedMethods;
        $this->allowedHeaders = $options['allowedHeaders'] ?? $options['allowed_headers'] ?? $this->allowedHeaders;
        $this->supportsCredentials =
            $options['supportsCredentials'] ?? $options['supports_credentials'] ?? $this->supportsCredentials;

        $maxAge = $this->maxAge;
        if (array_key_exists('maxAge', $options)) {
            $maxAge = $options['maxAge'];
        } elseif (array_key_exists('max_age', $options)) {
            $maxAge = $options['max_age'];
        }
        $this->maxAge = $maxAge === null ? null : (int)$maxAge;

        $exposedHeaders = $options['exposedHeaders'] ?? $options['exposed_headers'] ?? $this->exposedHeaders;
        $this->exposedHeaders = $exposedHeaders === false ? [] : $exposedHeaders;

        $this->normalizeOptions();
    }

    private function normalizeOptions(): void
    {
        // Normalize case
        $this->allowedHeaders = array_map('strtolower', $this->allowedHeaders);
        $this->allowedMethods = array_map('strtoupper', $this->allowedMethods);

        // Normalize ['*'] to true
        $this->allowAllOrigins = in_array('*', $this->allowedOrigins);
        $this->allowAllHeaders = in_array('*', $this->allowedHeaders);
        $this->allowAllMethods = in_array('*', $this->allowedMethods);

        // Transform wildcard pattern
        if (!$this->allowAllOrigins) {
            foreach ($this->allowedOrigins as $origin) {
                if (strpos($origin, '*') !== false) {
                    $this->allowedOriginsPatterns[] = $this->convertWildcardToPattern($origin);
                }
            }
        }
    }


    private function convertWildcardToPattern($pattern)
    {
        $pattern = preg_quote($pattern, '#');

        $pattern = str_replace('\*', '.*', $pattern);

        return '#^' . $pattern . '\z#u';
    }

    public function isCorsRequest(RequestInterface $request): bool
    {
        return $request->hasHeader('Origin');
    }

    public function isPreflightRequest(RequestInterface $request): bool
    {
        return $request->getMethod() === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
    }

    public function handlePreflightRequest(RequestInterface $request): ResponseInterface
    {
        $response = new Response(204);

        return $this->addPreflightRequestHeaders($response, $request);
    }

    public function addPreflightRequestHeaders(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        $this->configureAllowedOrigin($response, $request);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $this->configureAllowCredentials($response, $request);

            $this->configureAllowedMethods($response, $request);

            $this->configureAllowedHeaders($response, $request);

            $this->configureMaxAge($response, $request);
        }

        return $response;
    }

    public function isOriginAllowed(RequestInterface $request): bool
    {
        if ($this->allowAllOrigins === true) {
            return true;
        }

        $origin = $request->getHeaderLine('Origin');

        if (in_array($origin, $this->allowedOrigins)) {
            return true;
        }

        foreach ($this->allowedOriginsPatterns as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    public function addActualRequestHeaders(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        $this->configureAllowedOrigin($response, $request);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $this->configureAllowCredentials($response, $request);

            $this->configureExposedHeaders($response, $request);
        }

        return $response;
    }

    private function configureAllowedOrigin(ResponseInterface $response, RequestInterface $request): void
    {
        if ($this->allowAllOrigins === true && !$this->supportsCredentials) {
            // Safe+cacheable, allow everything
           $response->addHeader('Access-Control-Allow-Origin', '*');
        } elseif ($this->isSingleOriginAllowed()) {
            // Single origins can be safely set
           $response->addHeader('Access-Control-Allow-Origin', array_values($this->allowedOrigins)[0]);
        } else {
            // For dynamic headers, set the requested Origin header when set and allowed
            if ($this->isCorsRequest($request) && $this->isOriginAllowed($request)) {
               $response->addHeader('Access-Control-Allow-Origin', (string) $request->headers->get('Origin'));
            }

            $this->varyHeader($response, 'Origin');
        }

    }

    private function isSingleOriginAllowed(): bool
    {
        if ($this->allowAllOrigins === true || count($this->allowedOriginsPatterns) > 0) {
            return false;
        }

        return count($this->allowedOrigins) === 1;
    }

    private function configureAllowedMethods(ResponseInterface $response, RequestInterface $request): void
    {
        if ($this->allowAllMethods === true) {
            $allowMethods = strtoupper((string) $request->getHeaderLine('Access-Control-Request-Method'));
            $this->varyHeader($response, 'Access-Control-Request-Method');
        } else {
            $allowMethods = implode(', ', $this->allowedMethods);
        }

       $response->addHeader('Access-Control-Allow-Methods', $allowMethods);
    }

    private function configureAllowedHeaders(ResponseInterface $response, RequestInterface $request): void
    {
        if ($this->allowAllHeaders === true) {
            $allowHeaders = (string) $request->getHeaderLine('Access-Control-Request-Headers');
            $this->varyHeader($response, 'Access-Control-Request-Headers');
        } else {
            $allowHeaders = implode(', ', $this->allowedHeaders);
        }
       $response->addHeader('Access-Control-Allow-Headers', $allowHeaders);
    }

    private function configureAllowCredentials(ResponseInterface $response, RequestInterface $request): void
    {
        if ($this->supportsCredentials) {
           $response->addHeader('Access-Control-Allow-Credentials', 'true');
        }
    }

    private function configureExposedHeaders(ResponseInterface $response, RequestInterface $request): void
    {
        if ($this->exposedHeaders) {
           $response->addHeader('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
        }
    }

    private function configureMaxAge(ResponseInterface $response, RequestInterface $request): void
    {
        if ($this->maxAge !== null) {
           $response->addHeader('Access-Control-Max-Age', (string) $this->maxAge);
        }
    }

    public function varyHeader(ResponseInterface $response, string $header): ResponseInterface
    {
        if (!$response->hasHeader('Vary')) {
           $response->addHeader('Vary', $header);
        } elseif (!in_array($header, explode(', ', (string) $response->getHeaderLine('Vary')))) {
           $response->addHeader('Vary', ((string) $response->getHeaderLine('Vary')) . ', ' . $header);
        }

        return $response;
    }
}
