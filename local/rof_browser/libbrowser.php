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
		$id = 'deep2_' . $c->number;
		$data_path = $c->number;
		$data_rofid = $c->number;
		$listStyle = 'list-none';

		if ($c->sub != '') {
			$nbProg = nbSub($c->sub);
			$list .= '<li class="' . $listStyle . '">';
			$list .= '<span class="selected-deep2 curser-point" data_deep="2" '
				. 'id="' . $id . '" data_path="' . $data_path . '" data_rofid="' . $data_rofid . '">'
				. htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') . ' (' . $nbProg . ')</span>';
		//	$list .= '<a href="roffinal.php?rofid='.$c->number.'&amp;niveau=2">' . htmlentities($c->name, ENT_QUOTES, 'UTF-8') . ' (' . $nbProg . ')</a>';
			$list .= '</li>';
		} else {
			$list .= '<li class="' . $listStyle . '"><span>' . htmlspecialchars($c->name)
				. '</span></li>';
		}
	}
	$list .= '</ul>';
	return $list;
}

/**
 * construit l'arbre du rof (arbre selected)
 * @return string code html
 */
function afficheArbre() {
	$components = getRofComponents();
	$list = '<ul>';
	foreach ($components as $c) {
		$id = 'deep2_' . $c->number;
		$idElem = $id . '-elem';
		$data_path = $c->number;
		$data_rofid = $c->number;

		$style = 'collapse';
		$collapse = '';
		$infoNbEnf = '';
		$listStyle = 'list-none';
		if ($c->sub != '') {
			$nbEnf = nbSub($c->sub);
			$style = 'collapse curser-point';
			$collapse = '[+] ';
			$infoNbEnf = '('. $nbEnf .')';
		}
		$list .= '<li class="' . $listStyle . '">';
		$list .= '<span class="' . $style . '" data_deep="2" '
			.'title="Déplier" '
			. 'id="' . $id . '" data_path="' . $data_path . '" data_rofid="' . $data_rofid . '">'
			. $collapse . '</span><span class="element pointer" id="' . $idElem . '" title="Sélectionner">'
			. htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') . $infoNbEnf . '</span>';
		$list .= '</li>';
	}
	$list .= '</ul>';
	return $list;
}

/**
 * construit l'arbre du rof (arbre selected) avec des select pour
 * component, program et subprogram
 * @return string code html
 */
