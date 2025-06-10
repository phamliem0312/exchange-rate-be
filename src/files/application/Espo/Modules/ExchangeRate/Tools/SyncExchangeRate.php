<?php

namespace Espo\Modules\ExchangeRate\Tools;

use Carbon\Carbon;
use Espo\ORM\EntityManager;

class SyncExchangeRate
{
    const BANK_LIST = [
        'Vietcombank',
        'Agribank',
        'Techcombank',
        'VPbank',
        'MBbank',
        'TPbank',
        'ACB',
        'ABbank',
        'Eximbank',
        'LPbank',
        'MSB',
        'PublicBank',
        'UOB',
        'Vietbank',
        'NamAbank',
        'NCB',
        'PVCombank',
        'SCB',
        'SHB',
        'VietAbank',
        'Vietcapitalbank',
        'Kienlongbank',
        'BIDV',
        'Vietinbank',
        'GPbank',
        'HSBC',
        'PGbank',
        'VRbank',
        'Baovietbank',
        'HongLeongbank',
        'Indovinabank',
    ];

    const CURRENCY_LIST = [
        'USD',
        'EUR',
        'GBP',
        'JPY',
        'AUD',
        'NZD',
        'SGD',
        'THB',
        'CAD',
        'CHF',
        'HKD',
        'CNY',
        'KRW'
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

    public function getBankExchangeRate($bank): array
    {
        $method = 'get' . $bank . 'ExchangeRate';

        $exchangeRateList = [];

        if (method_exists($this, $method)) {
            $exchangeRateList = $this->$method();
        }

        return $exchangeRateList;
    }

    public function getVietcombankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('Y-m-d');
        $url = "https://vietcombank.com.vn/api/exchangerates?date=$date";

        $response = $this->fetch($url);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['Data'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['currencyCode'], self::CURRENCY_LIST)) {
                $data[] = [
                    'rate' => (float) $exchangeRate['sell'],
                    'fromCurrency' => $exchangeRate['currencyCode'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'VCB',
                ];
            }
        }

