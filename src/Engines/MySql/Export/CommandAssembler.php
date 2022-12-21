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

    public function execute(
        ConnectionInterface $connection,
        EnvironmentInterface $environment,
        string $dumpFile
    ): string {
        $ignoredTables = $this->tablesProvider->getIgnoredTables($environment);
        $emptyTables = $this->tablesProvider->getEmptyTables($environment);
        foreach ($this->tablesProvider->getAllTables($connection) as $table) {
            if (in_array($table, $ignoredTables) || in_array($table, $emptyTables)) {
                continue;
            }
            $commands[] = $this->getSingleCommand($connection, [$table], $dumpFile);
        }
        if (!empty($emptyTables)) {
            $commands[] = $this->getSingleCommand($connection, $emptyTables, $dumpFile, false);
        }
        if (empty($commands)) {
            return '';
        }
        array_unshift(
            $commands,
            "echo '/*!40014 SET @ORG_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;'"
            . ">> $dumpFile"
        );
        $commands[] = "echo '/*!40014 SET FOREIGN_KEY_CHECKS=@ORG_FOREIGN_KEY_CHECKS */;' >> $dumpFile";
        $commands[] = "cat $dumpFile | "
            . "sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' | gzip > $dumpFile.gz";
        return implode(';', $commands);
    }

    /**
     * @param string[] $tables
     */
    private function getSingleCommand(
        ConnectionInterface $connection,
        array $tables,
        string $dumpFile,
        bool $withData = true
    ): string {
        $parts = [
            "mysqldump --user=\"{$connection->getUser()}\"",
            "--password=\"{$connection->getPassword()}\"",
            "--single-transaction",
            "--no-tablespaces",
            "--host={$connection->getHost()}",
            $connection->getDatabase(),
            implode(' ', $tables)
        ];
        if (!$withData) {
            $parts[] = '--no-data';
        }
        $parts[] = ">> $dumpFile";
        return implode(' ', $parts);
    }
}
