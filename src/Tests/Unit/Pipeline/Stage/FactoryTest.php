<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\Pipeline\Stage;

use Driver\Pipeline\Stage\Factory;
use Driver\Pipeline\Stage\StageInterface;
use Driver\Tests\Unit\Helper\DI;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    private Factory $factory;

    protected function setUp(): void
    {
        $this->factory = DI::getContainer()->get(Factory::class);
    }

    public function testCreateReturnsCorrectClass(): void
    {
        $this->assertTrue(is_a($this->factory->create([], 'empty'), StageInterface::class));
    }
}
