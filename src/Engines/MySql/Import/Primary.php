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

use Driver\Commands\CommandInterface;
use Driver\Engines\S3\Download;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\LocalConnectionLoader;
use Driver\System\Logs\LoggerInterface;
use PDO;
use PDOException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class Primary extends Command implements CommandInterface
{
    /** @var LocalConnectionLoader */
    private $localConnection;

    /** @var array */
    private $properties;

    /** @var LoggerInterface */
    private $logger;

    /** @var Configuration */
    private $configuration;

    /** @var ConsoleOutput */
    private $output;

    public function __construct(
        LocalConnectionLoader $localConnection,
        Configuration $configuration,
        LoggerInterface $logger,
        ConsoleOutput $output,
        array $properties = []
    ) {
        $this->localConnection = $localConnection;
        $this->properties = $properties;
        $this->logger = $logger;
        $this->configuration = $configuration;
        $this->output = $output;
        return parent::__construct('import-data-from-system-primary');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $transport->getLogger()->notice("Import database from var/ into local MySQL started");
        $this->output->writeln("<comment>Import database from var/ into local MySQL started</comment>");
        $this->output->writeln("<comment>Preparing MySQL Connection using Magento (app/etc/env.php)</comment>");

        try {
            $conn = new PDO(
                "mysql:host={$this->localConnection->getHost()}",
                $this->localConnection->getUser(),
                $this->localConnection->getPassword()
            );
        } catch (PDOException $e) {
            $this->output->writeln('<error>Could not connect: ' . $e->getMessage() . '</error>');
            $this->output->write([
                '<info> Connected with:',
                'Host:' . $this->localConnection->getHost(),
                'User:' . $this->localConnection->getUser(),
                'Password:' . preg_replace('/.*/i', '*', $this->localConnection->getPassword()),
                '</info>'
            ]);
            throw new \Exception('Could not connect: ' . $e->getMessage());
        }

        $this->output->writeln("<comment>Creating Local Database: </comment>" .
            $this->getDatabaseCommand($environment)
        );

        if (!$conn->query($this->getDatabaseCommand($environment))) {
            $this->output->writeln('<error>Database cannot be created: ' . $conn->errorInfo()[2] . '</error>');
        }

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
            $this->output->writeln(
                '<info>Rows were inserted/updated back into ' . implode(', ', array_keys($preserved)) . '.'
            );
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

        $preserve = $this->localConnection->getPreserve();

        // I hate this cyclomatic complexity, but it's the most reasonable solution for this depth of configuration.
        foreach ($preserve as $tableName => $columns) {
            foreach ($columns as $columnName => $selectData) {
                foreach ($selectData as $like) {
                    $tableColumnNames = $this->getColumns($tableName);
                    $columnNames = $this->flattenColumns($tableColumnNames);

                    if (!count($tableColumnNames)) {
                        continue;
                    }

                    try {
                        $statement = $connection->prepare("SELECT ${columnNames} FROM ${tableName} "
                            . "WHERE ${columnName} LIKE :like");
                        if ($statement === false) {
                            continue;
                        }

                        $statement->execute(['like' => $like]);
                        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                            $output[$tableName][] = $row;
                        }
                    } catch (\Exception $ex) {
                        continue;
                    }
                }
            }
        }

        return $output;
    }

    private function getColumns($tableName): array
    {
        $connection = $this->getConnection();
        $columns = [];

        $statement = $connection->prepare("SHOW COLUMNS FROM ${tableName}");
        if (!$statement->execute()) {
            return [];
        }

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['Extra'])
                && $row['Extra'] === 'auto_increment') {
                continue;
            }

            $columns[] = $row['Field'];
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

                $tableColumnNames = $this->getColumns($tableName);
                $columnNames = $this->flattenColumns($tableColumnNames);
                $columnFillers = implode(', ', array_fill(0, count($row), '?'));
                $valuesList = implode(', ', array_map(function($key) {
                    return "`${key}` = VALUES(`${key}`)";
                }, array_keys($row)));

                $statement = $connection->prepare("INSERT INTO ${tableName} (${columnNames}) VALUES(${columnFillers})"
                    ." ON DUPLICATE KEY UPDATE ${valuesList}");
                $statement->execute(array_values($row));
            }
        }
    }

    private function getConnection(): PDO
    {
        return new PDO(
            "mysql:host={$this->localConnection->getHost()};dbname={$this->localConnection->getDatabase()}",
            $this->localConnection->getUser(),
            $this->localConnection->getPassword()
        );
    }
}
