<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\System;

use DI\Container;
use Driver\Tests\Unit\Utils;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = (new Utils())->getContainer();
    }

    public function testConsoleExistsInClass(): void
    {
        $application = $this->container->get('Driver\System\Application');
        $this->assertTrue(is_a($application->getConsole(), 'Symfony\Component\Console\Application'));
    }
}
