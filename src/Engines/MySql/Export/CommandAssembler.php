<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Export;

use Driver\Engines\ConnectionInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;

use function array_unshift;
use function implode;
use function in_array;

class CommandAssembler
{
    private TablesProvider $tablesProvider;

    public function __construct(TablesProvider $tablesProvider)
    {
        $this->tablesProvider = $tablesProvider;
    }

    /**
     * @return string[]
     */
    public function execute(
        ConnectionInterface $connection,
        EnvironmentInterface $environment,
        string $dumpFile
    ): array {
        $ignoredTables = $this->tablesProvider->getIgnoredTables($environment);
        $emptyTables = $this->tablesProvider->getEmptyTables($environment);
        $commands = [$this->getStructureCommand($connection, $ignoredTables, $dumpFile)];
        foreach ($this->tablesProvider->getAllTables($connection) as $table) {
            if (in_array($table, $ignoredTables) || in_array($table, $emptyTables)) {
                continue;
            }
            $commands[] = $this->getDataCommand($connection, [$table], $dumpFile);
        }
        if (empty($commands)) {
            return [];
        }
        array_unshift(
            $commands,
            "echo '/*!40014 SET @ORG_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;'"
            . ">> $dumpFile"
        );
        $commands[] = "echo '/*!40014 SET FOREIGN_KEY_CHECKS=@ORG_FOREIGN_KEY_CHECKS */;' >> $dumpFile";
        $commands[] = "cat $dumpFile | "
            . "sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' | gzip > $dumpFile.gz";
        return $commands;
    }

    /**
     * @param string[] $ignoredTables
     */
    private function getStructureCommand(
        ConnectionInterface $connection,
        array $ignoredTables,
        string $dumpFile
    ): string {
        $parts = [
            "mysqldump --user=\"{$connection->getUser()}\"",
            "--password=\"{$connection->getPassword()}\"",
            "--single-transaction",
            "--no-tablespaces",
            "--no-data",
            "--host={$connection->getHost()}",
            $connection->getDatabase()
        ];
        foreach ($ignoredTables as $table) {
            $parts[] = "--ignore-table={$connection->getDatabase()}.{$table}";
        }
        $parts[] = ">> $dumpFile";
        return implode(' ', $parts);
    }

    /**
     * @param string[] $tables
     */
    private function getDataCommand(ConnectionInterface $connection, array $tables, string $dumpFile): string
    {
        $parts = [
            "mysqldump --user=\"{$connection->getUser()}\"",
            "--password=\"{$connection->getPassword()}\"",
            "--single-transaction",
            "--no-tablespaces",
            "--no-create-info",
            "--host={$connection->getHost()}",
            $connection->getDatabase(),
            implode(' ', $tables)
        ];
        $parts[] = ">> $dumpFile";
        return implode(' ', $parts);
    }
}
