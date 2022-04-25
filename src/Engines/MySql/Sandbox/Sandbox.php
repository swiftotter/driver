<?php
/**
 * SwiftOtter_Base is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SwiftOtter_Base is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with SwiftOtter_Base. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Joseph Maxwell
 * @copyright SwiftOtter Studios, 11/19/16
 * @package default
 **/

namespace Driver\Engines\MySql\Sandbox;

use Driver\System\AwsClientFactory;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Driver\System\Random;
use Driver\System\RemoteIP;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Sandbox
{
    private $configuration;
    private $instance;
    private $initialized;
    private $remoteIpFetcher;
    private $logger;
    private $random;
    private $awsClientFactory;

    private $securityGroupId;
    private $securityGroupName;

    private $dbName;
    private $identifier;
    private $username;
    private $password;
    private $statuses;
    private $output;
    /** @var \Symfony\Component\EventDispatcher\EventDispatcher */
    private EventDispatcher $eventDispatcher;

    public function __construct(
        Configuration $configuration,
        RemoteIP $remoteIpFetcher,
        LoggerInterface $logger,
        Random $random,
        AwsClientFactory $awsClientFactory,
        ConsoleOutput $output,
        EventDispatcher $eventDispatcher,
        $disableInstantiation = true
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

    public function init()
    {
        $this->logger->info("Using RDS instance: " . $this->getIdentifier());
        $this->output->writeln("<comment>Using RDS instance: " . $this->getIdentifier() . '</comment>');

        if ($this->initialized || $this->configuration->getNode('connections/rds/instance-name') || $this->getInstanceActive()) {
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

    public function shutdown()
    {
        if ($this->configuration->getNode('connections/rds/instance-name')) {
            $this->logger->info("Using static RDS instance and will not shutdown: " . $this->getIdentifier());
            $this->output->writeln("<comment>Using static RDS instance and will not shutdown: " . $this->getIdentifier() . '</comment>');
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

    public function getJson()
    {
        return [
            'host' => $this->getEndpointAddress(),
            'port' => $this->getEndpointPort(),
            'user' => $this->getUsername(),
            'password' => $this->getPassword(),
            'database' => $this->getDBName()
        ];
    }

    public function getInstanceActive()
    {
        $status = $this->getInstanceStatus();
        return isset($status['DBInstanceStatus']) && ($status['DBInstanceStatus'] === "available" || $status['DBInstanceStatus'] === "backing_up");
    }

    public function getEndpointAddress()
    {
        $status = $this->getInstanceStatus();
        return isset($status['Endpoint']['Address']) ? $status['Endpoint']['Address'] : null;
    }

    public function getEndpointPort()
    {
        $status = $this->getInstanceStatus();
        return isset($status['Endpoint']['Port']) ? $status['Endpoint']['Port'] : 3306;
    }

    public function getDbParameterGroupName()
    {
        $value = $this->configuration->getNode('connections/rds/parameter-group-name');

        if (!is_array($value) && $value) {
            return $value;
        } else {
            return false;
        }
    }

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

    private function getSecurityGroup()
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

    public function authorizeIp()
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
                throw $ex;
                $this->output->writeln("Exception: " . $ex->getMessage());
            }
        }
    }

    private function getPublicIp()
    {
        return $this->remoteIpFetcher->getPublicIP();
    }

    public function getDBName()
    {
        if (!$this->dbName) {
            $this->dbName = $this->configuration->getNode('connections/rds/instance-db-name');

            if (!$this->dbName) {
                $this->dbName = 'd' . $this->getRandomString(12);
            }
        }

        return $this->dbName;
    }

    private function getIdentifier()
    {
        if (!$this->identifier) {
            $this->identifier = $this->configuration->getNode('connections/rds/instance-identifier');

            if (!$this->identifier) {
                $this->identifier = 'driver-upload-' . $this->getRandomString(6);
            }
        }

        return $this->identifier;
    }

    private function getStorageType()
    {
        $storageType = $this->configuration->getNode('connections/rds/storage-type');

        if (!$storageType) {
            $storageType = 'standard';
        }

        return $storageType;
    }

    private function getEngine()
    {
        $engine = $this->configuration->getNode('connections/rds/engine');

        if (!$engine) {
            $engine = 'MySQL';
        }

        return $engine;
    }

    public function getSecurityGroupName()
    {
        if (!$this->securityGroupName) {
            $this->securityGroupName = $this->configuration->getNode('connections/rds/security-group-name');

            if (!$this->securityGroupName) {
                $this->securityGroupName = 'driver-temp-' . $this->getRandomString(6);
            }
        }

        return $this->securityGroupName;
    }

    public function getUsername()
    {
        if (!$this->username) {
            $this->username = $this->configuration->getNode('connections/rds/instance-username');

            if (!$this->username) {
                $this->username = 'u' . $this->getRandomString(12);
            }
        }

        return $this->username;
    }

    public function getPassword()
    {
        if (!$this->password) {
            $this->password = $this->configuration->getNode('connections/rds/instance-password');

            if (!$this->password) {
                $this->password = $this->getRandomString(30);
            }
        }

        return $this->password;
    }

    private function getRandomString($length)
    {
        return $this->random->getRandomString($length);
    }

    /**
     * @return \Aws\Ec2\Ec2Client
     */
    private function getEc2Client()
    {
        return $this->awsClientFactory->create('Ec2', $this->getAwsParameters("ec2", '2016-09-15'));
    }

    /**
     * @return \Aws\Rds\RdsClient
     */
    private function getRdsClient()
    {
        return $this->awsClientFactory->create('Rds', $this->getAwsParameters("rds", '2014-10-31'));
    }

    /**
     * @param $type
     * @param $version
     * @return array
     */
    private function getAwsParameters($type, $version)
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
            $this->output->writeln('<fg=blue>No region specified. Are you sure that config.d/connections.yaml exists?</>');
        }

        return $parameters;
    }
}
