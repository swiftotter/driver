<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Sandbox;

use Aws\Ec2\Ec2Client;
use Aws\Rds\RdsClient;
use Aws\Result;
use Driver\System\AwsClientFactory;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Driver\System\Random;
use Driver\System\RemoteIP;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Sandbox
{
    private const DEFAULT_ENGINE = 'MySQL';

    private Configuration $configuration;
    private RemoteIP $remoteIpFetcher;
    private LoggerInterface $logger;
    private Random $random;
    private AwsClientFactory $awsClientFactory;
    private ConsoleOutput $output;
    private EventDispatcher $eventDispatcher;
    private ?Result $instance = null;
    private bool $initialized = false;
    private ?string $securityGroupId = null;
    private ?string $securityGroupName = null;
    private ?string $dbName = null;
    private ?string $identifier = null;
    private ?string $username = null;
    private ?string $password = null;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $statuses = [];

    public function __construct(
        Configuration $configuration,
        RemoteIP $remoteIpFetcher,
        LoggerInterface $logger,
        Random $random,
        AwsClientFactory $awsClientFactory,
        ConsoleOutput $output,
        EventDispatcher $eventDispatcher,
        bool $disableInstantiation = true
    ) {
        $this->configuration = $configuration;
        $this->remoteIpFetcher = $remoteIpFetcher;
        $this->logger = $logger;
        $this->random = $random;
        $this->output = $output;
        $this->awsClientFactory = $awsClientFactory;
        if (!$disableInstantiation) {
            $this->init();
        }
        $this->eventDispatcher = $eventDispatcher;
    }

    public function init(): bool
    {
        $this->logger->info("Using RDS instance: " . $this->getIdentifier());
        $this->output->writeln("<comment>Using RDS instance: " . $this->getIdentifier() . '</comment>');

        if (
            $this->initialized
            || $this->configuration->getNode('connections/rds/instance-name')
            || $this->getInstanceActive()
        ) {
            $this->logger->info("Using RDS instance: " . $this->getIdentifier());
            $this->output->writeln("<comment>Using RDS instance: " . $this->getIdentifier() . '</comment>');
            return false;
        }

        $this->logger->info("Preparing to create RDS instance: " . $this->getIdentifier());
        $this->output->writeln("<comment>Preparing to create RDS instance: " . $this->getIdentifier() . '</comment>');
        $this->initialized = true;

        try {
            $client = $this->getRdsClient();

            $parameters = [
                'DBName' => $this->getDBName(),
                'DBInstanceIdentifier' => $this->getIdentifier(),
                'AllocatedStorage' => 100,
                'DBInstanceClass' => $this->configuration->getNode('connections/rds/instance-type'),
                'Engine' => $this->getEngine(),
                'MasterUsername' => $this->getUsername(),
                'MasterUserPassword' => $this->getPassword(),
                'VpcSecurityGroupIds' => [$this->getSecurityGroup()],
                'BackupRetentionPeriod' => 0,
                'StorageType' => $this->getStorageType()
            ];

            $engineVersion = $this->getEngineVersion();
            if ($engineVersion) {
                $parameters['EngineVersion'] = $engineVersion;
            }

            if ($parameterGroupName = $this->getDbParameterGroupName()) {
                $parameters['DBParameterGroupName'] = $parameterGroupName;
            }

            $this->instance = $client->createDBInstance($parameters);

            $this->logger->info("RDS instance is initializing: " . $this->getIdentifier());
            $this->logger->info("Username: " . $this->getUsername());
            $this->logger->info("Password: " . $this->getPassword());
            $this->output->writeln("<comment>RDS instance is initializing: " . $this->getIdentifier() . '</comment>');

            return true;
        } catch (\Exception $ex) {
            $this->logger->info("On snap. RDS instance creation failed: " . $ex->getMessage(), [
                "trace" => $ex->getTraceAsString()
            ]);
            $this->output->writeln("<error>On snap. RDS instance creation failed: " . $ex->getMessage() . "</error>");
            throw new \Exception("<error>RDS instance creation failed: " . $ex->getMessage() . "</error>");
        }
    }

    public function shutdown(): bool
    {
        if ($this->configuration->getNode('connections/rds/instance-name')) {
            $this->logger->info("Using static RDS instance and will not shutdown: " . $this->getIdentifier());
            $this->output->writeln(
                "<comment>Using static RDS instance and will not shutdown: " . $this->getIdentifier() . '</comment>'
            );
            return false;
        }

        $rds = $this->getRdsClient();
        $rds->deleteDBInstance([
            'DBInstanceIdentifier' => $this->getIdentifier(),
            'SkipFinalSnapshot' => true
        ]);

        $ec2 = $this->getEc2Client();
        $ec2->deleteSecurityGroup([
            'GroupId' => $this->getSecurityGroup()
        ]);

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function getJson(): array
    {
        return [
            'host' => $this->getEndpointAddress(),
            'port' => $this->getEndpointPort(),
            'user' => $this->getUsername(),
            'password' => $this->getPassword(),
            'database' => $this->getDBName()
        ];
    }

    public function getInstanceActive(): bool
    {
        $status = $this->getInstanceStatus();
        return isset($status['DBInstanceStatus'])
            && ($status['DBInstanceStatus'] === "available" || $status['DBInstanceStatus'] === "backing_up");
    }

    public function getEndpointAddress(): ?string
    {
        $status = $this->getInstanceStatus();
        return $status['Endpoint']['Address'] ?? null;
    }

    public function getEndpointPort(): string
    {
        $status = $this->getInstanceStatus();
        return (string)($status['Endpoint']['Port'] ?? 3306);
    }

    public function getDbParameterGroupName(): string
    {
        $value = $this->configuration->getNode('connections/rds/parameter-group-name');

        if (!is_array($value) && $value) {
            return $value;
        } else {
            return '';
        }
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint
    public function getInstanceStatus()
    {
        try {
            $client = $this->getRdsClient();
            $result = $client->describeDBInstances(['DBInstanceIdentifier' => $this->getIdentifier()]);
            if (isset($result['DBInstances'][0])) {
                $this->statuses[$this->getIdentifier()] = $result['DBInstances'][0];
            }

            return $this->statuses[$this->getIdentifier()];
        } catch (\Exception $ex) {
            return ['DBInstanceStatus' => false];
        }
    }

    public function authorizeIp(): void
    {
        try {
            $this->getEc2Client()->authorizeSecurityGroupIngress([
                'GroupName' => $this->getSecurityGroupName(),
                'IpPermissions' => [
                    [
                        'IpProtocol' => 'tcp',
                        "IpRanges" => [
                            [
                                "CidrIp" => $this->getPublicIp() . '/32'
                            ]
                        ],
                        "ToPort" => "3306",
                        "FromPort" => "3306"
                    ]
                ]
            ]);
        } catch (\Exception $ex) {
            if (stripos($ex->getMessage(), 'InvalidPermission.Duplicate') === false) {
                $this->output->writeln("Exception: " . $ex->getMessage());
                throw $ex;
            }
        }
    }

    public function getDBName(): string
    {
        if (!$this->dbName) {
            $this->dbName = (string)$this->configuration->getNode('connections/rds/instance-db-name');

            if (!$this->dbName) {
                $this->dbName = 'd' . $this->getRandomString(12);
            }
        }

        return $this->dbName;
    }

    public function getSecurityGroupName(): string
    {
        if (!$this->securityGroupName) {
            $this->securityGroupName = (string)$this->configuration->getNode('connections/rds/security-group-name');

            if (!$this->securityGroupName) {
                $this->securityGroupName = 'driver-temp-' . $this->getRandomString(6);
            }
        }

        return $this->securityGroupName;
    }

    public function getUsername(): string
    {
        if (!$this->username) {
            $this->username = (string)$this->configuration->getNode('connections/rds/instance-username');

            if (!$this->username) {
                $this->username = 'u' . $this->getRandomString(12);
            }
        }

        return $this->username;
    }

    public function getPassword(): string
    {
        if (!$this->password) {
            $this->password = (string)$this->configuration->getNode('connections/rds/instance-password');

            if (!$this->password) {
                $this->password = $this->getRandomString(30);
            }
        }

        return $this->password;
    }

    private function getSecurityGroup(): string
    {
        if (!$this->securityGroupId) {
            $client = $this->getEc2Client();

            $securityGroup = $client->createSecurityGroup([
                'GroupName' => $this->getSecurityGroupName(),
                'Description' => 'Temporary security group for Driver uploads'
            ]);

            $this->authorizeIp();

            $this->securityGroupId = $securityGroup['GroupId'];
        }

        return $this->securityGroupId;
    }

    private function getPublicIp(): string
    {
        return $this->remoteIpFetcher->getPublicIP();
    }

    private function getIdentifier(): string
    {
        if (!$this->identifier) {
            $this->identifier = (string)$this->configuration->getNode('connections/rds/instance-identifier');

            if (!$this->identifier) {
                $this->identifier = 'driver-upload-' . $this->getRandomString(6);
            }
        }

        return $this->identifier;
    }

    private function getStorageType(): string
    {
        $storageType = $this->configuration->getNode('connections/rds/storage-type');

        if (!$storageType) {
            $storageType = 'standard';
        }

        return $storageType;
    }

    private function getEngine(): string
    {
        return (string)($this->configuration->getNode('connections/rds/engine') ?? self::DEFAULT_ENGINE);
    }

    private function getEngineVersion(): ?string
    {
        return $this->configuration->getNode('connections/rds/engine-version');
    }

    private function getRandomString(int $length): string
    {
        return $this->random->getRandomString($length);
    }

    private function getEc2Client(): Ec2Client
    {
        return $this->awsClientFactory->create('Ec2', $this->getAwsParameters("ec2", '2016-09-15'));
    }

    private function getRdsClient(): RdsClient
    {
        return $this->awsClientFactory->create('Rds', $this->getAwsParameters("rds", '2014-10-31'));
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    private function getAwsParameters(string $type, string $version): array
    {
        $parameters = [
            'credentials' => [
                'key' => $this->configuration->getNode("connections/{$type}/key")
                    ?? $this->configuration->getNode("connections/aws/key"),
                'secret' => $this->configuration->getNode("connections/{$type}/secret")
                    ?? $this->configuration->getNode("connections/aws/secret")
            ],
            'region' => $this->configuration->getNode("connections/{$type}/region")
                ?? $this->configuration->getNode("connections/aws/region"),
            'version' => $version,
            'service' => $type
        ];

        if (empty($parameters['region'])) {
            $this->output->writeln(
                '<fg=blue>No region specified. Are you sure that .driver/connections.yaml exists?</>'
            );
        }

        return $parameters;
    }
}
