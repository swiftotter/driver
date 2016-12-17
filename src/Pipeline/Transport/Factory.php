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

namespace Driver\Pipeline\Transport;

use Driver\Pipeline\Transport;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Driver\Pipeline\Environment\Factory as EnvironmentFactory;

class Factory
{
    private $configuration;
    private $type;
    private $logger;
    private $environmentFactory;

    public function __construct(Configuration $configuration, $type, LoggerInterface $logger, EnvironmentFactory $environmentFactory)
    {
        $this->configuration = $configuration;
        $this->type = $type;
        $this->logger = $logger;
        $this->environmentFactory = $environmentFactory;
    }

    public function create($pipeline)
    {
        return new $this->type($pipeline, [], [], $this->environmentFactory->createDefault());
    }
}