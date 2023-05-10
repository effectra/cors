# Effectra CORS Middleware

Effectra CORS Middleware is a PHP package that provides Cross-Origin Resource Sharing (CORS) functionality for your web applications. It allows you to handle CORS headers and enable cross-origin requests, providing flexibility and security for your API endpoints.

## Installation

You can easily install the Effectra CORS Middleware package using Composer. Run the following command:

```bash
composer require effectra/cors
```

## Usage

To use the Effectra CORS Middleware in your application, follow the example below:

```php
<?php

namespace Your\Namespace;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Effectra\Cors\CorsMiddleware;

class YourMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Instantiate the CorsMiddleware
        $cors = new CorsMiddleware();

        // Process the CORS headers
        $response = $cors->process($request, $handler);

        // Get origin and headers from request
        $origin = $request->getHeaderLine('Origin');
        $headers = $request->getHeaderLine('Access-Control-Request-Headers');

        // Set headers for response
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withHeader('Access-Control-Allow-Headers', $headers);
        $response = $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

        return $response;
    }
}
```

In the above example, we create a middleware class that implements the `MiddlewareInterface` provided by PSR-15. The `process` method is responsible for handling the CORS headers and returning the modified response.

To enable CORS, you need to instantiate the `CorsMiddleware` class and invoke its `process` method, passing in the request and handler. The middleware will extract the necessary headers from the request and set the appropriate headers in the response.

You can customize the allowed origins, headers, and methods based on your specific requirements by modifying the code accordingly.

## Contributing

Contributions are welcome! If you encounter any issues or have suggestions for improvements, please feel free to submit a pull request or open an issue on the GitHub repository.

## License

This package is open-source and available under the [MIT License](https://opensource.org/licenses/MIT).
