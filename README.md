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
From [v0.4.0](https://github.com/meng-tian/async-soap-guzzle/tree/v0.4.0) or newer, an instance of `Psr\Http\Message\RequestFactoryInterface` and an instance of `Psr\Http\Message\StreamFactoryInterface` need to be injected into `Meng\AsyncSoap\Guzzle\Factory`. These two interfaces are defined in [PSR-17](https://www.php-fig.org/psr/psr-17/) to create [PSR-7](https://www.php-fig.org/psr/psr-7/) compliant HTTP instances. This change will decouple this library from any specific implementation of PSR-7 and PSR-17. Clients can determine which implementation of PSR-17 they want to use. Plenty of different implementations of PSR17 can be found from [Packagist](https://packagist.org/?query=psr-17), e.g., `symfony/psr-http-message-bridge`, or `laminas/laminas-diactoros`.

1. Require this library and an implementation of PSR-17 in your `composer.json`:
```json
...
    "require": {
        "php": ">=7.1.0",
        "meng-tian/async-soap-guzzle": "~0.4.0",
        "laminas/laminas-diactoros": "^2.0"  # this can be replaced by any implementation of PSR-17
    },
...
```
2. Run `composer install`

3. Create your async SOAP client and call your SOAP messages:
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
