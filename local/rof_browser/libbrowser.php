<?php
require_once($CFG->dirroot . '/report/rofstats/roflib.php');

/**
 * renvoie la liste des components
 * @return array of objects
 **/
function getRofComponents() {
    global $DB;
    $components = $DB->get_records('rof_component');
    return $components;
}
/**
 * construit la liste html des components
 * @return string code html
 */
function treeComponent () {
	$components = getRofComponents();
	$list = '<ul>';
	foreach ($components as $c) {
		if ($c->sub != '') {
			$nbProg = nbSub($c->sub);
			$list .= '<li>';

			$list .= '<span class="selected-niv2 curser-point" id="niv_'.trim($c->id).'">'
				. htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') . ' (' . $nbProg . ')</span>';
		//	$list .= '<a href="roffinal.php?id='.$c->id.'&amp;niveau=2">' . htmlentities($c->name, ENT_QUOTES, 'UTF-8') . ' (' . $nbProg . ')</a>';
			$list .= '</li>';
		} else {
			$list .= '<li><span>' . htmlspecialchars($c->name) . '</span></li>';
		}
	}
	$list .= '</ul>';
	return $list;
}
/*
 * réécrit la liste les éléments fils en suivant le format "'fils_1', 'fils2'"
 * @param string $sub : "fils_1, fils2"
 * @return sting
 */
function subToString ($sub) {
	$mysub = '';
	$tabsub = explode(',', $sub);
	foreach ($tabsub as $rofid) {
		$mysub .= "'".trim($rofid)."',";
	}
	return substr($mysub, 0, -1);
}
/**
 * renvoie le nombre d'identifiant rof contenu dans $sub
 * @param string $sub : "fils_1, fils2"
 * @return int
 */
function nbSub($sub) {
	if ($sub == '') {
		return 0;
	}
	$tabsub = explode(',', $sub);
	return count($tabsub);
}

class rof_browser {

	public $action = 'view'; // create, view
	public $detail = 1; // simple,
	public $format = 'list'; // list, table

	protected $niveau;
	protected $idPere;

	public $tabNiveau = array(
		1 => array('code' =>'component', 'tabsub' => 'rof_component', 'tabenf' => 'rof_component'),
		2 => array('code' =>'progam', 'tabsub' => 'rof_component', 'tabenf' => 'rof_program'),
		3 => array('code' =>'subprogam', 'tabsub' => 'rof_program', 'tabenf' => 'rof_program'),
		4 => array('code' =>'ue', 'tabsub' => 'rof_program', 'tabenf' => 'rof_course'),
		5 => array('code' =>'course', 'tabsub' => 'rof_course', 'tabenf' => 'rof_course'),
		6 => array('code' =>'course1', 'tabsub' => 'rof_course', 'tabenf' => 'rof_course'),
		7 => array('code' =>'course2', 'tabsub' => 'rof_course', 'tabenf' => 'rof_course'),
		8 => array('code' =>'course3', 'tabsub' => 'rof_course', 'tabenf' => 'rof_course'),
		9 => array('code' =>'course4', 'tabsub' => 'rof_course', 'tabenf' => 'rof_course'),
		10 => array('code' =>'course5', 'tabsub' => 'rof_course', 'tabenf' => 'rof_course'),
	);


	public function setNiveau($niveau) {
		$this->niveau = $niveau;
	}

	public function setIdPere($idPere) {
		$this->idPere = $idPere;
	}

