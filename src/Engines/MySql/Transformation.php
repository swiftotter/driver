<?php

declare(strict_types=1);

namespace Driver\Engines\MySql;

use Driver\Commands\CommandInterface;
use Driver\Engines\MySql\Sandbox\Utilities;
use Driver\Engines\ReconnectingPDO;
use Driver\Engines\RemoteConnectionInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class Transformation extends Command implements CommandInterface
{
    private Configuration $configuration;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;
    private RemoteConnectionInterface $connection;
    private LoggerInterface $logger;
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
        $this->properties = $properties;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->output = $output;

        parent::__construct('mysql-transformation');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        if (
            count($environment->getOnlyForPipeline())
            && !in_array($transport->getPipeline(), $environment->getOnlyForPipeline())
        ) {
            return $transport->withStatus(new Status('mysql-transformation', 'stale'));
        }

        $this->applyTransformationsTo($this->connection->getConnection(), $environment->getTransformations());
        return $transport->withStatus(new Status('mysql-transformation', 'success'));
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param string[] $transformations
     */
    private function applyTransformationsTo(ReconnectingPDO $connection, array $transformations): void
    {
        array_walk($transformations, function ($query) use ($connection): void {
            try {
                $this->logger->info("Attempting: " . $query);
                $this->output->writeln("<comment> Attempting: " . $query . '</comment>');
                $connection->query($query);
                $this->logger->info("Successfully executed: " . $query);
                $this->output->writeln("<info>Successfully executed: " . $query . '</info>');
            } catch (\Exception $ex) {
                $this->logger->error("Query transformation failed: " . $query, [
                    $ex->getMessage(),
                    $ex->getTraceAsString()
                ]);
                $this->output->writeln("<error>Query transformation failed: " . $query, [
                    $ex->getMessage(),
                    $ex->getTraceAsString()
                ] . '</error>');
            }
        });
    }
}
