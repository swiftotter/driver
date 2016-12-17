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

namespace Driver\Commands;

use DI\Container;
use Driver\System\Configuration;

class Factory
{
    private $configuration;
    private $container;
    private $substitutions;

    public function __construct(Configuration $configuration, Container $container)
    {
        $this->configuration = $configuration;
        $this->container = $container;
    }

    /**
     * @param $name
     * @return CommandInterface
     */
    public function create($name, $properties = [])
    {
        $className = $this->getClassName($name);
        return $this->container->make($className, ['properties' => $properties]);
    }

    private function getClassName($name)
    {
        $class = $this->runSubstitutions($this->configuration->getNode("commands/{$name}/class"));
        if (class_exists($class) && in_array(CommandInterface::class, class_implements($class))) {
            return $class;
        } else {
            throw new \Exception("{$name} doesn't exist or it doesn't implement the type " . CommandInterface::class . ".");
        }
    }

    private function runSubstitutions($name)
    {
        $substitutions = $this->getSubstitutions();
        preg_match_all("/%(.+)%/U", $name, $matches);

        if (count($matches) > 1) {
            $replacements = array_reduce($matches[1], function($carry, $name) use ($substitutions) {
                $carry['%'.$name.'%'] = $substitutions[$name];
                return $carry;
            }, []);

            return str_replace(array_keys($replacements), array_values($replacements), $name);
        } else {
            return $name;
        }
    }

    private function getSubstitutions()
    {
        if (!$this->substitutions) {
            $databaseEngine = $this->configuration->getNode('connections/database');
            if (is_array($databaseEngine)) {
                $databaseEngine = 'mysql';
            }

            $substitutions = [
                'engine' => $this->configuration->getNode("engines/{$databaseEngine}/class-name")
            ];
            $this->substitutions = $substitutions;

        }

        return $this->substitutions;
    }
}