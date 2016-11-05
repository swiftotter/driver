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
 * @copyright SwiftOtter Studios, 10/28/16
 * @package default
 **/

namespace Driver\Tests\Unit\Commands;

use Driver\Commands\Pipe;
use Driver\Pipes\Master as PipeMaster;
use Driver\Pipes\Set\Factory;
use Driver\Pipes\Set\Primary;
use Driver\System\Configuration;
use Driver\Tests\Unit\Helper\DI;

class PipeTest extends \PHPUnit_Framework_TestCase
{
    /** @var Pipe $subject */
    private $subject;

    protected function setUp()
    {
        $configuration = new Configuration(new Configuration\YamlLoader());
        $this->subject = DI::getContainer()->get(Pipe::class);
    }

    public function testNameAndDescriptionAreSet()
    {
        $configureMethod = new \ReflectionMethod($this->subject, 'configure');
        $configureMethod->setAccessible(true);
        $configureMethod->invoke($this->subject);

        $this->assertNotEmpty($this->subject->getName());
    }


}