<?php

declare(strict_types=1);

namespace Driver\Commands\Webhook;

use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use JmesPath\Env;
use Symfony\Component\Console\Command\Command;

class PostCommand extends Command implements CommandInterface
{
    private Configuration $configuration;
    private Webhook $webhook;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(Configuration $configuration, Webhook $webhook, array $properties = [])
    {
        $this->configuration = $configuration;
        $this->webhook = $webhook;
        $this->properties = $properties;

        parent::__construct('webhook-post-command');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        $url = $this->configuration->getNode('connections/webhooks/post-url');

        if (!is_array($url) && $url) {
            $this->webhook->post($url, $transport->getAllData());
        }

        if (isset($this->properties['url'])) {
            $this->webhook->post($this->properties['url'], $transport->getAllData());
        }

        return $transport->withStatus(new Status('webhook_postcommand', 'success'));
    }
}
