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

use Driver\Engines\MySql\Sandbox\Utilities;
use Driver\Pipeline\Stage\Factory as StageFactory;
use Driver\Pipeline\Transport\Status;
use Driver\System\YamlFormatter;
use Haystack\HArray;
use Icicle\Concurrent\Worker\Environment;

class Primary implements EnvironmentInterface
{
    private $name;
    private $properties;
    private $files;
    private $ignoredTables;

    /** @var Utilities $utilities */
    private $utilities;

    public function __construct($name, array $properties, Utilities $utilities)
    {
        $this->properties = $properties;
        $this->name = $name;
        $this->utilities = $utilities;
    }

    public function addFile($type, $path)
    {
        $this->files[$type] = $path;
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getData($key)
    {
        return $this->properties[$key];
    }

    public function getAllData()
    {
        return $this->properties;
    }

    public function addIgnoredTable($tableName)
    {
        $this->ignoredTables[] = $tableName;
    }

    public function getIgnoredTables()
    {
        if (!$this->ignoredTables && isset($this->properties['ignored_tables'])) {
            $this->ignoredTables = $this->properties['ignored_tables'];
        }
        return $this->ignoredTables;
    }

    public function getSort()
    {
        if (isset($this->properties['sort'])) {
            return (int)$this->properties['sort'];
        } else {
            return 1000;
        }
    }

    public function getTransformations()
    {
        if (isset($this->properties['transformations'])) {
            return $this->flattenTransformations($this->properties['transformations']);
        } else {
            return [];
        }
    }

    private function flattenTransformations($input)
    {
        $output = [];
        array_walk($input, function($transformations, $tableName) use (&$output) {
            $output = array_merge($output, $this->parseVariables($this->utilities->tableName($tableName), $transformations));
        });

        return $output;
    }

    protected function parseVariables($tableName, array $input)
    {
        return array_map(function($query) use ($tableName) {
            return str_replace("{{table_name}}", $tableName, $query);
        }, $input);
    }
}