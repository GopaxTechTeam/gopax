# 고팍스 REST API 문서
## 안내
### API 접속 주소(URL)
고팍스의 REST API는 계정/주문 관리 및 공개 마켓 데이터에 대한 엔드포인트를 제공합니다.

https://api.gopax.co.kr

### 1. 요청/응답 형식
모든 요청 및 응답의 content-type 은 application/json 이며, 통상적인 HTTP 상태코드를 준수합니다. 예를 들어 성공적으로 접속한 경우에는 200의 상태코드가 반환됩니다.

### 2. 오류
접속 장애 등의 문제가 있을 경우 그에 준하는 HTTP 상태코드가 반환됩니다.

HTTP 오류코드

이름|설명
------------|------------
400|잘못된 요청 - 요청 형식이 유효하지 않음
401|	권한 없음 - 잘못된 API 키
403|	금지됨 - 요청한 리소스에 대한 접근 권한이 없음
404|	찾을 수 없음
500|	내부 서버 오류 - 서버에 문제가 발생함

요청 처리 중 논리적인 오류가 발생했을 경우 HTTP 상태코드는 여전히 200으로 반환되지만, 응답 내용이 항상 다음의 형식으로 반환됩니다.

```
{
    errormsg: '[오류 내용에 대한 설명]'
}
```

### 3. 페이지 처리
고팍스는 REST 요청에 대한 응답이 배열(array)의 형태로 반환되는 경우 커서 페이지 처리가 적용됩니다. 대부분의 엔드포인트는 기본적으로 최신 항목을 반환하며, 추가적인 결과를 가져오려는 경우에는 이미 반환된 데이터를 기준으로 처리하고자 하는 페이지의 방향을 명시해야 합니다.

이름|기본값|설명
------------|------------|------------
pastmax||본 페이지 ID 뒤의 (오래된) 페이지를 요청함
lastestmin||본 페이지 ID 앞의 (새로운) 페이지를 요청함
limit|100|각 요청에 포함되는 결과의 갯수 (최대 100 / 기본 100)

### 4. 타임스탬프
```
2017-01-01T16:03:08.123456z
```
API의 모든 타임스탬프는 [ISO 8601](https://ko.wikipedia.org/wiki/ISO_8601) 형식에 따라 microsecond 단위로 반환됩니다. 대부분의 현대적 프로그래밍 언어 및 라이브러리에서 해당 형식이 지원됩니다.

### 5. API 호출 횟수 제한
API 호출 횟수 제한을 초과하면 429 - 너무 많은 요청 상태코드가 반환됩니다.

Public API 는 IP 당, Private API 는 API Key 당 호출 횟수가 제한됩니다. 각각 최근 10초의 구간 안에서 최대 60번의 API 호출이 가능합니다.
### 6. Private API 인증
Private API에 인증하기 위해, REST 요청에 항상 다음의 HTTP 헤더가 포함되어야 합니다.

 1. API-KEY: 발급받은 API 키
 2. SIGNATURE: 메시지 서명 값 (아래에 설명)
 3. NONCE: 중복되지 않고 계속 증가하는 값 (통상적으로 timestamp)

같은 NONCE 값이 사용되면 서버에서 거부합니다.

HTTP 본문의 content-type은 application/json 으로 설정해야 합니다.

SIGNATURE 는 다음 과정에 따라 생성합니다.

 1. 다음의 내용을 순서대로 문자열로 연결합니다.
       1. 헤더의 NONCE 값
       2. HTTP Method(대문자로): 'GET', 'POST', 'DELETE' 등
       3. API 엔드포인트 경로 (예: '/orders', '/trading-pairs/ETH-KRW/book')
       4. JSON 형식의 요청 변수 본문 (없을 경우 아무 문자열도 연결하지 마십시오)
 2. 발급 받은 secret 을 base64 로 디코딩합니다.
 3. 2.의 값을 secret key 로 사용하여 sha512 HMAC 으로 서명합니다.
 4. 3.의 값을 base64 로 인코딩합니다.

아래는 node.js 예제입니다.

```
//
// 잔액 조회하기
//
var crypto = require('crypto');
var request = require('request');

// 발급받은 api키와 시크릿키를 입력한다
var apikey = '';
var secret = '';

// nonce값 생성
var nonce = Date.now() * 1000;
var method = 'GET';
var requestPath = '/balances';

// 필수 정보를 연결하여 prehash 문자열을 생성함
var what = nonce + method + requestPath;
// base64로 secret을 디코딩함
var key = Buffer(secret, 'base64');
// secret으로 sha512 hmac을 생성함
var hmac = crypto.createHmac('sha512', key);

// hmac으로 필수 메시지에 서명하고
// 그 결과물을 base64로 인코딩함
var sign = hmac.update(what).digest('base64');

var host = 'api.gopax.co.kr';

var options = {
  method,
  json: true,
  url: `https://${host}${requestPath}`,
  headers: {
    'API-KEY': apikey,
    Signature: sign,
    Nonce: nonce,
  },
  strictSSL: false,
};

