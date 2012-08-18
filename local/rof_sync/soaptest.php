<?php

$urlbase = 'http://formation.univ-paris1.fr/';
$urlext ='cdm/services/cataManager';
$url = $urlbase . $urlext . '?wsdl' ;

$soapClient = new SoapClient($url, array('trace' => 1));


$reqParams = array(
    '_cmd' => 'getAllFormations',
    '_lang' => 'fr-FR',
    '__composante' => '00',
    '__1' => '__composante',  // incompréhensible mais nécessaire
//    '_oid' => 'UP1-PROG34252',
);

$callParams = setCallParams($reqParams);

try {
    $formResponse = $soapClient->getResponse($callParams, '1010');
    echo $formResponse;
} catch (SoapFault $soapFault) {
    echo "SoapFault : \n";
    echo $soapFault;
    file_put_contents("lastrequest.xml", $soapClient->__getLastRequest());
    // file_put_contents("lastresponse.xml", $soapClient->__getLastResponse());
    echo "\nFin SoapFault\n\n" ;
}



/**
 * Turn "logical" $reqParams into "artificial" $callParams used to request
 * the CDM-fr web service
 * @param  array $reqParams
 * @return $callParams
 */
function setCallParams($reqParams) {
    $v = array();
    foreach($reqParams as $key=>$value) {
        $v[] = array($value);
    }
    $callParams = array(
        'names' => array_keys($reqParams),
        'values' => $v,
    );
    return $callParams;
}


/**
 * Display the WSDL auto-documented prototypes
 * @return void
 */
function displayWsdlInformation($url) {
    $soapClient = new SoapClient($url);

    echo "Functions: ";
    $functions = $soapClient->__getFunctions();
    print_r($functions);

    echo "\nTypes: ";
    $types = $soapClient->__getTypes();
    print_r($types);

    echo "\n\n**************\n\n";
}