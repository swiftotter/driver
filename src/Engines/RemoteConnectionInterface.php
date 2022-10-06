<?php

declare(strict_types=1);

namespace Driver\Engines;

interface RemoteConnectionInterface extends ConnectionInterface
{
    public function useSsl(): bool;
    public function test(callable $onFailure): void;
    public function authorizeIp(): void;
}
