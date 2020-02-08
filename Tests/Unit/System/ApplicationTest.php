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

namespace Driver\Tests\Unit\System;

use DI\Container;
use Driver\System\Application;
use Driver\Tests\Unit\Utils;
use Symfony\Component\Console\Application as ConsoleApplication;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /** @var $container Container */
    protected $container;

    protected function setUp()
    {
        $this->container = (new Utils())->getContainer();
    }


    public function testConsoleExistsInClass()
    {
        $application = $this->container->get('Driver\System\Application');

        $this->assertTrue(is_a($application->getConsole(), 'Symfony\Component\Console\Application'));
    }
}