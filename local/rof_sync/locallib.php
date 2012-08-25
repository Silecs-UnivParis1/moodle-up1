<?php

$rofUrl = 'http://formation.univ-paris1.fr/cdm/services/cataManager?wsdl' ;


/**
 * fetch "constantes" from webservice and insert them into table rof_constant
 *
 * @param bool $dryrun : if set, no modification to database
 * @return lastinsertid
 */
function fetchConstants($dryrun=0) {
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
            if (! $dryrun ) {
                $lastinsertid = $DB->insert_record('rof_constant', $record);
            }
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
            if (! $dryrun ) {
            $lastinsertid = $DB->insert_record('rof_constant', $record);
            }
        }
    }
    return $lastinsertid;
}


/**
 * fetch "composantes" from webservice/database and insert them into table rof_component
 * @param bool $dryrun : if set, no modification to database
 * @return lastinsertid
 * @todo how to fetch rofid (ex. UP1-OU3282) from component number ??? implement this
 */
function fetchComponents($dryrun=0) {
global $DB;

    $components = $DB->get_records('rof_constant', array('element' => 'composante'));

    foreach ($components as $component) {
        $record = new stdClass();
        $record->rofid = ''; // to be completed later
        $record->import = $component->dataimport; // = dataid
        $record->oai = $component->dataoai;
        $record->name = $component->value;
        $record->number = $component->dataid; // = dataimport
        $record->sub= ''; // to be completed later
        if (! $dryrun ) {
            $lastinsertid = $DB->insert_record('rof_component', $record);
        }
    }

}

/**
 * fetch "programs" and "subPrograms" from webservice and insert them into table rof_program
 * @param integer $verb verbosity
 * @param bool $dryrun : if set, no modification to database
 * @return number of inserted rows
 */
function fetchPrograms($verb=0, $dryrun=0) {
global $DB;
    $total = 0;

    $reqParams = array(
        '_cmd' => 'getAllFormations',
        '_lang' => 'fr-FR',
        '__composante' => null, // à modifier
        '__1' => '__composante',  // incompréhensible mais nécessaire
    );

    $components = $DB->get_records_menu('rof_component', array(), '', 'id, number');
    foreach ($components as $id => $compNumber) {
        $subComp[$compNumber] = array(); // liste des programmes fils
        $reqParams['__composante'] = $compNumber;
        $xmlResp = doSoapRequest($reqParams);
        $xmlTree = new SimpleXMLElement($xmlResp);
        $cnt = 0;

        foreach ($xmlTree->children() as $element) { //only program elements should be better
            $elt = (string)$element->getName();
            if ($elt != 'program') continue;
            $ProgRofid = (string)$element->programID;
            $subComp[$compNumber][] = $ProgRofid;
            if ( $DB->record_exists('rof_program', array('rofid' => $ProgRofid) ) ) {
                // already seen, by another component
                continue;
            }
            $subProgs[$ProgRofid] = array();
            $record = new stdClass();
            $record->compnumber = ''; // potentiellement plusieurs composantes mères
            $record->rofid = $ProgRofid;
            $record->name  = (string)$element->programName->text;
            $record->level = 1;
            $record->oneparent = $compNumber;
            foreach($element->programCode as $code) {
                $codeset = (string)$code->attributes();
                $val = (string)$code[0];
                if (preg_match('/Diplome$/', $codeset) && ! strrchr($codeset, '.')) { // ni oai. ni uniform.
                    $field = str_replace('Diplome', 'dip', $codeset);
                    $record->$field = $val;
                }
            }
            if (! $dryrun ) {
                $lastinsertid = $DB->insert_record('rof_program', $record);
                if ( $lastinsertid) {
                    $cnt++;
                }
            }
            // insert subprograms
            foreach($element->subProgram as $subp) {
                $record->rofid = (string)$subp->programID;
                $subProgs[$ProgRofid][] = $record->rofid;
                if ( $DB->record_exists('rof_program', array('rofid' => $record->rofid) ) ) {
                    continue;
                }
                $record->name  = (string)$subp->programName->text;
                $record->level = 2;
                $record->oneparent = $ProgRofid;
                if (! $dryrun ) {
                    $slastinsertid = $DB->insert_record('rof_program', $record);
                    if ( $slastinsertid) {
                        $cnt++;
                    }
                }
            }
            // update program to store subprograms
            $dbprogram = $DB->get_record('rof_program', array('id' => $lastinsertid));
            $dbprogram->sub = serializeArray($subProgs[$ProgRofid]);
            if (! $dryrun ) {
                $DB->update_record('rof_program', $dbprogram);
            }
        } //foreach ($element)

        if ($verb > 0) {
            echo " : $compNumber->$cnt";
        }
        $total += $cnt;
    } //foreach($components)


    if ($verb > 0) {
        echo "\n relations composantes <-> programmes\n";
    }
    foreach ($components as $id => $compNumber) {
        // composante -> programmes
        if ($verb > 0) {
            echo "$compNumber ";
        }
        $dbcomp= $DB->get_record('rof_component', array('number' => $compNumber));
        $dbcomp->sub = serializeArray($subComp[$compNumber]);
        if (! $dryrun ) {
            $DB->update_record('rof_component', $dbcomp);
        }

        // programme -> composantes
        foreach ($subComp[$compNumber] as $prog) {
            $parentProg[$prog][] = $compNumber;
        }
    }
    foreach ($parentProg as $prog => $parents) {
        if ($verb > 0) {
            echo ".";
        }
        $dbprog= $DB->get_record('rof_program', array('rofid' => $prog));
        $dbprog->parents = serializeArray($parents);
        $dbprog->components = $dbprog->parents;
        $dbprog->parentsnb = count($parents);
        if (! $dryrun ) {
            $DB->update_record('rof_program', $dbprog);
        }
    }

    if ($verb > 0) {
        echo "\n relations programmes <-> sous-programmes\n";
    }
    // relations programmes <-> sous-programmes
    foreach ($subProgs as $prog => $listSubs) {
        if ($verb > 0) { echo '.'; }
        foreach ($listSubs as $subprog) {
            $parentSubProg[$subprog][] = $prog;
        }
    }
    foreach ($parentSubProg as $subprog => $listParents) {
        if ($verb > 0) { echo '*'; }
        $dbprog= $DB->get_record('rof_program', array('rofid' => $subprog));
        $dbprog->parents = serializeArray($listParents);
        $dbprog->parentsnb = count($listParents);
        if (! $dryrun ) {
            $DB->update_record('rof_program', $dbprog);
        }
    }

    return $total;
}


