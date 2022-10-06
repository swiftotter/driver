<?php

declare(strict_types=1);

namespace Driver\System\Logs;

use Psr\Log\LoggerInterface as BaseLoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface LoggerInterface extends BaseLoggerInterface
{
    public function setParams(InputInterface $input, OutputInterface $output): void;
}
