<?php

use GuzzleHttp\Client;
use Meng\AsyncSoap\Guzzle\Factory;
use Meng\Soap\HttpBinding\RequestBuilder;

//todo try other options, e.g. classmap; try non-wsdl mode; try other web services.
class SoapClientTest extends PHPUnit_Framework_TestCase
{
    /** @var  Factory */
    private $factory;

    protected function setUp()
    {
        $this->factory = new Factory();
    }

    /**
     * @test
     */
    public function call()
    {
        $client = $this->factory->create(
            new Client(),
            new RequestBuilder(),
            'http://www.webservicex.net/Statistics.asmx?WSDL'
        );
        $response = $client->call('GetStatistics', [['X' => [1,2,3]]]);
        $this->assertNotEmpty($response);
    }

    /**
     * @test
     * @dataProvider webServicesProvider
     */
    public function callAsync($wsdl, $options, $function, $args, $contains)
    {
        $client = $this->factory->create(
            new Client(),
            new RequestBuilder(),
            $wsdl,
            $options
        );
        $response = $client->callAsync($function, $args)->wait();
        $this->assertNotEmpty($response);
        foreach ($contains as $contain) {
            $this->assertArrayHasKey($contain, (array)$response);
        }
    }

    public function webServicesProvider()
    {
        return [
            [
                'wsdl' => 'http://www.webservicex.net/Statistics.asmx?WSDL',
                'options' => [],
                'function' => 'GetStatistics',
                'args' => [['X' => [1,2,3]]],
                'contains' => [
                    'Sums', 'Average', 'StandardDeviation', 'skewness', 'Kurtosis'
                ]
            ],
            [
                'wsdl' => 'http://www.webservicex.net/CurrencyConvertor.asmx?WSDL',
                'options' => [],
                'function' => 'ConversionRate',
                'args' => [['FromCurrency' => 'GBP', 'ToCurrency' => 'USD']],
                'contains' => [
                    'ConversionRateResult'
                ]
            ],
        ];
    }
}