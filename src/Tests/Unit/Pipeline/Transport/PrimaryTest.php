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
 * @copyright SwiftOtter Studios, 10/8/16
 * @package default
 **/

namespace Driver\Tests\Unit\Pipeline;

use Driver\Pipeline\Master;
use Driver\Pipeline\Transport\Primary;
use Driver\Pipeline\Transport\Status;
use Driver\Tests\Unit\Helper\DI;

class PrimaryTest extends \PHPUnit_Framework_TestCase
{
    /** @var Primary */
    private $transport;
    private $node = 'test';
    private $message = 'this is a message';

    public function setUp()
    {
        $this->transport = DI::getContainer()->make(Primary::class, ['pipeline' => Master::DEFAULT_NODE, 'statuses' => [], 'data' => []]);

        parent::setUp();
    }

    public function testCanSetDataKey()
    {
        $new = $this->transport->withNewData('sample_key', 'sample_data');

        $this->assertTrue($this->transport !== $new);
        $this->assertSame('sample_data', $new->getData('sample_key'));
    }

    public function testWithStatusReturnsNewObject()
    {
        $this->assertTrue($this->transport !== $this->transport->withStatus(new Status($this->node, $this->message)));
    }

    public function testGetStatusesByNodeReturnsValues()
    {
        $this->assertCount(1, $this->transport->withStatus(new Status($this->node, $this->message))->getStatusesByNode($this->node));
        $this->assertCount(0, $this->transport->getStatusesByNode($this->node));
    }

    public function testGetErrorsReturnsValues()
    {
        $transport = new Primary(Master::DEFAULT_NODE, [], [], new \Driver\System\Logs\Primary());
        $this->assertCount(1, $transport->withStatus(new Status($this->node, $this->message, true))->getErrors());
        $this->assertCount(0, $this->transport->getErrorsByNode($this->node));
    }

    public function testGetErrorsByNodeReturnsValues()
    {
        $this->assertCount(1,
            $this->transport->withStatus(new Status($this->node, $this->message, true))
                ->withStatus(new Status($this->node . '_test', $this->message))
                ->withStatus(new Status($this->node, $this->message))
                ->getErrors()
        );

        $this->assertCount(0, $this->transport->getErrors());
    }

}