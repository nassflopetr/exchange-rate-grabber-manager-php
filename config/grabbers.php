<?php

declare(strict_types = 1);

return [
    \NassFloPetr\ExchangeRateGrabber\Grabbers\NbuDOMDocumentGrabber::class => 'Національний банк України',
    \NassFloPetr\ExchangeRateGrabber\Grabbers\PrivatBankJSONGrabber::class => 'ПриватБанк',
    \NassFloPetr\ExchangeRateGrabber\Grabbers\OschadBankDOMDocumentGrabber::class => 'ОщадБанк',
    \NassFloPetr\ExchangeRateGrabber\Grabbers\UkrGasBankDOMDocumentGrabber::class => 'УКРГАЗБАНК',
    \NassFloPetr\ExchangeRateGrabber\Grabbers\UkrSibBankDOMDocumentGrabber::class => 'УкрСибБанк',
];
