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
    private array $properties = [];
    private ConsoleOutput $output;
    private DebugMode $debugMode;

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

    public function getProperties()
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        if ($this->debugMode->get()) {
            $transport->getLogger()->notice('No sandbox shutdown due to debug mode enabled.');
        }

        return $this->apply($transport, $environment);
    }

    public function error(TransportInterface $transport, EnvironmentInterface $environment)
    {
        return $this->apply($transport, $environment);
    }

    private function apply(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $transport->getLogger()->notice("Shutting down RDS");
        $this->output->writeln("<comment>Shutting down RDS</comment>");
        $this->sandbox->shutdown();
        return $transport->withStatus(new Status('sandbox_shutdown', 'success'));
    }
}
