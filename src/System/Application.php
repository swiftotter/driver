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

use DI\Container;
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
    protected $configuration;
    protected $container;

    const RUN_MODE_NORMAL = 'normal';
    const RUN_MODE_TEST = 'test';

    public function __construct(ConsoleApplication $console, Configuration $configuration, Container $container)
    {
        $this->console = $console;
        $this->configuration = $configuration;
        $this->container = $container;
    }

    public function run($mode = self::RUN_MODE_NORMAL)
    {
        foreach ($this->configuration->getNode('commands') as $name => $settings) {
            $this->console->add($this->container->get($settings['class']));
        }

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