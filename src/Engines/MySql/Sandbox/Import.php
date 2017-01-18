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
 * @copyright SwiftOtter Studios, 12/3/16
 * @package default
 **/

namespace Driver\Engines\MySql\Sandbox;

use Driver\Commands\CommandInterface;
use Driver\Engines\MySql\Connection as LocalConnection;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Driver\Engines\MySql\Sandbox\Connection as SandboxConnection;

class Import extends Command implements CommandInterface
{
    private $localConnection;
    private $sandboxConnection;
    private $ssl;
    private $properties;
    private $logger;

    public function __construct(LocalConnection $localConnection, Ssl $ssl, SandboxConnection $sandboxConnection, LoggerInterface $logger, array $properties = [])
    {
        $this->localConnection = $localConnection;
        $this->sandboxConnection = $sandboxConnection;
        $this->ssl = $ssl;
        $this->properties = $properties;
        $this->logger = $logger;

        return parent::__construct('mysql-sandbox-import');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $this->sandboxConnection->test(function(SandboxConnection $connection) {
            $connection->authorizeIp();
        });

        $this->logger->notice("Importing database into RDS");

        if ($results = system($this->assembleCommand($transport->getData('dump-file')))) {
            throw new \Exception('Import to RDS instance failed: ' . $results);
        } else {
            $this->logger->notice("Import to RDS completed.");
            return $transport->withStatus(new Status('sandbox_init', 'success'));
        }
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function assembleCommand($path)
    {
        $command = implode(' ', [
            "mysql --user={$this->sandboxConnection->getUser()}",
                "--password={$this->sandboxConnection->getPassword()}",
                "--host={$this->sandboxConnection->getHost()}",
                "--port={$this->sandboxConnection->getPort()}",
                "--ssl",
                "--ssl-ca={$this->ssl->getPath()}",
                "{$this->sandboxConnection->getDatabase()}",
            "<",
            $path
        ]);

        if (stripos($this->localConnection->getConnection()->getAttribute(\PDO::ATTR_SERVER_VERSION), 'maria') !== false) {
            $command = str_replace('--ssl-mode=VERIFY_CA', '--ssl', $command);
        }

        return $command;
    }
}
