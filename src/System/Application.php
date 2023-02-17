<?php

declare(strict_types=1);

namespace Driver\System;

use DI\Container;
use Driver\Pipeline\Command;
use Symfony\Component\Console\Application as ConsoleApplication;

class Application
{
    private ConsoleApplication $console;
    private Container $container;

    public function __construct(ConsoleApplication $console, Container $container)
    {
        $this->console = $console;
        $this->container = $container;
    }

    public function run(): void
    {
        $this->console->add($this->container->get(Command::class));
        $this->console->run();
    }

    public function getConsole(): ConsoleApplication
    {
        return $this->console;
    }
}
