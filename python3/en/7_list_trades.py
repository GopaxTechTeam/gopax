#
# List trades
#
from __future__ import print_function
import time, base64, hmac, hashlib, json
import requests

# Use your API key and the secret
apikey = '' # TODO:
secret = '' # TODO:

# Generate nonce
nonce = str(time.time())
method = 'GET'
base_url = 'https://api.gopax.co.kr' # TODO:
request_path = '/trades'

# Generate prehash string
what = nonce + method + request_path
# Decode the secret using base64
key = base64.b64decode(secret)
# Generate the signature using HMAC
signature = hmac.new(key, str(what).encode('utf-8'), hashlib.sha512)
# Finally, Encode the signature in base64
signature_b64 = base64.b64encode(signature.digest())

def findOldestId(response):
    """Find the oldest ID value for pagination
    from the last response body"""
    return min([ i['id'] for i in response ])

def printResponse(p):
    "Prints response body"
    print(p.text)
	
custom_headers = {
    'API-Key': apikey,
    'Signature': signature_b64,
    'Nonce': nonce
}

def main():
    # HTTP request method = GET
    querystring = ''
    last_oldest_id = None
    while True:
        if last_oldest_id != None:
            # pastmax = maximum id value among the trades in the last API response
            # set it to exclude any result with smaller id
            querystring = '?pastmax=' + str(last_oldest_id)
        req = requests.get(url=base_url + request_path + querystring,
                headers=custom_headers)

        if req.ok:
            response = json.loads(req.text)
            if len(response) < 1:
                break
            else:
                print(response)
                last_oldest_id = findOldestId(response)
        else:
            print('Error!')
            printResponse(req)
 
if __name__ == '__main__':
    main()

