<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\Pipeline\Set;

use Driver\Pipeline\Master;
use Driver\Pipeline\Span\Primary;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\Pipeline\Transport\Primary as Transport;
use Driver\Tests\Unit\Helper\DI;
use PHPUnit\Framework\TestCase;

class PrimaryTest extends TestCase
{
    public function testInvokeReturnsTransport(): void
    {
        $pipelineName = Master::DEFAULT_NODE;
        $configuration = new Configuration(new Configuration\FileCollector(), new Configuration\FileLoader());
        $set = DI::getContainer()->make(
            Primary::class,
            ['list' => $configuration->getNode('pipelines/' . $pipelineName)]
        );

        $this->assertTrue(is_a(
            $set(new Transport($pipelineName), true),
            TransportInterface::class
        ));
    }
}
