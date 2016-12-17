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

namespace Driver\Pipeline\Transport;

use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\System\Logs\LoggerInterface;

interface TransportInterface
{
    const STATUS_FAILED = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_PENDING = 3;

    public function __construct($pipeline, $statuses = [], $data = [], EnvironmentInterface $environment, LoggerInterface $log = null);

    /**
     * @return array
     */
    public function getPipeline();

    /**
     * @return array
     */
    public function getErrors();

    /**
     * @param $node
     * @return array
     */
    public function getErrorsByNode($node);

    /**
     * @return array
     */
    public function getStatuses();

    /**
     * @param $node
     * @return array
     */
    public function getStatusesByNode($node);

    /**
     * @return array
     */
    public function getAllData();

    /**
     * @param Status $status
     * @return self
     */
    public function withStatus(Status $status);

    /**
     * @param string $key
     * @return mixed
     */
    public function getData($key);

    /**
     * @param string $key
     * @param string $value
     * @return self
     */
    public function withNewData($key, $value);
}