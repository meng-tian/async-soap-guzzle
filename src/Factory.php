<?php

namespace Meng\AsyncSoap\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use Meng\AsyncSoap\SoapClientInterface;
use Meng\Soap\HttpBinding\HttpBinding;
use Meng\Soap\HttpBinding\RequestBuilder;
use Meng\Soap\Interpreter;
use Psr\Http\Message\ResponseInterface;

class Factory
{
    /**
     * Create an instance of SoapClientInterface asynchronously.
     *
     * @param ClientInterface $client       A Guzzle HTTP client.
     * @param mixed $wsdlOrWsdlUrl          WSDL or URI of the WSDL file or NULL if working in non-WSDL mode.
     * @param array $options                Supported options: location, uri, style, use, soap_version, encoding,
     *                                      exceptions, classmap, typemap, and feature. HTTP related options should
     *                                      be configured against $client, e.g., authentication, proxy, user agent,
     *                                      and connection timeout etc.
     * @return SoapClientInterface
     */
    public function create(ClientInterface $client, $wsdlOrWsdlUrl, array $options = [])
    {
        if (null === $wsdlOrWsdlUrl) {
            $httpBindingPromise = new FulfilledPromise(
                new HttpBinding(new Interpreter($wsdlOrWsdlUrl, $options), new RequestBuilder)
            );
        } elseif ('<' === $wsdlOrWsdlUrl[0]) {
            $httpBindingPromise = new FulfilledPromise(
                new HttpBinding(
                    new Interpreter('data://text/plain;base64,' . base64_encode($wsdlOrWsdlUrl), $options),
                    new RequestBuilder
                )
            );
        } else {
            $httpBindingPromise = $client->requestAsync('GET', $wsdlOrWsdlUrl)->then(
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