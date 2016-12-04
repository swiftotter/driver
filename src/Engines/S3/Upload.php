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
 * @copyright SwiftOtter Studios, 12/3/16
 * @package default
 **/

namespace Driver\Engines\S3;

use Aws\S3\S3Client;
use Driver\Commands\CommandInterface;
use Driver\Pipes\Transport\TransportInterface;
use Driver\System\Configuration;
use Symfony\Component\Console\Command\Command;

class Upload extends Command implements CommandInterface
{
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        parent::__construct('s3-upload');
    }

    public function go(TransportInterface $transport)
    {
        $client = $this->getS3Client();
        $client->upload();
    }

    private function getS3Client()
    {
        return new S3Client($this->getAwsParameters());
    }

    private function getAwsParameters()
    {
        $parameters = [
            'credentials' => [
                'key' => $this->configuration->getNode("connections/ec2/key"),
                'secret' => $this->configuration->getNode("connections/ec2/secret")],
            'region' => $this->configuration->getNode("connections/ec2/region"),
            'version' => '2006-03-01'
        ];
        return $parameters;
    }
}