<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\System;

use Driver\System\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    private Configuration $configuration;

    public function setUp(): void
    {
        $this->configuration = new Configuration(new Configuration\FileCollector(), new Configuration\FileLoader());
    }

    public function testGetAllNodesReturnsInformation(): void
    {
        $result = $this->configuration->getNodes();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetNodeReturnsInformation(): void
    {
        $this->assertSame('unknown', $this->configuration->getNode('test/value'));
    }
}
