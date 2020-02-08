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

namespace Driver\Tests\Unit\Commands;

use DI\ContainerBuilder;
use Driver\Commands\Factory;
use Driver\Pipeline\Command;
use Driver\System\Configuration;
use Driver\System\DependencyConfig;
use Driver\Tests\Unit\Helper\DI;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateReturnsPipeClass()
    {
        $factory = DI::getContainer()->get(Factory::class);
        $this->assertSame(Command::class, get_class($factory->create('pipeline')));
    }
}