<?php

$urlbase = 'http://formation.univ-paris1.fr/';
$urlext ='cdm/services/cataManager';


$url = $urlbase . $urlext . '?wsdl' ;
$soapClient = new SoapClient($url, array('trace' => 1));

/*
echo "Functions: ";
$functions = $soapClient->__getFunctions();
print_r($functions);

echo "\nTypes: ";
$types = $soapClient->__getTypes();
print_r($types);

echo "\n\n**************\n\n";
*/

$reqParams = array(
    '_cmd' => 'getFormation',
    '_lang' => 'fr-FR',
    '_oid' => 'UP1-PROG34252',
);

$v = array();
foreach($reqParams as $key=>$value) {
    $v[] = array($value);
}
$callParams = array(
    'names' => array_keys($reqParams),
    'values' => $v,
);

try {
    //$formResponse = $soapClient->getResponse($reqParams);
    $formResponse = $soapClient->getResponse($callParams, '1010');
    // $formResponse = $soapClient->__soapCall('getResponse', $reqParams);
    echo $formResponse;
} catch (SoapFault $soapFault) {
    echo "SoapFault : \n";
    echo $soapFault;
    echo "\nFin SoapFault\n\n" ;
}

file_put_contents("lastrequest.xml", $soapClient->__getLastRequest());
file_put_contents("lastresponse.xml", $soapClient->__getLastResponse());
