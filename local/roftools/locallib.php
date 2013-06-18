<?php
/**
 * @package    local
 * @subpackage roftools
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

require_once($CFG->dirroot . "/course/lib.php");
// for listpages...
require_once($CFG->dirroot . "/lib/resourcelib.php");
require_once($CFG->dirroot . "/mod/page/lib.php");
require_once($CFG->dirroot . "/course/lib.php");

/* @var $DB moodle_database */

// Classes d'équivalence des diplômes pour les catégories
function equivalent_diplomas() {

    $diplomaEqv = array(
        'Licences' => 'L1,L2,L3,DP',
        'Masters' => 'M1,E1,M2,E2,30',
        'Doctorats' => '40',
        'Autres' => 'U2,U3,U4,U5,U6,PG,PC,PA,P1'
    );

    foreach ($diplomaEqv as $eqv => $strdiplomas) {
        $diplomas = explode(',', $strdiplomas);
        foreach ($diplomas as $diploma) {
            $idxEqv[$diploma] = $eqv;
        }
    }
    return $idxEqv;
}

function high_level_categories() {
    return
        array(
            array(
                'name' => get_config('local_roftools', 'rof_year_name'),
                'idnumber' => get_config('local_roftools', 'rof_year_code')
                ),
            array(
                'name' =>  get_config('local_roftools', 'rof_etab_name'),
                'idnumber' => get_config('local_roftools', 'rof_etab_code'),
                ),
        );
}

function create_rof_categories($verb=0) {
    global $DB;

    $dipOrdre = array('Licences', 'Masters', 'Doctorats', 'Autres');
    $idxEqv = equivalent_diplomas();
    $hlCategories = high_level_categories();
    $parentid=0;

    // Crée les deux niveaux supérieurs
    foreach ($hlCategories as $hlcat) {
        $newcategory = new stdClass();
        $newcategory->name = $hlcat['name'];
        $newcategory->idnumber = $hlcat['idnumber'];
        $newcategory->parent = $parentid;

        $category = create_course_category($newcategory);
        $parentid = $category->id;
        fix_course_sortorder();
     }

    $rofRootId = $parentid;

    // Crée les niveaux issus du ROF : composantes (3) et types-diplômes simplifiés (4)
    $components = $DB->get_records('rof_component');
    foreach ($components as $component) {
        if ($verb > 0) {
            echo "\n$component->number $component->name \n";
        }
        $newcategory = new stdClass();
        $newcategory->name = $component->name;
        $newcategory->idnumber = '3:' . $component->number;
        $newcategory->parent = $rofRootId;
        $category = create_course_category($newcategory);
        $compCatId = $category->id;
        fix_course_sortorder();
        list ($inSql, $inParams) = $DB->get_in_or_equal($component->sub);
        $sql = 'SELECT * FROM {rof_program} WHERE rofid ' . $inSql;
        $programs = $DB->get_records_sql($sql, $inParams);

        $diplomeCat = array();
        foreach ($programs as $program) {
            if ($verb >= 1) {
                echo '.';
            }
            if ($verb >= 2) {
                echo " $program->rofid ";
            }
            $typesimple = simplifyType($program->typedip, $idxEqv);
            $diplomeCat[$typesimple] = TRUE;
        } // $programs

        foreach ($dipOrdre as $classeDiplome) {
            if ( isset($diplomeCat[$classeDiplome]) ) {
                $newcategory = new stdClass();
                $newcategory->name = $classeDiplome;
                $newcategory->idnumber = '4:' . $component->number .'/'. $classeDiplome;
                $newcategory->parent = $compCatId;
                if ($verb >= 1) {
                    echo " $classeDiplome";
                }
                $category = create_course_category($newcategory);
                // $progCatId = $category->id;
                fix_course_sortorder();
            }
        } // $dipOrdre
        if ($verb >= 2) {
            echo "\n";
        }
    } // $components

}

/**
 * returns a simplified category for the diploma, ex. 'L2' -> 'Licences'
 * @param string $typedip
 * @return string
 */
function simplifyType($typedip, $idxEqv) {
    if (isset($idxEqv[$typedip])) {
        return $idxEqv[$typedip];
    } else {
        return 'Autres';
    }
}


