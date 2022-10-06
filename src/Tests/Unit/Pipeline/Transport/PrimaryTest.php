<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\Pipeline;

use Driver\Pipeline\Master;
use Driver\Pipeline\Transport\Primary;
use Driver\Pipeline\Transport\Status;
use Driver\Tests\Unit\Helper\DI;
use PHPUnit\Framework\TestCase;

class PrimaryTest extends TestCase
{
    private Primary $transport;
    private string $node = 'test';
    private string $message = 'this is a message';

    public function setUp(): void
    {
        $this->transport = DI::getContainer()->make(
            Primary::class,
            ['pipeline' => Master::DEFAULT_NODE, 'statuses' => [], 'data' => []]
        );

        parent::setUp();
    }

    public function testCanSetDataKey(): void
    {
        $new = $this->transport->withNewData('sample_key', 'sample_data');

        $this->assertTrue($this->transport !== $new);
        $this->assertSame('sample_data', $new->getData('sample_key'));
    }

    public function testWithStatusReturnsNewObject(): void
    {
        $this->assertTrue(
            $this->transport !== $this->transport->withStatus(new Status($this->node, $this->message))
        );
    }

    public function testGetStatusesByNodeReturnsValues(): void
    {
        $this->assertCount(
            1,
            $this->transport->withStatus(new Status($this->node, $this->message))->getStatusesByNode($this->node)
        );
        $this->assertCount(0, $this->transport->getStatusesByNode($this->node));
    }

    public function testGetErrorsReturnsValues(): void
    {
        $transport = new Primary(Master::DEFAULT_NODE);
        $this->assertCount(
            1,
            $transport->withStatus(new Status($this->node, $this->message, true))->getErrors()
        );
        $this->assertCount(0, $this->transport->getErrorsByNode($this->node));
    }

    public function testGetErrorsByNodeReturnsValues(): void
    {
        $this->assertCount(
            1,
            $this->transport->withStatus(new Status($this->node, $this->message, true))
                ->withStatus(new Status($this->node . '_test', $this->message))
                ->withStatus(new Status($this->node, $this->message))
                ->getErrors()
        );
        $this->assertCount(0, $this->transport->getErrors());
    }
}
