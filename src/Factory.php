<?php

namespace Meng\AsyncSoap\Guzzle;

use GuzzleHttp\ClientInterface;
use Meng\Soap\HttpBinding\RequestBuilder;
use Meng\Soap\Interpreter;
use Psr\Http\Message\ResponseInterface;

class Factory
{
    public function create(ClientInterface $client, RequestBuilder $httpBinding, $wsdl, array $options = [])
    {
        $interpreterPromise = $client->requestAsync('GET', $wsdl)->then(
            function (ResponseInterface $response) use ($options) {
                $wsdl = $response->getBody()->__toString();
                return new Interpreter('data://text/plain;base64,' . base64_encode($wsdl), $options);
            }
        );

        return new SoapClient($client, $interpreterPromise, $httpBinding);
    }
}