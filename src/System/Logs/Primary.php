<?php

declare(strict_types=1);

namespace Driver\System\Logs;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Psr\Log\LogLevel;

class Primary implements LoggerInterface
{
    /** @var array<int, string> */
    private array $logLevelMap = [
        1 => LogLevel::DEBUG,
        2 => LogLevel::INFO,
        5 => LogLevel::NOTICE,
        8 => LogLevel::WARNING,
        9 => LogLevel::ERROR,
        10 => LogLevel::CRITICAL
    ];

    private ?ConsoleLogger $consoleLogger = null;
    private ?InputInterface $input = null;
    private ?OutputInterface $output = null;

    private function getConsoleLogger(): ConsoleLogger
    {
        if (!$this->consoleLogger && $this->input && $this->output) {
            $this->consoleLogger = new ConsoleLogger($this->output, [], []);
        }

        return $this->consoleLogger;
    }

    public function setParams(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function emergency($message, array $context = array()): void
    {
        $this->log(10, $message);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function alert($message, array $context = array()): void
    {
        $this->log(10, $message);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function critical($message, array $context = array()): void
    {
        $this->log(10, $message);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function error($message, array $context = array()): void
    {
        $this->log(9, $message);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function warning($message, array $context = array()): void
    {
        $this->log(8, $message);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function notice($message, array $context = array()): void
    {
        $this->log(5, $message);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function info($message, array $context = array()): void
    {
        $this->log(2, $message);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function debug($message, array $context = array()): void
    {
        $this->log(1, $message);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    public function log($level, $message, array $context = array()): void
    {
        $message = date('m/d/y H:i:s') . ' ' . $message;

        if (!(array_key_exists($level, $this->logLevelMap))) {
            return;
        }

        /** @var string $levelKey */
        $logVerbosityLevel = $this->logLevelMap[$level];

        $this->getConsoleLogger()->log($logVerbosityLevel, $message);
    }
}
