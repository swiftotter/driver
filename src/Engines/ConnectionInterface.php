<?php

declare(strict_types=1);

namespace Driver\Engines;

interface ConnectionInterface
{
    public function isAvailable(): bool;

    public function getConnection(): ReconnectingPDO;

    public function getDSN(): string;

    public function getCharset(): string;

    public function getHost(): string;

    public function getPort(): string;

    public function getDatabase(): string;

    public function getUser(): string;

    public function getPassword(): string;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getPreserve(): array;
}
