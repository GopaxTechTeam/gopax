<?php
namespace Gopax;

class Response
{
    public $httpStatus;
    public $data;
    public $errorMsg;
    public $errorCode;
    
    public function __construct($httpStatus, $json)
    {
        $response = json_decode($json);
        
        $this->httpStatus = $httpStatus;
        
        if ($response->errormsg) {
            $this->errorMsg = $response->errormsg;
            
            $tokenizedError  = explode(':', $response->errormsg);
            $this->errorCode = trim($tokenizedError[1]);
        } else {
            $this->data     = $response;
            $this->errormsg = NULL;
        }
    }
}

class Constants
{
    const LIMIT = 'limit';
    const MARKET = 'market';
    
    const BUY = 'buy';
    const SELL = 'sell';
    
    const BUY_SELL_1 = 1;
    const BUY_SELL_50 = 2;
    
    const PASTMAX = 'pastmax';
    const LATESTMIN = 'latestmin';
    const AFTER = 'after';
    const BEFORE = 'before';
}

class Errors
{
    const INVALID_ASSET = 100;
    const INVALID_TRADING_PAIR = 101;
    const INVALID_USER = 102;
    const INVALID_ORDER_TYPE = 103;
    const NOT_ENABLED_TRADING_PAIR = 104;
    const NOT_ACTIVATED_TRADING_PAIR = 105;
    const NOT_ENABLED_ASSET = 106;
    const INVALID_AMOUNT = 107;
    const INVALID_PRICE = 108;
    const TOO_MANY_ACTIVE_ORDERS = 200;
    const INSUFFICIENT_BALANCE = 201;
    const INVALID_ID = 202;
    const INVALID_NUMBERS_OVERFLOW = 203;
}

class Request
{
    public $data;
    
    public function toJSON()
    {
        return json_encode($this->data);
    }
}

class OrderRequest extends Request
{
    public function __construct(string $type, string $side, float $price, float $amount, string $tradingPairName)
    {
        $this->data['type']            = $type; // LIMIT, MARKET
        $this->data['side']            = $side; // BUY, SELL
        $this->data['price']           = $price;
        $this->data['amount']          = $amount;
        $this->data['tradingPairName'] = $tradingPairName;
    }
}

class Client
{
    private $apiKey;
    private $apiSecret;
    
    const API_HOST = 'https://api.gopax.co.kr';
    const VERSION = 'gopax-php-sdk-20171216';
    
    public function __construct(string $apiKey = '', string $apiSecret = '')
    {
        $this->apiKey    = $apiKey;
        $this->apiSecret = $apiSecret;
    }
    
    private static function getNonce()
    {
        $mt = explode(' ', microtime());
        return $mt[1] . substr($mt[0], 2, 6);
    }
    
    private function getSignature($nonce, $method, $path, $body)
    {
        $tokenizedPath = explode('?', $path);
        $requestPath   = $tokenizedPath[0];
        $data          = $nonce . $method . $requestPath . $body;
        $secret        = base64_decode($this->apiSecret);
        return base64_encode(hash_hmac('sha512', $data, $secret, true));
    }
    
    private function request(string $method, string $path, Request $request = NULL)
    {
        $curl = curl_init();
        
        $nonce     = self::getNonce();
        $method    = strtoupper($method);
        $postData  = $request ? $request->toJSON() : '';
        $signature = $this->getSignature($nonce, $method, $path, $postData);
        
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'API-KEY: ' . $this->apiKey;
        $headers[] = 'SIGNATURE: ' . $signature;
        $headers[] = 'NONCE: ' . $nonce;

        curl_setopt($curl, CURLOPT_USERAGENT, self::VERSION);
        curl_setopt($curl, CURLOPT_URL, self::API_HOST . $path);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        } elseif ($method !== 'GET') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }
        
        $json       = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        return new Response($httpStatus, $json);
    }
    
    public function getBalances(string $assetName = NULL)
    {
        $url = '/balances';
        
        if ($assetName !== NULL) {
            $url .= "/$assetName";
        }
        
        return $this->request('GET', $url);
    }
    
    public function getOrders(string $tradingPairName = NULL)
    {
        $url = '/orders';
        
        if ($tradingPairName !== NULL) {
            $url .= "?trading-pair-name=$tradingPairName";
        }
        
        return $this->request('GET', $url);
    }
    
    public function getOrder(int $orderId)
    {
        return $this->request('GET', "/orders/$orderId");
    }
    
    public function order(OrderRequest $request)
    {
        return $this->request('POST', "/orders", $request);
    }
    
    public function cancelOrder(int $orderId)
    {
        return $this->request('DELETE', "/orders/$orderId");
    }
    
    public function getTrades(string $tradingPairName = NULL)
    {
        $url = '/trades';
        
        if ($tradingPairName !== NULL) {
            $url .= "?trading-pair-name=$tradingPairName";
        }
        
        return $this->request('GET', $url);
    }
    
    public function getAssets()
    {
        return $this->request('GET', '/assets');
    }
    
    public function getTradingPairs()
    {
        return $this->request('GET', '/trading-pairs');
    }
    
    public function getTicker(string $tradingPairName)
    {
        return $this->request('GET', "/trading-pairs/$tradingPairName/ticker");
    }
    
    public function getOrderbook(string $tradingPairName, int $level = NULL)
    {
        $url = "/trading-pairs/$tradingPairName/book";
        
        if ($level !== NULL) {
            $url .= "?level=$level";
        }
        
        return $this->request('GET', $url);
    }
    
    public function getStats(string $tradingPairName)
    {
        return $this->request('GET', "/trading-pairs/$tradingPairName/stats");
    }
    
    public function getRecentTrades(string $tradingPairName, array $options = NULL)
    {
        $url = "/trading-pairs/$tradingPairName/trades";
        
        if ($options) {
            $params = array();
            
            foreach ($options as $key => $value) {
                $params[] = '$key=' . urlencode($value);
            }
            
            if (count($params) > 0) {
                $url .= '?' . join('&', $params);
            }
        }
        
        return $this->request('GET', $url);
    }
    
    public function getCandles(string $tradingPairName, int $start, int $end, int $interval)
    {
        return $this->request('GET', "/trading-pairs/$tradingPairName/candles?" . "start=$start&end=$end&interval=$interval");
    }
}
