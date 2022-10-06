<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Export;

use Driver\Commands\CleanupInterface;
use Driver\Commands\CommandInterface;
use Driver\Engines\LocalConnectionInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Driver\System\Random;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class Primary extends Command implements CommandInterface, CleanupInterface
{
    private const DEFAULT_DUMP_PATH = '/tmp';

    private LocalConnectionInterface $localConnection;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;
    private LoggerInterface $logger;
    private Random $random;
    private ?string $path = null;
    private Configuration $configuration;
    private ConsoleOutput $output;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        LocalConnectionInterface $localConnection,
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
        return parent::__construct('mysql-default-export');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        $transport->getLogger()->notice("Exporting database from local MySql");
        $this->output->writeln("<comment>Exporting database from local MySql</comment>");

        $transport->getLogger()->debug(
            "Local connection string: " . str_replace(
                $this->localConnection->getPassword(),
                '',
                $this->assembleCommand($environment)
            )
        );
        $this->output->writeln("<comment>Local connection string: </comment>" . str_replace(
            $this->localConnection->getPassword(),
            '',
            $this->assembleCommand($environment)
        ));

        $command = implode(';', array_filter([
            $this->assembleCommand($environment),
            $this->assembleEmptyCommand($environment)
        ]));

        $results = system($command);

        if ($results) {
            $this->output->writeln('<error>Import to RDS instance failed: ' . $results . '</error>');
            throw new \Exception('Import to RDS instance failed: ' . $results);
        } else {
            $this->logger->notice("Database dump has completed.");
            $this->output->writeln("<info>Database dump has completed.</info>");
            return $transport->withStatus(new Status('sandbox_init', 'success'))
                ->withNewData('dump-file', $this->getDumpFile());
        }
    }

    public function cleanup(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        if ($this->getDumpFile() && file_exists($this->getDumpFile())) {
            @unlink($this->getDumpFile());
        }
        return $transport;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function assembleEmptyCommand(EnvironmentInterface $environment): string
    {
        $tables = implode(' ', $environment->getEmptyTables());

        if (!$tables) {
            return '';
        }

        return implode(' ', array_merge(
            $this->getDumpCommand(),
            [
                "--no-data",
                $tables,
                "| sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g'",
                ">>",
                $this->getDumpFile()
            ]
        ));
    }

    public function assembleCommand(EnvironmentInterface $environment): string
    {
        return implode(' ', array_merge(
            $this->getDumpCommand(),
            [
                $this->assembleEmptyTables($environment),
                $this->assembleIgnoredTables($environment),
                "| sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g'",
                ">",
                $this->getDumpFile()
            ]
        ));
    }

    /**
     * @return string[]
     */
    private function getDumpCommand(): array
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

    private function assembleEmptyTables(EnvironmentInterface $environment): string
    {
        $tables = $environment->getEmptyTables();
        $output = [];

        foreach ($tables as $table) {
            $output[] = '--ignore-table=' . $this->localConnection->getDatabase() . '.' . $table;
        }

        return implode(' ', $output);
    }

    private function assembleIgnoredTables(EnvironmentInterface $environment): string
    {
        $tables = $environment->getIgnoredTables();
        $output = implode(' | ', array_map(function ($table) {
            return "awk '!/^INSERT INTO `{$table}` VALUES/'";
        }, $tables));

        return $output ? ' | ' . $output : '';
    }

    private function getDumpFile(): string
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
