<?php

namespace Meng\AsyncSoap\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function nonWsdlMode()
    {
        $factory = new Factory();
        $client = $factory->create(new Client(), null, ['uri'=>'', 'location'=>'']);
        $this->assertTrue($client instanceof SoapClient);
    }

    /**
     * @test
     */
    public function wsdlMode()
    {
        $wsdl = $this->getTestWsdl();

        $factory = new Factory();
        $handlerMock = new MockHandler([new Response('200', [], $wsdl)]);
        $handler = new HandlerStack($handlerMock);
        $clientMock = new Client(['handler' => $handler]);
        $client = $factory->create($clientMock, 'wsdl');
        $this->assertTrue($client instanceof SoapClient);

    }

    /**
     * @test
     */
    public function acceptWsdlContentToBePassedIn()
    {
        $wsdl = $this->getTestWsdl();
        $factory = new Factory();
        $clientMock = new Client();

        $soapClient = $factory->create($clientMock, $wsdl);

        $this->assertTrue($soapClient instanceof SoapClient);
    }

    private function getTestWsdl()
    {
        // this wsdl adapted from https://www.w3.org/2001/04/wsws-proceedings/uche/wsdl.html
        $wsdl = <<<EOD
<definitions name="EndorsementSearch"
  targetNamespace="http://namespaces.snowboard-info.com"
  xmlns:es="http://www.snowboard-info.com/EndorsementSearch.wsdl"
  xmlns:esxsd="http://schemas.snowboard-info.com/EndorsementSearch.xsd"
  xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
  xmlns="http://schemas.xmlsoap.org/wsdl/"
>

  <!-- omitted types section with content model schema info -->

  <message name="GetEndorsingBoarderRequest">
    <part name="body" element="esxsd:GetEndorsingBoarder"/>
  </message>

  <message name="GetEndorsingBoarderResponse">
    <part name="body" element="esxsd:GetEndorsingBoarderResponse"/>
  </message>

  <portType name="GetEndorsingBoarderPortType">
    <operation name="GetEndorsingBoarder">
      <input message="es:GetEndorsingBoarderRequest"/>
      <output message="es:GetEndorsingBoarderResponse"/>
    </operation>
  </portType>

  <binding name="EndorsementSearchSoapBinding"
           type="es:GetEndorsingBoarderPortType">
    <soap:binding style="document"
                  transport="http://schemas.xmlsoap.org/soap/http"/>
    <operation name="GetEndorsingBoarder">
      <soap:operation
        soapAction="http://www.snowboard-info.com/EndorsementSearch"/>
      <input>
        <soap:body use="literal"
          namespace="http://schemas.snowboard-info.com/EndorsementSearch.xsd"/>
      </input>
      <output>
        <soap:body use="literal"
          namespace="http://schemas.snowboard-info.com/EndorsementSearch.xsd"/>
      </output>
      <fault>
        <soap:body use="literal"
          namespace="http://schemas.snowboard-info.com/EndorsementSearch.xsd"/>
      </fault>
    </operation>
  </binding>

  <service name="EndorsementSearchService">
    <documentation>snowboarding-info.com Endorsement Service</documentation>
    <port name="GetEndorsingBoarderPort"
          binding="es:EndorsementSearchSoapBinding">
      <soap:address location="http://www.snowboard-info.com/EndorsementSearch"/>
    </port>
  </service>

</definitions>
EOD;

        return $wsdl;
    }
}
