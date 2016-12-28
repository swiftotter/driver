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
use Symfony\Component\Console\Command\Command;

class Shutdown extends Command implements CommandInterface, ErrorInterface
{
    private $configuration;
    private $sandbox;
    private $properties;

    public function __construct(Configuration $configuration, Sandbox $sandbox, array $properties = [])
    {
        $this->configuration = $configuration;
        $this->sandbox = $sandbox;
        $this->properties = $properties;

        return parent::__construct('mysql-sandbox-shutdown');
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        return $this->apply($transport, $environment);
    }

    public function error(TransportInterface $transport, EnvironmentInterface $environment)
    {
        return $this->apply($transport, $environment);
    }

    private function apply(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $this->sandbox->shutdown();
        return $transport->withStatus(new Status('sandbox_shutdown', 'success'));
    }
}