function print_rof() {
	$components = getRofComponents();
	$list = '<div class="select-elem">';
	$list .= '<select class="selectmenu" id="select-2">';
	$list .= '<option selected="selected" data_deep="2">Composante</option>';
	foreach ($components as $c) {
		$id = 'deep2_' . $c->number;
		$idElem = $id . '-elem';
		$data_path = $c->number;
		$data_rofid = $c->number;
		$disabled = ' disabled="disabled" ';

		$style = 'collapse';
		$collapse = '';
		$infoNbEnf = '';
		$listStyle = 'list-none';
		if ($c->sub != '') {
			$disabled = ' ';

			$nbEnf = nbSub($c->sub);
			$style = 'collapse curser-point';
			$collapse = '[+] ';
			$infoNbEnf = '('. $nbEnf .')';
		}
		$list .= '<option '.$disabled.' data_deep="2" '
			. 'id="' . $id . '" data_path="' . $data_path . '" data_rofid="' . $data_rofid . '"'
			. '>'
			. htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') . $infoNbEnf
			. '</option>';
	}
	$list .= '</select>';
	$list .= '</div>';
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

	protected $niveau;
	protected $rofid;
	protected $selected;
	protected $path;
	protected $format;
	protected $typedip;

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

	public function setRofid($rofid) {
		$this->rofid = $rofid;
	}

	public function setSelected($selected) {
		$this->selected = $selected;
	}

	public function setPath($path) {
		$this->path = $path;
	}

	/**
	 * format=1 : select, format=0 : list
	 */
	public function setFormat($format) {
		$this->format = $format;
	}

	public function setTypedip($typedip) {
		$this->typedip = $typedip;
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
		$titleElem = 'rof:' . $sp->rofid . $listeTitle;

		$coden = trim('deep'.$niveau);
		$id =  $coden . '_' . $sp->rofid;
		$data_path = $this->path . '_' . $sp->rofid;

		$nbSub = nbSub($sp->sub);
		$nbCourses = 0;
		if (isset($sp->courses)) {
			$nbCourses = nbSub($sp->courses);
		}
		$nbEnf = $nbSub + $nbCourses;
        $detUrl = new moodle_url('/report/rofstats/view.php', array('rofid' => $sp->rofid, 'path' => $data_path));
        $listStyle = 'list-none';

		if ($nbEnf) {
			/**	$element .= '<a href="roffinal.php?niveau='.$niveau.'&rofid='.$sp->rofid.'"><span class="curser-point">'
				. htmlentities($sp->name, ENT_QUOTES, 'UTF-8') . ', ' . $sp->rofid . ' ('.$nbEnf.') </span>';**/
			$element .= '<li class="' . $listStyle . '"><span class="selected-'
				. $coden . ' curser-point" id="'. $id . '" title="'
				. $titleElem . '" data_deep="' . $niveau . '" data_rofid="' . $sp->rofid
				. '" data_path="' . $data_path . '">'
                . html_writer::link($detUrl, '( i )') . "  "
				. htmlentities($sp->name, ENT_QUOTES, 'UTF-8') . ' (' . $nbEnf . ')</span></li>';
		} else {
			$element .= '<li class="' . $listStyle . '"><span title="'
				. $titleElem . '" data_path="' . $data_path . '">'
                . html_writer::link($detUrl, '( i )') . "  "
                . htmlentities($sp->name, ENT_QUOTES, 'UTF-8') . '</span></li>';
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

		list($pere, $stop) = rofGetRecord($this->rofid);

		if ($this->niveau == 2) {
			$sub = subToString($pere->sub);
			$sort = " ORDER BY FIND_IN_SET(typedip, '" . typeDiplomeOrderedList() . "') ";
			if ($this->typedip) {
				$sql = 'SELECT * FROM ' . $tabEnf . ' WHERE '. " rofid in ({$sub}) AND typedip='".$this->typedip."' " . $sort;
				$subList = $DB->get_records_sql($sql);
			} else {
				$sql = "SELECT c.value, c.dataimport FROM {rof_constant} c JOIN " . $tabEnf
					. " r ON (r.typedip=c.dataimport) "
					. "WHERE " . " r.rofid in ({$sub}) AND element LIKE 'typeDiplome' "
					. "GROUP BY  c.value, c.dataimport "
					. $sort;
				$subList = $DB->get_records_sql($sql);
				return $this->print_select_type_diplome($subList, $this->rofid, 2);
			}
		} elseif ($this->niveau==4) {
			// dans rof_progam, la liste des enfants courses est dans le champ courses
			if (isset($pere->courses)) {
				$sub = subToString($pere->courses);
			} else {
				$sub = subToString($pere->sub);
			}
			$subList = $DB->get_records_select($tabEnf, " rofid in ({$sub})");
		} elseif ($this->niveau==3) {
			if ($pere->sub != '') {
				$sub = subToString($pere->sub);
				$subList = $DB->get_records_select($tabEnf, " rofid in ({$sub})");
			}
			// si il y a aussi des cours à ce niveau
			if ($pere->courses != '') {
				$sub = subToString($pere->courses);
				$tabEnf = 'rof_course';
				$subList2 = $DB->get_records_select($tabEnf, " rofid in ({$sub})");
			}
		} else {
			$sub = subToString($pere->sub);
			$subList = $DB->get_records_select($tabEnf, " rofid in ({$sub})");
		}

		$blocListe = '';
		$nomFonction = 'afficheListe';
		if ($this->format) {
			$nomFonction = 'print_select';
		}

		if (isset($subList) && count($subList)) {
			$blocListe .= $this->$nomFonction($subList, $nivEnf);
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

		$cf = 'per' . $this->rofid;
		if ($this->niveau == 2) {
			$cf = 'cont-deep' . $this->niveau;
		}
		$cf .= ' item ';

		if ($nbSubList) {
		$list = '<ul class="'.$cf.'">';
			foreach ($subList as $id => $sl) {
				if ($this->selected == 1) {
					$list .= $this->createItem($sl, $nivEnf);
				} else {
					$list .= $this->createElement($sl, $nivEnf);
				}
			}
		$list .= '</ul>';
		}
		return $list;
	}

	/**
	 * construit le code HTML sous forme d'un élement select des les elements $subList
	 * @param array() $subList
	 * @param int $nivEnf
	 * return string code HTML d'un élément select
	 */
	function print_select($subList, $nivEnf) {
		$list = '';
		$nbSubList = count($subList);

		if ($nbSubList) {
			$list = '<div class="select-elem">';
			$list .= '<select class="selectmenu" id="select-' . $nivEnf . '">';
			$list .= '<option selected="selected" data_deep="' . $nivEnf . '">Diplôme</option>';
				foreach ($subList as $id => $sl) {
					$list .= $this->print_option($sl, $nivEnf);
				}
			$list .= '</select>';
			$list .= '</div>';
		}
		return $list;
	}

	function print_select_type_diplome($subList, $rofid, $nivEnf) {
		$list = '';
		$nbSubList = count($subList);

		if ($nbSubList) {
			$list = '<div class="select-elem">';
			$list .= '<select class="selectmenu select-typedip" id="select-' . $nivEnf . '-typedip">';
			$list .= '<option selected="selected" data_deep="' . $nivEnf . '">Type diplôme</option>';
				foreach ($subList as $id => $sl) {
					$list .= '<option data_deep="' . $nivEnf . '" data_path="'
						. $rofid . '" data_rofid="' . $rofid . '" id="deep2_'
						. $rofid . '_'.$sl->dataimport.'" data_typedip="' . $sl->dataimport . '">'
						. $sl->value . '</option>';
				}
			$list .= '</select>';
			$list .= '</div>';
		}
		return $list;
	}

	/**
	 * Construit un item d'une liste (arbre selected)
	 * @param $object $sp correspond à l'objet à afficher
	 * @param $niveau
	 * @return string
	 */
	function createItem($sp, $niveau) {
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

		$coden = trim('deep'.$niveau);
		$id = $coden.'_'.$sp->rofid;
		$idElem = $id . '-elem';
		$titleElem = 'rof:' . $sp->rofid . $listeTitle;
		$data_path = $this->path . '_' . $sp->rofid;

		$nbEnf = $nbSub + $nbCourses;
        $detUrl = new moodle_url('/report/rofstats/view.php', array('rofid' => $sp->rofid, 'path' => $data_path));

		$style = 'collapse';
		$collapse = '';
		$infoNbEnf = '';
		$listStyle = 'list-none';
		if ($nbEnf) {
			$style = 'collapse curser-point';
			$collapse = '[+] ';
			$infoNbEnf = '('. $nbEnf .')';
		}
		$element .= '<li class="' . $listStyle . '">'
			. '<span class="' . $style . '" id="'. $id . '" title="Déplier" '
			. 'data_deep="' . $niveau . '" data_rofid="' . $sp->rofid
			. '" data_path="' . $data_path . '">' . $collapse . '</span>'
			. html_writer::link($detUrl, '( i )', array('title'=>'Information')) . "  "
			. '<span class="element pointer" id="' . $idElem
			. '" title="' . $titleElem . '">'
			. htmlentities($sp->name, ENT_QUOTES, 'UTF-8') . $infoNbEnf . '</span>'
			. '</li>';
		return $element;
	}

	/*
	 * Construit un item d'une liste (arbre selected)
	 * @param $object $sp correspond à l'objet à afficher
	 * @param $niveau
	 * @return string
	 */
	function print_option($sp, $niveau) {
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

		$coden = trim('deep'.$niveau);
		$id = $coden.'_'.$sp->rofid;
		$idElem = $id . '-elem';
		$labelelem = htmlentities($sp->name, ENT_QUOTES, 'UTF-8');
		$titleElem = 'rof:' . $sp->rofid . $listeTitle;
		$data_path = $this->path . '_' . $sp->rofid;

		$nbEnf = $nbSub + $nbCourses;

		$infoNbEnf = '';
		$disabled = ' disabled="disabled" ';
		if ($nbEnf) {
			$disabled = '';
			$infoNbEnf = '('. $nbEnf .')';
		}
		$element .= '<option ' . 'data_deep="' . $niveau . '" data_rofid="' . $sp->rofid
			. '" id="' . $id . '" data_path="' . $data_path . '" '
			. 'title="' . $titleElem . '" '.$disabled.'>'
			. $labelelem . $infoNbEnf . '</span>'
			. '</option>';
		return $element;
	}
}
