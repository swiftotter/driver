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

namespace Driver\System;

use \Symfony\Component\Console\Application as ConsoleApplication;

/**
 * Class Application
 * Main class to run functionality
 *
 * @package Driver\System
 */
class Application
{
    protected $console;

    const RUN_MODE_NORMAL = 'normal';
    const RUN_MODE_TEST = 'test';

    public function __construct(ConsoleApplication $console)
    {
        $this->console = $console;
    }

    public function run($mode = self::RUN_MODE_NORMAL)
    {
        $this->console->run();
    }

    /**
     * @return ConsoleApplication
     */
    public function getConsole()
    {
        return $this->console;
    }
}