<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabberManager\Observers;

use Psr\Log\LoggerInterface;
use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;
use NassFloPetr\ExchangeRateGrabberManager\Core\Application;
use NassFloPetr\ExchangeRateGrabber\Observers\ExchangeRateObserver;

class LoggingExchangeRate implements ExchangeRateObserver
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = (new Application())->getLogger();
    }

    public function exchangeRateCreated(ExchangeRate $exchangeRate): void
    {
        $this->logger->debug(
            'Exchange rate was successfully created.',
            ['exchange_rate' => \serialize($exchangeRate)]
        );
    }

    public function exchangeRateUpdated(ExchangeRate $preExchangeRate, ExchangeRate $latestExchangeRate): void
    {
        $this->logger->debug(
            'Exchange rate was successfully updated.',
            [
                'pre_exchange_rate' => \serialize($preExchangeRate),
                'latest_exchange_rate' => \serialize($latestExchangeRate)
            ]
        );
    }

    public function exchangeRateChanged(ExchangeRate $preExchangeRate, ExchangeRate $latestExchangeRate): void
    {
        $this->logger->debug(
            'Exchange rate was successfully changed.',
            [
                'pre_exchange_rate' => \serialize($preExchangeRate),
                'latest_exchange_rate' => \serialize($latestExchangeRate)
            ]
        );
    }
}
