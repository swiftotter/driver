<?php

declare(strict_types=1);

namespace Driver\Engines\S3;

use Aws\Result;
use Aws\S3\S3Client;
use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Environment\Manager as EnvironmentManager;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\LocalConnectionLoader;
use Driver\System\Logs\LoggerInterface;
use Driver\System\S3FilenameFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class Download extends Command implements CommandInterface
{
    public const DOWNLOAD_PATH_KEY = 'download_path';

    private LocalConnectionLoader $localConnection;
    private Configuration $configuration;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;
    private LoggerInterface $logger;
    private ConsoleOutput $output;
    private EnvironmentManager $environmentManager;
    private S3FilenameFormatter $s3FilenameFormatter;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        LocalConnectionLoader $localConnection,
        Configuration $configuration,
        LoggerInterface $logger,
        ConsoleOutput $output,
        EnvironmentManager $environmentManager,
        S3FilenameFormatter $s3FilenameFormatter,
        array $properties = []
    ) {
        $this->localConnection = $localConnection;
        $this->configuration = $configuration;
        $this->properties = $properties;
        $this->logger = $logger;
        $this->output = $output;
        $this->environmentManager = $environmentManager;
        $this->s3FilenameFormatter = $s3FilenameFormatter;

        parent::__construct('s3-download');
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        try {
            $filename = $this->s3FilenameFormatter->execute($environment, $this->getFileKey());

            $transport->getLogger()->notice(
                sprintf("Beginning file download from: s3://%s/%s", $this->getBucket(), $filename)
            );
            $this->output->writeln(
                sprintf("<comment>Beginning file download from: s3://%s/%s</comment>", $this->getBucket(), $filename)
            );
            $date = date('Y-m-d');
            $client = $this->getS3Client();

            $outputFile = "var/${date}" . $filename;

            $output = $client->getObject([
                'Bucket' => $this->getBucket(),
                'Key' => $this->getDirectory() . $filename,
                'SourceFile' => $transport->getData($environment->getName() . '_completed_file'),
                'ContentType' => 'application/gzip',
                'SaveAs' => $outputFile
            ]);

            $transport->getLogger()->notice(
                sprintf("Downloaded file from: s3://%s/%s", $this->getBucket(), $this->getFileName($environment))
            );
            $this->output->writeln(
                sprintf(
                    "<info>Downloaded file from: s3://%s/%s to project var/ directory</info>",
                    $this->getBucket(),
                    $this->getFileName($environment)
                )
            );

            if (strpos($this->getFileName($environment), ".gz") !== false) {
                system("gunzip -f " . $outputFile);
                $outputFile = str_replace(".gz", "", $outputFile);
            }

            return $transport->withNewData('s3_url', $this->getObjectUrl($output))
                ->withNewData(self::DOWNLOAD_PATH_KEY, $outputFile);
        } catch (\Exception $ex) {
            $this->output->section();
            $this->output->writeln('<info>Failed getting object from S3: ' . $ex->getTraceAsString() . '</info>');
            $this->logger->error('Failed getting object from S3: ' . $ex->getMessage(), [
                $ex->getMessage(),
                $ex->getTraceAsString()
            ]);

            return $transport->withStatus(new Status('s3-download', $ex->getMessage(), true));
        }
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    private function getObjectUrl(Result $data): string
    {
        return (string)$data->get('ObjectURL');
    }

    private function getFileName(EnvironmentInterface $environment): string
    {
        $replace = str_replace('{{environment}}', '-' . $environment->getName(), $this->getFileKey());
        return str_replace('{{date}}', date('YmdHis'), $replace);
    }

    private function getFileKey(): string
    {
        if ($this->compressOutput()) {
            return $this->configuration->getNodeString('connections/s3/compressed-file-key');
        } else {
            return $this->configuration->getNodeString('connections/s3/uncompressed-file-key');
        }
    }

    private function compressOutput(): bool
    {
        return (bool)$this->configuration->getNode('configuration/compress-output') === true;
    }

    private function getBucket(): string
    {
        return $this->configuration->getNodeString('connections/s3/bucket');
    }

    private function getDirectory(): string
    {
        $directory = (string)$this->configuration->getNode('connections/s3/directory');
        if ($directory) {
            $directory .= '/';
        }

        return $directory;
    }

    private function getS3Client(): S3Client
    {
        return new S3Client($this->getAwsParameters());
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    private function getAwsParameters(): array
    {
        return [
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
    }
}
