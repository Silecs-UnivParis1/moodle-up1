<?php
require_once($CFG->dirroot . '/local/roftools/roflib.php');

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
 * construit l'arbre du rof (arbre selected) avec des select pour
 * component, program et subprogram
 * @return string code html
 */
function print_rof() {
	$components = getRofComponents();
     $list = '<div>Rechercher un élément pédagogique dans l\'offre de formation de l\'établissement</div>';
	$list .= '<div class="select-elem">';
	$list .= '<select class="selectmenu" id="select-2">';
	$list .= '<option selected="selected" data_deep="2">Sélectionner la composante</option>';
	foreach ($components as $c) {
        $id = 'deep2_' . $c->number;
		$idElem = $id . '-elem';
		$data_path = $c->number;
		$data_rofid = $c->number;
		if ($c->sub != '') {
            $list .= '<option data_deep="2" '
			. 'id="' . $id . '" data_path="' . $data_path . '" data_rofid="' . $data_rofid . '"'
			. '>'
			. htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8')
			. '</option>';
		}
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
    protected $readonly;

    protected $elemPere;

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

    public $constant_diplome = array(
        'licence' => 'Licence',
        'master1' => 'Master 1',
        'master2' => 'Master 2',
        'dipU' => 'Diplôme d\'université',
        'magistere' => 'Magistère'
    );

    public $constant_diplome_key = array(
        'licence' => array('L1', 'L2', 'L3', 'DP'),
        'master1' => array('M1', 'E1'),
        'master2' => array('M2', 'E2', 'MA'),
        'dipU' => array('U2', 'U3', 'U4', 'U5', 'U6'),
        'magistere' => array('30')
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

    public function setReadonly($readonly) {
		$this->readonly= $readonly;
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

		list($pere, $stop) = rof_get_record($this->rofid);

		if ($this->niveau == 2) {
			$sub = subToString($pere->sub);
			$sort = " ORDER BY FIND_IN_SET(typedip, '" . rof_typeDiplome_ordered_list() . "') ";

			if($this->format) {
				if ($this->typedip) {
                    $mydip = '';
                    if ($this->typedip=='divers') {
                        $listedip = $this->gettypedipdivers();
                        $mydip = subToString($listedip);
                    } else {
                        $listedip = $this->constant_diplome_key[$this->typedip];
                        foreach ($listedip as $rofid) {
                            $mydip .= "'".trim($rofid)."',";
                        }
                        $mydip = substr($mydip, 0, -1);
                    }

					$sql = 'SELECT * FROM ' . $tabEnf . ' WHERE '. " rofid in ({$sub}) AND typedip in (".$mydip.") " . $sort;
					$subList = $DB->get_records_sql($sql);
				} else {
                    $sql = "SELECT DISTINCT typedip FROM rof_program WHERE subnb > 0 and rofid in ({$sub})";
					$subList = $DB->get_records_sql($sql);
					return $this->print_select_type_diplome($subList, $this->rofid, 2);
				}
			} else {
				$sql = 'SELECT * FROM ' . $tabEnf . ' WHERE '. " rofid in ({$sub}) " . $sort;
				$subList = $DB->get_records_sql($sql);
			}

		} elseif ($this->niveau==3) {
            if ( $this->selected == 1) {
                $this->elemPere = $DB->get_record($tabSub, array('rofid' => $this->rofid));
            }

			if ($pere->sub != '') {
				$sub = subToString($pere->sub);
				$subList = $DB->get_records_select($tabEnf, " rofid in ({$sub}) order by FIELD(rofid, {$sub})");
			}
			// si il y a aussi des cours à ce niveau
			if ($pere->courses != '') {
				$sub = subToString($pere->courses);
				$tabEnf = 'rof_course';
				$subList2 = $DB->get_records_select($tabEnf, " rofid in ({$sub}) order by FIELD(rofid, {$sub})");
			}
		} elseif ($this->niveau==4) {
			// dans rof_progam, la liste des enfants courses est dans le champ courses
			if (isset($pere->courses)) {
				$sub = subToString($pere->courses);
			} else {
				$sub = subToString($pere->sub);
			}
			$subList = $DB->get_records_select($tabEnf, " rofid in ({$sub}) order by FIELD(rofid, {$sub})");
		}  else {
			$sub = subToString($pere->sub);
			$subList = $DB->get_records_select($tabEnf, " rofid in ({$sub}) order by FIELD(rofid, {$sub})");
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

        if ($this->niveau == 3 && $this->selected == 1) {
            $coden = trim('deep'.$this->niveau);
            $id = $coden.'_'.$this->rofid;
            $idElem = $id . '-elem';
            $intitule = htmlentities( $this->elemPere->name, ENT_QUOTES, 'UTF-8');

			$listeTitle = 'type:'.$this->elemPere->typedip.', domaine:'.$this->elemPere->domainedip
			.', nature:'.$this->elemPere->naturedip.', cycle:'.$this->elemPere->cycledip.', rythme: '
            .$this->elemPere->rythmedip.', langue:'.$this->elemPere->languedip;

            $classsel = 'element pointer oplus';
            if ($this->readonly == 1) {
                $classsel = '';
            }
            $list .= '<div class="dip-sel">'
                . '<span class="expanded collapse" data_deep="'.$this->niveau.'" data_path="'
                . $this->path . '" data_rofid="'.$this->rofid.'" id="'.$id.'"> - </span>'
                . '<span class="intitule" title="'.$listeTitle.'">'.$intitule.'</span>'
                . '<span class="' . $classsel . '" title="Sélectionner" id="'
                . $idElem . '"></span>'
                . '</div>';
        }

		if ($nbSubList) {
		$list .= '<ul class="'.$cf.'">';
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
			$list .= '<option selected="selected" data_deep="' . $nivEnf . '">Sélectionner le diplôme</option>';
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
            $listdipint = array();
            $divers = FALSE;
            // tableau intermediaire
            $tabkey = $this->generate_tabconstantkey();

            foreach ($subList as $key=>$val) {
                if (array_key_exists($key, $tabkey)) {
                    $listdipint[$tabkey[$key]] = $tabkey[$key];
                } else {
                    $divers = TRUE;
                }
            }

			$list = '<div class="select-elem">';
			$list .= '<select class="selectmenu select-typedip" id="select-' . $nivEnf . '-typedip">';
			$list .= '<option selected="selected" data_deep="' . $nivEnf . '">Sélectionner le type de diplôme</option>';
            foreach ($this->constant_diplome as $code => $label) {
                if (array_key_exists($code, $listdipint)) {
                    $list .= '<option data_deep="' . $nivEnf . '" data_path="'
                        . $rofid . '" data_rofid="' . $rofid . '" id="deep2_'
                        . $rofid . '_' . $code . '" data_typedip="' . $code . '">'
                        . $label . '</option>';
                }
            }
            if ($divers) {
                $code = 'divers';
                $label = 'Divers';
                $list .= '<option data_deep="' . $nivEnf . '" data_path="'
                    . $rofid . '" data_rofid="' . $rofid . '" id="deep2_'
                    . $rofid . '_' . $code . '" data_typedip="' . $code . '">'
                    . $label . '</option>';
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
        $titleElem = '';
		// table rof_program
		if (isset($sp->typedip)) {
			$listeTitle .= 'type:'.$sp->typedip.', domaine:'.$sp->domainedip
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

		if (isset($sp->code)) {
            $titleElem .= 'code apogée : ' . $sp->code;
            if ($listeTitle != '') {
                 $titleElem .= ', ';
            }
        }
        if ($listeTitle != '') {
            $titleElem .= $listeTitle;
        }

		$data_path = $this->path . '_' . $sp->rofid;
		$intitule = htmlentities($sp->name, ENT_QUOTES, 'UTF-8');

		$nbEnf = $nbSub + $nbCourses;

		$style = 'collapse';
		$collapse = '';
		$listStyle = 'list-none list-item';
		if ($nbEnf) {
			$style = 'collapse curser-point collapsed';
			$collapse = ' + ';
		}
        $spancomp = '';
        if (isset($sp->composition)) {
            $spancomp .= '<span class="comp rof-hidden">'
            . $sp->composition . '</span>';
        }
        $classsel = 'element pointer oplus';
        if ($this->readonly == 1) {
            $classsel = '';
        }
		$element .= '<li class="' . $listStyle . '"><div class="elem-li">'
			. '<span class="' . $style . '" id="'. $id . '" title="Déplier" '
			. 'data_deep="' . $niveau . '" data_rofid="' . $sp->rofid
			. '" data_path="' . $data_path . '">' . $collapse . '</span>'
			. '<span class="intitule" title="' . $titleElem . '">' . $intitule . '</span>'
            . $spancomp
			. '<span class="' . $classsel . '" title="Sélectionner" id="'
			. $idElem . '"></span>'
			. '</div></li>';
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
        $titleElem = '';
		// table rof_program
		if (isset($sp->typedip)) {
			$listeTitle .= 'type:'.$sp->typedip.', domaine:'.$sp->domainedip
			.', nature:'.$sp->naturedip.', cycle:'.$sp->cycledip.', rythme: '.$sp->rythmedip.', langue:'.$sp->languedip;
		}
		$nbSub = nbSub($sp->sub);
		$nbCourses = 0;
		if (isset($sp->courses)) {
			$nbCourses = nbSub($sp->courses);
		}
        $nbEnf = $nbSub + $nbCourses;

        if ($nbEnf) {
            $coden = trim('deep'.$niveau);
            $id = $coden.'_'.$sp->rofid;
            $idElem = $id . '-elem';
            $labelelem = htmlentities($sp->name, ENT_QUOTES, 'UTF-8');

            if (isset($sp->code)) {
                $titleElem .= 'code apogée : ' . $sp->code;
                if ($listeTitle != '') {
                    $titleElem .= ', ';
                }
            }
            if ($listeTitle != '') {
                $titleElem .= $listeTitle;
            }

            $data_path = $this->path . '_' . $sp->rofid;
            $element .= '<option ' . 'data_deep="' . $niveau . '" data_rofid="' . $sp->rofid
			. '" id="' . $id . '" data_path="' . $data_path . '" '
			. 'title="' . $titleElem . '" >'
			. $labelelem . '</span>'
			. '</option>';
		}
		return $element;
	}

    /**
     * Construit le tableau intermédiaire faisant correspondre à chaque rof_program.typedip
     * le code interne de $this->constant_diplome_key
     * @return array $tabkey
     */
    function generate_tabconstantkey()
    {
        $tabkey = array();
        foreach ($this->constant_diplome_key as $dipl =>$keys) {
            foreach ($keys as $k) {
                $tabkey[$k] = $dipl;
            }
        }
        return $tabkey;
    }

    /**
     * renvoie la liste des rof_program.typedip classés
     * dans le regroupement de diplôme divers
     * @return string $list de format "cide1, code2, ..."
     */
    function gettypedipdivers()
    {
        global $DB;
        $list = '';
        $tabkey = $this->generate_tabconstantkey();
        $sql = "SELECT DISTINCT typedip FROM rof_program";
        $typesdip = $DB->get_records_sql($sql);
        foreach ($typesdip as $k=>$v) {
            if (! array_key_exists($k, $tabkey)) {
                 $list .= $k . ',';
            }
        }
        if ($list != '') {
            $list = substr($list, 0, -1);
        }
        return $list;
    }
}
