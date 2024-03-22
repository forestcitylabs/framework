Class Discovery
===============

There are many situations where a component may need to parse a list of classes for attributes, getting this list of classes can be done using the class discovery component.

Scan Directory Class Discovery
------------------------------

The scan directory class discovery component allows you to define paths to directories and returns a list of classes found in those directories.

```php
<?php

use ForestCityLabs\Framework\Utility\ClassDiscovery\ScanDirectoryDiscovery;

$discovery = new ScanDirectoryDiscovery([__DIR__ . '/src/Entity']);
$classes = $discovery->discoverClasses();
```

The above example will return all classes defined in the `src/Entity` directory.

Manual Class Discovery
----------------------

The simplest way to define classes is to just tell the system which classes you are looking for.

```php
<?php

use ForestCityLabs\Framework\Utility\ClassDiscovery\ManualDiscovery;

$discovery = new ManualDiscovery([\DateTimeInterface::class]);
$classes = $discover->discoverClasses();
```

Chained Class Discovery
-----------------------

The above methods can be combined using the chained class discovery method.

```php
<?php

use ForestCityLabs\Framework\Utility\ClassDiscovery\ChainedDiscovery;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ScanDirectoryDiscovery;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ManualDiscovery;

$discovery = new ChainedDiscovery([
    new ManualDiscovery([\DateTimeInterface::class]),
    new ScanDirectoryDiscovery([__DIR__ . 'src/Entity']),
]);
$classes = $discovery->discoverClasses();
```
