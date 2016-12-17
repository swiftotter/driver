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

    public function __construct($name, array $properties)
    {
        $this->properties = $properties;
        $this->name = $name;
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
}