//**** Listpages creation
// affected tables :
// * page (course=1)
// * course_modules (course=1, module=15, instance->page, section->course_sections ?, idnumber='')
// * course_sections (course=1, section=1, summary='<p>Section descr...</p>', sequence->course_modules)



/**
 * create the 2 automatic list pages for each of the "official" Component course-categories
 * @global moodle_database $DB
 */
function listpages_create() {
    global $DB;

    $rootcat = $DB->get_field('course_categories', 'id', array('idnumber' => '2:UP1', 'depth' => 2), MUST_EXIST);

    $itercategories = $DB->get_records('course_categories', array('visible' => 1, 'parent' => $rootcat));
    foreach ($itercategories as $category) {
        echo "Creating page for " . $category->name . "\n";
        listpages_create_for($category);
    }
}

/**
 * Create the 2 automatic list pages for the given course category
 *
 * @global moodle_database $DB
 * @param DBrecord $category record from table 'course_categories'
 */
function listpages_create_for($category) {
    global $DB;

    $url = array();
    $views = array(
        'tableau' => array('code' => 'tableau', 'name' => 'vue tableau', 'format' => 'table', 'sister' => +1),
        'arborescence' => array('code' => 'arborescence','name' => 'vue arborescence', 'format' => 'tree', 'sister' => -1),
    );
    $courseId = 1;
    $modulePage = $DB->get_field('modules', 'id', array('name' => 'page'));

    course_create_sections_if_missing($courseId, 1);

    $template = new ListpagesTemplates($category);
    $template->sisterpagelink = ''; /** @todo sisterpagelink */

    $cmsId = array();
    foreach ($views as $viewcode => $view) {
        $template->view = $view;

        $newcm = new stdClass();
        $newcm->course = $courseId;
        $newcm->module = $modulePage;
        $newcm->instance = 0; // not known yet, will be updated later (this is similar to restore code)
        $newcm->visible = 1;
        $newcm->visibleold = 1;
        $newcm->groupmode = 0;
        $newcm->groupingid = 0;
        $newcm->groupmembersonly = 0;
        $newcm->completion = 0;
        $newcm->completiongradeitemnumber = NULL;
        $newcm->completionview = 0;
        $newcm->completionexpected = 0;
        $newcm->availablefrom = 0;
        $newcm->availableuntil = 0;
        $newcm->showavailability = 0;
        $newcm->showdescription = 0;
        /**
         * @todo Optimize with a direct DB action, then call rebuild_course_cache() once the loop has ended.
         */
        $cmid = add_course_module($newcm);
        $cmsId[$viewcode] = $cmid;

        $pagedata = new stdClass();
        $pagedata->coursemodule  = $cmid;
        $pagedata->printheading = 0; /** @todo Check format */
        $pagedata->printintro= 0; /** @todo Check format */
        $pagedata->section = 1;
        $pagedata->course = $courseId;
        $pagedata->introformat = FORMAT_MOODLE;
        $pagedata->legacyfiles = 0;
        $pagedata->display = RESOURCELIB_DISPLAY_AUTO;
        $pagedata->revision = 1;
        $pagedata->name = $template->getName();
        $pagedata->intro = $template->getIntro();
        $pagedata->content = $template->getContent();

        page_add_instance($pagedata);
        course_add_cm_to_section($courseId, $cmid, $pagedata->section);

        $url = new moodle_url('/mod/page/view.php', array('id' => $cmid));
        echo "    {$view['name']} : $url\n";
    }
    // update crossed links
    foreach ($view as $viewcode => $view) {
        foreach ($cmsId as $othercode => $cmId) {
            if ($othercode !== $viewcode) {
                $otherUrl = new moodle_url('/mod/page/view.php', array('id' => $cmId));
                $DB->execute(
                        "UPDATE {page} SET content = REPLACE(content, '{link-$othercode}', ?)",
                        array($otherUrl->out(true))
                );
            }
        }
    }
}

/**
 * Return an array of templates.
 *
 * @return array of templates
 */
class ListpagesTemplates
{
// {format} = table | tree

