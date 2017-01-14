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

use Driver\Commands\CleanupInterface;
use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\Random;
use Symfony\Component\Console\Command\Command;

class Export extends Command implements CommandInterface, CleanupInterface
{
    private $connection;
    private $ssl;
    private $random;
    private $filename;
    private $configuration;
    private $properties;
    private $utilities;

    private $files = [];

    public function __construct(Connection $connection, Ssl $ssl, Random $random, Configuration $configuration, Utilities $utilities, array $properties = [])
    {
        $this->connection = $connection;
        $this->ssl = $ssl;
        $this->random = $random;
        $this->configuration = $configuration;
        $this->properties = $properties;
        $this->utilities = $utilities;

        return parent::__construct('mysql-sandbox-export');
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $this->connection->test(function(Connection $connection) {
            $connection->authorizeIp();
        });

        $environmentName = $environment->getName();
        $command = $this->assembleCommand($environmentName, $environment->getIgnoredTables());

        $this->files[] = $this->getFilename($environmentName);

        if ($results = system($command)) {
            throw new \Exception('Import to RDS instance failed: ' . $results);
        } else {
            return $transport
                ->withNewData('completed_file', $this->getFilename($environmentName))
                ->withNewData($environmentName . '_completed_file', $this->getFilename($environmentName))
                ->withStatus(new Status('sandbox_init', 'success'));
        }
    }

    public function cleanup(TransportInterface $transport, EnvironmentInterface $environment)
    {
        array_walk($this->files, function($fileName) {
            if ($fileName && file_exists($fileName)) {
                @unlink($fileName);
            }
        });
    }

    private function assembleCommand($environmentName, $ignoredTables)
    {
        $command = implode(' ', array_merge([
            "mysqldump --user={$this->connection->getUser()}",
            "--password={$this->connection->getPassword()}",
            "--host={$this->connection->getHost()}",
            "--port={$this->connection->getPort()}"
        ], $this->getIgnoredTables($ignoredTables)));

        $command .= " {$this->connection->getDatabase()} ";

        if ($this->compressOutput()) {
            $command .= ' ' . implode(' ', [
                '|',
                'gzip --best'
            ]);
        }

        $command .= ' ' . implode(' ', [
            '>',
            $this->getFilename($environmentName)
        ]);

        return $command;
    }

    private function getIgnoredTables($ignoredTables)
    {
        if (!is_array($ignoredTables)) {
            $ignoredTables = [];
        }

        $tableNames = array_filter(array_map(function($oldTableName) {
            return $this->utilities->tableName($oldTableName);
        }, $ignoredTables));

        return array_map(function($tableName) {
            return "--ignore-table=".$this->connection->getDatabase().".".$tableName;
        }, $tableNames);
    }

    private function compressOutput()
    {
        return (bool)$this->configuration->getNode('configuration/compress-output') === true;
    }

    private function getFilename($environmentName)
    {
        if (!$this->filename) {
            do {
                $file = '/tmp/driver_tmp_' . $environmentName . '_' . $this->random->getRandomString(10) . ($this->compressOutput() ? '.gz' : '.sql');
            } while (file_exists($file));

            $this->filename = $file;
        }

        return $this->filename;
    }
}