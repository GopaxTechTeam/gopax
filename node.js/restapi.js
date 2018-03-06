const crypto = require('crypto');
const request = require('request');

const apiKey = 'apiKey';
const secret = 'secret';

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
