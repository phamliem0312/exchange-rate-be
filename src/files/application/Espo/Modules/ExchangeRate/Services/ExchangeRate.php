<?php

namespace Espo\Modules\ExchangeRate\Services;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\Service;
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

    public function exchangeRateList(): array
    {

        $query = $this->entityManager
            ->getQueryBuilder()
            ->select(['creditInsCode', 'exchangeRate'])
            ->from('ExchangeRate')
            ->order('exchangeRate', 'ASC')
            ->build();

        $rows = $this->entityManager->getQueryExecutor()->execute($query)->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'bankCode' => $row['creditInsCode'],
                'exchangeRate' => $row['exchangeRate'],
            ];
        }, $rows);
    }
}