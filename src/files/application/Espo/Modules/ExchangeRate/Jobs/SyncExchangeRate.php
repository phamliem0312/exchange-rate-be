<?php

namespace Espo\Modules\ExchangeRate\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Modules\ExchangeRate\Tools\SyncExchangeRate as SyncExchangeRateTool;

class SyncExchangeRate implements JobDataLess
{
    public function __construct(
        private SyncExchangeRateTool $syncExchangeRate,
    ) {}

    public function run(): void
    {
        $this->syncExchangeRate->sync();
    }
}