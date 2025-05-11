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
        $currency = $request->getQueryParam('currency');

        if (!$currency) {
            throw new BadRequest('Currency is required');
        }

        $exchangeRate = $this->getRecordService()->getBestExchangeRate($currency);

        return (object) [
            'exchangeRate' => $exchangeRate['rate'],
            'bankCode' => $exchangeRate['bankCode'],
        ];
    }

    public function getActionConvertExchangeRate(Request $request): float
    {
        $fromCurrency = $request->getQueryParam('fromCurrency');
        $amount = $request->getQueryParam('amount');
        $bankCode = $request->getQueryParam('bankCode');

        if (!$fromCurrency) {
            throw new BadRequest('From currency is required');
        }

        if (!$amount) {
            throw new BadRequest('amount is required');
        }

        if (!$bankCode) {
            throw new BadRequest('Bank code is required');
        }

        return $this->getRecordService()->convertExchangeRate($fromCurrency, $amount, $bankCode);
    }

    public function getActionExchangeRateList(Request $request): stdClass
    {
        $list = $this->getRecordService()->exchangeRateList();

        return (object) [
            'list' => $list,
            'total' => count($list),
        ];
    }
}