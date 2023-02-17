<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Sandbox;

use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\DebugMode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class Init extends Command implements CommandInterface
{
    private Configuration $configuration;
    private Sandbox $sandbox;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties = [];
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

        return parent::__construct('mysql-sandbox-init');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        if ($this->debugMode->get()) {
            $transport->getLogger()->notice('No sandbox initialization due to debug mode enabled.');
            return $transport->withStatus(new Status('sandbox_init', 'success'));
        }

        $transport->getLogger()->notice('Initializing sandbox.');
        $this->output->writeln('<info>Initializing sandbox.</info>');
        pcntl_signal(SIGTERM, [$this, "signalHandler"]);
        pcntl_signal(SIGINT, [$this, "signalHandler"]);
        $this->output->writeln('<info>You can exit this with Ctrl + C.</info>');

        $this->sandbox->init();

        $this->output->writeln('');

        $tries = 0;
        $maxTries = 100;
        ProgressBar::setFormatDefinition(
            'minimal',
            '<info>Checking if sandbox is active... </info><fg=white;bg=blue>Progress : %percent%%</>'
        );
        $progressBar = new ProgressBar($this->output, $maxTries);
        $progressBar->setFormat('minimal');

        while (!($active = $this->sandbox->getInstanceActive()) && $tries < $maxTries) {
            $transport->getLogger()->notice('Checking if sandbox is active.');
            $tries++;
            sleep(10);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->output->writeln('');
        $this->output->writeln('<info>Completed!</info>');

        if (!$active && $tries === $maxTries) {
            $this->output->writeln('<error>RDS instance was not able to be started.</error>');
            throw new \Exception('RDS instance was not able to be started.');
        }

        $transport->getLogger()->notice('Sandbox initialized.');
        $this->output->writeln('<comment>Sandbox initialized.</comment>');

        return $transport->withStatus(new Status('sandbox_init', 'success'));
    }

    public function signalHandler(int $signal): void
    {
        if ($signal !== SIGINT && $signal !== SIGKILL) {
            return;
        }
        $this->output->write('<info>Cancelling! Shutting down RDS instance...</info>');
        $result = $this->sandbox->shutdown();

        if ($result) {
            $this->output->write('Successfully shut down RDS instance.');
        } else {
            $this->output->write('Failed to shut down RDS instance. Please login to AWS and kill the instance.');
        }
    }
}
