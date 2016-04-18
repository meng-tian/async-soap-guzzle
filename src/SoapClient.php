<?php

namespace Meng\AsyncSoap\Guzzle;

use Meng\AsyncSoap\SoapClientInterface;
use Meng\Soap\HttpBinding\HttpBinding;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\ClientInterface;
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
        return $this->deferredHttpBinding->then(
            function (HttpBinding $httpBinding) use ($name, $arguments, $options, $inputHeaders) {
                $request = $httpBinding->request($name, $arguments, $options, $inputHeaders);
                return $this->client->sendAsync($request);
            }
        )->then(
            function (ResponseInterface $response) use ($name, $output_headers) {
                return $this->deferredHttpBinding->then(
                    function (HttpBinding $httpBinding) use ($response, $name, $output_headers) {
                        return $httpBinding->response($response, $name, $output_headers);
                    }
                );
            }
        );
    }
}