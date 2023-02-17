<?php

declare(strict_types=1);

namespace Driver\Pipeline\Stage;

use DI\Container;
use Driver\System\Configuration;

class Factory
{
    private Configuration $configuration;
    private Container $container;
    private string $type;

    public function __construct(Configuration $configuration, Container $container, string $type)
    {
        $this->configuration = $configuration;
        $this->container = $container;
        $this->type = $type;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function create(array $actions, string $name): StageInterface
    {
        return $this->container->make($this->type, ['actions' => $actions, 'name' => $name]);
    }
}
