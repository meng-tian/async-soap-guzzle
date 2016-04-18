# Asynchronous SOAP client

[![Build Status](https://travis-ci.org/meng-tian/async-soap-guzzle.svg?branch=master)](https://travis-ci.org/meng-tian/async-soap-guzzle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/meng-tian/async-soap-guzzle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/meng-tian/async-soap-guzzle/?branch=master)
[![codecov.io](https://codecov.io/github/meng-tian/async-soap-guzzle/coverage.svg?branch=master)](https://codecov.io/github/meng-tian/async-soap-guzzle?branch=master)

An asynchronous SOAP client build on top of Guzzle. The `SoapClient` implements [meng-tian/php-async-soap](https://github.com/meng-tian/php-async-soap).

## Requirement
PHP 5.5 --enablelibxml --enable-soap

## Install
```
composer install meng-tian/async-soap-guzzle
```

## Usage
```php
use GuzzleHttp\Client;
use Meng\AsyncSoap\Guzzle\Factory;

$factory = new Factory();
$client = $factory->create(new Client(), 'http://www.webservicex.net/Statistics.asmx?WSDL');

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
