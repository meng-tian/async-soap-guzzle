<?php

namespace Meng\AsyncSoap\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    /**
     * @test
     */
    public function nonWsdlMode()
    {
        $factory = new Factory();
        $client = $factory->create(new Client(), new StreamFactory(), new RequestFactory(), null, ['uri'=>'', 'location'=>'']);

        $this->assertTrue($client instanceof SoapClient);
    }

    /**
     * @test
     */
    public function wsdlFromHttpUrl()
    {
        $handlerMock = new MockHandler([
            new Response('200', [], fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'example.wsdl', 'r'))
        ]);
        $handler = new HandlerStack($handlerMock);
        $clientMock = new Client(['handler' => $handler]);

        $factory = new Factory();
        $client = $factory->create($clientMock, new StreamFactory, new RequestFactory, 'http://www.mysite.com/wsdl');

        $this->assertTrue($client instanceof SoapClient);
    }

    /**
     * @test
     */
    public function wsdlFromLocalFile()
    {
        $factory = new Factory();
        $client = $factory->create(new Client(), new StreamFactory(), new RequestFactory(), dirname(__FILE__) . DIRECTORY_SEPARATOR . 'example.wsdl');

        $this->assertTrue($client instanceof SoapClient);
    }

    /**
     * @test
     */
    public function wsdlFromDataUri()
    {
        $wsdlString = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'example.wsdl');
        $wsdl = 'data://text/plain;base64,' . base64_encode($wsdlString);

        $factory = new Factory();
        $client = $factory->create(new Client(), new StreamFactory(), new RequestFactory(), $wsdl);

        $this->assertTrue($client instanceof SoapClient);
    }
}
