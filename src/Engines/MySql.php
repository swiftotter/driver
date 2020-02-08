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
use Driver\System\LocalConnectionLoader;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;

class MySql extends Command implements CommandInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $properties;

    /** @var LocalConnectionLoader */
    private $connection;

    public function __construct(LocalConnectionLoader $connection, LoggerInterface $logger, array $properties = [])
    {
        $this->logger = $logger;
        $this->properties = $properties;
        $this->connection = $connection;

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
        $value = $this->connection->getConnection()->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
        $this->logger->notice('Successfully connected: ' . $value);
        return $transport->withStatus(new Status('connection', 'success'));
    }
}