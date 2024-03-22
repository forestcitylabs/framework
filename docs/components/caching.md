Caching
=======

The Forest City Labs Framework ships with several implementations of the [PSR-6](https://www.php-fig.org/psr/psr-6/) for a few common caching back-ends. There are also several commands that can be used to manage the cache backends.

Drivers
-------

### `FilesystemCachePool`

#### Requirements

* [league/flysystem](https://packagist.org/packages/league/flysystem):^3.0

#### Configuration

The filesystem cache pool accepts a flysystem and uses that to serialize and store cache items.

```php
<?php

use ForestCityLabs\Framework\Cache\Pool\FilesystemCachePool;
use League\Flysystem\Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

$pool = new FilesystemCachePool(
    new Flysystem(
        new LocalFilesystemAdapter(__DIR__ . '/var/cache')
    )
)
```

### `DbalCachePool`

#### Requirements

* [doctrine/dbal](https://packagist.org/packages/doctrine/dbal):^3.0

#### Configuration

```php
<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use ForestCityLabs\Framework\Cache\Pool\DbalCachePool;

$connection = DriverManager::getConnection(
    (new DsnParser())->parse('mysqli://root:root@localhost/app')
); 
$pool = new DbalCachePool($connection);
```

### `PredisCachePool`

#### Requirements

* [predis/predis](https://packagist.org/packages/predis/predis):^2.0

#### Configuration

```php
<?php

use ForestCityLabs\Framework\Cache\Pool\PredisCachePool;
use Predis\Client;

$pool = new PredisCachePool(new Client());
```
