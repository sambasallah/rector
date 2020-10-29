# All 601 Rectors Overview

- [Projects](#projects)
---

## Projects

- [CodingStyle](#codingstyle) (1)

## CodingStyle

### `YieldClassMethodToArrayClassMethodRector`

- class: [`Rector\CodingStyle\Rector\ClassMethod\YieldClassMethodToArrayClassMethodRector`](/rules/coding-style/src/Rector/ClassMethod/YieldClassMethodToArrayClassMethodRector.php)
- [test fixtures](/rules/coding-style/tests/Rector/ClassMethod/YieldClassMethodToArrayClassMethodRector/Fixture)

Turns yield return to array return in specific type and method

```php
<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ClassMethod\YieldClassMethodToArrayClassMethodRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(YieldClassMethodToArrayClassMethodRector::class)
        ->call('configure', [[
            YieldClassMethodToArrayClassMethodRector::METHODS_BY_TYPE => [
                'EventSubscriberInterface' => ['getSubscribedEvents'],
            ],
        ]]);
};
```

↓


<br><br>

