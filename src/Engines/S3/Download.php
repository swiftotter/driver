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

use Aws\Result;
use Aws\S3\S3Client;
use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\LocalConnectionLoader;
use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class Download extends Command implements CommandInterface
{
    /** @var LocalConnectionLoader */
    private $localConnection;
    protected $configuration;
    protected $properties;
    /** @var LoggerInterface */
    private $logger;
    /** @var ConsoleOutput */
    private $output;

    public function __construct(LocalConnectionLoader $localConnection, Configuration $configuration, LoggerInterface $logger, ConsoleOutput $output, array $properties = [])
    {
        $this->localConnection = $localConnection;
        $this->configuration = $configuration;
        $this->properties = $properties;
        $this->logger = $logger;
        $this->output = $output;

        parent::__construct('s3-download');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment)
    {
        try {
            $transport->getLogger()->notice("Beginning file download from: s3://" . $this->getBucket() . "/" . $this->getFileName($environment));
            $this->output->writeln("<comment>Beginning file download from: s3://" . $this->getBucket() . "/" . $this->getFileName($environment) . '</comment>');
            $date = date('Y-m-d');
            $client = $this->getS3Client();
            $output = $client->getObject([
                'Bucket' => $this->getBucket(),
                'Key' => $this->getDirectory() . $this->getFileName($environment),
                'SourceFile' => $transport->getData($environment->getName() . '_completed_file'),
                'ContentType' => 'application/gzip',
                'SaveAs' => "var/{$this->localConnection->getDatabase()}_" . str_replace('-', '_', $date) .'.sql'
            ]);

            $transport->getLogger()->notice("Downloaded file from: s3://" . $this->getBucket() . "/" . $this->getFileName($environment));
            $this->output->writeln("<info>Downloaded file from: s3://" . $this->getBucket() . "/" . $this->getFileName($environment) . ' to project var/ directory</info>');

            return $transport->withNewData('s3_url', $this->getObjectUrl($output));
        } catch (\Exception $ex) {
            $this->logger->error('Failed getting object from S3: ' . $ex->getMessage(), [
                $ex->getMessage(),
                $ex->getTraceAsString()
            ]);

            $this->output->writeln('<error>Failed getting object from S3: ' . $ex->getMessage(), [
                $ex->getMessage(),
                $ex->getTraceAsString()
            ] . '</error>');

            return $transport->withStatus(new Status('s3-download', $ex->getMessage(), true));
        }

    }

    public function getProperties()
    {
        return $this->properties;
    }

    protected function getObjectUrl(\Aws\Result $data)
    {
        return $data->get('ObjectURL');
    }

    private function getFileName(EnvironmentInterface $environment)
    {
        $replace = str_replace('{{environment}}', '-' . $environment->getName(), $this->getFileKey());
        $replace = str_replace('{{date}}', date('YmdHis'), $replace);

        return $replace;
    }

    private function getFileKey()
    {
        if ($this->compressOutput()) {
            return $this->configuration->getNode('connections/s3/compressed-file-key');
        } else {
            return $this->configuration->getNode('connections/s3/uncompressed-file-key');
        }
    }

    private function compressOutput()
    {
        return (bool)$this->configuration->getNode('configuration/compress-output') === true;
    }

    private function getBucket()
    {
        return $this->configuration->getNode('connections/s3/bucket');
    }

    private function getDirectory()
    {
        $directory = $this->configuration->getNode('connections/s3/directory');
        if ($directory) {
            $directory .= '/';
        }

        return $directory;
    }

    private function getS3Client()
    {
        return new S3Client($this->getAwsParameters());
    }

    private function getAwsParameters()
    {
        $parameters = [
            'credentials' => [
                'key' => $this->configuration->getNode("connections/s3/key")
                    ?? $this->configuration->getNode("connections/aws/key"),
                'secret' => $this->configuration->getNode("connections/s3/secret")
                    ?? $this->configuration->getNode("connections/aws/secret")
            ],
            'region' => $this->configuration->getNode("connections/s3/region")
                ?? $this->configuration->getNode("connections/aws/region"),
            'version' => '2006-03-01'
        ];
        return $parameters;
    }
}
