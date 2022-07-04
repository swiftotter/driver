<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Transformation;

use Driver\Commands\CommandInterface;
use Driver\Engines\MySql\Transformation\UpdateValues\Join;
use Driver\Engines\MySql\Transformation\UpdateValues\QueryBuilder;
use Driver\Engines\MySql\Transformation\UpdateValues\Value;
use Driver\Engines\RemoteConnectionInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

use function is_array;
use function is_string;

class UpdateValues extends Command implements CommandInterface
{
    const COMMAND_NAME = 'mysql-transformation-update-values';

    private Configuration $configuration;
    private RemoteConnectionInterface $connection;
    private ConsoleOutput $output;
    private QueryBuilder $queryBuilder;
    private array $properties = [];

    public function __construct(
        Configuration $configuration,
        RemoteConnectionInterface $connection,
        ConsoleOutput $output,
        QueryBuilder $queryBuilder,
        array $properties = []
    ) {
        $this->configuration = $configuration;
        $this->connection = $connection;
        $this->output = $output;
        $this->queryBuilder = $queryBuilder;
        $this->properties = $properties;
        parent::__construct(self::COMMAND_NAME);
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        $config = $this->configuration->getNode('update-values');
        if ((isset($config['disabled']) && $config['disabled'] === true) || !isset($config['tables'])) {
            return $transport->withStatus(new Status(self::COMMAND_NAME, 'success'));
        }

        $transport->getLogger()->notice("Beginning table updates from update-values.yaml.");
        $this->output->writeln("<comment>Beginning table updates from update-values.yaml.</comment>");

        foreach ($config['tables'] as $table => $data) {
            $this->update($table, $data);
        }

        $transport->getLogger()->notice("Database has been updated.");
        $this->output->writeln("<info>Database has been updated.</info>");

        return $transport->withStatus(new Status(self::COMMAND_NAME, 'success'));
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    private function update(string $table, array $data): void
    {
        $joins = $this->buildJoins($data);
        $values = $this->buildValues($data);
        $query = $this->queryBuilder->build($table, $values, $joins);
        if (!$query) {
            return;
        }
        $connection = $this->connection->getConnection();
        try {
            $connection->query($query);
        } catch (Exception $ex) {
            // Do nothing
        }
    }

    /**
     * @return Join[]
     */
    private function buildJoins(array $data): array
    {
        if (empty($data['joins'])) {
            return [];
        }

        if (!is_array($data['joins'])) {
            throw new InvalidArgumentException('Joins must be an array.');
        }

        $joins = [];
        foreach ($data['joins'] as $joinData) {
            foreach (['table', 'alias', 'on'] as $key) {
                if (empty($joinData[$key]) || !is_string($joinData[$key])) {
                    throw new InvalidArgumentException(
                        sprintf('Join option "%s" must be a non-empty string.', $key)
                    );
                }
            }
            $joins[] = new Join($joinData['table'], $joinData['alias'], $joinData['on']);
        }
        return $joins;
    }

    /**
     * @return Value[]
     */
    private function buildValues(array $data): array
    {
        if (empty($data['values'])) {
            return [];
        }

        if (!is_array($data['values'])) {
            throw new InvalidArgumentException('Values must be an array.');
        }

        $values = [];
        foreach ($data['values'] as $valueData) {
            foreach (['field', 'value'] as $key) {
                if (empty($valueData[$key]) || !is_string($valueData[$key])) {
                    throw new InvalidArgumentException(
                        sprintf('Value option "%s" must be a non-empty string.', $key)
                    );
                }
            }
            $values[] = new Value($valueData['field'], $valueData['value']);
        }
        return $values;
    }
}
