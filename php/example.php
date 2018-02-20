<?php
require_once('./lib/gopax.php');

// Without Api Key & Api Secret
$client = new \Gopax\Client();
// Or
// $client = new \Gopax\Client('API KEY', 'API SECRET');

//////////////////
// ** Private APIs
//////////////////

// Get Balances
// print_r($client->getBalances());
// print_r($client->getBalances('KRW'));

// Get Orders
// print_r($client->getOrders());
// print_r($client->getOrders('BTC-KRW'));
// print_r($client->getOrder(123));

// Place Order
// $orderRequest = new \Gopax\OrderRequest(
//   \Gopax\Constants::LIMIT, \Gopax\Constants::BUY, 2000000, 0.1, 'BTC-KRW'
// );
// print_r($client->order($orderRequest));

// Cancel Order
// print_r($client->cancelOrder(123));

// Get Trades (My Complete Orders)
// print_r($client->getTrades());
// print_r($client->getTrades('BTC-KRW'));

/////////////////
// ** Public APIs
/////////////////

// Get Assets
// print_r($client->getAssets());

// Get Trading Pairs
// print_r($client->getTradingPairs());

// Get Ticker
// print_r($client->getTicker('BTC-KRW'));

// Get Orderbook
// print_r($client->getOrderbook('BTC-KRW'));
// print_r($client->getOrderbook('BTC-KRW', \Gopax\Constants::BUY_SELL_1));
// print_r($client->getOrderbook('BTC-KRW', \Gopax\Constants::BUY_SELL_50));

// Get Recent Trades (Complete Orders)
// print_r($client->getRecentTrades('BTC-KRW'));

// $recentRequestOptions[\Gopax\Constants::PASTMAX] = 239881;
// print_r($client->getRecentTrades('BTC-KRW', $recentRequestOptions));

// Get Trading Pair's 24H Stats
// print_r($client->getStats('BTC-KRW'));

// Get Trade History
// $start = (time() - 60 * 60 * 24) * 1000;
// $end = time() * 1000;
// $interval = 1;
// print_r($client->getCandles('BTC-KRW', $start, $end, $interval));
