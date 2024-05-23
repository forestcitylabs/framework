Sessions
========

The Forest City Labs Framework uses a custom sessions implementation that is both object-oriented and fits in the middleware paradigm.

Requirements
------------

The session component requires the [`dflydev/fig-cookies`](https://packagist.org/packages/dflydev/fig-cookies) library to function.

The session drivers must have a back-end, the available drivers are:

* Filesystem: [league/flysystem](https://packagist.org/packages/league/flysystem):^3.0
* DBAL: [doctrine/dbal](https://packagist.org/packages/doctrine/dbal):^3.0
* Predis: [predis/predis](https://packagist.org/packages/predis/predis):^2.0

Configuration
-------------

### Available Drivers

#### Filesystem

To configure the filesystem driver simply create a flysystem using any adapter and pass it to the session driver as follows.

```php title="Filesystem Session Driver"
<?php

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use ForestCityLabs\Framework\Session\Driver\FilesystemSessionDriver;

$driver = new FilesystemSessionDriver(
    new Filesystem(
        new LocalFilesystemAdapter(__DIR__ . '/var/session')
    )
);
```

#### DBAL 

To use the DBAL adapter you also need to create the session table within the corresponding database. The `session:create-table` command can be used for this purpose.

```php title="DBAL Session Driver"
<?php

use Doctrine\DBAL\DriverManager;
use ForestCityLabs\Framework\Session\Driver\DbalSessionDriver;

$driver = new DbalSessionDriver(
    DriverManager::getConnection([
        'dbname' => 'mydb',
        'user' => 'user',
        'password' => 'secret',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ])
);
```

!!! note
    You can set the table name using the second parameter in the constructor, the default is `session`.

#### Predis

The predis adapter only requires a predis client to function, you can create one as follows.

```php title="Predis Session Driver"
<?php

use ForestCityLabs\Framework\Session\Driver\PredisSessionDriver;
use Predis\Client;

$driver = new PredisSessionDriver(
    new Client()
);
```

Usage
-----

To use a session you need to create a session and fill it with items, the session middleware will handle much of this for you by creating a `_session` attribute on your request and checking if that attribute has items within it. This middleware will automatically manage your cookies using the `dflydev/fig-cookies` library.

```php
<?php

use ForestCityLabs\Framework\Middleware\SessionMiddleware;

$middleware = new SessionMiddleware($driver);
$kernel->addMiddleware($middleware);
$kernel->handle($request);
```

The `ForestCityLabs\Framework\Session\Session` class is the API for interacting with the session.

```php
<?php

$session = $request->getAttribute('_session');
$session->setValue('hello', 'there');
```

Once the session reaches the middleware again it will be automatically saved using the configured driver.

```php
<?php

$session = $request->getAttribute('_session');
print_r($session->getValue('hello'));
/**
 * Will output "there".
 */
```
