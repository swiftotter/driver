<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Sandbox;

use Driver\Commands\CommandInterface;
use Driver\Commands\ErrorInterface;
use Driver\Engines\MySql\Sandbox\Sandbox;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\DebugMode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class Shutdown extends Command implements CommandInterface, ErrorInterface
{
    private Configuration $configuration;
    private Sandbox $sandbox;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;
    private ConsoleOutput $output;
    private DebugMode $debugMode;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        Configuration $configuration,
        Sandbox $sandbox,
        ConsoleOutput $output,
        DebugMode $debugMode,
        array $properties = []
    ) {
        $this->configuration = $configuration;
        $this->sandbox = $sandbox;
        $this->properties = $properties;
        $this->output = $output;
        $this->debugMode = $debugMode;

        return parent::__construct('mysql-sandbox-shutdown');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        if ($this->debugMode->get()) {
            $transport->getLogger()->notice('No sandbox shutdown due to debug mode enabled.');
            return $transport->withStatus(
                new Status('sandbox_shutdown', 'No sandbox shutdown due to debug mode enabled.')
            );
        }

        return $this->apply($transport, $environment);
    }

    public function error(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        return $this->apply($transport, $environment);
    }

    private function apply(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        $transport->getLogger()->notice("Shutting down RDS");
        $this->output->writeln("<comment>Shutting down RDS</comment>");
        $this->sandbox->shutdown();
        return $transport->withStatus(new Status('sandbox_shutdown', 'success'));
    }
}
