<?php

declare(strict_types=1);

namespace Driver\System;

use DI;
use Driver\Engines\LocalConnectionInterface;
use Driver\Engines\MySql\Sandbox\Connection;
use Driver\Engines\RemoteConnectionInterface;
use Driver\Pipeline\Environment;
use Driver\Pipeline\Stage;
use Driver\Pipeline\Span;
use Driver\Pipeline\Transport\Factory as TransportFactory;
use Driver\Pipeline\Transport\Primary as TransportPrimary;
use Driver\System\Logs\LoggerInterface;
use Driver\System\Logs\Primary;

class DependencyConfig
{
    private bool $isDebug;

    public function __construct(bool $isDebug = false)
    {
        $this->isDebug = $isDebug;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function get(): array
    {
        return [
            LoggerInterface::class => DI\Factory(function () {
                return new Primary();
            }),
            Environment\EnvironmentInterface::class => DI\factory([Environment\Primary::class, 'create']),
            Environment\Factory::class => DI\autowire()->constructorParameter('type', Environment\Primary::class),
            Stage\StageInterface::class => DI\factory([Stage\Primary::class, 'create']),
            Stage\Factory::class => DI\autowire()->constructorParameter('type', Stage\Primary::class),
            Span\SpanInterface::class => DI\factory([Span\Primary::class, 'create']),
            Span\Factory::class => DI\autowire()->constructorParameter('type', Span\Primary::class),
            TransportFactory::class => DI\autowire()->constructorParameter('type', TransportPrimary::class),
            DebugMode::class => DI\create()->constructor($this->isDebug),
            RemoteConnectionInterface::class => DI\autowire(
                $this->isDebug ? DebugExternalConnection::class : Connection::class
            ),
            LocalConnectionInterface::class => DI\autowire(
                \Driver\System\LocalConnectionLoader::class
            )
        ];
    }
}
