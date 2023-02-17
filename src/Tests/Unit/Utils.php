<?php

declare(strict_types=1);

namespace Driver\Tests\Unit;

use DI\Container;
use DI\ContainerBuilder;
use Driver\System\DependencyConfig;

class Utils
{
    private ContainerBuilder $containerBuilder;

    public function __construct()
    {
        $this->containerBuilder = new ContainerBuilder();
        $this->containerBuilder->addDefinitions((new DependencyConfig())->get());
    }

    public function getContainer(): Container
    {
        return $this->containerBuilder->build();
    }
}
