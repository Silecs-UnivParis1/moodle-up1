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



//list of formations
$url = $urlbase . $urlext . '?wsdl' ;
$soapClient = new SoapClient($url);

$requestParams = array(
    '_cmd' => 'getAllFormations',
    '_lang' => 'fr-FR',
    );

$formResponse = $soapClient->__soapCall('getResponse', $requestParams);

// $formResponse = $soapClient->getResponse($requestParams);


var_dump($formResponse);