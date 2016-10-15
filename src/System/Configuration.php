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
use Driver\System\Configuration\YamlLoader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    /** @var YamlLoader $loader */
    protected $loader;

    protected $nodes = [];

    protected $files = [];

    public function __construct(YamlLoader $loader)
    {
        $this->loader = $loader;
    }

    public function getNodes()
    {
        return $this->nodes;
    }

    public function getNode($node)
    {
        $path = explode('/', $node);
        $nodes = $this->nodes;

        return array_reduce($path, function($nodes, $item) {
            if (isset($nodes[$item])) {
                return $nodes[$item];
            } else {
                return [];
            }
        }, $nodes);
    }

    protected function loadConfigurationFor($file)
    {
        if (!isset($this->files[$file])) {
            try {
                $contents = Yaml::parse($this->loader->load($file));
                $this->files[$file] = $contents;
                $this->nodes = array_merge_recursive($this->nodes, $contents);
            } catch (ParseException $e) {
                $this->files[$file] = [];
            }
        }

        return $this->files[$file];
    }

    protected function loadAllConfiguration()
    {

    }
}