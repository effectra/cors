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

use Effectra\Cors\CorsMiddleware;
use Slim\Factory\AppFactory;

// Create App
$app = AppFactroy::create();

// Add Cors Middleware
$app->add(new CorsMiddleware());

// Define app routes


// Run app
$app->run();

```

## Contributing

Contributions are welcome! If you encounter any issues or have suggestions for improvements, please feel free to submit a pull request or open an issue on the GitHub repository.

## License

This package is open-source and available under the [MIT License](https://opensource.org/licenses/MIT).
