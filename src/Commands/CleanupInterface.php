<?php

declare(strict_types=1);

namespace Driver\Commands;

use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\TransportInterface;

interface CleanupInterface
{
    public function cleanup(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface;
}
