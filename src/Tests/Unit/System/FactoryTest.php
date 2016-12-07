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
 * @copyright SwiftOtter Studios, 10/29/16
 * @package default
 **/

namespace Driver\Tests\Unit\System;

use Driver\Pipeline\Transport\Error;
use Driver\System\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryReturnsObject()
    {
        $factory = new Factory\Base();
        $error = $factory->create(Error::class, 'Test Message');
        $this->assertEquals('Test Message', $error->getMessage());
    }
}