<?php

declare(strict_types=1);

namespace Driver\Pipeline;

use Driver\Pipeline\Span\Factory as PipeLineSpanFactory;
use Driver\Pipeline\Transport\Factory as TransportFactory;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;

class Master
{
    const DEFAULT_NODE = 'build';

    private Configuration $configuration;
    private PipeLineSpanFactory $pipeLineFactory;
    private TransportFactory $transportFactory;

    public function __construct(
        Configuration $configuration,
        PipeLineSpanFactory $pipeLineFactory,
        TransportFactory $transportFactory
    ) {
        $this->configuration = $configuration;
        $this->pipeLineFactory = $pipeLineFactory;
        $this->transportFactory = $transportFactory;
    }

    public function run(string $set): void
    {
        $pipeline = $this->pipeLineFactory->create($set);
        $transport = $pipeline($this->createTransport($set));
        $pipeline->cleanup($transport);
    }

    protected function createTransport(string $set): TransportInterface
    {
        return $this->transportFactory->create($set);
    }
}
