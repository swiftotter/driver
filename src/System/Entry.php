<?php

declare(strict_types=1);

namespace Driver\System;

use DI\ContainerBuilder;

class Entry
{
    /** @var string[]|null */
    private static ?array $arguments = null;

    /**
     * @param string[] $arguments
     */
    public static function go(array $arguments): void
    {
        set_time_limit(0);

        if (!is_array($arguments)) {
            $arguments = [];
        }
        self::$arguments = $arguments;
        self::configureDebug();

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions((new DependencyConfig(self::isDebug()))->get());
        $container = $containerBuilder->build();

        $application = $container->get(Application::class);
        $application->run();
    }

    private static function configureDebug(): void
    {
        if (self::isDebug()) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }
    }

    private static function isDebug(): bool
    {
        return count(array_filter(self::$arguments, function ($argument) {
            return strpos($argument, '--debug') !== false;
        })) > 0;
    }
}