    /** @var string tableau | arborescence */
    public $view;
    /** @var string link to page arbre if current page = tableau, and reverse */
    public $sisterpagelink;

    private $category;
    /** @var string component name ex. 02-Économie */
    private $compname;
    /** @var array 4th depth subcategories (Licence, Master, ...) */
    private $niveauxLmda;
    private $catCode;

    private static $tpl_name = 'Espaces de cours de {compname} ({vue})';
    private static $tpl_intro = <<<EOL
<p>
    L'espace que vous cherchez n'est pas listé sur cette page ?
    Avez-vous pensé à le trouver du côté des
    “<a title="EPI" href="http://epi.univ-paris1.fr">anciens EPI</a>” ?
</p>
EOL;
    private static $tpl_contenttab = array(
        'tableau' => <<< EOL
<div class="tabtree">
    <ul class="tabrow0">
        <li class="first onerow here selected">
            <a class="nolink"><span>{vue}</span></a>
            <div class="tabrow1 empty"></div>
        </li>
        <li class="last onerow"><a href="{link-arborescence}">Vue arborescente</a></li>
    </ul>
</div>
EOL
        ,
        'arborescence' => <<< EOL
<div class="tabtree">
    <ul class="tabrow0">
        <li class="first onerow"><a href="{link-tableau}">Vue tableau</a></li>
        <li class="last onerow here selected">
            <a class="nolink"><span>{vue}</span></a>
            <div class="tabrow1 empty"></div>
        </li>
    </ul>
</div>
EOL
    );
    private static $tpl_contentmain = <<< EOL
<h3>Espaces de cours de {niveaulmda}</h3>
<p>
    [courselist format={format} node={node}]
</p>
<p></p>
EOL;
    private static $tpl_contentfoot = <<< EOL
<p>
    <span style="font-size: x-small;">
        Les Espaces pédagogiques interactifs proposent des informations et des ressources pédagogiques en accompagnement des cours.
        Les enseignants les publient à l’intention des étudiants inscrits aux enseignements concernés pour guider leur travail personnel,
        approfondir certaines questions, préparer les travaux et devoirs ou encore réviser les examens.
    </span>
</p>
<p>
    <span style="font-size: x-small;">
        Les documents, quelle que soit leur nature, publiés dans les Espaces pédagogiques interactifs de l'Université Paris 1 Panthéon-Sorbonne,
        sont protégés par le <a title="Code de la propriété intellectuelle - Legifrance" href="http://www.legifrance.gouv.fr/affichCode.do?cidTexte=LEGITEXT000006069414">Code de la propriété intellectuelle</a> (Article L 111-1). Toute reproduction partielle ou totale sans autorisation écrite de l\'auteur est interdite, sauf celles prévues à l'article L 122-5 du <a title="Code de la propriété intellectuelle - Legifrance" href="http://www.legifrance.gouv.fr/affichCode.do?cidTexte=LEGITEXT000006069414">Code de la propriété intellectuelle</a>.
    </span>
    <span style="font-size: small;">
        <a href="http://www.celog.fr/cpi/lv1_tt2.htm"><br /> </a>
    </span>
</p>
EOL;

    public function __construct($category) {
        $this->setCategory($category);
    }

    public function setCategory($category) {
        global $DB;
        $this->category = $category;
        $this->compname = $category->name;
        $this->niveauxLmda = $DB->get_records('course_categories', array('parent' => $category->id));
        $this->catCode = substr($this->category->idnumber, 2);
    }

    public function getName() {
        return str_replace(
                array('{compname}', '{vue}'),
                array($this->compname, $this->view['name']),
                self::$tpl_name
        );
    }

    public function getIntro() {
        return self::$tpl_intro;
    }

    public function getContent() {
        $content = str_replace('{vue}', $this->view['name'], self::$tpl_contenttab[$this->view['code']]);
        foreach ($this->niveauxLmda as $niveau) {
            $node = '/cat' . $niveau->id . '/' . $this->catCode;
            $content .= str_replace(
                    array('{niveaulmda}', '{node}', '{format}'),
                    array($this->category->name, $node, $this->view['format']),
                    self::$tpl_contentmain
            );
        }
        $content .= self::$tpl_contentfoot;
        return $content;
    }
}
