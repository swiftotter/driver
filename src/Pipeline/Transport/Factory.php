<?php

declare(strict_types=1);

namespace Driver\Pipeline\Transport;

use Driver\System\Logs\LoggerInterface;

class Factory
{
    private string $type;
    private LoggerInterface $logger;

    public function __construct(string $type, LoggerInterface $logger)
    {
        $this->type = $type;
        $this->logger = $logger;
    }

    public function create(string $pipeline): TransportInterface
    {
        return new $this->type($pipeline, $this->logger);
    }
}
