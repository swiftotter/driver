<?php

declare(strict_types=1);

namespace Driver\Pipeline\Transport;

use Driver\System\Logs\LoggerInterface;
use Driver\System\Logs\Primary as PrimaryLogger;

use function array_merge;

class Primary implements TransportInterface
{
    private string $pipeline;
    private LoggerInterface $logger;
    /** @var Status[] */
    private array $statuses;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $data;

    /**
     * @param Status[] $statuses
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        string $pipeline,
        ?LoggerInterface $logger = null,
        array $statuses = [],
        array $data = []
    ) {
        $this->pipeline = $pipeline;
        $this->logger = $logger ?? new PrimaryLogger();
        $this->statuses = $statuses;
        $this->data = $data;
    }

    /**
     * @return Status[]
     */
    public function getErrors(): array
    {
        return array_filter($this->statuses, function (Status $status) {
            return $status->isError();
        });
    }

    /**
     * @return Status[]
     */
    public function getErrorsByNode(string $node): array
    {
        return array_filter($this->statuses, function (Status $status) use ($node) {
            return $status->isError() && $status->getNode() === $node;
        });
    }

    public function getPipeline(): string
    {
        return $this->pipeline;
    }

    public function withStatus(Status $status): self
    {
        return new self(
            $this->pipeline,
            $this->logger,
            array_merge($this->statuses, [$status]),
            $this->data
        );
    }

    /**
     * @return Status[]
     */
    public function getStatuses(): array
    {
        return $this->statuses;
    }

    /**
     * @return Status[]
     */
    public function getStatusesByNode(string $node): array
    {
        return array_filter($this->statuses, function (Status $status) use ($node) {
            return $status->getNode() === $node;
        });
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function withNewData(string $key, $value): self
    {
        return new self(
            $this->pipeline,
            $this->logger,
            $this->statuses,
            array_merge($this->data, [$key => $value])
        );
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint
    public function getAllData(): array
    {
        return $this->data;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint
    public function getData(string $key)
    {
        return $this->data[$key] ?? false;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
