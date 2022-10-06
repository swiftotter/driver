<?php

declare(strict_types=1);

namespace Driver\Commands\Webhook;

use Driver\Commands\CommandInterface;
use Driver\Engines\MySql\Sandbox\Sandbox;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Symfony\Component\Console\Command\Command;

class Transform extends Command implements CommandInterface
{
    private const ACTION = 'transform';

    private Configuration $configuration;
    private Webhook $webhook;
    private Sandbox $sandbox;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        Configuration $configuration,
        Webhook $webhook,
        Sandbox $sandbox,
        array $properties = []
    ) {
        $this->configuration = $configuration;
        $this->webhook = $webhook;
        $this->sandbox = $sandbox;
        $this->properties = $properties;

        parent::__construct('webhook-transform-command');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        $url = $this->configuration->getNode('connections/webhooks/transform-url');

        if (is_array($url) || $url || strpos('https://', $url) === false) {
            return $transport->withStatus(new Status('webhook_transform', 'error'));
        }

        $data = [
            'action' => self::ACTION,
            'sandbox' => $this->sandbox->getJson()
        ];
        $this->webhook->post($url, $data);

        return $transport->withStatus(new Status('webhook_transform', 'success'));
    }
}
