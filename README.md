# Effectra/cors

Effectra/cors is a PHP package that provides middleware and service classes for handling Cross-Origin Resource Sharing (CORS) in web applications. CORS is a security feature implemented by web browsers to control access to resources on a different origin.

## Installation

You can install the Effectra/cors package via Composer:

```bash
composer require effectra/cors
```

## Usage

### CorsMiddleware

The `CorsMiddleware` class is a PSR-15 middleware that can be used in a middleware stack to handle CORS requests. It checks incoming requests for CORS-related headers and adds appropriate headers to the response.

#### Example:

```php
use Effectra\Cors\CorsMiddleware;
use Effectra\Cors\CorsService;
use Psr\Http\Server\MiddlewareInterface;

// Create a CorsService instance with desired configuration
$corsService = new CorsService([
    'allowedOrigins' => ['https://example.com'],
    'allowedMethods' => ['GET', 'POST'],
    'allowedHeaders' => ['Content-Type'],
]);

// Create the CorsMiddleware instance
$corsMiddleware = new CorsMiddleware($corsService, ['/api']);

// Add the middleware to your middleware stack
$middlewareStack = [
    // Other middleware...
    $corsMiddleware,
    // Other middleware...
];
```

### CorsService

The `CorsService` class provides the core functionality for handling CORS requests. It allows you to configure various CORS-related settings.

#### Example:

```php
use Effectra\Cors\CorsService;

// Create a CorsService instance with desired configuration
$corsService = new CorsService([
    'allowedOrigins' => ['https://example.com'],
    'allowedMethods' => ['GET', 'POST'],
    'allowedHeaders' => ['Content-Type'],
]);

// Check if a request is a CORS request
$isCorsRequest = $corsService->isCorsRequest($request);

// Check if a request is a preflight request
$isPreflightRequest = $corsService->isPreflightRequest($request);

// Handle a preflight request and add necessary headers to the response
$preflightResponse = $corsService->handlePreflightRequest($request);

// Check if a specific origin is allowed
$isOriginAllowed = $corsService->isOriginAllowed($request);

// Add CORS headers to the actual response
$actualResponse = $corsService->addActualRequestHeaders($response, $request);
```

## Configuration

The `CorsService` class allows you to configure various CORS-related settings using an options array.

### Available Options:

- `allowedOrigins`: An array of allowed origins.
- `allowedOriginsPatterns`: An array of allowed origin patterns (wildcards).
- `allowedMethods`: An array of allowed HTTP methods.
- `allowedHeaders`: An array of allowed headers.
- `supportsCredentials`: Whether credentials (cookies, HTTP authentication) should be supported.
- `maxAge`: Maximum time (in seconds) that the results of a preflight request can be cached.
- `exposedHeaders`: An array of headers exposed to the response.

## Contributing

If you'd like to contribute to this project, please follow our [contribution guidelines](CONTRIBUTING.md).

## License

The Effectra/cors package is open-sourced software licensed under the [MIT license](LICENSE).