<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Sandbox;

use Driver\Commands\CleanupInterface;
use Driver\Commands\CommandInterface;
use Driver\Engines\RemoteConnectionInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\Random;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

use function array_key_exists;

class Export extends Command implements CommandInterface, CleanupInterface
{
    private RemoteConnectionInterface $connection;
    private Ssl $ssl;
    private Random $random;
    /** @var array<string, string> */
    private array $filenames = [];
    private Configuration $configuration;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;
    private Utilities $utilities;
    private ConsoleOutput $output;
    /** @var string[] */
    private array $files = [];

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        RemoteConnectionInterface $connection,
        Ssl $ssl,
        Random $random,
        Configuration $configuration,
        Utilities $utilities,
        ConsoleOutput $output,
        array $properties = []
    ) {
        $this->connection = $connection;
        $this->ssl = $ssl;
        $this->random = $random;
        $this->configuration = $configuration;
        $this->properties = $properties;
        $this->utilities = $utilities;
        $this->output = $output;

        return parent::__construct('mysql-sandbox-export');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        $this->connection->test(function (Connection $connection): void {
            $connection->authorizeIp();
        });

        $transport->getLogger()->notice("Exporting database from remote MySql RDS");
        $this->output->writeln(
            "<comment>Exporting database from remote MySql RDS. Please wait... this will take a long time.</comment>"
        );

        $environmentName = $environment->getName();
        $command = $this->assembleCommand(
            $environmentName,
            $environment->getIgnoredTables(),
            $transport->getData('triggers-dump-file')
        );

        $this->files[] = $this->getFilename($environmentName);

        $results = system($command);

        if ($results) {
            $this->output->writeln('<error>Export from RDS instance failed: ' . $results . '</error>');
            throw new \Exception('Export from RDS instance failed: ' . $results);
        } else {
            return $transport
                ->withNewData('completed_file', $this->getFilename($environmentName))
                ->withNewData($environmentName . '_completed_file', $this->getFilename($environmentName))
                ->withStatus(new Status('sandbox_init', 'success'));
        }
    }

    public function cleanup(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        array_walk($this->files, function ($fileName): void {
            if ($fileName && file_exists($fileName)) {
                @unlink($fileName);
            }
        });
        return $transport;
    }

    /**
     * @param string[] $ignoredTables
     */
    private function assembleCommand(string $environmentName, array $ignoredTables, string $triggersDumpFile): string
    {
        $filename = $this->getFilename($environmentName);
        $command = implode(' ', array_merge([
            "mysqldump --user={$this->connection->getUser()}",
            "--password={$this->connection->getPassword()}",
            "--host={$this->connection->getHost()}",
            "--port={$this->connection->getPort()}",
            "--no-tablespaces"
        ], $this->getIgnoredTables($ignoredTables)));
        $command .= " {$this->connection->getDatabase()} ";
        $command .= "| sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' ";
        if ($this->compressOutput()) {
            $command .= "| gzip --best ";
        }
        $command .= "> $filename;";
        $command .= ($this->compressOutput() ? "cat" : "gunzip < ") . " $triggersDumpFile >> $filename;";

        return $command;
    }

    /**
     * @param string[] $ignoredTables
     * @return string[]
     */
    private function getIgnoredTables(array $ignoredTables): array
    {
        $tableNames = array_filter(array_map(function ($oldTableName) {
            return $this->utilities->tableName($oldTableName);
        }, $ignoredTables));

        return array_map(function ($tableName) {
            return "--ignore-table=" . $this->connection->getDatabase() . "." . $tableName;
        }, $tableNames);
    }

    private function compressOutput(): bool
    {
        return (bool)$this->configuration->getNode('configuration/compress-output') === true;
    }

    private function getFilename(string $environmentName): string
    {
        if (array_key_exists($environmentName, $this->filenames)) {
            return $this->filenames[$environmentName];
        }

        $path = $this->configuration->getNode('connections/rds/dump-path');
        if (!$path) {
            $path = '/tmp';
        }
        do {
            $file = $path . '/driver_tmp_' . $environmentName . '_' . $this->random->getRandomString(10)
                . ($this->compressOutput() ? '.gz' : '.sql');
        } while (file_exists($file));

        $this->filenames[$environmentName] = $file;
        return $this->filenames[$environmentName];
    }
}
