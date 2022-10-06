<?php

declare(strict_types=1);

namespace Driver\Engines\S3;

use Aws\Result;
use Aws\S3\S3Client;
use Driver\Commands\CommandInterface;
use Driver\Pipeline\Environment\EnvironmentInterface;
use Driver\Pipeline\Transport\Status;
use Driver\Pipeline\Transport\TransportInterface;
use Driver\System\Configuration;
use Driver\System\Logs\LoggerInterface;
use Driver\System\S3FilenameFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Upload extends Command implements CommandInterface
{
    private Configuration $configuration;
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
    private array $properties;
    private LoggerInterface $logger;
    private ConsoleOutput $output;
    private S3FilenameFormatter $s3FilenameFormatter;
    private EventDispatcher $eventDispatcher;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function __construct(
        Configuration $configuration,
        LoggerInterface $logger,
        ConsoleOutput $output,
        S3FilenameFormatter $s3FilenameFormatter,
        EventDispatcher $eventDispatcher,
        array $properties = []
    ) {
        $this->configuration = $configuration;
        $this->properties = $properties;
        $this->logger = $logger;
        $this->output = $output;
        $this->s3FilenameFormatter = $s3FilenameFormatter;

        parent::__construct('s3-upload');
        $this->eventDispatcher = $eventDispatcher;
    }

    public function go(TransportInterface $transport, EnvironmentInterface $environment): TransportInterface
    {
        $this->eventDispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleCommandEvent $event): void {
            $this->output->writeln('Cancel registered!');
        });

        try {
            $filename = $this->s3FilenameFormatter->execute($environment, $this->getFileKey());

            $transport->getLogger()->notice(
                sprintf("Beginning file upload to: s3://%s/%s", $this->getBucket(), $filename)
            );

            $this->output->writeln(
                sprintf("<comment>Beginning file upload to: s3://%s/%s</comment>", $this->getBucket(), $filename)
            );

            $client = $this->getS3Client();
            $output = $client->putObject([
                'Bucket' => $this->getBucket(),
                'Key' => $this->getDirectory() . $filename,
                'SourceFile' => $transport->getData($environment->getName() . '_completed_file'),
                'ContentType' => 'application/gzip'
            ]);

            $transport->getLogger()->notice(sprintf("Uploaded file to: s3://%s/%s", $this->getBucket(), $filename));
            $this->output->writeln(sprintf("<info>Uploaded file to: s3://%s/%s</info>", $this->getBucket(), $filename));

            return $transport->withNewData('s3_url', $this->getObjectUrl($output));
        } catch (\Exception $ex) {
            $this->logger->error('Failed putting object to S3: ' . $ex->getMessage(), [
                $ex->getMessage(),
                $ex->getTraceAsString(),
                $filename
            ]);

            $this->output->writeln(sprintf(
                '<error>Failed putting object to S3: %s\n%s\n%s</error>',
                $ex->getMessage(),
                $ex->getTraceAsString(),
                $filename
            ));

            return $transport->withStatus(new Status('s3-upload', $ex->getMessage(), true));
        }
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
    public function getProperties(): array
    {
        return $this->properties;
    }

    private function getObjectUrl(Result $data): string
    {
        return $data->get('ObjectURL');
    }

    private function getFileKey(): string
    {
        if ($this->compressOutput()) {
            $fileKey = $this->configuration->getNodeString('connections/s3/compressed-file-key');
        } else {
            $fileKey = $this->configuration->getNodeString('connections/s3/uncompressed-file-key');
        }

        return $fileKey;
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
