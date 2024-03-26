# Routing

The routing component consists of [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware that uses the [nikic/fast-route](https://packagist.org/packages/nikic/fast-route) package to route requests at specific paths to controllers.

Route mappings are defined using [PHP Attributes](https://www.php.net/manual/en/language.attributes.overview.php) on their relevant controllers.

## Requirements

* [nikic/fast-route](https://packagist.org/packages/nikic/fast-route)
* [cocur/slugify](https://packagist.org/packages/cocur/slugify)
* A container library implementing [PSR-11](https://www.php-fig.org/psr/psr-11).
* An event dispatcher library implementing [PSR-14](https://www.php-fig.org/psr/psr-14).
* A response factory library implementing [PSR-17](https://www.php-fig.org/psr/psr-17).
* A cache library implementing [PSR-6](https://www.php-fig.org/psr/psr-6) (check the caching section for implementations that this framework provides).
* A logging library implementing [PSR-3](https://www.php-fig.org/psr/psr-3).
* A [PSR-15](https://www.php-fig.org/psr/psr-15/) compliant request handler (see the Kernel class for a request handler that ships with this framework).

## Usage

### Creating controllers

Controllers are any class that uses attributes to map methods to paths. PHP attributes are used to create this relationship. Every method _must_ return an object implementing the `Psr\Http\Message\ResponseInterface` interface.

```php title="Example controller" 
use ForestCityLabs\Framework\Routing\Attribute\Route;
use Psr\Http\Message\ResponseInterface;

class MyController
{
    #[Route("/home")]
    public function home(): ResponseInterface
    {
        return new Response();
    }
}
```

#### Controllers are services

All controllers are considered services in the FCL Framework, so making use of other services is done through the use of wiring them into the constructor using your preferred PSR-11 compliant container. Here is an example of using a response factory to create a response instead of creating the response manually.

```php title="Example service use in a controller"
use ForestCityLabs\Framework\Routing\Attribute\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class MyController
{
    public function __construct(
        private ResponseFactoryInterface $response_factory
    ) {}

    #[Route("/home")]
    public function home(): ResponseInterface
    {
        return $this->response_factory->createResponse();
    }
}
```

If your service is not required in _every_ method of the controller you may with to only inject the service into the specific method, which can be done the by type-hinting the service in the method.

```php title="Using a service in a single method"
use ForestCityLabs\Framework\Routing\Attribute\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class MyController
{
    #[Route("/home")]
    public function home(
        ResponseFactoryInterface $response_factory
    ): ResponseInterface {
        return $response_factory->createResponse();
    }
}
```

#### Accessing the incoming request

A common requirement within a controller is accessing the incoming request, this can be done by simply adding the request as a parameter on the method as shown.

```php title="Accessing the incoming request"
use ForestCityLabs\Framework\Routing\Attribute\Route;
use Psr\Http\Message\ResponseInterface;

class MyController
{
    #[Route("/home")]
    public function home(ServerRequestInterface $request): ResponseInterface
    {
        $auth_token = $request->getHeader('token');
        return new Response();
    }
}
```

#### Using path parameters

You can use path parameters as defined in [nikic/FastRoute](https://github.com/nikic/FastRoute), here is an example of mapping a parameter into the method.

```php title="Parameters in paths"
use ForestCityLabs\Framework\Routing\Attribute\Route;
use Psr\Http\Message\ResponseInterface;

class MyController
{
    #[Route("/blog/{id}")]
    public function getBlog(string $id): ResponseInterface
    {
        return new Response();
    }
}
```

### Using the Middleware

The middleware requires the metadata provider to be able to match a path to the controller. To create the middleware you need to pass this in as well.

```php title="Middleware usage example"
use ForestCityLabs\Framework\Routing\MetadataProvider;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ManualClassDiscovery;
use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Cocur\Slugify\Slugify;

$metadata_provider = new MetadataProvider(
    new ManualDiscovery([\Application\Controller\MyController::class]), # (1)
    new CacheItemPoolInterface(), # (2)
    new LoggerInterface(), # (3)
    new Slugify()
);

$middleware = new RoutingMiddleware(
    $container, # (4)
    $route_dispatcher, # (5)
    $event_dispatcher, # (6)
    $metadata_provider,
    $response_factory, # (7)
    $parameter_processor # (8)
);

$kernel->addMiddleware($middleware);
```

1. This is an array of classes that are annotated with your routing metadata.
2. A PSR-6 compliant cache pool.
3. A PSR-3 compliant logger.
4. A PSR-11 compliant container.
5. The route dispatcher to use when matching routes.
6. A PSR-14 compliant event dispatcher.
7. A PSR-17 compliant http response factory.
8. The parameter processor service.