/**
 * fetch "courses" from webservice and insert them into table rof_course
 * @param integer $verb verbosity
 * @param bool $dryrun : if set, no modification to database
 * @return number of inserted rows
 */
function fetchCourses($verb=0, $dryrun=0) {
global $DB;
    $total = 0;
    $dbltotal = 0;
    $cnt = 1;

    $programs = $DB->get_records_menu('rof_program', array('level' => 1), '', 'id, rofid');
    foreach ($programs as $id => $progRofId) {
        if ($verb > 0) {
            echo '.';
        }
        if ($verb > 1) {
            echo "\n". $cnt. "  id=". $id ."  p=". $progRofId ."->";
        }
        $count = fetchCoursesByProgram($progRofId, $verb, $dryrun);
        $total += $count[0];
        $dbltotal += $count[1];
        if ($verb > 1) {
            echo " cnt=". $count[0] . "   dbl=".$count[1];
        }
        $cnt++;
    }
    if ($verb > 0) {
        echo "\nCourses : total=$total  doublons=$dbltotal \n";
    }
    return $total;
}

/**
 * fetch "courses" from webservice and insert them into table rof_course
 * limited to a Program
 * @param string $progRofId (ex. UP1-PROG35376) = rof_program.rofid
 * @param integer $verb verbosity
 * @param bool $dryrun : if set, no modification to database
 * @return array(number of inserted rows, number of prevented doublets)
 */
