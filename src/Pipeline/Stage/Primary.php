<?php

declare(strict_types=1);

namespace Driver\Pipeline\Stage;

use Driver\Commands\CleanupInterface;
use Driver\Commands\CommandInterface;
use Driver\Commands\ErrorInterface;
use Driver\Commands\Factory as CommandFactory;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;

use function array_reduce;

class Primary implements StageInterface
{
    private const PIPE_SET_NODE = 'parent';
    private const REPEAT_PREFIX = 'repeat';
    private const EMPTY_NODE = 'empty';

    /** @var CommandInterface[] */
    private array $actions;
    private string $name;
    private CommandFactory $commandFactory;
    private ?EnvironmentInterface $environment;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        array $actions,
        string $name,
        CommandFactory $commandFactory,
        EnvironmentInterface $environment = null
    ) {
        $this->commandFactory = $commandFactory;
        $this->actions = $this->sortActions($this->initActions($actions));
        $this->name = $name;
        $this->environment = $environment;
    }

    public function __invoke(TransportInterface $transport, bool $testMode = false): TransportInterface
    {
        $actions = !$testMode ? $this->actions : [];

        $transport = array_reduce($actions, function (TransportInterface $transport, CommandInterface $command) {
            return $this->runCommand($transport, $command);
        }, $transport);

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'complete'));
    }

    public function cleanup(TransportInterface $transport, bool $testMode = false): TransportInterface
    {
        $actions = !$testMode ? $this->actions : [];

        $transport = array_reduce($actions, function (TransportInterface $transport, CommandInterface $command) {
            /** @var CleanupInterface $command */
            if (is_a($command, CleanupInterface::class)) {
                $command->cleanup($transport, $this->environment);
            }
            return $transport;
        }, $transport);

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'cleaned'));
    }

    public function isRepeatable(): bool
    {
        return substr($this->name, 0, strlen(self::REPEAT_PREFIX)) === self::REPEAT_PREFIX;
    }

    public function withEnvironment(EnvironmentInterface $environment): StageInterface
    {
        return new self($this->actions, $this->name, $this->commandFactory, $environment);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEnvironment(): ?EnvironmentInterface
    {
        return $this->environment;
    }

    /**
     * @return CommandInterface[]
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    private function initActions(array $actions): array
    {
        return array_map(function ($properties) {
            if (is_a($properties, CommandInterface::class)) {
                return $properties;
            } else {
                if ($properties['name'] !== self::EMPTY_NODE) {
                    return $this->commandFactory->create($properties['name'], $properties);
                }
            }
        }, $actions);
    }

    private function runCommand(TransportInterface $transport, CommandInterface $command): TransportInterface
    {
        try {
            if (!$this->hasError($transport)) {
                return $this->verifyTransport($command->go($transport, $this->environment), $command);
            } elseif ($this->hasErrorHandler($command)) {
                return $this->verifyTransport($command->error($transport, $this->environment), $command);
            } else {
                return $transport;
            }
        } catch (\Exception $ex) {
            return $transport->withStatus(new Status(get_class($command), $ex->getMessage(), true));
        }
    }

    private function hasErrorHandler(CommandInterface $command): bool
    {
        return is_a($command, ErrorInterface::class);
    }

    private function hasError(TransportInterface $transport): bool
    {
        return array_reduce($transport->getStatuses(), function (bool $carry, Status $status): bool {
            return $carry || $status->isError();
        }, false);
    }

    /**
     * @param CommandInterface[] $actions
     * @return CommandInterface[]
     */
    private function sortActions(array $actions): array
    {
        $actions = array_filter($actions);
        $getSort = function (CommandInterface $command) {
            $properties = $command->getProperties();
            return $properties['sort'] ?? 1000;
        };

        usort($actions, function (CommandInterface $a, CommandInterface $b) use ($getSort) {
            if ($getSort($a) == $getSort($b)) {
                return 0;
            }
            return ($getSort($a) < $getSort($b)) ? -1 : 1;
        });

        return $actions;
    }

    private function verifyTransport(TransportInterface $transport, CommandInterface $command): TransportInterface
    {
        if (!$transport) {
            throw new \Exception(
                'No Transport object was returned from the last command executed: ' . get_class($command)
            );
        }

        return $transport;
    }
}
