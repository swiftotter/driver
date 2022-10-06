<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\Helper;

use DI\Container;
use DI\ContainerBuilder;
use Driver\System\DependencyConfig;

class DI
{
    public static function getContainer(): Container
    {
        $dependencyConfig = new DependencyConfig();
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions($dependencyConfig->get());
        return $containerBuilder->build();
    }
}
