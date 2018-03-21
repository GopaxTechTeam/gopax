//
// 잔액 조회하기
//

using System;  
using System.IO;  
using System.Net;
using System.Security.Cryptography;
using System.Text;

namespace gopaxApi
{
   class Program
   {
       static void Main(string[] args)
       {
           // 발급받은 api키와 시크릿키를 입력한다
           string apikey   = "";
           string secret   = "";

           string path     = "/balances";
           string method   = "GET";

           // nonce값 생성
           Int32 nonce = (Int32)(DateTime.UtcNow.Subtract(new DateTime(1970, 1, 1))).TotalSeconds;

           // 필수 정보를 연결하여 prehash 문자열을 생성함
           string what = nonce.ToString() + method + path;
           Encoding enc = new UTF8Encoding(true, true);
           byte[] whatData = enc.GetBytes(what);

           // base64로 secret을 디코딩함
           var secretDecodedData = System.Convert.FromBase64String(secret);

           // secret으로 sha512 hmac을 생성함
           // hmac으로 필수 메시지에 서명하고
           // 그 결과물을 base64로 인코딩함
           var signature = string.Empty;
           using (var hmac = new HMACSHA512(secretDecodedData))
           {
               var hash = hmac.ComputeHash(whatData);
               signature = Convert.ToBase64String(hash);
           }

           HttpWebRequest request = (HttpWebRequest)WebRequest.Create("https://api.gopax.co.kr" + path);
           request.Method = method;
           request.ContentType = "application/json";
           request.Headers.Add("NONCE", nonce.ToString());
           request.Headers.Add("API-KEY", apikey);
           request.Headers.Add("SIGNATURE", signature);

           try{
               using (HttpWebResponse response = (HttpWebResponse)request.GetResponse())
               using (Stream stream = response.GetResponseStream())
               using (StreamReader reader = new StreamReader(stream))
               {
                   Console.WriteLine(reader.ReadToEnd());
               }    
           }
           catch(Exception ex){
               Console.WriteLine(ex);
           }
       }
   }
}
