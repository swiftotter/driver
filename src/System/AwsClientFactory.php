<?php

declare(strict_types=1);

namespace Driver\System;

use Aws\AwsClient;

class AwsClientFactory
{
    /** @var callable|null */
    private $creator;

    public function __construct(callable $creator = null)
    {
        $this->creator = $creator;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function create(string $serviceType, array $arguments): AwsClient
    {
        if (!$this->creator || !is_callable($this->creator)) {
            return $this->doCreate($serviceType, $arguments);
        } else {
            return $this->creator->__invoke($serviceType, $arguments);
        }
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    private function doCreate(string $serviceType, array $arguments): AwsClient
    {
        if (strpos($serviceType, "\\") !== false) {
            $type = $serviceType;
        } else {
            $type = '\\Aws\\' . $serviceType . '\\' . $serviceType . 'Client';
        }

        return new $type($arguments);
    }
}
