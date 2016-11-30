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
use Driver\Engines\MySql\Sandbox\Sandbox;
use Driver\Pipes\Transport\Status;
use Driver\Pipes\Transport\TransportInterface;
use Driver\System\Configuration;
use Symfony\Component\Console\Command\Command;

class Init extends Command implements CommandInterface
{
    private $configuration;
    private $sandbox;

    public function __construct(Configuration $configuration, Sandbox $sandbox)
    {
        $this->configuration = $configuration;
        $this->sandbox = $sandbox;

        return parent::__construct('mysql-sandbox-init');
    }

    public function go(TransportInterface $transport)
    {
        $this->sandbox->init();
        return $transport->withStatus(new Status('sandbox_init', 'success'));
    }
}