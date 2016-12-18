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
 * @copyright SwiftOtter Studios, 12/10/16
 * @package default
 **/

namespace Driver\Pipeline\Environment;

use Driver\Commands\Environment\Setup;
use Driver\System\Configuration;
use Haystack\HArray;

class Manager
{
    protected $runFor;
    protected $factory;
    protected $configuration;

    public function __construct(Factory $factory, Configuration $configuration)
    {
        $this->factory = $factory;
        $this->configuration = $configuration;
        $this->runFor = new \ArrayIterator([]);
    }

    public function setRunFor($environments)
    {
        if (strtolower($environments) === 'all' || !$environments) {
            $environmentList = $this->getAllEnvironments();
        }
        else if (is_string($environments)) {
            $environmentList = array_filter(explode(',', $environments));
            $environmentList = array_walk($environmentList, 'trim');
        } else {
            $environmentList = $environments;
        }

        $this->runFor = new \ArrayIterator($this->mapNamesToEnvironments($environmentList));
    }

    private function getAllEnvironments()
    {
        $output = (new HArray($this->configuration->getNode('environments')))
            ->filter(function($value) {
                return !isset($value['empty']) || $value['empty'] == false;
        })->toArray();

        return array_keys($output);
    }

    private function mapNamesToEnvironments(array $environments)
    {
        $mapped = (new HArray($environments))->map(function($name) {
            return $this->factory->create($name);
        })->toArray();

        usort($mapped, function(EnvironmentInterface $a, EnvironmentInterface $b) {
            if ($a->getSort() == $b->getSort()) {
                return 0;
            }

            return ($a->getSort() < $b->getSort()) ? -1 : 1;
        });

        return $mapped;
    }

    /**
     * @return \ArrayIterator
     */
    public function getRunFor()
    {
        return $this->runFor;
    }
}