function fetchCoursesByProgram($progRofId, $verb=0, $dryrun=0) {
global $DB;

    $reqParams = array(
        '_cmd' => 'getFormation',
        '_lang' => 'fr-FR',
        '_oid' => $progRofId,
    );
    $xmlResp = doSoapRequest($reqParams);
    $xmlTree = new SimpleXMLElement($xmlResp);
    $cnt = 0;
    $dblcnt = 0; // compte les doublons évités

    // references subProgram -> courses level 1 (UE)
    $program = $xmlTree->program;
    foreach($program->subProgram as $subp) {
        $subpRofId = (string)$subp->programID;
        $subsProg[$subpRofId] = array();

        //search and references all courses of a subprogram
        // if ( property_exists($subp, 'programStructure') ) {
        if ( ! empty($subp->programStructure->subBlock->subBlock) ) {
            $content = $subp->programStructure->subBlock->subBlock; //ELP
            foreach ($content->children() as $element) {
                if ( (string)$element->getName() != 'refCourse') continue;
                $attrs = $element->attributes();
                $courseref = (string)$attrs['ref'];
                $subsProg[$subpRofId][] = $courseref;
            }
            $dbprogram = $DB->get_record('rof_program', array('rofid' => $subpRofId));
            $dbprogram->sub = serializeArray($subsProg[$subpRofId]);
            if (! $dryrun ) {
                $DB->update_record('rof_program', $dbprogram);
            }
        }
        if ( ! empty($subp->contacts) ) {
            $listRefPersons = fetchRefPersons($subp->contacts) ;
            updateRefPersons('rof_program', $subpRofId, $listRefPersons, $dryrun);
        }
    }

    if ( ! empty($program->contacts) ) {
        $listRefPersons = fetchRefPersons($program->contacts) ;
        updateRefPersons('rof_program', $progRofId, $listRefPersons, $dryrun);
    }

    // then, browse all courses
    foreach ($xmlTree->children() as $element) {
        if ( (string)$element->getName() != 'course') continue;
        if ($DB->record_exists('rof_course', array('rofid' => (string)$element->courseID))) {
            $dblcnt++;
            continue;
        }
        $record = new stdClass();
        $record->rofid = (string)$element->courseID;
        $record->name  = (string)$element->courseName->text;
        $record->code = (string)$element->courseCode;
        $record->level = 0; // on ne sait pas encore
        $record->oneparent = $progRofId;
        $subsCourse[$record->rofid] = array();
        if (! $dryrun ) {
            $lastinsertid = $DB->insert_record('rof_course', $record);
            if ( $lastinsertid) {
                $cnt++;
            }
        }
        // print_r($record);
        $desc = $element->courseDescription;

        if ( ! empty($element->contacts) ) {
            $listRefPersons = fetchRefPersons($element->contacts) ;
            updateRefPersons('rof_course', $record->rofid, $listRefPersons, $dryrun);
        }

        //** @todo réécrire la suite en DOM ?
        foreach ($element->courseDescription->children() as $subBlock) {
            if ( (string)$subBlock->getName() != 'subBlock') continue;
            $attrs = $subBlock->attributes();
            if ( (string)$attrs['userDefined'] != 'courseStructure' ) continue ;

            if ($subBlock->count() > 0) {
                $refBlock = $subBlock->subBlock;
                // print_r($refBlock); die();
                foreach ($refBlock->children() as $refCourse) {
                    if ( (string)$refCourse->getName() != 'refCourse') continue;
                    $attrRC = $refCourse->attributes();
                    $subsCourse[$record->rofid][] = (string)$attrRC['ref'];
                    // echo $attrRC['ref']." "; die();
                }
            }
        } // fin réécrire en DOM ?
    }

    // update courses with subcourses (sub)
    if ( isset($subsCourse) ) {
        foreach($subsCourse as $course => $subcourses) {
            $dbcourse = $DB->get_record('rof_course', array('rofid' => $course));
            $dbcourse->sub = serializeArray($subcourses);
            if (! $dryrun ) {
                $DB->update_record('rof_course', $dbcourse);
            }
        }
    }

    // then, browse all persons
    foreach ($xmlTree->children() as $person) {
        if ( (string)$person->getName() != 'person') continue;
        if ($DB->record_exists('rof_person', array('rofid' => (string)$element->personID))) {
            continue;
        }
        $record = fetchPerson($person);
        if ( ! $DB->record_exists('rof_person', array('rofid' => $record->rofid)) ) {
            $DB->insert_record('rof_person', $record);
        }
    }

    return array($cnt, $dblcnt);
}

/**
 * fetch <refPerson>s from an xml <contacts> element
 * @param type XmlElement
 * @return array( string refPerson )
 */
function fetchRefPersons($xmlContacts) {
    $res = array();
    foreach ($xmlContacts->children() as $refPerson) {
        if ( (string)$refPerson->getName() != 'refPerson' ) continue;
        $attrs = $refPerson->attributes();
        $res[] = (string)$attrs['ref'];
    }
    return $res;
}


/**
 * fetch info from an xml <person> element
 * @param XmlElement $xmlPerson
 * @return object record
 */
function fetchPerson($xmlPerson) {
    $record = new stdClass();
    $record->rofid = trim((string)$xmlPerson->personID);
    $record->givenname = substr(trim((string)$xmlPerson->name->given), 0, 250);
    $record->familyname = substr(trim((string)$xmlPerson->name->family), 0, 250);
    $record->title = substr(trim((string)$xmlPerson->title->text), 0, 250);
    $record->role = substr(trim((string)$xmlPerson->role->text), 0, 250);
    $record->email = substr(trim((string)$xmlPerson->contactData->email), 0, 250);
    $record->oneparent = '';
    return $record;
}

/**
 * updateRefPersons for given table and list
 * @global type $DB
 * @param string $table
 * @param string $rofid
 * @param array(string) $listRefPersons
 * @param bool $dryrun : if set, no modification to database
 */
function updateRefPersons($table, $rofid, $listRefPersons, $dryrun) {
    global $DB;
    $dbrecord = $DB->get_record($table, array('rofid' => $rofid));
    if ( $dbrecord ) {
        $dbrecord->refperson = serializeArray($listRefPersons);
        if (! $dryrun ) {
            $DB->update_record($table, $dbrecord);
        }
    }
}

/**
 * returns a trivial serialization (csv) from an array
 * @param type $array (simple)
 * @return string serialized array
 */
function serializeArray($array) {
    return join(',', $array);
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