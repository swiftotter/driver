<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\Pipeline\Span;

use Driver\Pipeline\Master as PipeMaster;
use Driver\Pipeline\Master;
use Driver\Pipeline\Span\Factory;
use Driver\Pipeline\Span\Primary;
use Driver\Pipeline\Span\SpanInterface;
use Driver\System\Configuration;
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
        $this->assertTrue(is_a($this->factory->create('empty'), SpanInterface::class));
    }

    public function testPipelineExists(): void
    {
        $this->assertTrue(
            $this->runInaccessibleFunction('pipelineExists', PipeMaster::DEFAULT_NODE)
        );
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint,SlevomatCodingStandard.TypeHints.ReturnTypeHint
    private function runInaccessibleFunction(string $name, ...$arguments)
    {
        $method = new \ReflectionMethod($this->factory, $name);
        $method->setAccessible(true);

        return $method->invoke($this->factory, ...$arguments);
    }
}
