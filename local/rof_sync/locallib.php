<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.

$rofUrl = 'http://formation.univ-paris1.fr/cdm/services/cataManager?wsdl' ;

// fetchConstants();

// echo fetchComponents();

// fetchProgramsByComponent('01');

echo fetchPrograms(2);
echo "\n\n";
return 0;



/**
 * fetch "constantes" from webservice and insert them into table rof_constant
 *
 * @return lastinsertid
 * @todo manage updates ?
 */
function fetchConstants() {
global $DB;

    $reqParams = array(
        '_cmd' => 'getAllFormations',
        '_lang' => 'fr-FR',
        '__composante' => '00', // fausse composante, pour obtenir uniquement les constantes
        '__1' => '__composante',  // incompréhensible mais nécessaire
    );
    $xmlResp = doSoapRequest($reqParams);
    $xmlTree = new SimpleXMLElement($xmlResp);
    $constants = $xmlTree->properties->infoBlock->extension->uniform->constantes;

    // step 1 : element domaineDiplome
    foreach ($constants->domaineDiplome as $dd) {
        $elt='domaineDiplome';
        $elttype = (string)$dd->attributes()->type;
        foreach ($dd->data as $singledata) {
            $record = new stdClass();
            $record->element = $elt;
            $record->elementtype = $elttype;
            $record->dataid = (string)$singledata->attributes()->id;
            $record->dataimport = (string)$singledata->attributes()->import;
            $record->dataoai = (string)$singledata->attributes()->oai;
            $record->value = (string)$singledata->value;
            // print_r($record);
            $lastinsertid = $DB->insert_record('rof_constant', $record);
        }
    }
    // step 2 : other elements
    foreach ($constants->children() as $element) {
        $elt = (string)$element->getName();
        if ($elt == 'domaineDiplome') continue;
        foreach ($element->data as $singledata) {
            $record = new stdClass();
            $record->element = $elt;
            $record->elementtype = '';
            $record->dataid = (string)$singledata->attributes()->id;
            $record->dataimport = (string)$singledata->attributes()->import;
            $record->dataoai = (string)$singledata->attributes()->oai;
            $record->value = (string)$singledata->value;
            // print_r($record);
            $lastinsertid = $DB->insert_record('rof_constant', $record);
        }
    }
    return $lastinsertid;
}


/**
 * fetch "composantes" from webservice/database and insert them into table rof_component
 *
 * @return lastinsertid
 * @todo how to fetch rofid (ex. UP1-OU3282) from component number ??? implement this
 */
function fetchComponents() {
global $DB;

    $components = $DB->get_records('rof_constant', array('element' => 'composante'));

    foreach ($components as $component) {
        $record = new stdClass();
        $record->rofid = 0; // to be completed later
        $record->import = $component->dataimport; // = dataid
        $record->oai = $component->dataoai;
        $record->name = $component->value;
        $record->number = $component->dataid; // = dataimport
        $lastinsertid = $DB->insert_record('rof_component', $record);
    }

}

/**
 * fetch "programs" from webservice and insert them into table rof_program
 * @return number of inserted rows
 * @todo manage updates ?
 */
function fetchPrograms($verb=0) {
global $DB;
    $total = 0;

    $components = $DB->get_records_menu('rof_component', array(), '', 'id, number');
    foreach ($components as $id => $number) {
        $cnt = fetchProgramsByComponent($number);
        if ($verb > 0) {
            echo " : $number";
        }
        if ($verb > 1) {
            echo "->$cnt";
        }
        $total += $cnt;
    }
    return $total;
}

/**
 * fetch "programs" from webservice and insert them into table rof_program
 * limited to a Component
 * @param string $componentNumber (01 to 37, or more) = rof_component.number
 * @return number of inserted rows
 * @todo manage updates ?
 */
function fetchProgramsByComponent($componentNumber) {
global $DB;

    $reqParams = array(
        '_cmd' => 'getAllFormations',
        '_lang' => 'fr-FR',
        '__composante' => $componentNumber,
        '__1' => '__composante',  // incompréhensible mais nécessaire
    );
    $xmlResp = doSoapRequest($reqParams);
    $xmlTree = new SimpleXMLElement($xmlResp);
    $cnt = 0;

    foreach ($xmlTree->children() as $element) { //only program elements should be better
        $record = new stdClass();
        $elt = (string)$element->getName();
        if ($elt != 'program') continue;
        $record->compnumber = $componentNumber;
        $record->rofid = (string)$element->programID;
        $record->name  = (string)$element->programName->text;
        foreach($element->programCode as $code) {
            $codeset = (string)$code->attributes();
            $val = (string)$code[0];
            if (preg_match('/Diplome$/', $codeset) && ! strrchr($codeset, '.')) { // ni oai. ni uniform.
                $field = str_replace('Diplome', 'dip', $codeset);
                $record->$field = $val;
            }
        }
        $lastinsertid = $DB->insert_record('rof_program', $record);
        if ( $lastinsertid) {
            $cnt++;
        }
        // insert subprograms
        foreach($element->subProgram as $subp) {
            $record->rofid = (string)$subp->programID;
            $record->name  = (string)$subp->programName->text;
            $record->parentid = $lastinsertid;

            $slastinsertid = $DB->insert_record('rof_program', $record);
            if ( $slastinsertid) {
                $cnt++;
            }
        }
    }
    return $cnt;
}



/**
 * turns "logical" parameters into the form needed by the webservice
 * @param type $reqParams array of parameters
 * @return string XML response
 */
function doSoapRequest($reqParams) {
    global $rofUrl;

    $callParams = setCallParams($reqParams);
    $soapClient = new SoapClient($rofUrl, array('trace' => 1));

    try {
        $formResponse = $soapClient->getResponse($callParams, '1010');
        return $formResponse;
    } catch (SoapFault $soapFault) {
        echo "SoapFault : \n";
        echo $soapFault;
        file_put_contents("lastrequest.xml", $soapClient->__getLastRequest());
        echo "\nFin SoapFault\n\n" ;
        return false;
    }
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