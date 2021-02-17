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

namespace Driver\Engines\MySql;

use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\LocalConnectionLoader;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class Check extends Command implements CommandInterface
{
    /** @var LocalConnectionLoader */
    private $configuration;

    /** @var int */
    private $databaseSize;

    /** @var int */
    private $freeSpace;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $properties;

    /** @var ConsoleOutput */
    private $output;

    const DB_SIZE_KEY = 'database_size';

    public function __construct(
        LocalConnectionLoader $connection,
        LoggerInterface $logger,
        ConsoleOutput $output,
        array $properties = [],
        $databaseSize = null,
        $freeSpace = null
    ) {
        $this->configuration = $connection;
        $this->databaseSize = $databaseSize;
        $this->freeSpace = $freeSpace;
        $this->logger = $logger;
        $this->output = $output;
        $this->properties = $properties;

        return parent::__construct('mysql-system-check');
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        /** @var OutputInterface $output */
        if ($this->getDatabaseSize() < $this->getDriveFreeSpace()) {
            $this->output->writeln("<comment>Database size (" . $this->getDatabaseSize() . " MB) is less than the free space available on the PHP drive.</comment>");
            $this->logger->info("Database size is less than the free space available on the PHP drive.");
            return $transport->withNewData(self::DB_SIZE_KEY, $this->getDatabaseSize())->withStatus(new Status('check', 'success'));
        } else {
            $this->output->writeln("<error>There is NOT enough free space to dump the database.</error>");
            throw new \Exception('There is NOT enough free space to dump the database.');
        }
    }

    private function getDriveFreeSpace()
    {
        if ($this->freeSpace) {
            return $this->freeSpace;
        } else {
            return ceil(disk_free_space(getcwd()) / 1024 / 1024);
        }
    }

    private function getDatabaseSize()
    {
        if ($this->databaseSize) {
            return $this->databaseSize;
        } else {
            $connection = $this->configuration->getConnection();
            $statement = $connection->prepare(
                'SELECT ceiling(sum(data_length + index_length) / 1024 / 1024) as DB_SIZE FROM information_schema.tables WHERE table_schema = :database_name GROUP BY table_schema;'
            );

            $statement->execute(['database_name' => $this->configuration->getDatabase() ]);
            return $statement->fetch(\PDO::FETCH_ASSOC)["DB_SIZE"];
        }
    }
}
