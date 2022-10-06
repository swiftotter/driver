<?php

declare(strict_types=1);

namespace Driver\Pipeline\Transport;

use Driver\System\Logs\LoggerInterface;

interface TransportInterface
{
    public function getPipeline(): string;

    /**
     * @return Status[]
     */
    public function getErrors(): array;

    /**
     * @return Status[]
     */
    public function getErrorsByNode(string $node): array;

    /**
     * @return Status[]
     */
    public function getStatuses(): array;

    /**
     * @return Status[]
     */
    public function getStatusesByNode(string $node): array;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint
    public function getAllData(): array;

    public function withStatus(Status $status): self;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint
    public function getData(string $key);

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function withNewData(string $key, $value): self;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint
    public function getLogger(): LoggerInterface;
}
