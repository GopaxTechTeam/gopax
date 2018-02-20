# pylint: disable=invalid-name,missing-docstring,line-too-long,multiple-imports
# pylint: disable=too-many-arguments,redefined-outer-name,too-few-public-methods,deprecated-lambda
"""
GOPAX RESTful API Wrapper
"""
import time, base64, hashlib, hmac, json
from requests.auth import AuthBase
import requests


class GopaxApiAuth(AuthBase):
    def __init__(self, api_key, secret_key):
        self.api_key = api_key
        self.secret_key = secret_key

    def __call__(self, request):
        nonce = str(time.time())
        endpoint = request.path_url.split('?')[0]

	print request.body

        message = nonce + request.method + endpoint + (request.body or '')

        hmac_key = base64.b64decode(self.secret_key)
        signature = hmac.new(hmac_key, message, hashlib.sha512)
        signature_b64 = base64.b64encode(signature.digest())

        request.headers.update({'API-Key': self.api_key,
                                'Signature': signature_b64,
                                'Nonce': nonce})
        return request


class GopaxApi(object):
    def __init__(self, host, api_key, secret_key, verbose=False):
        self.host = host
        self.auth = GopaxApiAuth(api_key, secret_key)
        self.verbose = verbose

    def _invoke_gopax_api(self, method, endpoint, params=None, body=None):
        url = 'https://' + self.host + endpoint
        resp = method(url, params=params, json=body, auth=self.auth)

        if self.verbose:
            print '{} {}'.format(resp.request.method, resp.request.path_url)
            print 'Headers: {}'.format(resp.request.headers)
            print 'Body: {}'.format(resp.request.body)

        if not resp.ok:
            print '{} {}: {}'.format(resp.status_code, resp.reason, resp.text)
            return None
        return resp.json()

    # API List (Authentication needed)
    def GetBalances(self, asset=None):
        return self._invoke_gopax_api(requests.get, '/balances/' + (asset or ''))

    def GetOrders(self, trading_pair_name=None):
        params = {}
        if trading_pair_name:
            params['trading-pair-name'] = trading_pair_name
        return self._invoke_gopax_api(requests.get, '/orders', params=params)

    def GetOrder(self, order_id):
        return self._invoke_gopax_api(requests.get, '/orders/{}'.format(order_id))

    def PlaceOrder(self, trading_pair_name, limit_or_market, side, price, amount):
        body = {'tradingPairName': trading_pair_name,
                'type': limit_or_market,
                'side': side,
                'price': price,
                'amount': amount}
        return self._invoke_gopax_api(requests.post, '/orders', body=body)

    def CancelOrder(self, order_id):
        return self._invoke_gopax_api(requests.delete, '/orders/{}'.format(order_id))

    def GetTrades(self, trading_pair_name=None):
        params = {}
        if trading_pair_name:
            params['trading-pair-name'] = trading_pair_name
        return self._invoke_gopax_api(requests.get, '/trades', params=params)

    # API List (Public)
    def GetAssets(self):
        return self._invoke_gopax_api(requests.get, '/assets')

    def GetTradingPairs(self):
        return self._invoke_gopax_api(requests.get, '/trading-pairs')

    def GetTicker(self, trading_pair_name):
        return self._invoke_gopax_api(requests.get, '/trading-pairs/{}/ticker'.format(trading_pair_name))

    def GetOrderbook(self, trading_pair_name, level=None):
        params = {}
        if level:
            params['level'] = level
        return self._invoke_gopax_api(
            requests.get, '/trading-pairs/{}/book'.format(trading_pair_name), params=params)

    def GetRecentTrades(self, trading_pair_name, limit=100, pastmax=None,
                        latestmin=None, after=None, before=None):
        params = {'limit': limit,
                  'pastmax': pastmax,
                  'latestmin': latestmin,
                  'after': after,
                  'before': before}

        params = dict(filter(lambda (k, v): v is not None, params.items()))
        return self._invoke_gopax_api(
            requests.get, '/trading-pairs/{}/trades'.format(trading_pair_name), params=params)

    def GetStats(self, trading_pair_name):
        return self._invoke_gopax_api(requests.get, '/trading-pairs/{}/stats'.format(trading_pair_name))

    def GetHistoricalData(self, trading_pair_name, start, end, interval):
        params = {'start': start, 'end': end, 'interval': interval}
        return self._invoke_gopax_api(
            requests.get, '/trading-pairs/{}/candles'.format(trading_pair_name), params=params)


# Example
if __name__ == '__main__':

    host = 'api.gopax.co.kr'
    api_key = '<your api key>'
    secret_key = '<your secret key>'

    api = GopaxApi(host, api_key, secret_key)
    print api.GetBalances()
    print api.PlaceOrder('ETH-KRW', 'limit', 'buy', 1, 10)
