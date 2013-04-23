<?php

$urlbase = 'http://formation.univ-paris1.fr/';
$urlext = 'cdm/services/cataManager';
$url = $urlbase . $urlext . '?wsdl';

class MySoapClient extends SoapClient {
    private $template = '
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
                   xmlns:ns1="http://ws.catalogue.uniform.ustl.fr"
                   xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" >
  <SOAP-ENV:Body>
    <ns1:getResponse>
      <in0 xsi:type="ParametersBean">
        <names>
          <item xsi:type="xsd:string">_cmd</item>
          <item xsi:type="xsd:string">_lang</item>
          <item xsi:type="xsd:string">_oid</item>
        </names>
        <values>
          <item><item xsi:type="xsd:string">getFormation</item></item>
          <item><item xsi:type="xsd:string">fr-FR</item></item>
          <item><item xsi:type="xsd:string">%s</item></item>
        </values>
      </in0>
      <in1 xsi:type="xsd:string">%s</in1>
    </ns1:getResponse>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
';
    private $xmlRequest = '';

    function __doRequest($request, $location, $saction, $version, $one_way=null) {
        $doc = new DOMDocument();
        $doc->loadXML($this->xmlRequest);
        return parent::__doRequest($doc->saveXML(), $location, $saction, $version, $one_way);
    }

    function query($formation, $in1) {
        $this->xmlRequest = sprintf($this->template, $formation, $in1);
        return $this->getResponse();
    }
}

$soapClient = new MySoapClient($url);
$x = $soapClient->query('UP1-PROG34252', '1010');
echo $x;

file_put_contents("lastrequest.xml", $soapClient->__getLastRequest());
file_put_contents("lastresponse.xml", $soapClient->__getLastResponse());