	/**
	 * Construit un élément d'une liste
	 * @param $object $sp correspond à l'objet à afficher
	 * @param $niveau
	 * @return string
	 */
	function createElement($sp, $niveau) {
		$element = '';

		$listeTitle = '';
		// table rof_program
		if (isset($sp->typedip)) {
			$listeTitle .= ', type:'.$sp->typedip.', domaine:'.$sp->domainedip
			.', nature:'.$sp->naturedip.', cycle:'.$sp->cycledip.', rythme: '.$sp->rythmedip.', langue:'.$sp->languedip;
		}

		$nbSub = nbSub($sp->sub);
		$nbCourses = 0;
		if (isset($sp->courses)) {
			$nbCourses = nbSub($sp->courses);
		}
		$nbEnf = $nbSub + $nbCourses;

		if ($nbEnf) {
			/**	$element .= '<a href="roffinal.php?niveau='.$niveau.'&id='.$sp->id.'"><span class="curser-point">'
				. htmlentities($sp->name, ENT_QUOTES, 'UTF-8') . ', ' . $sp->rofid . ' ('.$nbEnf.') </span>';**/
			$coden = trim('niv'.$niveau);
			$element .= '<span class="selected-'.$coden.' curser-point" id="'.trim($coden .'_'.$sp->id).'" title="'
				. 'rof:' . $sp->rofid . $listeTitle . '">'
				. htmlentities($sp->name, ENT_QUOTES, 'UTF-8') . ' (' . $nbEnf . ')</span>';
		} else {
			$element .= '<span title="rof:' . $sp->rofid . $listeTitle . '">'. htmlentities($sp->name, ENT_QUOTES, 'UTF-8') . '</span>';
		}
		return $element;
	}

	/**
	 * Construit et renvoie le block de code html correspondant aux élément fils d'un élément
	 * @return string code html
	 */
	function createBlock() {
		// niveau 2 on peut avoir $tabEnf= rof_program ou/et $tabEnf= rof_course
		global $DB;
		$tabSub = $this->tabNiveau[$this->niveau]['tabsub'];
		$tabEnf = $this->tabNiveau[$this->niveau]['tabenf'];
		$nivEnf = (int)$this->niveau  + 1;

		$sort = '';

		if ($this->niveau == 2) {
			$sub = $DB->get_field_select($tabSub, 'sub', 'id = '.$this->idPere);
			$sub = subToString($sub);
			$sort = " ORDER BY FIND_IN_SET(typedip, '" . typeDiplomeOrderedList() . "') ";
			$sql = 'SELECT * FROM ' . $tabEnf . ' WHERE '. " rofid in ({$sub}) " . $sort;
			$subList = $DB->get_records_sql($sql);
		} elseif ($this->niveau==4) {
			// dans rof_progam, la liste des enfants courses est dans le champ courses
			$sub = $DB->get_field_select($tabSub, 'courses', 'id = '.$this->idPere);
			$sub = subToString($sub);
			$subList = $DB->get_records_select($tabEnf, " rofid in ({$sub})");
		} elseif ($this->niveau==3) {
			$sub =  $DB->get_field_select($tabSub, 'sub', 'id = '.$this->idPere);
			if ($sub != '') {
				$sub = subToString($sub);
				$subList = $DB->get_records_select($tabEnf, " rofid in ({$sub})");
			}
			// si il y a aussi des cours à ce niveau
			$sub =  $DB->get_field_select($tabSub, 'courses', 'id = '.$this->idPere);
			if ($sub != '') {
				$sub = subToString($sub);
				$tabEnf = 'rof_course';
				$subList2 = $DB->get_records_select($tabEnf, " rofid in ({$sub})");
			}
		} else {
			$sub = $DB->get_field_select($tabSub, 'sub', 'id = '.$this->idPere);
			$sub = subToString($sub);
			$subList = $DB->get_records_select($tabEnf, " rofid in ({$sub})");
		}

		$blocListe = '';
		if (isset($subList) && count($subList)) {
			$blocListe .= $this->afficheListe($subList, $nivEnf);
		}
		if (isset($subList2) && count($subList2)) {
			$blocListe .= $this->afficheListe($subList2, $nivEnf);
		}
		return $blocListe;
	}

	/**
	 * construit le code HTML listant les elements $subList
	 * @param array() $subList
	 * @param int $nivEnf
	 * return string
	 */
	function afficheListe($subList, $nivEnf) {
		$list = '';
		$nbSubList = count($subList);

		$cf = 'per' . $this->idPere;
		if ($this->niveau == 2) {
			$cf = 'cont-niv' . $this->niveau;
		}

		if ($nbSubList) {
		$list = '<ul class="'.$cf.'">';
			foreach ($subList as $id => $sl) {
				$list .= '<li>' . $this->createElement($sl, $nivEnf). '</li>';
			}
		$list .= '</ul>';
		}
		return $list;
	}
}
