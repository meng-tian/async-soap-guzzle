<?php

namespace Meng\AsyncSoap\Guzzle;

use Meng\AsyncSoap\SoapClientInterface;
use Meng\Soap\HttpBinding\RequestBuilder;
use Meng\Soap\Interpreter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;

class SoapClient implements SoapClientInterface
{
    private $deferredInterpreter;
    private $client;
    private $httpBinding;

    public function __construct(ClientInterface $client, PromiseInterface $interpreterPromise, RequestBuilder $httpBinding)
    {
        $this->deferredInterpreter = $interpreterPromise;
        $this->client = $client;
        $this->httpBinding = $httpBinding;
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
        return $this->prepareRequest($name, $arguments, $options, $inputHeaders)
            ->then(
                function (RequestInterface $request) {
                    return $this->client->sendAsync($request);
                }
            )
            ->then(
                function (ResponseInterface $response) use ($name, $output_headers) {
                    return $this->deferredInterpreter->then(
                        function (Interpreter $interpreter) use ($response, $name, $output_headers) {
                            return $interpreter->response($response->getBody()->getContents(), $name, $output_headers);
                        }
                    );
                }
            );
    }

    /**
     * @param string $name
     * @param array $arguments
     * @param array $options
     * @param mixed $inputHeaders
     * @return PromiseInterface
     */
    private function prepareRequest($name, array $arguments, array $options = null, $inputHeaders = null)
    {
        return $this->deferredInterpreter->then(
            function (Interpreter $interpreter) use ($name, $arguments, $options, $inputHeaders) {
                $soapRequest = $interpreter->request($name, $arguments, $options, $inputHeaders);
                if ($soapRequest->getSoapVersion() == '1') {
                    $this->httpBinding->isSOAP11();
                } else {
                    $this->httpBinding->isSOAP12();
                }
                $this->httpBinding->setEndpoint($soapRequest->getEndpoint());
                $this->httpBinding->setSoapAction($soapRequest->getSoapAction());
                $this->httpBinding->setSoapMessage(\GuzzleHttp\Psr7\stream_for($soapRequest->getSoapMessage()));
                return $this->httpBinding->getSoapHttpRequest();
            }
        );

    }
}