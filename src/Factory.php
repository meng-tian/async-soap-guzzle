<?php

namespace Meng\AsyncSoap\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use Meng\Soap\HttpBinding\HttpBinding;
use Meng\Soap\HttpBinding\RequestBuilder;
use Meng\Soap\Interpreter;
use Psr\Http\Message\ResponseInterface;

class Factory
{
    public function create(ClientInterface $client, $wsdl, array $options = [])
    {
        if (null === $wsdl) {
            $httpBindingPromise = new FulfilledPromise(
                new HttpBinding(new Interpreter($wsdl, $options), new RequestBuilder)
            );
        } else {
            $httpBindingPromise = $client->requestAsync('GET', $wsdl)->then(
                function (ResponseInterface $response) use ($options) {
                    $wsdl = $response->getBody()->__toString();
                    $interpreter = new Interpreter('data://text/plain;base64,' . base64_encode($wsdl), $options);
                    return new HttpBinding($interpreter, new RequestBuilder);
                }
            );
        }

        return new SoapClient($client, $httpBindingPromise);
    }
}