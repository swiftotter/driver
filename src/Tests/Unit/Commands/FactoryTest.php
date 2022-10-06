<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\Commands;

use Driver\Commands\Factory;
use Driver\Engines\MySql;
use Driver\Tests\Unit\Helper\DI;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testCreateReturnsPipeClass(): void
    {
        $factory = DI::getContainer()->get(Factory::class);
        $this->assertSame(MySql::class, get_class($factory->create('connect')));
    }
}
