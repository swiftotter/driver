<?php

declare(strict_types=1);

namespace Driver\Pipeline\Stage;

use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\TransportInterface;

interface StageInterface
{
    public function __invoke(TransportInterface $transport): TransportInterface;

    public function cleanup(TransportInterface $transport): TransportInterface;

    public function getName(): string;

    public function withEnvironment(EnvironmentInterface $environment): StageInterface;

    public function isRepeatable(): bool;
}
