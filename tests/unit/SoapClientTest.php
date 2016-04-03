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
use Meng\Soap\HttpBinding\RequestBuilder;
use Meng\Soap\HttpBinding\RequestException;
use Meng\Soap\Interpreter;
use Meng\Soap\SoapRequest;

class SoapClientTest extends \PHPUnit_Framework_TestCase
{
    private $handlerMock;
    private $clientMock;
    private $interpreterMock;
    private $httpBindingMock;
    private $deferredInterpreter;

    protected function setUp()
    {
        $this->handlerMock = new MockHandler();
        $handler = new HandlerStack($this->handlerMock);
        $this->clientMock = new Client(['handler' => $handler]);

        $this->interpreterMock = $this->getMockBuilder(Interpreter::class)
            ->disableOriginalConstructor()
            ->setMethods(['request', 'response'])
            ->getMock();

        $this->httpBindingMock = $this->getMockBuilder(RequestBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSOAP11', 'isSOAP12', 'setEndpoint', 'setSoapAction', 'setSoapMessage', 'getSoapHttpRequest'])
            ->getMock();
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function magicCallInterpreterFailed()
    {
        $this->deferredInterpreter = new RejectedPromise(new \Exception());
        $this->interpreterMock->expects($this->never())->method('request');

        $client = new SoapClient($this->clientMock, $this->deferredInterpreter, $this->httpBindingMock);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     * @expectedException \Meng\Soap\HttpBinding\RequestException
     */
    public function magicCallHttpBindingFailed()
    {
        $this->deferredInterpreter = new FulfilledPromise($this->interpreterMock);

        $this->interpreterMock->method('request')
            ->willReturn(
                new SoapRequest('www.endpoint.com', 'soapaction', '1', 'message')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $this->httpBindingMock->expects($this->exactly(1))->method('isSOAP11');
        $this->httpBindingMock->expects($this->never())->method('isSOAP12');
        $this->httpBindingMock->expects($this->exactly(1))->method('setEndpoint')->with('www.endpoint.com');
        $this->httpBindingMock->expects($this->exactly(1))->method('setSoapAction')->with('soapaction');
        $this->httpBindingMock->expects($this->exactly(1))->method('setSoapMessage')->with('message');
        $this->httpBindingMock->method('getSoapHttpRequest')->will($this->throwException(new RequestException()));

        $client = new SoapClient($this->clientMock, $this->deferredInterpreter, $this->httpBindingMock);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     * @expectedException \GuzzleHttp\Exception\RequestException
     */
    public function magicCallClientReturnError()
    {
        $this->deferredInterpreter = new FulfilledPromise($this->interpreterMock);

        $this->interpreterMock->method('request')
            ->willReturn(
                new SoapRequest('www.endpoint.com', 'soapaction', '1', 'message')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $this->httpBindingMock->expects($this->exactly(1))->method('isSOAP11');
        $this->httpBindingMock->expects($this->never())->method('isSOAP12');
        $this->httpBindingMock->expects($this->exactly(1))->method('setEndpoint')->with('www.endpoint.com');
        $this->httpBindingMock->expects($this->exactly(1))->method('setSoapAction')->with('soapaction');
        $this->httpBindingMock->expects($this->exactly(1))->method('setSoapMessage')->with('message');
        $this->httpBindingMock->method('getSoapHttpRequest')->willReturn(new Request('POST', 'www.endpoint.com'));

        $this->handlerMock->append(GuzzleRequestException::create(new Request('POST', 'www.endpoint.com'), new Response('500')));

        $client = new SoapClient($this->clientMock, $this->deferredInterpreter, $this->httpBindingMock);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     * @expectedException \SoapFault
     */
    public function magicCallClientReturnSoapFault()
    {
        $this->deferredInterpreter = new FulfilledPromise($this->interpreterMock);

        $this->interpreterMock->method('request')
            ->willReturn(
                new SoapRequest('www.endpoint.com', 'soapaction', '2', 'message')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $this->interpreterMock->method('response')
            ->will(
                $this->throwException(new \SoapFault('soap fault', 'soap fault'))
            )
            ->with(
                'body', 'someSoapMethod', null
            );

        $this->httpBindingMock->expects($this->never())->method('isSOAP11');
        $this->httpBindingMock->expects($this->exactly(1))->method('isSOAP12');
        $this->httpBindingMock->expects($this->exactly(1))->method('setEndpoint')->with('www.endpoint.com');
        $this->httpBindingMock->expects($this->exactly(1))->method('setSoapAction')->with('soapaction');
        $this->httpBindingMock->expects($this->exactly(1))->method('setSoapMessage')->with('message');
        $this->httpBindingMock->method('getSoapHttpRequest')->willReturn(new Request('POST', 'www.endpoint.com'));

        $this->handlerMock->append(new Response('200', [], 'body'));

        $client = new SoapClient($this->clientMock, $this->deferredInterpreter, $this->httpBindingMock);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     */
    public function magicCallSuccess()
    {
        $this->deferredInterpreter = new FulfilledPromise($this->interpreterMock);

        $this->interpreterMock->method('request')
            ->willReturn(
                new SoapRequest('www.endpoint.com', 'soapaction', '1', 'message')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $this->interpreterMock->method('response')
            ->willReturn(
                'SoapResult'
            )
            ->with(
                'body', 'someSoapMethod', null
            );

        $this->httpBindingMock->expects($this->exactly(1))->method('isSOAP11');
        $this->httpBindingMock->expects($this->never())->method('isSOAP12');
        $this->httpBindingMock->expects($this->exactly(1))->method('setEndpoint')->with('www.endpoint.com');
        $this->httpBindingMock->expects($this->exactly(1))->method('setSoapAction')->with('soapaction');
        $this->httpBindingMock->expects($this->exactly(1))->method('setSoapMessage')->with('message');
        $this->httpBindingMock->method('getSoapHttpRequest')->willReturn(new Request('POST', 'www.endpoint.com'));

        $this->handlerMock->append(new Response('200', [], 'body'));

        $client = new SoapClient($this->clientMock, $this->deferredInterpreter, $this->httpBindingMock);
        $this->assertEquals('SoapResult', $client->someSoapMethod(['some-key' => 'some-value'])->wait());
    }

    /**
     * @test
     */
    public function resultsAreEquivalent()
    {
        $this->deferredInterpreter = new FulfilledPromise($this->interpreterMock);

        $this->interpreterMock->method('request')
            ->willReturn(
                new SoapRequest('www.endpoint.com', 'soapaction', '1', 'message')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $this->interpreterMock->method('response')->willReturn(
            'SoapResult'
        );

        $this->httpBindingMock->expects($this->any())->method('isSOAP11');
        $this->httpBindingMock->expects($this->never())->method('isSOAP12');
        $this->httpBindingMock->expects($this->any())->method('setEndpoint')->with('www.endpoint.com');
        $this->httpBindingMock->expects($this->any())->method('setSoapAction')->with('soapaction');
        $this->httpBindingMock->expects($this->any())->method('setSoapMessage')->with('message');
        $this->httpBindingMock->method('getSoapHttpRequest')->willReturn(new Request('POST', 'www.endpoint.com'));

        $this->handlerMock->append(new Response('200', [], 'body'));
        $this->handlerMock->append(new Response('200', [], 'body'));
        $this->handlerMock->append(new Response('200', [], 'body'));

        $client = new SoapClient($this->clientMock, $this->deferredInterpreter, $this->httpBindingMock);
        $magicResult = $client->someSoapMethod(['some-key' => 'some-value'])->wait();
        $syncResult = $client->call('someSoapMethod', [['some-key' => 'some-value']]);
        $asyncResult = $client->callAsync('someSoapMethod', [['some-key' => 'some-value']])->wait();
        $this->assertEquals($magicResult, $asyncResult);
        $this->assertEquals($syncResult, $asyncResult);
    }
}
