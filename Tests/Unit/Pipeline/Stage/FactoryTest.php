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

namespace Driver\Tests\Unit\Pipeline\Stage;

use Driver\Pipeline\Stage\Factory;
use Driver\Pipeline\Stage\StageInterface;
use Driver\Tests\Unit\Helper\DI;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var Factory $factory */
    private $factory;

    protected function setUp()
    {
        $this->factory = DI::getContainer()->get(Factory::class);
    }

    public function testCreateReturnsCorrectClass()
    {
        $this->assertTrue(is_a($this->factory->create([]), StageInterface::class));
    }

    private function runInaccessibleFunction($class, $name, $argument = null)
    {
        $method = new \ReflectionMethod($class, $name);
        $method->setAccessible(true);

        return $method->invoke($this->factory, $argument);
    }
}