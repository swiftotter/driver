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

namespace Driver\Engines\MySql\Import;

use Driver\Commands\CleanupInterface;
use Driver\Commands\CommandInterface;
use Driver\Engines\S3\Download;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\LocalConnectionLoader;
use Driver\System\Logs\LoggerInterface;
use Driver\System\Random;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Primary extends Command implements CommandInterface
{
    /** @var LocalConnectionLoader */
    private $localConnection;

    /** @var array */
    private $properties;

    /** @var LoggerInterface */
    private $logger;

    /** @var Random */
    private $random;

    /** @var ?string */
    private $path;

    /** @var Configuration */
    private $configuration;

    /** @var ConsoleOutput */
    private $output;

    private $preserved;

    const DEFAULT_DUMP_PATH = '/tmp';

    public function __construct(
        LocalConnectionLoader $localConnection,
        Configuration $configuration,
        LoggerInterface $logger,
        Random $random,
        ConsoleOutput $output,
        array $properties = []
    ) {
        $this->localConnection = $localConnection;
        $this->properties = $properties;
        $this->logger = $logger;
        $this->random = $random;
        $this->configuration = $configuration;
        $this->output = $output;
        return parent::__construct('import-data-from-system-primary');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $transport->getLogger()->notice("Import database from var/ into local MySql started");
        $this->output->writeln("<comment>Import database from var/ into local MySql started</comment>");
        $this->output->writeln("<comment>Initialized MySql Connection: </comment>");

        $conn = mysqli_connect($this->localConnection->getHost(), $this->localConnection->getUser(), $this->localConnection->getPassword());
        if (!$conn) {
            $this->output->writeln('<error>Could not connect: ' . mysqli_connect_error() . '</error>');
            throw new \Exception('Could not connect: ' . mysqli_connect_error());
        }

        $this->output->writeln("<comment>Creating Local Database: </comment>" .
            $this->getDatabaseCommand($environment)
        );

        mysqli_query($conn, $this->getDatabaseCommand($environment));
        if ($conn->error !== "" && (strpos($conn->error, "database exists") === false)) {
            $this->output->writeln('<error>Database cannot be created: ' . $conn->error . '</error>');
        }

        mysqli_close($conn);

        $transport->getLogger()->debug(
            "Local connection string: " . str_replace(
                $this->localConnection->getPassword(),
                '',
                $this->assembleCommand($transport->getData(Download::DOWNLOAD_PATH_KEY))
            )
        );
        $this->output->writeln("<comment>Local connection string: </comment>" . str_replace(
                $this->localConnection->getPassword(),
                '',
                $this->assembleCommand(Download::DOWNLOAD_PATH_KEY)
            )
        );

        $preserved = $this->preserve();

        $command = $this->assembleCommand($transport->getData(Download::DOWNLOAD_PATH_KEY));
        $results = system($command);
        if ($results) {
            $this->output->writeln('<error>Import to local MYSQL failed: ' . $results . '</error>');
            throw new \Exception('Import to local MYSQL failed: ' . $results);
        } else {
            $this->logger->notice("Import to local MYSQL completed.");
            $this->output->writeln('<info>Import to local MYSQL completed.</info>');

            $this->restore($preserved);
            $this->output->writeln('<info>Rows were inserted/updated back into ' . implode(', ', array_keys($preserved)) . '.');
            return $transport->withStatus(new Status('db_import', 'success'));
        }

    }

    public function getDatabaseCommand(EnvironmentInterface $environment)
    {
        return "CREATE DATABASE {$this->localConnection->getDatabase()}";
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function assembleCommand(string $filename)
    {
        return implode(' ', $this->getImportCommand($filename));
    }

    private function getImportCommand(string $filename)
    {
        $date = date('Y-m-d');
        return [
            "mysql -u \"{$this->localConnection->getUser()}\"",
            "-h {$this->localConnection->getHost()}",
            "--password=\"{$this->localConnection->getPassword()}\"",
            "{$this->localConnection->getDatabase()}",
            "<",
            $filename
        ];
    }

    private function preserve(): array
    {
        $connection = $this->getConnection();

        $output = [];

        try {
            $preserve = $this->localConnection->getPreserve();

            // I hate this cyclomatic complexity, but it's the most reasonable solution for this depth of configuration.
            foreach ($preserve as $tableName => $columns) {
                foreach ($columns as $columnName => $selectData) {
                    foreach ($selectData as $like) {
                        $preparedTableName = mysqli_real_escape_string($connection, $tableName);
                        $preparedColumnName = mysqli_real_escape_string($connection, $columnName);
                        $tableColumnNames = $this->getColumns($tableName);
                        $columnNames = $this->flattenColumns($tableColumnNames);

                        if (!count($tableColumnNames)) {
                            continue;
                        }

                        try {
                            $stmt = $connection->prepare("SELECT ${columnNames} FROM ${preparedTableName} WHERE ${preparedColumnName} LIKE ?");
                            if ($stmt === false) {
                                continue;
                            }

                            $stmt->bind_param("s", $like);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                                $output[$tableName][] = $row;
                            }
                        } catch (\Exception $ex) {
                            continue;
                        }
                    }
                }
            }
        } finally {
            $connection->close();
        }

        return $output;
    }

    private function getColumns($tableName): array
    {
        $connection = $this->getConnection();
        $columns = [];

        try {
            $result = $connection->query("SHOW COLUMNS FROM ${tableName};");
            if (!$result) {
                return [];
            }

            while ($row = $result->fetch_assoc()) {
                if (isset($row['Extra'])
                    && $row['Extra'] === 'auto_increment') {
                    continue;
                }

                $columns[] = $row['Field'];
            }
        } finally {
            $connection->close();
        }

        return $columns;
    }

    private function flattenColumns(array $columnNames): string
    {
        return implode(', ', $columnNames);
    }

    private function restore(array $values): void
    {
        foreach ($values as $tableName => $rows) {
            foreach ($rows as $row) {
                $connection = $this->getConnection();

                $preparedTableName = mysqli_real_escape_string($connection, $tableName);
                $tableColumnNames = $this->getColumns($tableName);
                $columnNames = $this->flattenColumns($tableColumnNames);
                $columnFillers = implode(', ', array_fill(0, count($row), '?'));
                $valuesList = implode(', ', array_map(function($key) {
                    return "`${key}` = VALUES(`${key}`)";
                }, array_keys($row)));

                $stmt = $connection->prepare("INSERT INTO ${preparedTableName} (${columnNames}) VALUES(${columnFillers}) ON DUPLICATE KEY UPDATE ${valuesList}");
                $stmt->bind_param(implode('', array_fill(0, count($row), 's')), ...array_values($row));
                $stmt->execute();
            }
        }
    }

    private function getConnection()
    {
        return mysqli_connect(
            $this->localConnection->getHost(),
            $this->localConnection->getUser(),
            $this->localConnection->getPassword(),
            $this->localConnection->getDatabase()
        );
    }
}
