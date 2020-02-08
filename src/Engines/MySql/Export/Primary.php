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

namespace Driver\Engines\MySql\Export;

use Driver\Commands\CleanupInterface;
use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\LocalConnectionLoader;
use Driver\System\Logs\LoggerInterface;
use Driver\System\Random;
use Symfony\Component\Console\Command\Command;

class Primary extends Command implements CommandInterface, CleanupInterface
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

    const DEFAULT_DUMP_PATH = '/tmp';

    public function __construct(
        LocalConnectionLoader $localConnection,
        Configuration $configuration,
        LoggerInterface $logger,
        Random $random,
        array $properties = []
    ) {
        $this->localConnection = $localConnection;
        $this->properties = $properties;
        $this->logger = $logger;
        $this->random = $random;
        $this->configuration = $configuration;

        return parent::__construct('mysql-default-export');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $transport->getLogger()->notice("Exporting database from local MySql");

        $transport->getLogger()->debug(
            "Local connection string: " . str_replace(
                $this->localConnection->getPassword(),
                '',
                $this->assembleCommand($environment)
            )
        );

        $results = null;
        $command = implode(';', array_filter([
            $this->assembleCommand($environment),
            $this->assembleEmptyCommand($environment)
        ]));

        $results = system($command);

        if ($results) {
            throw new \Exception('Import to RDS instance failed: ' . $results);
        } else {
            $this->logger->notice("Database dump has completed.");
            return $transport->withStatus(new Status('sandbox_init', 'success'))->withNewData('dump-file', $this->getDumpFile());
        }
    }

    public function cleanup(TransportInterface $transport, EnvironmentInterface $environment)
    {
        if ($this->getDumpFile() && file_exists($this->getDumpFile())) {
            @unlink($this->getDumpFile());
        }
    }


    public function getProperties()
    {
        return $this->properties;
    }

    public function assembleEmptyCommand(EnvironmentInterface $environment)
    {
        $tables = implode(' ', $environment->getEmptyTables());

        if (!$tables) {
            return '';
        }

        return implode(' ', array_merge(
            $this->getDumpCommand($environment),
            [
                "--no-data",
                $tables,
                "| sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/'",
                ">>",
                $this->getDumpFile()
            ]
        ));
    }

    public function assembleCommand(EnvironmentInterface $environment)
    {
        return implode(' ', array_merge(
            $this->getDumpCommand($environment),
            [
                $this->assembleEmptyTables($environment),
                $this->assembleIgnoredTables($environment),
                "| sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/'",
                ">",
                $this->getDumpFile()
            ]
        ));
    }

    private function getDumpCommand(EnvironmentInterface $environment)
    {
        return [
            "mysqldump --user=\"{$this->localConnection->getUser()}\"",
            "--password=\"{$this->localConnection->getPassword()}\"",
            "--single-transaction",
            "--compress",
            "--order-by-primary",
            "--host={$this->localConnection->getHost()}",
            "{$this->localConnection->getDatabase()}"
        ];
    }

    private function assembleEmptyTables(EnvironmentInterface $environment)
    {
        $tables = $environment->getEmptyTables();
        $output = [];

        foreach ($tables as $table) {
            $output[] = '--ignore-table=' . $this->localConnection->getDatabase() . '.' . $table;
        }

        return implode(' ', $output);
    }

    private function assembleIgnoredTables(EnvironmentInterface $environment)
    {
        $tables = $environment->getIgnoredTables();
        $output = implode(' | ', array_map(function($table) {
            return "awk '!/^INSERT INTO `{$table}` VALUES/'";
        }, $tables));

        return $output ? ' | ' . $output : '';
    }

    private function getDumpFile()
    {
        if (!$this->path) {
            $path = $this->configuration->getNode('connections/mysql/dump-path');
            if (!$path) {
                $path = self::DEFAULT_DUMP_PATH;
            }
            $filename = 'driver-' . $this->random->getRandomString(6) . '.sql';

            $this->path = $path . '/' . $filename;
        }

        return $this->path;
    }
}
