<?php

declare(strict_types=1);

namespace Driver\Commands\Environment;

use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\TransportInterface;
use Symfony\Component\Console\Command\Command;

class Setup extends Command implements CommandInterface
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(array $properties = [])
    {
        $this->properties = $properties;
        parent::__construct('environment-configuration');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        // @todo Check this!
        return $transport->withEnvironment($environment);
    }
}
