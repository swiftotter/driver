<?php
/**
 * SwiftOtter_Base is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SwiftOtter_Base is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with SwiftOtter_Base. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Joseph Maxwell
 * @copyright SwiftOtter Studios, 11/19/16
 * @package default
 **/

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
    private array $properties = [];
    private ConsoleOutput $output;
    private DebugMode $debugMode;

    public function __construct(
        Configuration $configuration,
        Sandbox $sandbox,
        ConsoleOutput $output,
        DebugMode $debugMode,
        $properties = []
    ) {
        $this->configuration = $configuration;
        $this->sandbox = $sandbox;
        $this->properties = $properties;
        $this->output = $output;
        $this->debugMode = $debugMode;

        return parent::__construct('mysql-sandbox-init');
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        if ($this->debugMode->get()) {
            $transport->getLogger()->notice('No sandbox initialization due to debug mode enabled.');
            return $transport->withStatus(new Status('sandbox_init', 'success'));
        }

        $transport->getLogger()->notice('Initializing sandbox.');
        $this->output->writeln('<info>Initializing sandbox.</info>');

        try {
            pcntl_signal(SIGINT, [$this->sandbox, 'shutdown']);
        } catch (\Exception $exception) {
            $this->output->writeln('<error>Failed to shutdown RDS. Login to AWS and manually shutdown.</error>');
            throw new \Exception('Failed to shutdown RDS. Login to AWS and manually shutdown.');
        }

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

        while(!($active = $this->sandbox->getInstanceActive()) && $tries < $maxTries) {
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
}
