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
 * @copyright SwiftOtter Studios, 12/17/16
 * @package default
 **/

namespace Driver\Engines\MySql;

use Driver\Commands\CommandInterface;
use Driver\Engines\MySql\Sandbox\Connection as SandboxConnection;
use Driver\Engines\MySql\Sandbox\Utilities;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;

class Transformation extends Command implements CommandInterface
{
    private $configuration;
    private $properties;
    private $sandbox;
    private $utilities;
    private $logger;

    public function __construct(Configuration $configuration, SandboxConnection $sandbox, Utilities $utilities, LoggerInterface $logger, array $properties = [])
    {
        $this->configuration = $configuration;
        $this->properties = $properties;
        $this->sandbox = $sandbox;
        $this->logger = $logger;

        parent::__construct('mysql-transformation');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $this->applyTransformationsTo($this->sandbox->getConnection(), $environment->getTransformations());

        return $transport->withStatus(new Status('mysql_transformation', 'success'));
    }

    public function getProperties()
    {
        return $this->properties;
    }

    protected function applyTransformationsTo(\PDO $connection, $transformations)
    {
        try {
            $connection->beginTransaction();

            array_walk($transformations, function ($query) use ($connection) {
                $connection->query($query);
            });

            $connection->commit();
        } catch (\Exception $ex) {
            $connection->rollBack();
        }
    }

}