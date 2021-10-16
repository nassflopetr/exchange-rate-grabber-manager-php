<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabberManager\Storages;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;
use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateNotFoundException;

class RedisStorage implements Storage
{
    private \Redis $connection;

    public function __construct()
    {
        $connection = new \Redis();

        if (!$connection->connect(\getenv('REDIS_HOST'), (int) \getenv('REDIS_PORT'))) {
            throw new \Exception('Redis connection error.');
        }

        $password = \getenv('REDIS_PASSWORD');

        if ($password !== '' && !$connection->auth($password)) {
            throw new \Exception('Redis auth error.');
        }

        $this->connection = $connection;
    }

    public function getExchangeRate(ExchangeRate $exchangeRate): ExchangeRate
    {
        $preExchangeRate = $this->connection->get($this->getKey($exchangeRate));

        if (!\is_string($preExchangeRate)) {
            throw new ExchangeRateNotFoundException();
        }

        return \unserialize($preExchangeRate, ['allowed_classes' => [ExchangeRate::class]]);
    }

    public function createExchangeRate(ExchangeRate $exchangeRate): void
    {
        $key = $this->getKey($exchangeRate);

        if ($this->connection->exists($key) > 0) {
            throw new \Exception('Record already exists.');
        }

        if (!$this->connection->set($key, \serialize($exchangeRate))) {
            throw new \Exception('Creation record error.');
        }
    }

    public function updateExchangeRate(ExchangeRate $exchangeRate): void
    {
        $key = $this->getKey($exchangeRate);

        if ($this->connection->exists($key) === 0) {
            throw new ExchangeRateNotFoundException();
        }

        if (!$this->connection->set($key, \serialize($exchangeRate))) {
            throw new \Exception('Updating record error.');
        }
    }

    private function getKey(ExchangeRate $exchangeRate): string
    {
        return \sprintf(
            '%s:%s:%s',
            \get_class($exchangeRate->getGrabber()),
            $exchangeRate->getBaseCurrencyCode(),
            $exchangeRate->getDestinationCurrencyCode()
        );
    }
}
