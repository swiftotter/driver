<?php

declare(strict_types=1);

namespace Driver\Engines;

use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\LocalConnectionLoader;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class MySql extends Command implements CommandInterface
{
    private LocalConnectionLoader $connection;
    private LoggerInterface $logger;
    private ConsoleOutput $output;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        LocalConnectionLoader $connection,
        LoggerInterface $logger,
        ConsoleOutput $output,
        array $properties = []
    ) {
        $this->logger = $logger;
        $this->properties = $properties;
        $this->connection = $connection;
        $this->output = $output;
        parent::__construct();
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        $value = $this->connection->getConnection()->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
        $this->output->writeln('<info>Successfully connected: ' . $value . '</info>');
        $this->logger->notice('Successfully connected: ' . $value);
        return $transport->withStatus(new Status('connection', 'success'));
    }
}
