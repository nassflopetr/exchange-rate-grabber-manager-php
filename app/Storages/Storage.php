<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabberManager\Storages;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;

interface Storage
{
    public function getExchangeRate(ExchangeRate $exchangeRate): ExchangeRate;

    public function createExchangeRate(ExchangeRate $exchangeRate): void;

    public function updateExchangeRate(ExchangeRate $exchangeRate): void;
}
