<?php

namespace Meng\AsyncSoap\Guzzle;

use Meng\AsyncSoap\SoapClientInterface;
use Meng\Soap\HttpBinding\HttpBinding;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

class SoapClient implements SoapClientInterface
{
    private $deferredHttpBinding;
    private $client;

    public function __construct(ClientInterface $client, PromiseInterface $httpBindingPromise)
    {
        $this->deferredHttpBinding = $httpBindingPromise;
        $this->client = $client;
    }

    public function __call($name, $arguments)
    {
        return $this->callAsync($name, $arguments);
    }

    public function call($name, array $arguments, array $options = null, $inputHeaders = null, array &$outputHeaders = null)
    {
        $callPromise = $this->callAsync($name, $arguments, $options, $inputHeaders, $outputHeaders);
        return $callPromise->wait();
    }

    public function callAsync($name, array $arguments, array $options = null, $inputHeaders = null, array &$outputHeaders = null)
    {
        return \GuzzleHttp\Promise\coroutine(
            function () use ($name, $arguments, $options, $inputHeaders, &$outputHeaders) {
                /** @var HttpBinding $httpBinding */
                $httpBinding = (yield $this->deferredHttpBinding);
                $request = $httpBinding->request($name, $arguments, $options, $inputHeaders);

                try {
                    $response = (yield $this->client->sendAsync($request));
                    yield $this->parseResponse($httpBinding, $response, $name, $outputHeaders);
                } catch (RequestException $exception) {
                    if ($exception->hasResponse()) {
                        $response = $exception->getResponse();
                        yield $this->parseResponse($httpBinding, $response, $name, $outputHeaders);
                    } else {
                        throw $exception;
                    }
                } finally {
                    $request->getBody()->close();
                }
            }
        );
    }

    private function parseResponse(HttpBinding $httpBinding, ResponseInterface $response, $name, &$outputHeaders)
    {
        try {
            return $httpBinding->response($response, $name, $outputHeaders);
        } finally {
            $response->getBody()->close();
        }
    }
}