request(options, (err, response, b) => {
  if (err) {
    console.log('err:', err);
    return;
  }
  console.log(b);
});
```

아래는 python 예제입니다.

```
import time, base64, hmac, hashlib

nonce = str(time.time())
method = 'GET'
request_path = '/balances'

//필수 정보를 연결하여 prehash 문자열을 생성함

what = nonce + method + request_path # + request_body

//base64로 secret을 디코딩함

key = base64.b64decode(secret)

//hmac으로 필수 메시지에 서명하고
//그 결과물을 base64로 인코딩함

signature = hmac.new(key, what, hashlib.sha512)
return base64.b64encode(signature.digest())
```

# REST API 호출 목록
## 1. 인증이 필요한 호출
### 1. 잔액 조회하기
### 요청
```
GET /balances
```
### 응답
```
[
  {
    asset: 'KRW',
    avail: 10000000000,
    hold: 5000000,
    pendingWithdrawal: 5000000
  },
  ...
]
```
### 2. 자산 이름에 따라 잔액 조회하기
### 요청
```
GET /balances/<asset-name>
```
### 응답
```
{
  asset: 'KRW',
  avail: 10000000000,
  hold: 5000000,
  pendingWithdrawal: 5000000
}
```
### 3. 주문 조회하기
### 요청
```
GET /orders
```
### Query String Parameter
이름|	설명
------|------
trading-pair-name	|[선택] 유효한 거래쌍 이름(ETH-KRW, BTC-KRW, ...)
### 응답
```
[
  {
    id: 98723,
    price: 2986,
    amount: 9,
    tradingPairName: 'ETH-KRW',
    side: 'buy',
    type: 'limit',
    createdAt: '2016-12-08T20:02:28.53864Z' // ISO 8601 타임스탬프
  },
  ...
]
```
### 4. 주문 ID로 주문 조회하기
### 요청
```
GET /orders/<order-id>
```
### 응답
```
{
  id: 98723,
  status: 'placed' // placed, cancelled, completed, updated
  side: 'buy' // buy 또는 sell
  type: 'limit' // limit 또는 market
  price: 2986,
  amount: 9,
  remaining: 10,
  tradingPairName: 'ETH-KRW',
  createdAt: '2016-12-08T20:02:28.53864Z', // ISO 8601 타임스탬프
  updatedAt: '2016-12-08T20:02:28.53864Z', // ISO 8601 타임스탬프
}
```
### 5. 주문 등록하기
### 요청
```
POST /orders
```
### 요청 본문
```
{
  type: 'limit' // limit 또는 market
  side: 'buy' // buy 또는 sell
  price: 2986,
  amount: 9,
  tradingPairName: 'ETH-KRW'
}
```
### 응답
```
{
  id: 98723,
  price: 2986,
  amount: 9,
  tradingPairName: 'ETH-KRW',
  side: 'buy',
  type: 'limit',
  createdAt: '2016-12-08T20:02:28.53864Z' // ISO 8601 타임스탬프
}
```
주문에는 지정가(limit)와 시장가(market) 두 종류가 있습니다. 계정에 충분한 잔액이 있는 경우에만 주문을 등록할 수 있습니다. 주문이 등록되면, 주문의 지속기간 동안 계정의 해당 잔액이 홀드됩니다. 홀드되는 잔액의 종류 및 금액은 오더 종류 및 명시된 매개변수에 따라 결정됩니다.
### 6. 주문 ID로 주문 취소하기
### 요청
```
DELETE /orders/<order-id>
```
### 응답
```
{
}
```
### 7. 사용자 거래 기록 조회하기
### 요청
```
GET /trades
```
### Query String Parameter
이름|	설명
-----|-----
trading-pair-name|	[선택] 유효한 거래쌍 이름(ETH-KRW, BTC-KRW, ...)
### 응답
```
[
  { id: 4154562,
    orderId: 5323195,
    amount: 0.5315267,
    fee: 0.00079729,
    price: 101385,
    timestamp: 1504610614,
    side: 'buy',
    tradingPairName: 'BTC-KRW'
  },
  ...
]
```
## 2. 인증이 필요하지 않은 요청
### 1. 자산 조회하기
### 요청
```
GET /assets
```
### 응답
```
[
  {
    id: 'KRW',
    name: 'Korean Won'
  },
  {
    id: 'ETH',
    name: 'Ethereum'
  },
  ...
]
```
### 2. 특정 거래쌍 조회하기
### 요청
```
GET /trading-pairs
```
### 응답
```
[
  {
    name: 'ETH-KRW',
    baseAsset: 'ETH',
    quoteAsset: 'KRW'
  },
  ...
]
```
### 3. 특정 거래쌍의 거래량 조회하기
### 요청
```
GET /trading-pairs/<trading-pair-name>/ticker
```
### 응답
```
{
  price: 979,
  ask: 1303,
  bid: 985,
  volume: 3966,
  time: '2016-12-08T20:02:28.53864Z' // ISO 8601 타임스탬프
}
```
### 4. 특정 거래쌍의 호가창 조회하기
### 요청
```
GET /trading-pairs/<trading-pair-name>/book
```
### Query String Parameter
이름|	설명
-----|-----
level|	호가창의 상세정보 수준 (1 = 매수호가 및 매도호가, 2 = 매수 및 매도 주문 각 50개, 기타 = 호가창 전체)
### 응답
```
{
  bid: [
    [1, 2937, 2326], // id, 가격, 수량
    [2, 262, 2066],
    ...
  ],
  ask: [
    [1, 2937, 2326], // id, 가격, 수량
    [2, 262, 2066],
    ...
  ]
}
```
### 5. 최근 체결 거래 조회하기
### 요청
```
GET /trading-pairs/<trading-pair-name>/trades
```
### Query String Parameter
이름|	설명
-----|-----
limit	|반환되는 항목의 갯수 (최대 100)
pastmax	|이 ID보다 오래된 데이터를 제외함
latestmin|	이 ID보다 새로운 최신 데이터를 가져옴
after	|이 타임스탬프 이후의 데이터를 제외함 (ms 단위)
before	|이 타임스탬프 이전의 데이터를 제외함 (ms 단위)
### 응답
```
{
  id: 28374,
  price: 6234,
  amount: 22,
  side: 'buy' // buy 또는 sell
  time: '2016-12-08T20:02:28.53864Z' // ISO 8601 timestamp
  latestmin: 3092, // next cursor id to fetch newer data
  pastmax: 3022, // next cursor id to fetch older data
}
```
### 6. 특정 거래쌍의 최근 24시간 통계 조회하기
### 요청
```
GET /trading-pairs/<trading-pair-name>/stats
```
### 응답
```
{
  open: 34.19000000,
  high: 95.70000000,
  low: 7.06000000,
  close: 40.00000000,
  volume: 2.41000000,
  time: '2016-12-08T20:02:28.53864Z' // ISO 8601 timestamp
}
```
### 7. 특정 거래쌍의 과거 기록 조회하기
### 요청
```
GET /trading-pairs/<trading-pair-name>/candles
```
### Query String Parameter
이름|	설명
-----|-----
start	|시작 시점 (ms 단위)
end	|종료 시점 (ms 단위)
interval	|희망하는 시간 간격 (분 단위, 1/5/30/1440)
### 응답
```
{
  // time, low, high, open, close, volume
  [ 1504681856031, 9588, 10500, 10234, 10355, 2833.85258576 ],
  ...
}
```
### 8. 모든 거래쌍의 최근 24시간 통계 조회하기
### 요청
```
GET /trading-pairs/stats
```
### 응답
```
{
  [
    {
      name: 'BTC-KRW',
      open: 34.19000000,
      high: 95.70000000,
      low: 7.06000000,
      close: 40.00000000,
      volume: 2.41000000,
      time: '2016-12-08T20:02:28.53864Z' // ISO 8601 timestamp
    },
    ...
  ]
}
```
