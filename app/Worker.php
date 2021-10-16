<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabberManager;

use NassFloPetr\ExchangeRateGrabberManager\Core\Application;
use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateNotFoundException;

class Worker
{
    private Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function __invoke()
    {
        $logger = $this->application->getLogger();

        try {
            $storage = $this->application->getStorage();
            $grabbersClassNames = $this->application->getGrabbersClassNames();
            $currencyCodes = $this->application->getCurrencyCodes();
            $observersClassNames = $this->application->getObserversClassNames();

            $requests = [];

            $mh = \curl_multi_init();

            if (!$mh) {
                throw new \Exception(\sprintf('Can\'t create %s instance.', \CurlMultiHandle::class));
            }

            foreach ($grabbersClassNames as $key => $grabberClassName) {
                try {
                    $grabber = new $grabberClassName;
                    $ch = $grabber->getCurlHandle();

                    $requests[$key] = ['grabber' => $grabber, 'handle' => $ch];

                    \curl_multi_add_handle($mh, $requests[$key]['handle']);
                } catch (\Throwable $e) {
                    $logger->error($e->getMessage(), $e->getTrace());
                }
            }

            do {
                $status = \curl_multi_exec($mh, $active);

                if ($active > 0) {
                    \curl_multi_select($mh);
                }
            } while ($active > 0 && $status == \CURLM_OK);

            if ($status !== \CURLM_OK) {
                throw new \Exception(\curl_multi_strerror($status));
            }

            while (($curl_info = \curl_multi_info_read($mh)) !== false) {
                //
            }

            foreach ($requests as $request) {
                try {
                    $response = \curl_multi_getcontent($request['handle']);

                    \curl_multi_remove_handle($mh, $request['handle']);

                    if (!$response || \curl_errno($request['handle']) !== \CURLE_OK) {
                        throw new \Exception(
                            \sprintf(
                                'Open %s stream failed. %s.',
                                \curl_getinfo($request['handle'], \CURLINFO_EFFECTIVE_URL),
                                \curl_strerror(\curl_errno($request['handle']))
                            )
                        );
                    }

                    if (\curl_getinfo($request['handle'], \CURLINFO_RESPONSE_CODE) !== 200) {
                        throw new \Exception(
                            \sprintf(
                                'Open %s stream failed. Response code %d.',
                                \curl_getinfo($request['handle'], \CURLINFO_EFFECTIVE_URL),
                                \curl_getinfo($request['handle'], \CURLINFO_HTTP_CODE)
                            )
                        );
                    }

                    $logger->debug(
                        \sprintf(
                            'Open %s stream success. Response code %d. Grabber: %s',
                            \curl_getinfo($request['handle'], \CURLINFO_EFFECTIVE_URL),
                            \curl_getinfo($request['handle'], \CURLINFO_HTTP_CODE),
                            \get_class($request['grabber'])
                        )
                    );

                    foreach ($currencyCodes as $currencyCode) {
                        try {
                            $exchangeRate = $request['grabber']->getExchangeRate(
                                $currencyCode[0],
                                $currencyCode[1],
                                $response
                            );
                        } catch (ExchangeRateNotFoundException $e) {
                            $logger->warning($e->getMessage(), $e->getTrace());

                            continue;
                        } catch (\Throwable $e) {
                            $logger->error($e->getMessage(), $e->getTrace());

                            continue;
                        }

                        try {
                            $preExchangeRate = $storage->getExchangeRate($exchangeRate);

                            $preExchangeRate->attachObservers($observersClassNames);

                            $preExchangeRate->updateExchangeRate(
                                $exchangeRate->getBuyRate(),
                                $exchangeRate->getSaleRate(),
                                $exchangeRate->getTimestamp()
                            );

                            $preExchangeRate->detachObservers();

                            $storage->updateExchangeRate($preExchangeRate);
                        } catch (ExchangeRateNotFoundException $e) {
                            $exchangeRate->attachObservers($observersClassNames);

                            $exchangeRate->notifyExchangeRateCreated();

                            $exchangeRate->detachObservers();

                            $storage->createExchangeRate($exchangeRate);
                        } catch (\Throwable $e) {
                            $logger->error($e->getMessage(), $e->getTrace());
                        }
                    }
                } catch (\Throwable $e) {
                    $logger->error($e->getMessage(), $e->getTrace());
                }
            }
        } catch (\Throwable $e) {
            $logger->critical($e->getMessage(), $e->getTrace());
        }
    }
}
