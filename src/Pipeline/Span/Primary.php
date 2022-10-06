<?php

declare(strict_types=1);

namespace Driver\Pipeline\Span;

use ArrayIterator;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Environment\Factory as EnvironmentFactory;
use Driver\Pipeline\Environment\Manager as EnvironmentManager;
use Driver\Pipeline\Stage\Factory as StageFactory;
use Driver\Pipeline\Stage\StageInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\YamlFormatter;

use function array_filter;
use function array_walk;

class Primary implements SpanInterface
{
    private const PIPE_SET_NODE = 'parent';

    /** @var StageInterface[] */
    private array $stages;
    private StageFactory $stageFactory;
    private EnvironmentManager $environmentManager;
    private EnvironmentFactory $environmentFactory;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        array $list,
        StageFactory $stageFactory,
        YamlFormatter $yamlFormatter,
        EnvironmentManager $environmentManager,
        EnvironmentFactory $environmentFactory
    ) {
        $this->stageFactory = $stageFactory;
        $this->environmentManager = $environmentManager;
        $this->environmentFactory = $environmentFactory;
        $this->stages = $this->generateStageMap($yamlFormatter->extractSpanList($list));
    }

    public function __invoke(TransportInterface $transport, bool $testMode = false): TransportInterface
    {
        $stages = !$testMode ? $this->stages : [];

        /** @var StageInterface $stage */
        foreach ($stages as $stage) {
            $transport = $this->verifyTransport($stage($transport), $stage->getName());
        };

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'complete'));
    }

    public function cleanup(TransportInterface $transport, bool $testMode = false): TransportInterface
    {
        $stages = !$testMode ? $this->stages : [];

        array_walk($stages, function (StageInterface $stage) use ($transport) {
            return $stage->cleanup($transport);
        });

        return $transport->withStatus(new Status(self::PIPE_SET_NODE, 'cleaned'));
    }

    /**
     * @return StageInterface[]
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    private function generateStageMap(array $list): array
    {
        $stages = $this->mapToStageObjects($this->sortStages($this->filterStages($list)));

        $output = new ArrayIterator();
        $defaultEnvironment = new ArrayIterator([ $this->environmentFactory->createDefault() ]);

        array_walk($stages, function (StageInterface $stage) use (&$output, $defaultEnvironment): void {
            if ($stage->isRepeatable()) {
                $output = $this->repeatForEnvironments($stage, $output, $this->environmentManager->getRunFor());
            } else {
                $output = $this->repeatForEnvironments(
                    $stage,
                    $output,
                    $this->environmentManager->hasCustomRunList()
                        ? $this->environmentManager->getRunFor()
                        : $defaultEnvironment
                );
            }
        });

        return $output->getArrayCopy();
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification,SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    private function sortStages(array $stages): array
    {
        uasort($stages, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }
            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        return $stages;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification,SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    private function filterStages(array $stages): array
    {
        return array_filter($stages, function ($actions) {
            return count($actions) > 0;
        });
    }

    /**
     * @return StageInterface[] $stages
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    private function mapToStageObjects(array $stages): array
    {
        return array_filter(array_map(function ($stage, $name) {
            if (isset($stage['actions'])) {
                return $this->stageFactory->create($stage['actions'], $name);
            } else {
                return null;
            }
        }, array_values($stages), array_keys($stages)));
    }

    private function repeatForEnvironments(
        StageInterface $stage,
        ArrayIterator $output,
        ArrayIterator $environments
    ): ArrayIterator {
        return array_reduce(
            $environments->getArrayCopy(),
            function (ArrayIterator $input, EnvironmentInterface $environment) use ($stage) {
                $output = new ArrayIterator($input->getArrayCopy());
                $output->append($stage->withEnvironment($environment));

                return $output;
            },
            $output
        );
    }

    private function verifyTransport(?TransportInterface $transport, string $lastCommand): TransportInterface
    {
        if (!$transport) {
            throw new \Exception('No Transport object was returned from the last command executed: ' . $lastCommand);
        }

        return $transport;
    }
}
