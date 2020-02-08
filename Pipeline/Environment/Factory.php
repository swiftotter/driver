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

namespace Driver\Pipeline\Environment;

use DI\Container;
use Driver\Engines\MySql\Sandbox\Utilities;
use Driver\Pipeline\Master;
use Driver\System\Configuration;
use Driver\System\Factory\FactoryInterface;

class Factory
{
    const DEFAULT_ENV = 'default';

    private $configuration;
    private $container;
    private $type;
    private $utilities;

    public function __construct(Configuration $configuration, Container $container, Utilities $utilities, $type)
    {
        $this->configuration = $configuration;
        $this->container = $container;
        $this->type = $type;
        $this->utilities = $utilities;
    }

    public function createDefault()
    {
        return $this->container->make($this->type, [
            'name' => self::DEFAULT_ENV,
            'properties' => $this->getEnvironmentProperties(self::DEFAULT_ENV)
        ]);
    }

    /**
     * @param $name
     * @return EnvironmentInterface
     */
    public function create($name)
    {
        return $this->container->make($this->type, [
            'name' => $name,
            'properties' => $this->getEnvironmentProperties($name),
            'utilities' => $this->utilities
        ]);
    }

    protected function environmentExists($name)
    {
        return is_array($this->configuration->getNode("environments/{$name}"));
    }

    protected function getEnvironmentProperties($name)
    {
        if ($this->environmentExists($name)) {
            return $this->configuration->getNode("environments/{$name}");
        } else {
            throw new \Driver\Pipeline\Exception\PipeLineNotFound();
        }
    }
}