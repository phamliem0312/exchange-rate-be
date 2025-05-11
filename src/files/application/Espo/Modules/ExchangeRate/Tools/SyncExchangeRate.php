<?php

namespace Espo\Modules\ExchangeRate\Tools;

use Carbon\Carbon;
use Espo\ORM\EntityManager;

class SyncExchangeRate
{
    const BANK_LIST = [
            'Vietcombank',
            'Vietinbank',
            'BIDV',
            'Agribank',
            'Techcombank',
            'VPbank',
            'MBbank',
            'TPbank',
            'ACB',
            'VIB'
        ];

    const CURRENCY_LIST = [
        'USD', 'EUR', 'GBP', 'JPY', 'AUD',
        'NZD', 'SGD', 'THB', 'CAD', 'CHF',
        'HKD', 'CNY', 'KRW'
    ];

    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function sync(): void
    {
        foreach (self::BANK_LIST as $bank) {
            $method = 'get' . $bank . 'ExchangeRate';

            if (method_exists($this, $method)) {
                $exchangeRateList = $this->$method();

                foreach ($exchangeRateList as $exchangeRate) {
                    $this->saveExchangeRate($bank, $exchangeRate);
                }
            }
        }
    }

    public function getVietcombankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('Y-m-d');
        $url = "https://vietcombank.com.vn/api/exchangerates?date=$date";
        
        $response = $this->fetch($url);

        $exchangeRateList = $response['Data'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['currencyCode'], self::CURRENCY_LIST)) {
                $data[] = [
                    'rate' => (float) $exchangeRate['transfer'],
                    'fromCurrency' => $exchangeRate['currencyCode'],
                    'toCurrency' => 'VND',
                    'bankcode' => 'VCB',
                ];
            }
        }

        return $data;
    }

    private function saveExchangeRate(string $bank, array $exchangeRate): void
    {
        $bankCode = $exchangeRate['bankcode'];
        $fromCurrency = $exchangeRate['fromCurrency'];

        $this->entityManager->getSqlExecutor()->execute("DELETE FROM exchange_rate WHERE credit_ins_code='$bankCode' AND from_currency='$fromCurrency';");

        $data = [
            'name' => $bank . ' - ' . $fromCurrency,
            'creditInsCode' => $bankCode,
            'creditInsName' => $bank,
            'exchangeRate' => $exchangeRate['rate'],
            'fromCurrency' => $exchangeRate['fromCurrency'],
            'toCurrency' => $exchangeRate['toCurrency'] ?? 'VND',
        ];

        $this->entityManager->createEntity('ExchangeRate', $data);
    }

    private function fetch(string $url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        
        curl_close($ch);

        return json_decode($response, true);
    }
}