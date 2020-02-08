<?php
declare(strict_types=1);
/**
 * @by SwiftOtter, Inc. 2/8/20
 * @website https://swiftotter.com
 **/

namespace Driver\Engines\MySql\Transformation;

use Driver\Commands\CommandInterface;
use Driver\Engines\MySql\Sandbox\Connection as SandboxConnection;
use Driver\Engines\MySql\Transformation\Anonymize\Seed;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;

class Reduce extends Command implements CommandInterface
{
    /** @var Configuration */
    private $configuration;

    /** @var SandboxConnection */
    private $sandbox;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $properties;

    public function __construct(
        Configuration $configuration,
        SandboxConnection $sandbox,
        LoggerInterface $logger,
        array $properties = []
    ) {
        $this->configuration = $configuration;
        $this->sandbox = $sandbox;
        $this->logger = $logger;
        $this->properties = $properties;

        parent::__construct('mysql-transformation-reduce');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $config = $this->configuration->getNode('reduce/tables');

        $transport->getLogger()->notice("Beginning reducing table rows from reduce.yaml.");

        if (!is_array($config) || (isset($config['disabled']) &&  $config['disabled'] === true)) {
            return $transport->withStatus(new Status('mysql-transformation-reduce', 'skipped'));
        }

        foreach ($this->configuration->getNode('reduce/tables') as $tableName => $details) {
            try {
                $column = $details['column'];
                $statement = $details['statement'];

                $this->sandbox->getConnection()->query(
                    "DELETE FROM ${tableName} WHERE ${column} ${statement}"
                );
            } catch (\Exception $ex) {
                $this->logger->error($ex->getMessage());
            }
        }

        $transport->getLogger()->notice("Row reduction process complete.");

        return $transport->withStatus(new Status('mysql-transformation-reduce', 'success'));
    }

    public function getProperties()
    {
        return $this->properties;
    }

}