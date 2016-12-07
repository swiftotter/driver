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
 * @copyright SwiftOtter Studios, 10/29/16
 * @package default
 **/

namespace Driver\Pipeline\Stage;

use Driver\Commands\Factory as CommandFactory;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\YamlFormatter;

class Primary implements StageInterface
{
    const PIPE_SET_NODE = 'parent';

    private $actions;
    private $commandFactory;

    public function __construct(array $actions, CommandFactory $commandFactory, YamlFormatter $yamlFormatter)
    {
        $this->commandFactory = $commandFactory;
        $this->actions = $yamlFormatter->extractToAssociativeArray($actions);
    }

    public function __invoke(\Driver\Pipeline\Transport\TransportInterface $transport, $testMode = false)
    {
        if ($testMode) {
            $this->actions = [];
        }

        $transport = array_reduce($this->actions, function(TransportInterface $transport, $name) {
            $command = $this->commandFactory->create($name);
            return $this->verifyTransport($command->go($transport), $name);
        }, $transport);

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'complete'));
    }

    private function verifyTransport(\Driver\Pipeline\Transport\TransportInterface $transport, $lastCommand)
    {
        if (!$transport) {
            throw new \Exception('No Transport object was returned from the last command executed: ' . $lastCommand);
        }

        return $transport;
    }

    private function formatList(array $list)
    {
        $output = array_reduce($list, function($commands, array $item) {
            array_walk($item, function($name, $id) use (&$commands) {
                while (isset($commands[$id])) {
                    $id++;
                }
                $commands[$id] = $name;
            });

            return $commands;
        }, []);

        ksort($output);

        return $output;
    }
}