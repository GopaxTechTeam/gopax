const crypto = require('crypto');
const request = require('request');

/*
var apiKey = '32b1168a-ca84-46c2-a894-d423d9bad4ee';
var secret =
  'Xv0+tInzEueQ9kXZaqydcAtgDYR7TT90Mq/pkAmYmblrt3b0bfUX1NJsHnlIRZK2dUYb4GSAPVMN5dmbX2trhg==';
*/
const apiKey = '6dbe3b37-f212-4233-b85b-5f1e5db20159';
const secret =
    'HP1Oglka3H6ZRDZD82rMSaXh9qP5bj2xKF5JKqMEHYVJpmxcreqixqBNq9Wd79sDhq5MVwk1Xn4RtPN1hyGh4Q==';

/*
var body = {
  assetIds: [1, 3, 4]
};
var body = {
  type: 1,
  side: 1,
  price: 1,
  amount: 1,
  pairId: 1
};

var body = {
  type: 'limit',
  side: 'buy',
  price: 1000,
  amount: 1,
  tradingPairName: 'ETH-KRW'
};
*/
const nonce = Date.now() / 1000;
const method = 'DELETE';
const path = '/orders/8219';
const sign = crypto.createHmac('sha512', Buffer.from(secret, 'base64'))
  .update(nonce + method + path /* + JSON.stringify(body) */).digest('base64');

// var host = 'EC2PublicRelayALB-638579431.ap-southeast-2.elb.amazonaws.com:444';
const host = 'api.gopax.qa.streami.co';

const options = {
  method,
  //  body: body,
  json: true,
  url: `https://${host}${path}`,
  headers: {
    'API-Key': apiKey,
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
  console.log('body:', b);
  console.log('headers:', response.headers);
});
