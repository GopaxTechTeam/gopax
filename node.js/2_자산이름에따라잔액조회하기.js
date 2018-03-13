//
// 자산 이름에 따라 잔액 조회하기
//
var crypto = require('crypto');
var request = require('request');

// 발급받은 api키와 시크릿키를 입력한다
var apikey = '';
var secret = '';

// nonce값 생성
var nonce = Date.now() * 1000;
var method = 'GET';
var requestPath = '/balances/KRW'; // /balances/<asset-name>

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
