<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Sandbox;

use Driver\Commands\CommandInterface;
use Driver\Engines\RemoteConnectionInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\LocalConnectionLoader;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class Import extends Command implements CommandInterface
{
    private LocalConnectionLoader $localConnection;
    private RemoteConnectionInterface $remoteConnection;
    private Ssl $ssl;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;
    private LoggerInterface $logger;
    private ConsoleOutput $output;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        LocalConnectionLoader $localConnection,
        Ssl $ssl,
        RemoteConnectionInterface $connection,
        LoggerInterface $logger,
        ConsoleOutput $output,
        array $properties = []
    ) {
        $this->localConnection = $localConnection;
        $this->remoteConnection = $connection;
        $this->ssl = $ssl;
        $this->properties = $properties;
        $this->logger = $logger;
        $this->output = $output;
        return parent::__construct('mysql-sandbox-import');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        $this->remoteConnection->test(function (RemoteConnectionInterface $connection): void {
            $connection->authorizeIp();
        });

        $this->output->writeln(
            "<comment>Importing database into RDS. Please wait... This will take a long time.</comment>"
        );
        $this->logger->notice("Importing database into RDS");
        $resultCode = 0;
        system($this->assembleCommand($transport->getData('dump-file')), $resultCode);

        if ($resultCode !== 0) {
            $this->output->writeln('<error>Import to RDS instance failed.</error>');
            throw new \Exception('Import to RDS instance failed.');
        } else {
            $this->logger->notice("Import to RDS completed.");
            $this->output->writeln('<info>Import to RDS completed.</info>');
            return $transport->withStatus(new Status('sandbox_init', 'success'));
        }
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function assembleCommand(string $path): string
    {
        $command = implode(' ', [
            "mysql --user={$this->remoteConnection->getUser()}",
                "--password={$this->remoteConnection->getPassword()}",
                "--host={$this->remoteConnection->getHost()}",
                "--port={$this->remoteConnection->getPort()}",
                $this->remoteConnection->useSsl() ? "--ssl-ca={$this->ssl->getPath()}" : "",
                "{$this->remoteConnection->getDatabase()}",
            "<",
            $path
        ]);

        if (
            stripos(
                $this->localConnection->getConnection()->getAttribute(\PDO::ATTR_SERVER_VERSION),
                'maria'
            ) !== false
        ) {
            $command = str_replace('--ssl-mode=VERIFY_CA', '--ssl', $command);
        }

        return $command;
    }
}
