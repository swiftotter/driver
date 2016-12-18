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

namespace Driver\Pipeline\Environment;

use Driver\Engines\MySql\Sandbox\Utilities;
use Driver\Pipeline\Stage\Factory as StageFactory;
use Driver\System\YamlFormatter;

interface EnvironmentInterface
{
    public function __construct($name, array $properties, Utilities $utilities);

    /**
     * @return array
     */
    public function getFiles();

    /**
     * @param string $type
     * @param string $path
     * @return void
     */
    public function addFile($type, $path);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param $key
     * @return string
     */
    public function getData($key);

    /**
     * @return array
     */
    public function getAllData();

    /**
     * @return array
     */
    public function getTransformations();

    /**
     * @return int
     */
    public function getSort();

    /**
     * @return array
     */
    public function getIgnoredTables();

    /**
     * @param $tableName
     * @return void
     */
    public function addIgnoredTable($tableName);
}