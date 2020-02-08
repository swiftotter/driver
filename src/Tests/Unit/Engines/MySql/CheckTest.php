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
 * @copyright SwiftOtter Studios, 11/19/16
 * @package default
 **/

namespace Driver\Test\Unit\Engines\MySql;

use Driver\Engines\MySql\Check;
use Driver\Pipeline\Master;
use Driver\Pipeline\Transport\Primary;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\Tests\Unit\Helper\DI;

class CheckTest extends \PHPUnit_Framework_TestCase
{
    protected $checkClass;

    public function testGoCorrectlyMatchesValues()
    {
        /** @var Check $class */
        $class = DI::getContainer()->get(Check::class);

        $this->assertTrue(is_a($class->go(new Primary(Master::class, [], [], new \Driver\System\Logs\Primary())), TransportInterface::class));
    }
}