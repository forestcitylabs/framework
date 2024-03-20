Events
======

The Forest City Labs Framework ships with a generic ListenerProvider that can be used in conjunction with any [PSR-14](https://www.php-fig.org/psr/psr-14/) compliant event dispatcher (for example [league/event](https://event.thephpleague.com/3.0/)).

To configure an event listener you can use the `ForestCityLabs\Event\Attribute\EventListener` attribute.

Creating an Event Listener
--------------------------

```php
<?php

namespace Application\EventListener;

use ForestCityLabs\Framework\Attribute\EventListener;
use ForestCityLabs\Framework\Event\ListenerProvider;
use ForestCityLabs\Framework\Events\PreRouteDispatchEvent;

#[EventListener(PreRouteDispatchEvent::class, priority: ListenerProvider::PRIORITY_LATE)]
class PreRouteDispatchEventListener
{
    public function __invoke(PreRouteDispatchEvent $event) 
    {
        // ...do something with the event.
    }
}
```

The above example will listen for the pre route dispatch event and act later than most other events.

!!! note 

    The available priorities for the listener are, in order:

    * `PRIORITY_FIRST`
    * `PRIORITY_EARLY`
    * `PRIORITY_NORMAL`
    * `PRIORITY_LATE`
    * `PRIORITY_LAST`
    
    Please note that there is no guarantee that first or last will be first or last, just earlier/later than events using the other constants.


Configuring an Event Dispatcher
-------------------------------

To create an event dispatcher you first need to configure our listener provider.

!!! warning

    The example below uses the league event dispatcher, other event systems should work in a similar way, but there is no standard for how to wire a listener provider to a dispatcher.

```php
<?php

use ForestCityLabs\Framework\Event\ListenerProvider;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ScanDirectoryDiscovery;
use League\Event\EventDispatcher;
use Psr\Container\ContainerInterface;
use Psr\Cache\CacheItemPoolInterface;

$provider = new ListenerProvider(
    new ScanDirectoryDiscovery(__DIR__ . '/src/EventListener'),
    new CacheItemPoolInterface(), // Must be a concrete implementation.
    new ContainerInterface(), // Must be a concrete implementation.
);

// Now we can create the dispatcher.
$dispatcher = new EventDispatcher($provider);

// Dispatch an event.
$dispatcher->dispatch(new PreRouteDispatchEvent());
```

!!! tip

    To learn more about the `ScanDirectoryDiscovery` class and class discovery in general take a look at the [class discovery](/components/class_discovery.md) documentation.
