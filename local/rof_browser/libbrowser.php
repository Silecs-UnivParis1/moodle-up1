<?php

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

			$list .= '<span class="selected-niv2 curser-point" id="niv_'.$c->id.'">'
				. htmlentities($c->name, ENT_QUOTES, 'UTF-8') . ' (' . $nbProg . ')</span>';
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
		6 => array('code' =>'coursef', 'tabsub' => 'rof_course', 'tabenf' => 'rof_course'),
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
		$nbSub = nbSub($sp->sub);
		if ($sp->sub != '') {
			/**$element .= '<a href="roffinal.php?niveau='.$niveau.'&id='.$sp->id.'"><span class="curser-point">'
				. htmlentities($sp->name, ENT_QUOTES, 'UTF-8') . ', ' . $sp->rofid . '</span>';**/
			$coden = trim('niv'.$niveau);
			$element .= '<span class="selected-'.$coden.' curser-point" id="'.trim($coden .'_'.$sp->id).'">['
				. $sp->rofid .'] '. htmlentities($sp->name, ENT_QUOTES, 'UTF-8') . ' (' . $nbSub . ')</span>';
		} else {
			$element .= '<span>['.$sp->rofid.'] ' . htmlentities($sp->name, ENT_QUOTES, 'UTF-8') . '</span></a>';
		}
		return $element;
	}

	/**
	 * Construit et renvoie le block de code html correspondant aux élément fils d'un élément
	 * @return string code html
	 */
	function createBlock() {
		global $DB;
		$tabSub = $this->tabNiveau[$this->niveau]['tabsub'];
		$tabEnf = $this->tabNiveau[$this->niveau]['tabenf'];
		$nivEnf = (int)$this->niveau  + 1;
		$sub = $DB->get_field_select($tabSub, 'sub', 'id = '.$this->idPere);
		$sub = subToString($sub);
		$subList = $DB->get_records_select($tabEnf, " rofid in ({$sub})");
		$list = '';
		$nbSubList = count($subList);
		if ($nbSubList) {
		$list = '<ul class="cont-niv'.$this->niveau.'">';
			foreach ($subList as $id => $sl) {
				$list .= '<li>' . $this->createElement($sl, $nivEnf). '</li>';
			}
		$list .= '</ul>';
		}
		return $list;
	}
}
