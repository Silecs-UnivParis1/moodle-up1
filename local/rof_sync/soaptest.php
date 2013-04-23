<?php

$urlbase = 'http://formation.univ-paris1.fr/';
$urlext ='cdm/services/cataManager';
$url = $urlbase . $urlext . '?wsdl' ;

$soapClient = new SoapClient($url, array('trace' => 1));


$reqParams = array(
    '_cmd' => 'getFormation',
    '_lang' => 'fr-FR',
    '_oid' => $argv[1],  // UP1-PROG28336 program ; UP1-PROG28337 subprogram
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

