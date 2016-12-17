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

namespace Driver\Pipeline\Transport;

use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\System\Logs\LoggerInterface;

class Primary implements TransportInterface
{
    private $data;
    private $statuses;
    private $pipeline;
    private $logger;
    private $environment;

    public function __construct($pipeline, $statuses = [], $data = [], EnvironmentInterface $environment = null, LoggerInterface $logger = null)
    {
        $this->pipeline = $pipeline;
        $this->statuses = $statuses;
        $this->data = $data;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    public function getErrors()
    {
        return array_filter($this->statuses, function(Status $status) {
            return $status->isError();
        });
    }

    public function getErrorsByNode($node)
    {
        return array_filter($this->statuses, function(Status $status) use ($node) {
            return $status->isError() && $status->getNode() === $node;
        });
    }

    public function getPipeline()
    {
        return $this->pipeline;
    }

    public function withStatus(Status $status)
    {
        return new self($this->pipeline, array_merge($this->statuses, [ $status ]), $this->data, $this->environment, $this->logger);
    }

    public function getStatuses()
    {
        return $this->statuses;
    }

    public function getStatusesByNode($node)
    {
        return array_filter($this->statuses, function(Status $status) use ($node) {
            return $status->getNode() === $node;
        });
    }

    public function withNewData($key, $value)
    {
        return new self($this->pipeline, $this->statuses, array_merge($this->data, [$key => $value]), $this->environment, $this->logger);
    }

    public function getAllData()
    {
        return $this->data;
    }

    public function getData($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : false;
    }
}