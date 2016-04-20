<?php

namespace Meng\AsyncSoap\Guzzle;

use Meng\AsyncSoap\SoapClientInterface;
use Meng\Soap\HttpBinding\HttpBinding;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;

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

    public function callAsync($name, array $arguments, array $options = null, $inputHeaders = null, array &$output_headers = null)
    {
        return \GuzzleHttp\Promise\coroutine(
            function () use ($name, $arguments, $options, $inputHeaders, $output_headers) {
                /** @var HttpBinding $httpBinding */
                $httpBinding = (yield $this->deferredHttpBinding);
                $request = $httpBinding->request($name, $arguments, $options, $inputHeaders);
                try {
                    $response = (yield $this->client->sendAsync($request));
                } catch (RequestException $exception) {
                    $response = $exception->getResponse();
                } finally {
                    try {
                        yield $httpBinding->response($response, $name, $output_headers);
                    } finally {
                        $request->getBody()->close();
                        $response->getBody()->close();
                    }
                }
            }
        );
    }
}