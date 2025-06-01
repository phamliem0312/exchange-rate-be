<?php

namespace Espo\Modules\ExchangeRate\Services;

use Carbon\Carbon;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\Service;
use Espo\Core\Utils\DateTime;
use PDO;

class ExchangeRate extends Service
{
    public function getBestExchangeRate(string $fromCurrency, string $toCurrency): array
    {
        $subQuery = $this->entityManager
            ->getQueryBuilder()
            ->select('MIN:(exchangeRate)')
            ->from('ExchangeRate')
            ->where([
                'fromCurrency' => $fromCurrency,
                'toCurrency' => $toCurrency,
                'exchangeRate>' => 0,
            ])
            ->build();

        $query = $this->entityManager
            ->getQueryBuilder()
            ->select(['creditInsCode', 'exchangeRate'])
            ->from('ExchangeRate')
            ->where([
                'exchangeRate=s' => $subQuery,
            ])
            ->build();

        $row = $this->entityManager->getQueryExecutor()->execute($query)->fetchObject();

        if (!$row || !isset($row->exchangeRate)) {
            throw new NotFound('No exchange rate found for the given currency');
        }

        return [
            'rate' => $row->exchangeRate,
            'bankCode' => $row->creditInsCode,
        ];
    }

    public function convertExchangeRate(string $fromCurrency, string $toCurrency, float $amount, string $bankCode): float
    {
        $query = $this->entityManager
            ->getQueryBuilder()
            ->select('exchangeRate')
            ->from('ExchangeRate')
            ->where([
                'fromCurrency' => $fromCurrency,
                'toCurrency' => $toCurrency,
                'creditInsCode' => $bankCode,
            ])
            ->build();

        $row = $this->entityManager->getQueryExecutor()->execute($query)->fetchObject();

        if (!$row || !isset($row->exchangeRate)) {
            throw new NotFound('No exchange rate found for the given currency');
        }

        return $row->exchangeRate * $amount;
    }

    public function exchangeRateList(string $currency, $limit): array
    {

        $query = $this->entityManager
            ->getQueryBuilder()
            ->select(['creditInsCode', 'exchangeRate', 'creditInsName', 'createdAt'])
            ->from('ExchangeRate')
            ->where([
                'fromCurrency' => $currency,
                'exchangeRate>' => 0
            ])
            ->order('exchangeRate', 'ASC')
            ->limit(0, (int) $limit)
            ->build();

        $rows = $this->entityManager->getQueryExecutor()->execute($query)->fetchAll(PDO::FETCH_ASSOC);

        $fee = 0;

        return array_map(function ($row) use($fee){
            return [
                'name' => $row['creditInsName'],
                'rate' => $row['exchangeRate'],
                'fee' => $fee,
                'received' => $row['exchangeRate'] - $fee,
                'updatedAt' => Carbon::parse($row['createdAt'])->addHours(7)->format(DateTime::SYSTEM_DATE_TIME_FORMAT)
            ];
        }, $rows);
    }
}