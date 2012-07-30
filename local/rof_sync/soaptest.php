<?php

$urlbase = 'http://formation.univ-paris1.fr/';
$urlext ='cdm/services/cataManager';


$url = $urlbase . $urlext . '?wsdl' ;
$soapClient = new SoapClient($url);

//print_r($soapClient);

echo "Functions: ";
$functions = $soapClient->__getFunctions();
print_r($functions);

echo "\nTypes: ";
$types = $soapClient->__getTypes();
print_r($types);

echo "\n\n**************\n\n";

//list of formations
$url = $urlbase . $urlext . '?wsdl' ;
$soapClient = new SoapClient($url, array('trace' => 1));

/*
$requestParams = array(
    '_cmd' => 'getAllFormations',
    '_lang' => 'fr-FR',
    );
*/
$requestParams = array(
    '_cmd' => 'getFormation',
    '_lang' => 'fr-FR',
    '_oid' => 'UP1-PROG34252',
    );

try {
    // $formResponse = $soapClient->__soapCall('getParametersBean', $requestParams);
    $formResponse = $soapClient->__soapCall('getResponse', $requestParams);
} catch (SoapFault $soapFault) {
    echo "SoapFault : \n";
    echo $soapFault;
    echo "\nFin SoapFault\n\n" ;
}

$handle = fopen("lastrequest.xml", "w");
fwrite ($handle, $soapClient->__getLastRequest());
// echo "Request : \n", $soapClient->__getLastRequest(), "\n";
fclose($handle);
$handle = fopen("lastresponse.xml", "w");
fwrite ($handle, $soapClient->__getLastResponse());
// echo "Response : \n", $soapClient->__getLastResponse(), "\n";
fclose($handle);


// $formResponse = $soapClient->getResponse($requestParams);


//var_dump($formResponse);