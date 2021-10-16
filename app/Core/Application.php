<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabberManager\Core;

use Psr\Log\LoggerInterface;
use NassFloPetr\ExchangeRateGrabber\Grabbers\Grabber;
use NassFloPetr\ExchangeRateGrabberManager\Storages\Storage;
use NassFloPetr\ExchangeRateGrabber\Observers\ExchangeRateObserver;

class Application
{
    private static ?Application $instance = null;

    private array $grabbers;
    private array $currencyCodes;
    private array $observers;
    private array $storages;
    private array $loggers;

    private array $instances;

    public function __construct(?string $configDirectory = null)
    {
        if (\is_null($configDirectory)) {
            $configDirectory = \dirname(__DIR__, 2) . \DIRECTORY_SEPARATOR . 'config';
        }

        if (!\is_dir($configDirectory)) {
            throw new \Exception('Invalid configuration directory.');
        }

        $this->setGrabbers($configDirectory);
        $this->setCurrencyCodes($configDirectory);
        $this->setObservers($configDirectory);
        $this->setStorages($configDirectory);
        $this->setLoggers($configDirectory);

        $this->instances = [];

        self::$instance = $this;
    }

    public static function getInstance(): Application
    {
        if (\is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getGrabbersClassNames(): array
    {
        return \array_keys($this->grabbers);
    }

    public function getGrabberName(string $grabberClassName): string
    {
        if (
            !\class_exists($grabberClassName)
            || !\is_subclass_of($grabberClassName, Grabber::class)
            || !\array_key_exists($grabberClassName, $this->grabbers)
        ) {
            throw new \ValueError();
        }

        return $this->grabbers[$grabberClassName];
    }

    public function getObserversClassNames(): array
    {
        return $this->observers;
    }

    public function getCurrencyCodes(): array
    {
        return $this->currencyCodes;
    }

    public function getStorage(?string $storageChannel = null): Storage
    {
        if (\is_null($storageChannel)) {
            $storageChannel = \getenv('STORAGE_CHANNEL');
        }

        if (!\array_key_exists($storageChannel, $this->storages)) {
            throw new \ValueError();
        }

        return $this->getSingletonInstance($this->storages[$storageChannel]);
    }

    public function getLogger(?string $loggerChannel = null): LoggerInterface
    {
        if (\is_null($loggerChannel)) {
            $loggerChannel = \getenv('LOGGER_CHANNEL');
        }

        if (!\array_key_exists($loggerChannel, $this->loggers)) {
            throw new \ValueError();
        }

        return $this->getSingletonInstance($this->loggers[$loggerChannel]);
    }

    private function getSingletonInstance(string $className): LoggerInterface | Storage
    {
        if (
            !\class_exists($className) ||
            (
                !\is_subclass_of($className, LoggerInterface::class)
                && !\is_subclass_of($className, Storage::class)
            )
        ) {
            throw new \ValueError();
        }

        if (!\array_key_exists($className, $this->instances)) {
            $this->instances[$className] = new $className;
        }

        return $this->instances[$className];
    }

    private function setGrabbers(string $configDirectory): void
    {
        $grabbers = require $configDirectory . \DIRECTORY_SEPARATOR . 'grabbers.php';

        if (!\is_array($grabbers)) {
            throw new \TypeError();
        }

        foreach ($grabbers as $grabberClassName => $grabberName) {
            if (!\is_string($grabberClassName) || !\is_string($grabberName)) {
                throw new \TypeError();
            }

            if (
                !\class_exists($grabberClassName)
                || !\is_subclass_of($grabberClassName, Grabber::class)
                || \preg_match('/[^A-Za-zА-ЩЬЮЯҐЄІЇЙИа-щьюяґєіїйи\-_.\s\']$/', $grabberName)
            ) {
                throw new \ValueError();
            }
        }

        $this->grabbers = $grabbers;
    }

    private function setCurrencyCodes(string $configDirectory): void
    {
        $currencyCodes = require $configDirectory . \DIRECTORY_SEPARATOR . 'currency_codes.php';

        if (!\is_array($currencyCodes)) {
            throw new \TypeError();
        }

        foreach ($currencyCodes as $currencyCode) {
            if (!\is_array($currencyCode)) {
                throw new \TypeError();
            }

            if (
                \count($currencyCode) !== 2
                || !\preg_match('/^[A-Z]{3}$/', $currencyCode[0])
                || !\preg_match('/^[A-Z]{3}$/', $currencyCode[1])
            ) {
                throw new \ValueError();
            }
        }

        $this->currencyCodes = $currencyCodes;
    }

    private function setObservers(string $configDirectory): void
    {
        $observers = require $configDirectory . \DIRECTORY_SEPARATOR . 'observers.php';

        if (!\is_array($observers)) {
            throw new \TypeError();
        }

        foreach ($observers as $observerClassName) {
            if (!\is_string($observerClassName)) {
                throw new \TypeError();
            }

            if (
                !\class_exists($observerClassName)
                || !\is_subclass_of($observerClassName, ExchangeRateObserver::class)
            ) {
                throw new \ValueError();
            }
        }

        $this->observers = $observers;
    }

    private function setStorages(string $configDirectory): void
    {
        $storages = require $configDirectory . \DIRECTORY_SEPARATOR . 'storages.php';

        if (!\is_array($storages)) {
            throw new \TypeError();
        }

        foreach ($storages as $storageChannel => $storageClassName) {
            if (!\is_string($storageChannel) || !\is_string($storageClassName)) {
                throw new \TypeError();
            }

            if (
                \preg_match('/[^A-Za-z0-9\-_.\']$/', $storageChannel)
                || !\class_exists($storageClassName)
                || !\is_subclass_of($storageClassName, Storage::class)
            ) {
                throw new \ValueError();
            }
        }

        $this->storages = $storages;
    }

    private function setLoggers(string $configDirectory): void
    {
        $loggers = require $configDirectory . \DIRECTORY_SEPARATOR . 'loggers.php';

        if (!\is_array($loggers)) {
            throw new \TypeError();
        }

        foreach ($loggers as $loggerChannel => $loggerClassName) {
            if (!\is_string($loggerChannel) || !\is_string($loggerClassName)) {
                throw new \TypeError();
            }

            if (
                \preg_match('/[^A-Za-z0-9\-_.\']$/', $loggerChannel)
                || !\class_exists($loggerClassName)
                || !\is_subclass_of($loggerClassName, LoggerInterface::class)
            ) {
                throw new \ValueError();
            }
        }

        $this->loggers = $loggers;
    }
}
