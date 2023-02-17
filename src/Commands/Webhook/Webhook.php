<?php

declare(strict_types=1);

namespace Driver\Commands\Webhook;

use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use GuzzleHttp\Client;
use Symfony\Component\Console\Output\OutputInterface;

class Webhook implements WebhookInterface
{
    private Configuration $configuration;
    private LoggerInterface $logger;

    public function __construct(Configuration $configuration, LoggerInterface $logger)
    {
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function call(string $webhookUrl, array $data, string $method): void
    {
        $client = new Client();
        $options = $this->getAuth([]);
        $options = $this->getData($options, $data, $method);

        try {
            $client->request($method, $webhookUrl, $options);
        } catch (\Exception $ex) {
            $this->logger->alert($ex->getMessage());
        }
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function post(string $webhookUrl, array $data): void
    {
        $this->call($webhookUrl, $data, 'POST');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function get(string $webhookUrl, array $data): void
    {
        $this->call($webhookUrl, $data, 'GET');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification,SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    private function getData(array $options, array $data, string $method): array
    {
        if (!count($data)) {
            return $options;
        }

        if ($method === "GET") {
            return array_merge($options, [ 'query_string' => http_build_query($data)]);
        } else {
            return array_merge($options, [ 'json' => $data]);
        }
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification,SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    private function getAuth(array $options): array
    {
        $auth = $this->configuration->getNode('connections/webhook/auth');
        $node = [];

        if (count($auth)) {
            $node['auth'] = [ $auth['user'], $auth['password'] ];
        }

        return array_merge($options, $node);
    }
}
