<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Export;

use Driver\Engines\ConnectionInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;

use function exec;
use function explode;

class TablesProvider
{
    /**
     * @return string[]
     */
    public function getAllTables(ConnectionInterface $connection): array
    {
        $command = "mysql --user=\"{$connection->getUser()}\" --password=\"{$connection->getPassword()}\" "
            . "--host=\"{$connection->getHost()}\" --skip-column-names "
            . "-e \"SELECT GROUP_CONCAT(table_name SEPARATOR ',') FROM information_schema.tables "
            . "WHERE table_schema = '{$connection->getDatabase()}';\"";
        $result = exec($command);
        if (!$result) {
            throw new \RuntimeException('Unable to get table names');
        }
        return explode(",", $result);
    }

    /**
     * @return string[]
     */
    public function getEmptyTables(EnvironmentInterface $environment): array
    {
        return $environment->getEmptyTables();
    }

    /**
     * @return string[]
     */
    public function getIgnoredTables(EnvironmentInterface $environment): array
    {
        return $environment->getIgnoredTables();
    }
}
