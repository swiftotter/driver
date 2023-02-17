<?php

declare(strict_types=1);

namespace Driver\Commands;

use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\TransportInterface;

interface CommandInterface
{
    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array;
}
