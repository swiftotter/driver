<?php

declare(strict_types=1);

namespace Driver\Commands\Webhook;

interface WebhookInterface
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function call(string $webhookUrl, array $data, string $method): void;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function post(string $webhookUrl, array $data): void;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function get(string $webhookUrl, array $data): void;
}
