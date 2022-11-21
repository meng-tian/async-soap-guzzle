<?php

namespace Meng\AsyncSoap\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Meng\Soap\HttpBinding\HttpBinding;
use Meng\Soap\HttpBinding\RequestException;
use PHPUnit\Framework\TestCase;

class SoapClientTest extends TestCase
{
    /** @var  MockHandler */
    private $handlerMock;

    /** @var  ClientInterface */
    private $client;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $httpBindingMock;

    /** @var  PromiseInterface */
    private $httpBindingPromise;

    protected function setUp(): void
    {
        $this->handlerMock = new MockHandler();
        $handler = new HandlerStack($this->handlerMock);
        $this->client = new Client(['handler' => $handler]);

        $this->httpBindingMock = $this->getMockBuilder(HttpBinding::class)
            ->disableOriginalConstructor()
            ->setMethods(['request', 'response'])
            ->getMock();
    }

    /**
     * @test
     */
    public function magicCallDeferredHttpBindingRejected()
    {
        $this->httpBindingPromise = new RejectedPromise(new \Exception());
        $this->httpBindingMock->expects($this->never())->method('request');

        $client = new SoapClient($this->client, $this->httpBindingPromise);
        $this->expectException(\Exception::class);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     */
    public function magicCallHttpBindingFailed()
    {
        $this->httpBindingPromise = new FulfilledPromise($this->httpBindingMock);

        $this->httpBindingMock->method('request')
            ->will(
                $this->throwException(new RequestException())
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $this->httpBindingMock->expects($this->never())->method('response');

        $client = new SoapClient($this->client, $this->httpBindingPromise);
        $this->expectException(RequestException::class);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     */
    public function magicCall500Response()
    {
        $this->httpBindingPromise = new FulfilledPromise($this->httpBindingMock);

        $this->httpBindingMock->method('request')
            ->willReturn(
                new Request('POST', 'www.endpoint.com')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $response = new Response('500');
        $this->httpBindingMock->method('response')
            ->willReturn(
                'SoapResult'
            )
            ->with(
                $response, 'someSoapMethod', null
            );

        $this->handlerMock->append(GuzzleRequestException::create(new Request('POST', 'www.endpoint.com'), $response));

        $client = new SoapClient($this->client, $this->httpBindingPromise);
        $this->assertEquals('SoapResult', $client->someSoapMethod(['some-key' => 'some-value'])->wait());
    }

    /**
     * @test
     */
    public function magicCallResponseNotReceived()
    {
        $this->httpBindingPromise = new FulfilledPromise($this->httpBindingMock);

        $this->httpBindingMock->method('request')
            ->willReturn(
                new Request('POST', 'www.endpoint.com')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $this->httpBindingMock->expects($this->never())->method('response');

        $this->handlerMock->append(GuzzleRequestException::create(new Request('POST', 'www.endpoint.com')));

        $client = new SoapClient($this->client, $this->httpBindingPromise);
        $this->expectException(GuzzleRequestException::class);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     */
    public function magicCallUndefinedResponse()
    {
        $this->httpBindingPromise = new FulfilledPromise($this->httpBindingMock);

        $this->httpBindingMock->method('request')
            ->willReturn(
                new Request('POST', 'www.endpoint.com')
            )
            ->with(
                'someSoapMethod', [['some-key' => 'some-value']]
            );

        $this->httpBindingMock->expects($this->never())->method('response');

        $this->handlerMock->append(new \Exception());

        $client = new SoapClient($this->client, $this->httpBindingPromise);
        $this->expectException(\Exception::class);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();

    }

    /**
     * @test
     */
    public function magicCallClientReturnSoapFault()
    {
        $this->httpBindingPromise = new FulfilledPromise($this->httpBindingMock);

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

        $client = new SoapClient($this->client, $this->httpBindingPromise);
        $this->expectException(\SoapFault::class);
        $client->someSoapMethod(['some-key' => 'some-value'])->wait();
    }

    /**
     * @test
     */
    public function magicCallSuccess()
    {
        $this->httpBindingPromise = new FulfilledPromise($this->httpBindingMock);

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

        $client = new SoapClient($this->client, $this->httpBindingPromise);
        $this->assertEquals('SoapResult', $client->someSoapMethod(['some-key' => 'some-value'])->wait());
    }

    /**
     * @test
     */
    public function resultsAreEquivalent()
    {
        $this->httpBindingPromise = new FulfilledPromise($this->httpBindingMock);

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

        $client = new SoapClient($this->client, $this->httpBindingPromise);
        $magicResult = $client->someSoapMethod(['some-key' => 'some-value'])->wait();
        $syncResult = $client->call('someSoapMethod', [['some-key' => 'some-value']]);
        $asyncResult = $client->callAsync('someSoapMethod', [['some-key' => 'some-value']])->wait();
        $this->assertEquals($magicResult, $asyncResult);
        $this->assertEquals($syncResult, $asyncResult);
    }
}
