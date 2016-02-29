<?php

use GuzzleHttp\Client;
use Meng\AsyncSoap\Guzzle\Factory;
use Meng\Soap\HttpBinding\RequestBuilder;

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
            'http://www.webservicex.net/Statistics.asmx?WSDL',
            new RequestBuilder()
        );
        $response = $client->call('GetStatistics', [['X' => [1,2,3]]]);
        $this->assertNotEmpty($response);
    }

    /**
     * @test
     */
    public function callAsync()
    {
        $client = $this->factory->create(
            new Client(),
            'http://www.webservicex.net/Statistics.asmx?WSDL',
            new RequestBuilder()
        );
        $response = null;
        $promise = $client->callAsync('GetStatistics', [['X' => [1,2,3]]])->then(
            function ($result) use (&$response) {
                $response = $result;
            }
        );
        $promise->wait();
        $this->assertNotEmpty($response);
    }
}