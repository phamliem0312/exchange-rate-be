<?php

namespace Espo\Modules\ExchangeRate\Controllers;

use Espo\Modules\ExchangeRate\Tools\SyncExchangeRate;

class Test
{
    public function __construct(
        private SyncExchangeRate $syncExchangeRate,
    ) {}

    public function getActionExchangeRate()
    {
        return $this->syncExchangeRate->getVietcombankExchangeRate();
    }
}