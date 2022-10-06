<?php

declare(strict_types=1);

namespace Driver\Pipeline\Environment;

use DI\Container;
use Driver\Engines\MySql\Sandbox\Utilities;
use Driver\Pipeline\Exception\PipeLineNotFound;
use Driver\System\Configuration;

class Factory
{
    private const DEFAULT_ENV = 'default';

    private Configuration $configuration;
    private Container $container;
    private Utilities $utilities;
    private string $type;

    public function __construct(Configuration $configuration, Container $container, Utilities $utilities, string $type)
    {
        $this->configuration = $configuration;
        $this->container = $container;
        $this->utilities = $utilities;
        $this->type = $type;
    }

    public function createDefault(): EnvironmentInterface
    {
        return $this->container->make($this->type, [
            'name' => self::DEFAULT_ENV,
            'properties' => $this->getEnvironmentProperties(self::DEFAULT_ENV)
        ]);
    }

    public function create(string $name): EnvironmentInterface
    {
        return $this->container->make($this->type, [
            'name' => $name,
            'properties' => $this->getEnvironmentProperties($name),
            'utilities' => $this->utilities
        ]);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    private function getEnvironmentProperties(string $name): array
    {
        if ($this->environmentExists($name)) {
            return $this->configuration->getNode("environments/{$name}");
        } else {
            throw new PipeLineNotFound();
        }
    }

    private function environmentExists(string $name): bool
    {
        return is_array($this->configuration->getNode("environments/{$name}"));
    }
}
