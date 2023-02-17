<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Transformation;

use Driver\Commands\CommandInterface;
use Driver\Engines\RemoteConnectionInterface;
use Driver\Engines\MySql\Transformation\Anonymize\Seed;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Reduce extends Command implements CommandInterface
{
    private Configuration $configuration;
    private RemoteConnectionInterface $connection;
    private LoggerInterface $logger;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;
    private ConsoleOutput $output;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        Configuration $configuration,
        RemoteConnectionInterface $connection,
        LoggerInterface $logger,
        ConsoleOutput $output,
        array $properties = []
    ) {
        $this->configuration = $configuration;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->properties = $properties;
        $this->output = $output;

        parent::__construct('mysql-transformation-reduce');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        /** @var OutputInterface $output */
        $config = $this->configuration->getNode('reduce/tables');
        $transport->getLogger()->notice("Beginning reducing table rows from reduce.yaml.");
        $this->output->writeln("<comment>Beginning reducing table rows from reduce.yaml.</comment>");

        if (!is_array($config) || (isset($config['disabled']) &&  $config['disabled'] === true)) {
            return $transport->withStatus(new Status('mysql-transformation-reduce', 'skipped'));
        }

        foreach ($this->configuration->getNode('reduce/tables') as $tableName => $details) {
            $column = $details['column'];
            $statement = $details['statement'];

            $query = "DELETE FROM ${tableName} WHERE ${column} ${statement}";

            try {
                $this->connection->getConnection()->beginTransaction();
                $this->connection->getConnection()->query($query);
                $this->connection->getConnection()->commit();
            } catch (\Exception $ex) {
                $this->logger->error('An error occurred when running this query: ' . $query);
                $this->logger->error($ex->getMessage());
                $this->output->writeln(
                    '<error>An error occurred when running this query: ' . $query . $ex->getMessage() . '</error>'
                );
            }
        }

        $transport->getLogger()->notice("Row reduction process complete.");
        $this->output->writeln("<info>Row reduction process complete.</info>");

        return $transport->withStatus(new Status('mysql-transformation-reduce', 'success'));
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }
}
