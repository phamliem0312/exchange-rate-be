<?php

namespace Espo\Modules\ExchangeRate\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Controllers\Record;
use Espo\Core\Exceptions\BadRequest;
use stdClass;

class ExchangeRate extends Record
{
    public function getActionBestExchangeRate(Request $request): stdClass
    {
        $fromCurrency = $request->getQueryParam('fromCurrency');
        $toCurrency = $request->getQueryParam('toCurrency');

        if (!$fromCurrency || !$toCurrency) {
            throw new BadRequest('Currency is required');
        }

        $exchangeRate = $this->getRecordService()->getBestExchangeRate($fromCurrency, $toCurrency);

        return (object) [
            'exchangeRate' => $exchangeRate['rate'],
            'bankCode' => $exchangeRate['bankCode'],
        ];
    }

    public function getActionConvertExchangeRate(Request $request): float
    {
        $fromCurrency = $request->getQueryParam('fromCurrency');
        $toCurrency = $request->getQueryParam('toCurrency');
        $amount = $request->getQueryParam('amount');
        $bankCode = $request->getQueryParam('bankCode');

        if (!$fromCurrency) {
            throw new BadRequest('From currency is required');
        }

        if (!$bankCode) {
            throw new BadRequest('Bank code is required');
        }

        return $this->getRecordService()->convertExchangeRate($fromCurrency, $toCurrency, $amount, $bankCode);
    }

    public function getActionExchangeRateList(Request $request): stdClass
    {
        $fromCurrency = $request->getQueryParam('fromCurrency');
        $limit = $request->getQueryParam('limit');

        $list = $this->getRecordService()->exchangeRateList($fromCurrency, $limit);

        return (object) [
            'list' => $list,
            'total' => count($list),
        ];
    }
}