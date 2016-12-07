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
 * @copyright SwiftOtter Studios, 11/5/16
 * @package default
 **/

namespace Driver\Tests\Unit\Pipeline\Set;

use Driver\Pipeline\Master;
use Driver\Pipeline\Span\Primary;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\Pipeline\Transport\Primary as Transport;
use Driver\Tests\Unit\Helper\DI;

class PrimaryTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokeReturnsTransport()
    {
        $pipelineName = Master::DEFAULT_NODE;
        $configuration = new Configuration(new Configuration\YamlLoader());
        $set = DI::getContainer()->make(Primary::class, ['list' => $configuration->getNode('pipelines/' . $pipelineName)]);

        $this->assertTrue(is_a($set(new Transport($pipelineName, [], [], new \Driver\System\Logs\Primary()), true), TransportInterface::class));
    }
}