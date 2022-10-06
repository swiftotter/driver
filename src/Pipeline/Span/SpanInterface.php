<?php

declare(strict_types=1);

namespace Driver\Pipeline\Span;

use Driver\Pipeline\Transport\TransportInterface;

interface SpanInterface
{
    public function __invoke(TransportInterface $transport): TransportInterface;

    public function cleanup(TransportInterface $transport): TransportInterface;
}
