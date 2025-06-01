<?php

namespace Espo\Modules\ExchangeRate\Controllers;

use Espo\Core\Api\Request;
use Espo\Modules\ExchangeRate\Tools\SyncExchangeRate;

class Test
{
    public function __construct(
        private SyncExchangeRate $syncExchangeRate,
    ) {}

    public function getActionExchangeRate(Request $request): array
    {
        $bank = $request->getQueryParam('bank');
        return $this->syncExchangeRate->getBankExchangeRate($bank);
    }

    public function getActionRunJob(Request $request)
    {
        $this->syncExchangeRate->sync();

        return [
            'success' => true,
        ];
    }
}