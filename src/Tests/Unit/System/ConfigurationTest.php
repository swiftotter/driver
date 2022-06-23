<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\System;

use Driver\System\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private Configuration $configuration;

    public function setUp(): void
    {
        $this->configuration = new Configuration(new Configuration\FileCollector(), new Configuration\FileLoader());
    }

    public function testGetAllNodesReturnsInformation(): void
    {
        $result = $this->configuration->getNodes();
        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
    }

    public function testGetNodeReturnsInformation(): void
    {
        $this->assertSame('unknown', $this->configuration->getNode('test/value'));
    }
}
