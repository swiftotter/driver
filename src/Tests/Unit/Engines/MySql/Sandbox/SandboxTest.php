<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\Enines\MySql\Sandbox;

use Driver\Engines\MySql\Sandbox\Sandbox;
use Driver\System\AwsClientFactory;
use Driver\Tests\Unit\Helper\DI;
use PHPUnit\Framework\TestCase;

class SandboxTest extends TestCase
{
    public function testGetInstanceActiveReturnsTrue(): void
    {
        $creator = function ($serviceType, $arguments) {
            $type = '\\Aws\\Rds\\RdsClient';
            $stub = $this->getMockBuilder($type)
                ->setMethods(['describeDBInstances'])
                ->disableOriginalConstructor()
                ->setConstructorArgs([$arguments])
                ->getMock();

            $stub->method('describeDBInstances')
                ->willReturn([
                    'DBInstances' => [
                        [
                            'DBInstanceStatus' => 'available'
                        ]
                    ]
                ]);

            return $stub;
        };

        $sandbox = DI::getContainer()->make(
            Sandbox::class,
            ['disableInstantiation' => true, 'awsClientFactory' => new AwsClientFactory($creator)]
        );
        $this->assertTrue($sandbox->getInstanceActive());
    }
}
