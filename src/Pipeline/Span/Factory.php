<?php

declare(strict_types=1);

namespace Driver\Pipeline\Span;

use DI\Container;
use Driver\Pipeline\Exception\PipeLineNotFound;
use Driver\Pipeline\Master;
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

    public function create(string $pipelineName): SpanInterface
    {
        return $this->container->make($this->type, ['list' => $this->getNamedPipeline($pipelineName)]);
    }

    protected function pipelineExists(string $name): bool
    {
        return is_array($this->configuration->getNode("pipelines/{$name}"));
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    protected function getNamedPipeline(string $name): array
    {
        if ($this->pipelineExists($name)) {
            return $this->configuration->getNode("pipelines/{$name}");
        } else {
            throw new PipeLineNotFound();
        }
    }
}
