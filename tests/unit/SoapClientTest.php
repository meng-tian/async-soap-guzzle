<?php

namespace Meng\AsyncSoap\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Meng\Soap\HttpBinding\HttpBinding;
use Meng\Soap\HttpBinding\RequestException;

class SoapClientTest extends \PHPUnit_Framework_TestCase
{
    private $handlerMock;
    private $clientMock;
    private $httpBindingMock;
    private $deferredHttpBinding;

    protected function setUp()
    {
        $this->handlerMock = new MockHandler();
        $handler = new HandlerStack($this->handlerMock);
        $this->clientMock = new Client(['handler' => $handler]);

        $this->httpBindingMock = $this->getMockBuilder(HttpBinding::class)
            ->disableOriginalConstructor()
            ->setMethods(['request', 'response'])
            ->getMock();
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function magicCallDeferredHttpBindingRejected()
    {
        $this->deferredHttpBinding = new RejectedPromise(new \Exception());
        $this->httpBindingMock->expects($this->never())->method('request');

        $client = new SoapClient($this->clientMock, $this->deferredHttpBinding);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     * @expectedException \Meng\Soap\HttpBinding\RequestException
     */
    public function magicCallHttpBindingFailed()
    {
        $this->deferredHttpBinding = new FulfilledPromise($this->httpBindingMock);

        $this->httpBindingMock->method('request')
            ->will(
                $this->throwException(new RequestException())
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $client = new SoapClient($this->clientMock, $this->deferredHttpBinding);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     * @expectedException \GuzzleHttp\Exception\RequestException
     */
    public function magicCallClientReturnError()
    {
        $this->deferredHttpBinding = new FulfilledPromise($this->httpBindingMock);

        $this->httpBindingMock->method('request')
            ->willReturn(
                new Request('POST', 'www.endpoint.com')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $this->handlerMock->append(GuzzleRequestException::create(new Request('POST', 'www.endpoint.com'), new Response('500')));

        $client = new SoapClient($this->clientMock, $this->deferredHttpBinding);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     * @expectedException \SoapFault
     */
    public function magicCallClientReturnSoapFault()
    {
        $this->deferredHttpBinding = new FulfilledPromise($this->httpBindingMock);

        $this->httpBindingMock->method('request')
            ->willReturn(
                new Request('POST', 'www.endpoint.com')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $response = new Response('200', [], 'body');
        $this->httpBindingMock->method('response')
            ->will(
                $this->throwException(new \SoapFault('soap fault', 'soap fault'))
            )
            ->with(
                $response, 'someSoapMethod', null
            );

        $this->handlerMock->append($response);

        $client = new SoapClient($this->clientMock, $this->deferredHttpBinding);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     */
    public function magicCallSuccess()
    {
        $this->deferredHttpBinding = new FulfilledPromise($this->httpBindingMock);

        $this->httpBindingMock->method('request')
            ->willReturn(
                new Request('POST', 'www.endpoint.com')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $response = new Response('200', [], 'body');
        $this->httpBindingMock->method('response')
            ->willReturn(
                'SoapResult'
            )
            ->with(
                $response, 'someSoapMethod', null
            );

        $this->handlerMock->append($response);

        $client = new SoapClient($this->clientMock, $this->deferredHttpBinding);
        $this->assertEquals('SoapResult', $client->someSoapMethod(['some-key' => 'some-value'])->wait());
    }

    /**
     * @test
     */
    public function resultsAreEquivalent()
    {
        $this->deferredHttpBinding = new FulfilledPromise($this->httpBindingMock);

        $this->httpBindingMock->method('request')
            ->willReturn(
                new Request('POST', 'www.endpoint.com')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $response = new Response('200', [], 'body');
        $this->httpBindingMock->method('response')->willReturn(
            'SoapResult'
        );

        $this->handlerMock->append($response);
        $this->handlerMock->append($response);
        $this->handlerMock->append($response);

        $client = new SoapClient($this->clientMock, $this->deferredHttpBinding);
        $magicResult = $client->someSoapMethod(['some-key' => 'some-value'])->wait();
        $syncResult = $client->call('someSoapMethod', [['some-key' => 'some-value']]);
        $asyncResult = $client->callAsync('someSoapMethod', [['some-key' => 'some-value']])->wait();
        $this->assertEquals($magicResult, $asyncResult);
        $this->assertEquals($syncResult, $asyncResult);
    }
}
