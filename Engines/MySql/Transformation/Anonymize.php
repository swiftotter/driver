<?php
declare(strict_types=1);
/**
 * @by SwiftOtter, Inc. 2/7/20
 * @website https://swiftotter.com
 **/

namespace Driver\Engines\MySql\Transformation;

use Driver\Commands\CommandInterface;
use Driver\Engines\MySql\Sandbox\Connection as SandboxConnection;
use Driver\Engines\MySql\Sandbox\Utilities;
use Driver\Engines\MySql\Transformation\Anonymize\Seed;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;

class Anonymize extends Command implements CommandInterface
{
    /** @var Configuration */
    private $configuration;

    /** @var SandboxConnection */
    private $sandbox;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $properties;

    /** @var Seed */
    private $seed;

    public function __construct(
        Configuration $configuration,
        SandboxConnection $sandbox,
        Seed $seed,
        LoggerInterface $logger,
        array $properties = []
    ) {
        $this->configuration = $configuration;
        $this->properties = $properties;
        $this->sandbox = $sandbox;
        $this->logger = $logger;
        $this->seed = $seed;

        parent::__construct('mysql-transformation-anonymize');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        $config = $this->configuration->getNode('anonymize');
        if (isset($config['disabled']) && $config['disabled'] === true) {
            return $transport->withStatus(new Status('mysql-transformation-anonymize', 'success'));
        }

        if (!isset($config['tables'])) {
            return $transport->withStatus(new Status('mysql-transformation-anonymize', 'success'));
        }

        if (!isset($config['seed'])) {
            return $transport->withStatus(new Status('mysql-transformation-anonymize', 'success'));
        }

        $transport->getLogger()->notice("Beginning table anonymization from anonymize.yaml.");

        $this->seed->initialize();

        foreach ($config['tables'] as $table => $columns) {
            $this->anonymize($table, $columns);
        }

        $this->seed->destroy();

        $transport->getLogger()->notice("Database has been sanitized.");

        return $transport->withStatus(new Status('mysql-transformation-anonymize', 'success'));
    }

    private function anonymize(string $table, array $columns)
    {
        $connection = $this->sandbox->getConnection();

        if (isset($columns['truncate']) && $columns['truncate'] === true) {
            try {
                $connection->query("SET foreign_key_checks = 0;");
                $connection->query("TRUNCATE ${table};");
                $connection->query("SET foreign_key_checks = 1;");
            } catch (\Exception $ex) {}
        }


        foreach ($columns as $columnName => $description) {
            try {
                $method = $this->getTypeMethod($description);
                $select = $this->$method($description['type'] ?? 'general', $columnName);

                $query = "UPDATE `${table}` SET `${columnName}` = ${select} WHERE `${table}`.`${columnName}` IS NOT NULL;";

                $connection->beginTransaction();
                $connection->query($query);
                $connection->commit();
            } catch (\Exception $ex) {
                $connection->rollBack();
            }
        }
    }

    /*
     * email
     * firstname
     * lastname
     * full_name
     * general
     * phone
     * postcode
     * street
     * address
     */

    /**
     * Many thanks to this method for inspiration:
     * https://github.com/DivanteLtd/anonymizer/blob/master/lib/anonymizer/model/database/column.rb+
     *
     * @param string $table
     * @param string $columnName
     * @param $description
     * @return string
     */
    private function queryEmail($type, $columnName): string
    {
        return "CONCAT((SELECT CONCAT(MD5(FLOOR((NOW() + RAND()) * (RAND() * RAND() / RAND()) + RAND())))), \"@\", SUBSTRING({$columnName}, LOCATE('@', {$columnName}) + 1))";
    }

    private function queryFullName(): string
    {
        $table = Seed::FAKE_USER_TABLE;

        return "(SELECT CONCAT_WS(' ', ${table}.firstname, ${table}.lastname) FROM ${table} ORDER BY RAND() LIMIT 1)";
    }

    private function queryGeneral($type): string
    {
        if ($type === "general") {
            return "(SELECT MD5(FLOOR((NOW() + RAND()) * (RAND() * RAND() / RAND()) + RAND())))";
        }

        $table = Seed::FAKE_USER_TABLE;
        return "(SELECT ${table}.${type} FROM ${table} ORDER BY RAND() LIMIT 1)";
    }

    private function queryAddress(): string
    {
        $table = Seed::FAKE_USER_TABLE;
        return "(SELECT CONCAT(${table}.street, ', ', ${table}.city, ', ', ${table}.region, ' ', " .
            "${table}.postcode, ', ', ${table}.country_id) FROM ${table} ORDER BY RAND() LIMIT 1)";
    }

    private function queryEmpty(): string
    {
        return '""';
    }

    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param $description
     * @return string
     */
    private function getTypeMethod($description): string
    {
        $type = $description['type'] ?? 'general';
        if ($type === "full_name") {
            $type = "fullName";
        }

        $method = 'query' . ucfirst($type);

        if (!method_exists($this, $method)) {
            $method = 'queryGeneral';
        }
        return $method;
    }
}