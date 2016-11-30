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

use Aws\AwsClient;
use Aws\Ec2\Ec2Client;
use Aws\Rds\RdsClient;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Driver\System\RemoteIP;

class Sandbox
{
    private $configuration;
    private $instance;
    private $initialized;
    private $remoteIpFetcher;
    private $logger;

    private $securityGroupId;

    private $dbName;
    private $identifier;
    private $username;
    private $password;
    private $statuses;

    public function __construct(Configuration $configuration, RemoteIP $remoteIpFetcher, LoggerInterface $logger, $disableInstantiation = true)
    {
        $this->configuration = $configuration;
        $this->remoteIpFetcher = $remoteIpFetcher;
        $this->logger = $logger;

        if (!$disableInstantiation) {
            $this->init();
        }
    }

    public function init()
    {
        $this->logger->info("Using RDS instance: " . $this->getIdentifier());

        if ($this->initialized || $this->configuration->getNode('connections/rds/instance-name') || $this->getInstanceActive()) {
            $this->logger->info("Using RDS instance: " . $this->getIdentifier());
            return false;
        }

        $this->logger->info("Creating RDS instance: " . $this->getIdentifier());

        $this->initialized = true;

        $client = $this->getRdsClient();

        $parameters = [
            'DBName' => 'd' . $this->getDBName(),
            'DBInstanceIdentifier' => $this->getIdentifier(),
            'AllocatedStorage' => 5,
            'DBInstanceClass' => $this->configuration->getNode('connections/rds/instance-type'),
            'Engine' => 'MySQL',
            'MasterUsername' => 'u' . $this->getUsername(),
            'MasterUserPassword' => $this->getPassword(),
            'VpcSecurityGroupIds' => [ $this->getSecurityGroup() ]
        ];

        $this->instance = $client->createDBInstance($parameters);

        $this->logger->info("RDS instance is initializing: " . $this->getIdentifier());
        $this->logger->info("Username: " . $this->getUsername());
        $this->logger->info("Password: " . $this->getPassword());

        return true;
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

    public function getInstanceStatus($force = false)
    {
        if (!$this->statuses[$this->getIdentifier()] || $force) {
            $client = $this->getRdsClient();
            $result = $client->describeDBInstances(['DBInstanceIdentifier' => $this->getIdentifier()]);
            if (isset($result['DBInstances'][0])) {
                $this->statuses[$this->getIdentifier()] = $result['DBInstances'][0];
            }
        }

        return $this->statuses[$this->getIdentifier()];
    }

    private function getSecurityGroup()
    {
        if (!$this->securityGroupId) {
            $name = 'driver-temp-' . $this->getRandomString(6);
            $client = $this->getEc2Client();

            $securityGroup = $client->createSecurityGroup([
                'GroupName' => $name,
                'Description' => 'Temporary security group for Driver uploads'
            ]);

            $client->authorizeSecurityGroupIngress([
                'GroupName' => $name,
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

//            print_r($securityGroup);
//            print_r($authorization);

            $this->securityGroupId = $securityGroup['GroupId'];
        }

        return $this->securityGroupId;
    }

    private function getPublicIp()
    {
        return $this->remoteIpFetcher->getPublicIP();
    }

    private function getDBName()
    {
        if (!$this->dbName) {
            $this->dbName = $this->configuration->getNode('connections/rds/instance-name');

            if (!$this->dbName) {
                $this->dbName = $this->getRandomString(12);
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

    private function getUsername()
    {
        if (!$this->username) {
            $this->username = $this->configuration->getNode('connections/rds/instance-username');

            if (!$this->username) {
                $this->username = $this->getRandomString(12);
            }
        }

        return $this->username;
    }

    private function getPassword()
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
        return bin2hex(openssl_random_pseudo_bytes(round($length/2)));
    }

    private function getEc2Client()
    {
        return new Ec2Client($this->getAwsParameters("ec2", '2016-09-15'));
    }

    private function getRdsClient()
    {
        return new RdsClient($this->getAwsParameters("rds", '2014-10-31'));
    }

    private function getAwsParameters($type, $version)
    {
        $parameters = [
            'credentials' => [
                'key' => $this->configuration->getNode("connections/{$type}/key"),
                'secret' => $this->configuration->getNode("connections/{$type}/secret")],
            'region' => $this->configuration->getNode("connections/{$type}/region"),
            'version' => $version
        ];
        return $parameters;
    }
}