        return $data;
    }

    public function getBIDVExchangeRate(): array
    {
        $url = "https://bidv.com.vn/ServicesBIDV/ExchangeDetailServlet";

        $response = $this->fetch($url, 'POST', [], [
            'contentType' => 'application/x-www-form-urlencoded',
        ]);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['data'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['currency'], self::CURRENCY_LIST)) {
                $rate = floatval(str_replace(',', '', $exchangeRate['banCk']));
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['currency'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'BIDV',
                ];
            }
        }

        return $data;
    }

    public function getTechcombankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('Y-m-d');
        $url = "https://techcombank.com/content/techcombank/web/vn/vi/cong-cu-tien-ich/ty-gia/_jcr_content.exchange-rates.$date.integration.json";

        $response = $this->fetch($url);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['exchangeRate']["data"] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['sourceCurrency'], self::CURRENCY_LIST)) {
                $data[] = [
                    'rate' => (float) $exchangeRate['askRate'],
                    'fromCurrency' => $exchangeRate['sourceCurrency'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'TCB',
                ];
            }
        }

        return $data;
    }

    public function getTPbankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('Ymd');
        $url = "https://tpb.vn/CMCWPCoreAPI/api/public-service/get-currency-rate-core";

        $response = $this->fetch($url, 'POST', [
            "type" => "0",
            "RATE_DATE" => $date,
            "token" => "lpk82Q6sgFVDbnNcglA0_6g",
        ], [
            'token' => 'Y21jY29yZV9hcGk6VkZCQ1VHOXlkR0ZzTWpBeU13PT0=',
        ]);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['DATA'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['CCY'], self::CURRENCY_LIST)) {
                $rate = floatval(str_replace(',', '', $exchangeRate['TRANSFER_SELL_RATE']));
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['CCY'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'TPB',
                ];
            }
        }

        return $data;
    }

    public function getVPBankExchangeRate(): array
    {
        $url = "https://www.vpbank.com.vn/api/formula/getforeignexchange?date=";

        $response = $this->fetch($url);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['datas']['ForeignExchangeFrom'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['CurrencyType'], self::CURRENCY_LIST)) {
                $rate = $exchangeRate['Sell'];
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['CurrencyType'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'VPB',
                ];
            }
        }

        return $data;
    }

    public function getMBBankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('Y-m-d');

        $url = "https://www.mbbank.com.vn/api/getExchangeRate/$date";

        $headers = [
            'accept: application/json, text/plain, */*',
            'accept-language: en-US,en;q=0.9,vi;q=0.8,en-GB;q=0.7',
            'cache-control: no-cache',
            'mb-xsrf-token-formonline: SDw4ydpejtJfrL1LSQJuTtAd_89aH15ztglmLACtEGYYYxVy4_KmdycEk3K-VyAMm2gNbaVscBFxFFGxiGc6Ca2qjPQ__dDtDrXarTHRTDE1',
            'pragma: no-cache',
            'priority: u=1, i',
            'referer: https://www.mbbank.com.vn/ExchangeRate',
            'sec-ch-ua: "Chromium";v="134", "Not:A-Brand";v="24", "Microsoft Edge";v="134"',
            'sec-ch-ua-mobile: ?1',
            'sec-ch-ua-platform: "Android"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Mobile Safari/537.36 Edg/134.0.0.0',
        ];

        $cookie = 'ASP.NET_SessionId=cnc2opogrmeym5u3uq3ko5rh; LANG_CODE=VI; __RequestVerificationToken=hhUQEe4ij1vjNuT7Sre0gEykRwq3Qf4935J8yyAO0RCF3534NVdOEULsI19xjefwinPYeq_b008XA8BQEZ_4HqcWP70qjcDNC7V-gm4H0hM1; _gcl_au=1.1.820573670.1740473717; _ga=GA1.1.335319039.1740473717; alias_current=; f5_cspm=1234; f5avraaaaaaaaaaaaaaaa_session_=MDOJNKDAKMBLHKILIFAJGLPMJFNKCDCPNKKPHCLAFOAFPIMKLMELIOGCAKICLLGHKLEDDPOODEAGADDNHBCAGCJIOJMAJGNPPIIPNKGBNHCKLPHBLCGOCOLBFJPJCNJG; _ga_R3XMN343KH=GS1.1.1742968110.4.0.1742968110.60.0.0; RT="z=1&dm=www.mbbank.com.vn&si=b0d9ffe7-dfb6-48f7-9298-fbe823bdfb2e&ss=m8pi7l03&sl=1&tt=1ct&rl=1&ld=1cw&nu=4vbqfrz5&cl=6yu"';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $GLOBALS['log']->info('Lỗi cURL: ' . curl_error($ch));

            return [];
        }

        curl_close($ch);

        $response = json_decode($response, true);

        $exchangeRateList = $response['lst'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['currencyCode'], self::CURRENCY_LIST)) {
                $rate = $exchangeRate['sell_bank_transfer'];
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['currencyCode'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'MB',
                ];
            }
        }

        return $data;
    }

    public function getVietinbankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('Y-m-d');
        $applyDateResponse = $this->fetch("https://www.vietinbank.vn/ca-nhan/ty-gia-khcn", 'POST', json_encode([$date]), [
            'contentType' => 'text/plain;charset=UTF-8',
            'next-action' => 'e4d951bbcfdef7e590919ae33e39d2a200f3d9e7',
            'user-agent' => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Mobile Safari/537.36 Edg/134.0.0.0'
        ]);
        preg_match_all('/"apply_date":"([^"]+)"/', $applyDateResponse, $matches);
        $applyDate = $matches[1][0] ?? null;
        $url = 'https://www.vietinbank.vn/ca-nhan/ty-gia-khcn';

        // Payload bạn đã mô tả
        $data = json_encode([
            $applyDate,
            [
                "USD",
                "EUR",
                "JPY",
                "GBP",
                "AUD",
                "CAD",
                "CHF",
                "CNY",
                "DKK",
                "HKD",
                "KRW",
                "LAK",
                "NOK",
                "NZD",
                "SEK",
                "SGD",
                "THB",
                "SAR",
                "KWD"
            ]
        ]);

        $response = $this->fetch($url, 'POST', $data, [
            'contentType' => 'text/plain;charset=UTF-8',
            'next-action' => '1e43a43a5124d6cc3cb463bc54021b34f39a4065',
            'user-agent' => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Mobile Safari/537.36 Edg/134.0.0.0'
        ]);

        if (!$response) {
            return [];
        }

        $exchangeRateList = json_decode('[{' . explode('1:[{', $response)[1]) ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            $exchangeRate = (array) $exchangeRate;
            if (in_array($exchangeRate['currency_code'], self::CURRENCY_LIST)) {
                $rate = $exchangeRate['sell_rate'];
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['currency_code'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'Vietinbank',
                ];
            }
        }

        return $data;
    }

    public function getACBExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('Y-m-dTH:i:s');
        $url = "https://acb.com.vn/api/front/v1/currency?currency=VND&effectiveDateTime=$date";

        $response = $this->fetch($url);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (
                in_array($exchangeRate['exchangeCurrency'], self::CURRENCY_LIST)
                && $exchangeRate['dealType'] == 'ASK'
                && $exchangeRate['instrumentType'] == 'TRANSFER'
            ) {
                $rate = $exchangeRate['exchangeRate'];
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['exchangeCurrency'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'ACB',
                ];
            }
        }

        return $data;
    }

    public function getABbankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('d/m/Y');
        $url = "https://abbank.vn/thong-tin/ty-gia-ngoai-te-abbank.html/ajax/exchange-rate-currency-detail";

        $response = $this->fetch($url, 'POST', "date=$date", [
            'contentType' => 'application/x-www-form-urlencoded',
        ]);

        if (!$response) {
            return [];
        }

        $html = $response['message']['result'] ?? [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        $rows = $xpath->query('//tbody[@id="table-exchange-rate"]/tr');

        $exchangeRateList = [];

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length >= 5) {
                $currency = trim($cells->item(0)->textContent);
                $sellTransfer = str_replace(',', '', trim($cells->item(3)->textContent));

                if (strpos($currency, '(>50$)') != false) {
                    $exchangeRateList[] = [
                        'currency' => 'USD',
                        'exchangeRate' => (float) str_replace(',', '', $sellTransfer),
                    ];
                } else {
                    $exchangeRateList[] = [
                        'currency' => $currency,
                        'exchangeRate' => (float) str_replace(',', '', $sellTransfer),
                    ];
                }
            }
        }

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['currency'], self::CURRENCY_LIST)) {
                $rate =  $exchangeRate['exchangeRate'];
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['currency'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'ABB',
                ];
            }
        }

        return $data;
    }

    public function getEximbankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('Ymd');
        $quoteUrl = "https://eximbank.com.vn/api/front/v1/quote-count-list?strNoticeday=$date&strBRCD=1000";

        $quoteResponse = $this->fetch($quoteUrl);

        if (!$quoteResponse) {
            return [];
        }

        $quote = $quoteResponse[0]['QUOTECNT'];

        $url = "https://eximbank.com.vn/api/front/v1/exchange-rate?strNoticeday=$date&strBRCD=1000&strQuoteCNT=$quote";
        
        $response = $this->fetch($url);
        
        if (!$response) {
            return [];
        }

        $exchangeRateList = $response ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['CCYCD'], self::CURRENCY_LIST)) {
                $rate = (float) str_replace(',', '', $exchangeRate['TTSELLRT']);
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['CCYCD'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'Eximbank',
                ];
            }
        }

        return $data;
    }

    public function getLPbankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('d/m/Y');
        $url = "https://lpbank.com.vn/api/content-service/public/customer/exchange-rate?date=$date";

        $response = $this->fetch($url);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['data'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if ($exchangeRate['code'] == 'USD <50') {
                continue;
            }
            $currencyCode = substr($exchangeRate['code'], 0, 3);
            if (in_array($currencyCode, self::CURRENCY_LIST)) {
                $rate = $exchangeRate['sellCk'];
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $currencyCode,
                    'toCurrency' => 'VND',
                    'bankCode' => 'LPbank',
                ];
            }
        }

        return $data;
    }

    public function getBaovietbankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('d/m/Y');

        $url = "https://www.baovietbank.vn/AdTools/GetExChangeRate?dateTime=$date";

        $response = $this->fetch($url, 'GET', [], [
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Mobile Safari/537.36 Edg/134.0.0.0'
        ]);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['listCurr']['code'], self::CURRENCY_LIST)) {
                $rate = $exchangeRate['listEnq']['denomtranS_SELL_RATE'];
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['listCurr']['code'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'Baovietbank',
                ];
            }
        }

        return $data;
    }

    public function getMSBExchangeRate(): array
    {
        $latestDateTimeUrl = 'https://www.msb.com.vn/o/headless-ratecur/v1.0/latest-batch/1';

        $dateTimeResponse = $this->fetch($latestDateTimeUrl, 'GET', [], [
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Mobile Safari/537.36 Edg/134.0.0.0'
        ]);

        if (!$dateTimeResponse) {
            return [];
        }

        $datetime = $dateTimeResponse['items'][0]['dateTime'];

        $url = "https://www.msb.com.vn/o/headless-ratecur/v1.0/latest-currency?dateTime=$datetime";

        $response = $this->fetch($url, 'GET', [], [
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Mobile Safari/537.36 Edg/134.0.0.0'
        ]);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['items'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['currencyCode'], self::CURRENCY_LIST)) {
                $rate = $exchangeRate['sellTransferVND'];
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['currencyCode'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'MSB',
                ];
            }
        }

        return $data;
    }

    public function getPublicbankExchangeRate(): array
    {
        $url = "https://publicbank.com.vn/ToolsUtilities/GetListExchangeRateByDate";

        $response = $this->fetch($url, 'POST', [], [
            'contentType' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ]);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['data'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['ccycd'], self::CURRENCY_LIST)) {
                $rate = (float) str_replace('.', '', $exchangeRate['sellremittance']);
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['ccycd'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'Publicbank',
                ];
            }
        }

        return $data;
    }

    public function getPGbankExchangeRate(): array
    {
        $url = "https://www.pgbank.com.vn/api/v1/get-exchangerate?lang=vi";

        $response = $this->fetch($url, 'GET', [], [
            'Accept-Encoding' => 'gzip, deflate, br',
            'user-agent' => 'PostmanRuntime/7.44.0',
        ]);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['data'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['Ccy'], self::CURRENCY_LIST)) {
                $rate = $exchangeRate['SaleRate'];
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['Ccy'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'PGbank',
                ];
            }
        }

        return $data;
    }


    public function getUOBExchangeRate(): array
    {
        $url = "https://www.uob.com.vn/data-api-rates-vn/data-api/forex-vn?lang=en_VN";

        $response = $this->fetch($url);
        
        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['types'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['code'], self::CURRENCY_LIST)) {
                $rate = (float) str_replace(',', '', $exchangeRate['bankSell']);
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['code'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'UOB',
                ];
            }
        }

        return $data;
    }

    public function getVietbankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('m/d/Y');
        $maxCountTime = $this->fetch("https://www.vietbank.com.vn/api/ApiSupport/getcountupdatecurrencywheredate?date=$date");
        $url = "https://www.vietbank.com.vn/api/ApiSupport/getfiltercurrency?date=$date&counttime=$maxCountTime";

        $response = $this->fetch($url);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['currencyCode'], self::CURRENCY_LIST)) {
                $rate = (float) str_replace(',', '', $exchangeRate['saleTransfer']);
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['currencyCode'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'Vietbank',
                ];
            }
        }

        return $data;
    }

    public function getNamAbankExchangeRate(): array
    {
        $url = "https://www.namabank.com.vn/ty-gia";

        $html = $this->fetch($url, 'GET', [], [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1 Edg/134.0.0.0'
        ]);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        $data = [];

        $rows = $tables->item(4)->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr');
        foreach ($rows as $i => $row) {
            if ($i === 0) { continue; }

            $cols = $row->getElementsByTagName('td');
            if ($cols->length >= 4) {
                $currency = trim($cols->item(0)->textContent);
                $sell = trim($cols->item(3)->textContent);
                preg_match('/\((.*?)\)/', $currency, $matches);
                $currencyCode = $matches[1] ?? null;
                $data[] = [
                    'rate' => (float) str_replace(['.', ','], ['', '.'], $sell),
                    'fromCurrency' => $currencyCode,
                    'toCurrency' => 'VND',
                    'bankCode' => 'NamAbank',
                ];
            }
        }

        return $data;
    }

    public function getNCBExchangeRate(): array
    {
        $url = "https://www.ncb-bank.vn/vi/ty-gia-tien-te";

        $html = $this->fetch($url, 'GET', [], [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1 Edg/134.0.0.0'
        ]);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        $data = [];

        $rows = $tables->item(0)->getElementsByTagName('tr');
        foreach ($rows as $i => $row) {
            if ($i === 6 || $i === 5) { continue; }

            $cols = $row->getElementsByTagName('td');
            if ($cols->length === 6) {
                $currency = trim($cols->item(1)->textContent);
                $sell = trim($cols->item(4)->textContent);
                $data[] = [
                    'rate' => (float) str_replace(['.', ','], ['', '.'], $sell),
                    'fromCurrency' => $currency,
                    'toCurrency' => 'VND',
                    'bankCode' => 'NCB',
                ];
            }
        }

        return $data;
    }

    public function getPVCombankExchangeRate(): array
    {
        $date = Carbon::now()->addHours(7)->format('Y-m-d');

        $url = "https://www.pvcombank.com.vn/exchange-rate-by-date?Date=$date";

        $response = $this->fetch($url);

        if (!$response) {
            return [];
        }

        $exchangeRateList = $response['response'] ?? [];

        $data = [];

        foreach ($exchangeRateList as $exchangeRate) {
            if (in_array($exchangeRate['currencyCode'], self::CURRENCY_LIST)) {
                $rate = $exchangeRate['exRateResList'][0]['exRateDigitalChannelResList'][0]['exRateSellDigitalChannelRes']['exRate'];
                $data[] = [
                    'rate' => $rate,
                    'fromCurrency' => $exchangeRate['currencyCode'],
                    'toCurrency' => 'VND',
                    'bankCode' => 'PVCombank',
                ];
            }
        }

        return $data;
    }

    public function getSCBExchangeRate(): array
    {
        $url = "https://www.scb.com.vn/Handlers/GetForeignExchangeCount.aspx";

        $response = $this->fetch($url, 'POST');

        $tableNo = $response['tableno'][count($response['tableno']) - 1]['id'] ?? null;

        $url = "https://www.scb.com.vn/Handlers/GetForeignExchange.aspx?tableno=$tableNo";

        $html = $this->fetch($url, 'GET', [], [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1 Edg/134.0.0.0'
        ]);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        $data = [];

        $rows = $tables->item(0)->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr');
        foreach ($rows as $i => $row) {
            if ($i === 1 || $i === 2) { continue; }
            $cols = $row->getElementsByTagName('td');
            if ($cols->length === 5) {
                $currency = trim($cols->item(0)->textContent);
                $sell = trim($cols->item(3)->textContent);
                $data[] = [
                    'rate' => (float) str_replace([','], [''], $sell),
                    'fromCurrency' => $currency,
                    'toCurrency' => 'VND',
                    'bankCode' => 'SCB',
                ];
            }
        }

        return $data;
    }

    public function getHongLeongbankExchangeRate(): array
    {
        $url = "https://www.hlbank.com.vn/vi/global-markets/forex-rates.html";

        $html = $this->fetch($url, 'GET', [], [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1 Edg/134.0.0.0'
        ]);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        $data = [];

        $rows = $tables->item(0)->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr');
        foreach ($rows as $i => $row) {
            if ($i === 0 || $i === 1 || $i === 3) { continue; }

            $cols = $row->getElementsByTagName('td');
            if ($i === 2) {
            $sell = trim($cols->item(4)->getElementsByTagName('div')->item(1)->textContent);
                $data[] = [
                    'rate' => (float) str_replace([','], [''], $sell),
                    'fromCurrency' => 'USD',
                    'toCurrency' => 'VND',
                    'bankCode' => 'SCB',
                ];
                continue;
            }
            if ($cols->length < 5) {
                continue;
            }
            $currency = trim($cols->item(1)->getElementsByTagName('div')->item(1)->textContent);
            preg_match('/\((.*?)\)/', $currency, $matches);
            $currencyCode = $matches[1] ?? null;
            $sell = trim($cols->item(4)->getElementsByTagName('div')->item(1)->textContent);
            $data[] = [
                'rate' => (float) str_replace([','], [''], $sell),
                'fromCurrency' => $currencyCode,
                'toCurrency' => 'VND',
                'bankCode' => 'HongLeongbank',
            ];
        }

        return $data;
    }

    public function getSHBExchangeRate(): array
    {
        $now = Carbon::now()->addHours(7);
        $day = $now->day;
        $month = $now->month;
        $year = $now->year;
        $url = "https://www.shb.com.vn/wp-admin/admin-ajax.php";

        $html = $this->fetch($url, 'POST', "action=loading_exchange&date%5B%5D=$day&date%5B%5D=$month&date%5B%5D=$year&change_number=3", [
            'contentType' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ]);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        if ($tables->length === 0) {
            return [];
        }

        $data = [];

        $rows = $tables->item(0)->getElementsByTagName('tr');
        foreach ($rows as $i => $row) {
            if ($i === 0 || $i === 1 || $i === 2) { continue; }

            $cols = $row->getElementsByTagName('td');
            $currency = trim($cols->item(1)->textContent);
            $sell = trim($cols->item(4)->textContent);
            $data[] = [
                'rate' => (float) str_replace([','], [''], $sell),
                'fromCurrency' => substr($currency, 0 , 3),
                'toCurrency' => 'VND',
                'bankCode' => 'SHB',
            ];
        }

        return $data;
    }

    public function getIndovinabankExchangeRate(): array
    {
        $url = "https://www.indovinabank.com.vn/vi/lookup/rates";

        $html = $this->fetch($url, 'GET');

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $ul = $dom->getElementsByTagName('ul');

        $data = [];

        $lis = $ul->item(29)->getElementsByTagName('li');
        foreach ($lis as $i => $row) {
            if ($i === 1 || $i === 2) { continue; }

            $cols = $row->getElementsByTagName('span');
            $currency = trim($cols->item(1)->textContent);
            $sell = trim($cols->item(9)->textContent);
            $data[] = [
                'rate' => (float) str_replace([','], [''], $sell),
                'fromCurrency' => $currency,
                'toCurrency' => 'VND',
                'bankCode' => 'IndoVinabank',
            ];
        }

        return $data;
    }

    public function getVietAbankExchangeRate(): array
    {
        $date = Carbon::now()->format('d/m/Y');
        $url = "https://vietabank.com.vn/Currency/Filter";

        $html = $this->fetch($url, 'POST', "dateSearch=$date", [
            'contentType' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ]);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        $data = [];

        $rows = $tables->item(0)->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr');
        foreach ($rows as $i => $row) {
            if ($i === 1 || $i === 2) { continue; }

            $cols = $row->getElementsByTagName('td');
            if ($cols->length === 5) {
                $currency = trim($cols->item(0)->textContent);
                $sell = trim($cols->item(4)->textContent);
                $data[] = [
                    'rate' => (float) str_replace([','], [''], $sell),
                    'fromCurrency' => $currency,
                    'toCurrency' => 'VND',
                    'bankCode' => 'VietAbank',
                ];
            }
        }

        return $data;
    }

    public function getVietcapitalbankExchangeRate(): array
    {
        $date = Carbon::now()->format('d/m/Y');
        $url = "https://bvbank.net.vn/wp-admin/admin-ajax.php";

        $response = $this->fetch($url, 'POST', "action=exchange_rate_by_date&date=$date", [
            'contentType' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ]);

        $html = $response['data']['html'] ?? null;

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<table>' . $html . '</table>');
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        $data = [];

        $rows = $tables->item(0)->getElementsByTagName('tr');
        foreach ($rows as $i => $row) {
            $cols = $row->getElementsByTagName('td');
            if ($cols->length === 5) {
                $currency = trim($cols->item(0)->textContent);
                $sell = trim($cols->item(4)->textContent);
                $data[] = [
                    'rate' => (float) str_replace([','], [''], $sell),
                    'fromCurrency' => $currency,
                    'toCurrency' => 'VND',
                    'bankCode' => 'Vietcapitalbank',
                ];
            }
        }

        return $data;
    }

    public function getKienlongbankExchangeRate(): array
    {
        $url = "https://laisuat.kienlongbank.com/PageTyGia.aspx";

        $html = $this->fetch($url, 'GET');

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        $data = [];

        $rows = $tables->item(0)->getElementsByTagName('tr');
        foreach ($rows as $i => $row) {
            if ($i === 0 || $i === 2 || $i === 3) { continue; }
            $cols = $row->getElementsByTagName('td');
            if ($cols->length === 4) {
                $currency = substr(trim($cols->item(0)->textContent), 0 , 3);
                $sell = trim($cols->item(3)->textContent);
                $data[] = [
                    'rate' => (float) str_replace(['.', ','], ['', '.'], $sell),
                    'fromCurrency' => $currency,
                    'toCurrency' => 'VND',
                    'bankCode' => 'Kienlongbank',
                ];
            }
        }

        return $data;
    }

    public function getGPbankExchangeRate(): array
    {
        $url = "https://www.gpbank.com.vn/RateDetail";

        $html = $this->fetch($url, 'GET', []);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        $data = [];

        $rows = $tables->item(0)->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr');
        foreach ($rows as $i => $row) {
            if ($i === 0 || $i === 1 || $i === 2) { continue; }
            $cols = $row->getElementsByTagName('td');
            if ($cols->length === 5) {
                $currency = substr(trim($cols->item(0)->textContent), 0 , 3);
                $sell = trim($cols->item(4)->textContent);
                $data[] = [
                    'rate' => (float) str_replace([','], [''], $sell),
                    'fromCurrency' => $currency,
                    'toCurrency' => 'VND',
                    'bankCode' => 'GPbank',
                ];
            }
        }

        return $data;
    }

    public function getHSBCExchangeRate(): array
    {
        $url = "https://www.hsbc.com.vn/foreign-exchange/rate";

        $html = $this->fetch($url, 'GET', [], [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
        ]);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        $data = [];

        $rows = $tables->item(2)->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr');

        foreach ($rows as $i => $row) {
            $th = $row->getElementsByTagName('th');
            $cols = $row->getElementsByTagName('td');
            preg_match('/\((.*?)\)/', trim($th->item(0)->textContent), $matches);
            $currency = $matches[1];
            $sell = trim($cols->item(3)->textContent);
            $data[] = [
                'rate' => (float) str_replace(['.', ','], ['', '.'], $sell),
                'fromCurrency' => $currency,
                'toCurrency' => 'VND',
                'bankCode' => 'HSBC',
            ];
        }

        return $data;
    }

    public function getVRbankExchangeRate(): array
    {
        $url = "https://www.vrbank.com.vn/vi/ty-gia-ngoai-te";

        $html = $this->fetch($url, 'GET', [], [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
        ]);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        $rows = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' tbl-td ')]");
        $data = [];

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('div');
            $currency = trim($cells->item(2)->textContent);
            $buyTransfer = (float) str_replace(',', '', trim($cells->item(6)->textContent));
            $data[] = [
                'rate' => $buyTransfer,
                'fromCurrency' => $currency,
                'toCurrency' => 'VND',
                'bankCode' => 'VRBank',
            ];
        }

        return $data;
    }

    public function getAgribankExchangeRate(): array
    {
        $now = Carbon::now()->addHours(7);
        $year = $now->year;
        $date = $now->format('d-m-Y');
        $url = "https://www.agribank.com.vn/wcm/connect/ttkhac/ty-gia/$year/$date?source=library&srv=cmpnt&cmpntid=b42b798a-7057-49c3-b0fd-3766e30729cf";

        $html = $this->fetch($url, 'GET', [], [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1 Edg/134.0.0.0'
        ]);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Lấy bảng tỷ giá đầu tiên (ngoại tệ)
        $tables = $dom->getElementsByTagName('table');

        $data = [];

        $rows = $tables->item(0)->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr');
        foreach ($rows as $i => $row) {
            $cols = $row->getElementsByTagName('td');
            if ($cols->length == 4) {
                $currency = trim($cols->item(0)->textContent);
                $sell = trim($cols->item(3)->textContent);
                $data[] = [
                    'rate' => (float) $sell,
                    'fromCurrency' => $currency,
                    'toCurrency' => 'VND',
                    'bankCode' => 'Agribank',
                ];
            }
        }

        return $data;
    }


    private function saveExchangeRate(string $bank, array $exchangeRate): void
    {
        if ($exchangeRate['rate'] == 0) {
            return;
        }
        $bankCode = $exchangeRate['bankCode'];
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

    private function fetch(string $url, string $method = 'GET', $data = [], array $options = [])
    {
        $ch = curl_init($url);

        $headers = [];

        foreach ($options as $key => $value) {
            if ($key  == 'cookie') {
                curl_setopt($ch, CURLOPT_COOKIE, $value);
                continue;
            }
            if ($key == 'User-Agent') {
                curl_setopt($ch, CURLOPT_USERAGENT, $value);
                continue;
            }
            if ($key !== 'token' && $key !== 'contentType') {
                $headers[] = $key . ':' . $value;
            }
        }

        $headers = array_merge($headers, [
            'Content-Type: ' . $options['contentType'] ?? 'application/json', // ['application/json, 'application/x-www-form-urlencoded', 'text/xml', ...]
        ]);

        if ($options['contentType'] === 'token') {
            array_push($headers, 'Authorization: Basic ' . $options['token']);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_ALLOW_BEAST);
        }

        if(strpos($url, 'hsbc') > 0) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $GLOBALS['log']->info('Lỗi cURL: ' . curl_error($ch));

            return null;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $GLOBALS['log']->info('URL: ' . $url);

        if ($httpCode !== 200) {
            $GLOBALS['log']->info('Lỗi HTTP: ' . $httpCode);
            return null;
        }
        $GLOBALS['log']->info('Data: ' . $response);

        return json_decode($response, true) ? json_decode($response, true) : $response;
    }
}
