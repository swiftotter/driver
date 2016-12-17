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
 * @copyright SwiftOtter Studios, 11/19/16
 * @package default
 **/

namespace Driver\Engines;

use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\Engines\MySql\Connection;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;

class MySql extends Command implements CommandInterface
{
    private $configuration;
    private $logger;
    private $properties;

    public function __construct(Connection $configuration, LoggerInterface $logger, array $properties = [])
    {
        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->properties = $properties;

        parent::__construct(null);
    }

    public function getProperties()
    {
        return $this->properties;
    }

    protected function configure()
    {
        $this->setName('mysql-connect')
            ->setDescription('Connects to MySQL.');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $value = $this->configuration->getConnection()->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
        $this->logger->notice('Successfully connected.');
        return $transport->withStatus(new Status('connection', 'success'));
    }
}