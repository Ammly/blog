---
extends: _layouts.post
section: content
title: Integrating Equity's Jenga API v2
date: 2019-05-03
description: Working with Equity's Jenga API and GuzzleHttp on Laravel
cover_image: /assets/img/intergration.svg
featured: true
categories: [API, Laravel]
---

This article will take you through the first steps to get started working with [Equity Bank's Jenga API](https://jengahq.io) to perform different transactions  using Laravel with GuzzleHttp.

I will assume you already registered for [JengaHq account](https://jengahq.io/) and your account is already activated.



## Generate SSL Certificate

- Follow the [Official Documentation](https://developer.jengaapi.io/docs/generating-signatures) guide on how to generate your SSL keys.
- Upload the public key to your [JengaHq dashboard API Keys section](https://test.jengahq.io/#!/developers/api-keys) on the API Keys section.

_One thing to note when uploading your public key is to remove the start and end. Otherwise you will get invalid certificate error._



## Create Your Test Application

We will be using __Laravel__ PHP Framework to perfom tests but you can use anything you like. See instructions to set up Laravel on the [Official Documentation](https://laravel.com/docs) or just create using composer

``composer create-project --prefer-dist laravel/laravel jenga``

This will create a project called __jenga__. 

* Navigate to the directory and setup your database credentials in the `.env` file.

* Store the SSL keys you generated [above](#generate-ssl-certificate) in the`storage` folder.

* Populate the following configs on your `.env` file with the credentials from your `Jenga Hq Account`


  ```bash
  JENGA_USERNAME=
  JENGA_PASSWORD=
  JENGA_API_KEY=
  JENGA_PHONE=
  JENGA_BASE_ENDPOINT=https://uat.jengahq.io
  
  ```

  
* Install  [GuzzleHttp](https://guzzlehttp.com) for making HTTP requests  ​	

  ​	` composer install GuzzleHttp `


* Create a `JengaController`, or use any name you like.

  ​	` php artisan make:controller JengaController `

  

## Generate Access Token

The generated token will be sent as the Authorization bearer token in the header of your requests. 

`Authorization: Bearer token`

- Create a route on your ` routes/web ` file. You can give it any name. 

  

  ​	`  Route::get('authenticate', 'JengaController@authenticate');`

  

- Create `  authenticate` action on `  JengaController`. We are also making a constructor to grab the config file settings for your Jenga Account.


  ```php 
  <?php 
  
  namespace App\Http\Controllers;
  
  use GuzzleHttp\Client;
  
  class Jenga {
      
      public $username;
      public $password;
      public $api_key;
      public $phone;
      public $endpoint;
      public $token;
  
      public function __construct()
      {
          $this->username = env('JENGA_USERNAME') ?? '';
          $this->password = env('JENGA_PASSWORD') ?? '';
          $this->api_key = env('JENGA_API_KEY') ?? '';
          $this->phone = env('JENGA_PHONE') ?? '';
          $this->endpoint = env('JENGA_ENDPOINT') ?? '';
          $this->token = $this->authenticate() ?? '';
      }
      
      public function authenticate()
      {
          try {
              $client = new Client([
                  'base_uri' => $this->endpoint,
                  'verify' => false
              ]);
              $request = $client->request('POST', '/identity/v2/token', [
                  'headers' => [
                  'Authorization' => $this->api_key,
                  'Content-type' => 'application/x-www-form-urlencoded',
                  ],
                  'form_params' => [
                      'username' => $this->username,
                      'password' => $this->password,
                  ],
              ]);
              $response = json_decode($request->getBody()->getContents())->access_token;
  
              return $response;
              
          } catch (RequestException $e) {
              return (string) $e->getResponse()->getBody();
          }
      }
  }
  
  ```

  This request if successful, should return a `  token ` that you will send with all your requests. 



## Send a uniquely signed signature with each request

The following are some requests you can make to JengaHq's API.

### Account Balance
Here's a request to get the account balance.

- Add a route on your ` routes/web ` file.  

  

  ​	`  Route::get('accountBalance', 'JengaController@accountBalance');`

  

- Create `  accountBalance` action on `  JengaController`. 

  

```php
<?php 
public function accountBalance($params)
    {
        $defaults = [
            'account_id' => 127381,
            'country_code' => 'KE',
            'date' => date('Y-m-d'),
        ];
    
        $params = array_merge($defaults, $params);
    
        $plainText = $params['country_code'].$params['account_id'];
    
        $privateKey = openssl_pkey_get_private('file://'.storage_path('privatekey.pem'));
    
        $token = $this->token;
    
        openssl_sign($plainText, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    
        try {
            $client = new Client(['base_uri' => $this->endpoint]);
            $request = $client->request('GET', 'account/v2/accounts/balances/'.$params['country_code'].'/'.$params['account_id'], [
                'headers' => [
                'Authorization' => 'Bearer '.$token,
                'signature' => base64_encode($signature),
                'Content-type' => 'application/json',
                'Accept' => '*/*',
                ],
            ]);
            $response = json_decode($request->getBody()->getContents());
            dd($response);
            //return $response;
        } catch (RequestException $e) {
            return (string) $e->getResponse()->getBody();
        }
    }

```

Successful Response Sample

```json
{
  "currency": "KES"
  "balances": array:2 [
    0 => {
      "amount": "84676.95"
      "type": "Current"
    }
    1 => {
      +"amount": "84676.95"
      +"type": "Available"
    }
  ]
}
```





### Send Money ( Within Equity Bank )
Here's a request to send money within Equity bank.

- Add a route on your ` routes/web ` file.  

  

  ​	`  Route::get('sendMoneyInternal', 'JengaController@sendMoneyInternal');`

  

- Add `  sendMoneyInternal` action on `  JengaController`. 

```php
<?php 

public function sendMoneyInternal()
    {
    	$params = [
            'country_code' => 'KE',
            'date' => date('Y-m-d'),
            'source_name' => 'John Doe',
            'source_accountNumber' => '0001092883',
            'destination_name' => 'Jane Doe',
            'destination_mobileNumber' => '25474738846',
            'destination_bankCode' => 63,
            'destination_accountNumber' => '9200002773',
            'transfer_currencyCode' => 'KES',
            'transfer_amount' => '10',
            'transfer_type' => 'InternalFundsTransfer',
            'transfer_reference' => '127364836548',
            'transfer_description' => 'Some description',
        ];
        
        $plainText = $params['source_accountNumber'].$params['transfer_amount'].$params['transfer_currencyCode'].$params['transfer_reference'];
    
        $privateKey = openssl_pkey_get_private('file://'.storage_path('privatekey.pem'));
    
        $token = $this->authenticate();
    
        openssl_sign($plainText, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    
        try {
            $client = new Client(['base_uri' => $this->endpoint]);
            $request = $client->request('POST', '/transaction/v2/remittance', [
                'headers' => [
                'Authorization' => 'Bearer '.$token,
                'signature' => base64_encode($signature),
                'Content-type' => 'application/json',
                'Accept' => '*/*',
                ],
                'json' => [
                    'source' => [
                      'countryCode' => $params['country_code'],
                      'name' => $params['source_name'],
                      'accountNumber' => $params['source_accountNumber'],
                   ],
                   'destination' => [
                      'type' => 'bank',
                      'countryCode' => $params['country_code'],
                      'name' => $params['destination_name'],
                      'accountNumber' => $params['destination_accountNumber'],
                   ],
                   'transfer' => [
                      'type' => $params['transfer_type'],
                      'amount' => $params['transfer_amount'],
                      'currencyCode' => $params['transfer_currencyCode'],
                      'reference' => $params['transfer_reference'],
                      'date' => $params['date'],
                      'description' => $params['transfer_description'],
                   ],
                ],
            ]);
            
            $response = json_decode($request->getBody());
            dd($response);
            //return $response;
            
        } catch (RequestException $e) {
            return (string) $e->getResponse()->getBody();
        }
    }

```

Successful Response Sample

```json
{
    "transactionId": "10000345333355",
    "status": "SUCCESS"
}

```

### Send Money ( PesaLink to Bank Account )
Here's a request to send money outside of Equity bank through PesaLink

- Add a route on your ` routes/web ` file.  

  

  ​	`  Route::get('sendMoneyPesalink', 'JengaController@sendMoneyPesalink');`

  

- Add `  sendMoneyPesalink` action on `  JengaController`. 

```php
<?php

public function sendMoneyPesalink($params)
    {
    	$defaults = [
            'country_code' => 'KE',
            'date' => date('Y-m-d'),
            'source_name' => 'John Doe',
            'source_accountNumber' => '0001092883',
            'destination_name' => 'Jane Doe',
            'destination_mobileNumber' => '25474738846',
            'destination_bankCode' => 63,
            'destination_accountNumber' => '9200002773',
            'transfer_currencyCode' => 'KES',
            'transfer_amount' => '10',
            'transfer_type' => 'PesaLink', 
            'transfer_reference' => '127364836548',
            'transfer_description' => 'Some description',
        ];
    
    	$params = array_merge($defaults, $params);
    
        $plainText = $params['transfer_amount'].$params['transfer_currencyCode'].$params['transfer_reference'].$params['destination_name'].$params['source_accountNumber'];
    
        $privateKey = openssl_pkey_get_private('file://'.storage_path('privatekey.pem'));
    
        $token = $this->authenticate();
    
        openssl_sign($plainText, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    
        try {
            $client = new Client(['base_uri' => $this->endpoint]);
            $request = $client->request('POST', '/transaction/v2/remittance', [
                'headers' => [
                'Authorization' => 'Bearer '.$token,
                'signature' => base64_encode($signature),
                'Content-type' => 'application/json',
                'Accept' => '*/*',
                ],
                'json' => [
                    'source' => [
                      'countryCode' => $params['country_code'],
                      'name' => $params['source_name'],
                      'accountNumber' => $params['source_accountNumber'],
                   ],
                   'destination' => [
                      'type' => 'bank',
                      'countryCode' => $params['country_code'],
                      'name' => $params['destination_name'],
                      'mobileNumber' => $params['destination_mobileNumber'],
                      'bankCode' => $params['destination_bankCode'],
                      'accountNumber' => $params['destination_accountNumber'],
                   ],
                   'transfer' => [
                      'type' => $params['transfer_type'],
                      'amount' => $params['transfer_amount'],
                      'currencyCode' => $params['transfer_currencyCode'],
                      'reference' => $params['transfer_reference'],
                      'date' => $params['date'],
                      'description' => $params['transfer_description'],
                   ],
                ],
            ]);
            $response = json_decode($request->getBody()->getContents());
            
            return $response;
            
        } catch (RequestException $e) {
            return (string) $e->getResponse()->getBody();
        }
    }

```

Successful Response

```json
{
    "transactionId": "10000345333355",
    "status": "SUCCESS"
}

```

There are other requests you can send just by following the [Jenga API documentation](https://developer.jengaapi.io/). I will clean up the code before uploading to github then post a link here.