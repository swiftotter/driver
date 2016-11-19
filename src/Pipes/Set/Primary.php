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

namespace Driver\Pipes\Set;

use Driver\Commands\Factory as CommandFactory;
use Driver\Pipes\Transport\Status;

class Primary implements SetInterface
{
    const PIPE_SET_NODE = 'parent';

    private $list;
    private $commandFactory;

    public function __construct(array $list, CommandFactory $commandFactory)
    {
        $this->commandFactory = $commandFactory;
        $this->list = $this->formatList($list);
    }

    public function __invoke(\Driver\Pipes\Transport\TransportInterface $transport, $testMode = false)
    {
        if ($testMode) {
            $this->list = [];
        }

        array_walk($this->list, function($name) use (&$transport) {
            $command = $this->commandFactory->create($name);
            $transport = $this->verifyTransport($command->go($transport), $name);
        });

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'complete'));
    }

    private function verifyTransport(\Driver\Pipes\Transport\TransportInterface $transport, $lastCommand)
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