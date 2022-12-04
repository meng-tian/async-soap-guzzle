# Asynchronous SOAP client

[![codecov.io](https://codecov.io/github/meng-tian/async-soap-guzzle/coverage.svg?branch=master)](https://codecov.io/github/meng-tian/async-soap-guzzle?branch=master) ![workflow](https://github.com/meng-tian/async-soap-guzzle/actions/workflows/main.yaml/badge.svg)

An asynchronous SOAP client build on top of Guzzle. The `SoapClient` implements [meng-tian/php-async-soap](https://github.com/meng-tian/php-async-soap).

## Requirement
PHP 7.1 --enablelibxml --enable-soap

## Install
```
composer require meng-tian/async-soap-guzzle
```

## Usage
```php
use GuzzleHttp\Client;
use Meng\AsyncSoap\Guzzle\Factory;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;

$factory = new Factory();
$client = $factory->create(new Client(), new StreamFactory(), new RequestFactory(), 'http://www.webservicex.net/Statistics.asmx?WSDL');

// async call
$promise = $client->callAsync('GetStatistics', [['X' => [1,2,3]]]);
$result = $promise->wait();

// sync call
$result = $client->call('GetStatistics', [['X' => [1,2,3]]]);

// magic method
$promise = $client->GetStatistics(['X' => [1,2,3]]);
$result = $promise->wait();
```

## License
This library is released under [MIT](https://github.com/meng-tian/async-soap-guzzle/blob/master/LICENSE) license.
