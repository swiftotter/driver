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

use Driver\Commands\CleanupInterface;
use Driver\Commands\CommandInterface;
use Driver\Commands\ErrorInterface;
use Driver\Commands\Factory as CommandFactory;
use Driver\Pipeline\Command;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\YamlFormatter;
use GuzzleHttp\Promise\Promise;
use Haystack\HArray;

class Primary implements StageInterface
{
    const PIPE_SET_NODE = 'parent';
    const REPEAT_PREFIX = 'repeat';
    const EMPTY_NODE = 'empty';

    private $actions;
    private $commandFactory;
    private $environment;
    private $name;

    public function __construct(array $actions, $name, CommandFactory $commandFactory, EnvironmentInterface $environment = null)
    {
        $this->commandFactory = $commandFactory;
        $this->actions = $this->sortActions($this->initActions($actions));
        $this->name = $name;
        $this->environment = $environment;
    }

    private function initActions($actions)
    {
        return array_map(function($properties) {
            if (is_a($properties, CommandInterface::class)) {
                return $properties;
            } else {
                if ($properties['name'] !== self::EMPTY_NODE) {
                    return $this->commandFactory->create($properties['name'], $properties);
                }
            }
        }, $actions);
    }

    public function isRepeatable()
    {
        return substr($this->name, 0, strlen(self::REPEAT_PREFIX)) === self::REPEAT_PREFIX;
    }

    public function withEnvironment(EnvironmentInterface $environment)
    {
        return new self($this->actions, $this->name, $this->commandFactory, $environment);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function __invoke(\Driver\Pipeline\Transport\TransportInterface $transport, $testMode = false)
    {
        $actions = !$testMode ? $this->actions : [];

        $transport = array_reduce($actions, function(TransportInterface $transport, CommandInterface $command) {
            return $this->runCommand($transport, $command);
        }, $transport);

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'complete'));
    }

    public function cleanup(\Driver\Pipeline\Transport\TransportInterface $transport, $testMode = false)
    {
        $actions = !$testMode ? $this->actions : [];

        $transport = array_reduce($actions, function(TransportInterface $transport, CommandInterface $command) {
            /** @var CleanupInterface $command */
            if (is_a($command, CleanupInterface::class)) {
                $command->cleanup($transport, $this->environment);
            }
            return $transport;
        }, $transport);

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'cleaned'));
    }

    private function runCommand(TransportInterface $transport, CommandInterface $command)
    {
        try {
            if (!$this->hasError($transport)) {
                return $this->verifyTransport($command->go($transport, $this->environment), $command);
            } else if ($this->hasErrorHandler($command)) {
                return $this->verifyTransport($command->error($transport, $this->environment), $command);
            } else {
                return $transport;
            }
        } catch (\Exception $ex) {
            return $transport->withStatus(new Status($command, $ex->getMessage(), true));
        }
    }

    private function hasErrorHandler(CommandInterface $command)
    {
        return is_a($command, ErrorInterface::class);
    }

    private function hasError(TransportInterface $transport)
    {
        return (new HArray($transport->getStatuses()))->reduce(function($carry, Status $status) {
            return $carry || $status->isError();
        }, false);
    }

    private function sortActions($actions)
    {
        $actions = array_filter($actions);
        $getSort = function(CommandInterface $command) {
            $properties = $command->getProperties();
            if (isset($properties['sort'])) {
                return $properties['sort'];
            } else {
                return 1000;
            }
        };

        usort($actions, function(CommandInterface $a, CommandInterface $b) use ($getSort) {
            if ($getSort($a) == $getSort($b)) {
                return 0;
            }
            return ($getSort($a) < $getSort($b)) ? -1 : 1;
        });

        return $actions;
    }

    private function verifyTransport(\Driver\Pipeline\Transport\TransportInterface $transport, CommandInterface $command)
    {
        if (!$transport) {
            throw new \Exception('No Transport object was returned from the last command executed: ' . get_class($command));
        }

        return $transport;
    }

//    private function formatList(array $list)
//    {
//        $output = array_reduce($list, function($commands, array $item) {
//            array_walk($item, function($name, $id) use (&$commands) {
//                while (isset($commands[$id])) {
//                    $id++;
//                }
//                $commands[$id] = $name;
//            });
//
//            return $commands;
//        }, []);
//
//        ksort($output);
//
//        return $output;
//    }
}