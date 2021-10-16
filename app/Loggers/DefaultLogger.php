<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabberManager\Loggers;

use Psr\Log\LogLevel;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;

class DefaultLogger extends AbstractLogger
{
    private string $logFilePath;

    public function __construct(?string $logFilePath = null)
    {
        if (\is_null($logFilePath)) {
            $logFilePath = \getenv('LOGGER_FILE_PATH');
        }

        $path = \realpath(\pathinfo($logFilePath, \PATHINFO_DIRNAME));

        if (!\is_readable($path)) {
            throw new \Exception(\sprintf('%s log directory protected for read.', $path));
        }

        if (!\is_writable($path)) {
            throw new \Exception(\sprintf('%s log directory protected for write.', $path));
        }

        $logFilePath = $path . \DIRECTORY_SEPARATOR . \pathinfo($logFilePath, \PATHINFO_BASENAME);

        if (
            !\in_array(
                \pathinfo($logFilePath, \PATHINFO_EXTENSION),
                [
                    'log',
                ]
            )
        ) {
            throw new \Exception(\sprintf('%s file extension is not allowed.', $logFilePath));
        }

        if (
            \file_exists($logFilePath)
            && (!\is_readable($logFilePath) || !\is_writable($logFilePath))
        ) {
            throw new \Exception(\sprintf('%s file is protected for read or write.', $logFilePath));
        }

        $this->logFilePath = $logFilePath;
    }

    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        if (
            !\in_array(
                $level,
                [
                    LogLevel::EMERGENCY,
                    LogLevel::ALERT,
                    LogLevel::CRITICAL,
                    LogLevel::ERROR,
                    LogLevel::WARNING,
                    LogLevel::NOTICE,
                    LogLevel::INFO,
                    LogLevel::DEBUG,
                ]
            )
        ) {
            throw new InvalidArgumentException(\sprintf('%s log level not found.', $level));
        }

        $fileExists = \file_exists($this->logFilePath);

        $handle = \fopen($this->logFilePath, $fileExists ? 'a' : 'w');

        \fwrite($handle, ($fileExists ? PHP_EOL : '') . $this->getLogMessage($level, $message, $context));

        \fclose($handle);
    }

    private function getLogMessage(string $level, string $message, array $context): string
    {
        return '[' . (new \DateTime())->format('d.m.Y H:i:s') . '] '
            . \strtoupper($level) . ': ' . $message
            . (!empty($context) ? (' ' . \json_encode($context, \JSON_THROW_ON_ERROR)) : '');
    }
}
