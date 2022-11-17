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
use Exception;
use RuntimeException;
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
    private CommandAssembler $commandAssembler;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        LocalConnectionInterface $localConnection,
        Configuration $configuration,
        LoggerInterface $logger,
        Random $random,
        ConsoleOutput $output,
        CommandAssembler $commandAssembler,
        array $properties = []
    ) {
        $this->localConnection = $localConnection;
        $this->properties = $properties;
        $this->logger = $logger;
        $this->random = $random;
        $this->configuration = $configuration;
        $this->output = $output;
        $this->commandAssembler = $commandAssembler;
        return parent::__construct('mysql-default-export');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        $transport->getLogger()->notice("Exporting database from local MySql");
        $this->output->writeln("<comment>Exporting database from local MySql</comment>");

        try {
            $command = $this->commandAssembler->execute($this->localConnection, $environment, $this->getDumpFile());
            if (empty($command)) {
                throw new RuntimeException('Nothing to import');
            }

            $transport->getLogger()->debug(
                "Local connection string: " . str_replace(
                    $this->localConnection->getPassword(),
                    '',
                    $command
                )
            );
            $this->output->writeln("<comment>Local connection string: </comment>" . str_replace(
                $this->localConnection->getPassword(),
                '',
                $command
            ));

            $results = system($command);

            if ($results) {
                throw new RuntimeException($results);
            }
        } catch (Exception $e) {
            $this->output->writeln('<error>Import to RDS instance failed: ' . $e->getMessage() . '</error>');
            throw new Exception('Import to RDS instance failed: ' . $e->getMessage());
        }

        $this->logger->notice("Database dump has completed.");
        $this->output->writeln("<info>Database dump has completed.</info>");
        return $transport->withStatus(new Status('sandbox_init', 'success'))
            ->withNewData('dump-file', $this->getDumpFile());
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
