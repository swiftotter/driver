<?php
/**
 * SwiftOtter_Base is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SwiftOtter_Base is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with SwiftOtter_Base. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Joseph Maxwell
 * @copyright SwiftOtter Studios, 11/25/16
 * @package default
 **/

namespace Driver\Tests\Unit\Enines\MySql\Sandbox;

use Driver\Engines\MySql\Sandbox\Sandbox;
use Driver\System\AwsClientFactory;
use Driver\Tests\Unit\Helper\DI;

class SandboxTest extends \PHPUnit_Framework_TestCase
{
    public function testGetInstanceActiveReturnsTrue()
    {
        $creator = function($serviceType, $arguments) {
            $type = '\\Aws\\AwsClient';
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

        $sandbox = DI::getContainer()->make(Sandbox::class, ['disableInstantiation' => true, 'awsClientFactory' => new AwsClientFactory($creator)]);
        $this->assertTrue($sandbox->getInstanceActive());
    }
}