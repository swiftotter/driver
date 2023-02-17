<?php

declare(strict_types=1);

namespace Driver\Engines\MySql;

use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\LocalConnectionLoader;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class Check extends Command implements CommandInterface
{
    private const DB_SIZE_KEY = 'database_size';

    private LocalConnectionLoader $configuration;
    private LoggerInterface $logger;
    private ConsoleOutput $output;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;
    private ?int $databaseSize;
    private ?int $freeSpace;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        LocalConnectionLoader $connection,
        LoggerInterface $logger,
        ConsoleOutput $output,
        array $properties = [],
        ?int $databaseSize = null,
        ?int $freeSpace = null
    ) {
        $this->configuration = $connection;
        $this->databaseSize = $databaseSize;
        $this->freeSpace = $freeSpace;
        $this->logger = $logger;
        $this->output = $output;
        $this->properties = $properties;

        return parent::__construct('mysql-system-check');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        /** @var OutputInterface $output */
        if ($this->getDatabaseSize() < $this->getDriveFreeSpace()) {
            $this->output->writeln(
                "<comment>Database size (" . $this->getDatabaseSize()
                    . " MB) is less than the free space available on the PHP drive.</comment>"
            );
            $this->logger->info("Database size is less than the free space available on the PHP drive.");
            return $transport->withNewData(self::DB_SIZE_KEY, $this->getDatabaseSize())
                ->withStatus(new Status('check', 'success'));
        } else {
            $this->output->writeln("<error>There is NOT enough free space to dump the database.</error>");
            throw new \Exception('There is NOT enough free space to dump the database.');
        }
    }

    private function getDriveFreeSpace(): int
    {
        if ($this->freeSpace) {
            return $this->freeSpace;
        } else {
            return (int)ceil(disk_free_space(getcwd()) / 1024 / 1024);
        }
    }

    private function getDatabaseSize(): int
    {
        if ($this->databaseSize) {
            return $this->databaseSize;
        } else {
            $connection = $this->configuration->getConnection();
            $statement = $connection->prepare(
                'SELECT ceiling(sum(data_length + index_length) / 1024 / 1024) as DB_SIZE '
                    . 'FROM information_schema.tables WHERE table_schema = :database_name GROUP BY table_schema;'
            );

            $statement->execute(['database_name' => $this->configuration->getDatabase() ]);
            return (int)$statement->fetch(\PDO::FETCH_ASSOC)["DB_SIZE"];
        }
    }
}
