<?php

/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */

/**
 * PHPUnit integration tests
 *
 * @package    local
 * @subpackage mwsgroups
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once dirname(__DIR__) . '/lib.php';

// To run only this test case: phpunit mws_search_users_testcase local/mwsgroups/tests/mws_search_users_test.php

/**
 * Test mws_search_users functions.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 François Gannaz <francois.gannaz@silecs.info>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mws_search_users_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();
        static $init = false;
        $this->resetAfterTest(false);
        if (!$init) {
            $dataset = $this->createCsvDataSet(
                array(
                    'user' => __DIR__ . '/fixtures/user.csv',
                    'cohort' => __DIR__ . '/fixtures/cohort.csv',
                    'cohort_members' => __DIR__ . '/fixtures/cohort_members.csv',
                )
            );
            $this->loadDataSet($dataset);
            $init = true;
        }
    }

    public function test_simple_token() {
        $search_u = new mws_search_users();
        $search_u->maxrows = 10;
        $search_u->filterstudent = 'both';
        $search_u->exclude = array();
        $search_u->affiliation = false;
        $search_u->affectation = false;
        $search_u->cohorts = array();

        $users = $search_u->search("italo");
        $this->assertInternalType('array', $users);
        $this->assertCount(2, $users);
        $this->assertEquals('italo.calvino', $users[0]['uid']);
        $this->assertEquals('italo.svevo', $users[1]['uid']); // sorted by lastname, so Svevo comes last

        $this->assertCount(3, $search_u->search("i"));

        $this->assertCount(8, $search_u->search("")); // including guest and admin

        $this->assertCount(0, $search_u->search("féodor"));
    }

    public function test_filterstudent() {
        $search_u = new mws_search_users();
        $search_u->maxrows = 10;
        $search_u->exclude = array();
        $search_u->affiliation = false;
        $search_u->affectation = false;
        $search_u->cohorts = array();

        $search_u->filterstudent = 'both';
        $this->assertCount(3, $search_u->search("i"));

        $search_u->filterstudent = 'only';
        $this->assertCount(2, $search_u->search("i"));

        $search_u->filterstudent = 'no';
        $this->assertCount(1, $search_u->search("i"));

        $search_u->filterstudent = 'no';
        $this->assertCount(0, $search_u->search("italo"));
    }

    public function test_maxrows() {
        $search_u = new mws_search_users();
        $search_u->maxrows = 2;
        $search_u->exclude = array();
        $search_u->affiliation = false;
        $search_u->affectation = false;
        $search_u->cohorts = array();
        $search_u->filterstudent = 'both';

        $this->assertCount(2, $search_u->search("i"));
    }

    public function test_cohorts() {
        $search_u = new mws_search_users();
        $search_u->exclude = array();
        $search_u->affiliation = false;
        $search_u->affectation = false;
        $search_u->filterstudent = 'both';

        $search_u->cohorts = array('italiens');
        $this->assertCount(2, $search_u->search("i"));

        $search_u->cohorts = array('russes');
        $this->assertCount(1, $search_u->search("i"));

        $search_u->cohorts = array('none');
        $this->assertCount(0, $search_u->search("i"));
    }

    public function test_exclude() {
        $search_u = new mws_search_users();
        $search_u->affiliation = false;
        $search_u->affectation = false;
        $search_u->filterstudent = 'both';
        $search_u->cohorts = array();

        $search_u->exclude = array('italo.svevo');
        $this->assertCount(2, $search_u->search("i"));

        $search_u->exclude = array('marcel.proust');
        $this->assertCount(3, $search_u->search("i"));

        $search_u->exclude = array('italo.svevo', 'italo.calvino');
        $this->assertCount(1, $search_u->search("i"));

        $search_u->maxrows = 2;
        $search_u->exclude = array('italo.svevo', 'italo.calvino');
        $this->assertCount(1, $search_u->search("i"));
        $search_u->maxrows = 2;
        $search_u->exclude = array('isaac.babel', 'italo.calvino');
        $this->assertCount(1, $search_u->search("i"));
        $search_u->maxrows = 2;
        $search_u->exclude = array('isaac.babel', 'italo.svevo');
        $this->assertCount(1, $search_u->search("i"));
    }

    public function test_affectation() {
        $search_u = new mws_search_users();
        $search_u->affiliation = false;
        $search_u->exclude = array();
        $search_u->filterstudent = 'both';
        $search_u->cohorts = array();

        $search_u->affectation = false;
        $users = $search_u->search("i");
        $this->assertCount(3, $users);
        $this->assertArrayNotHasKey('supannEntiteAffectation', $users[0]);
        $this->assertArrayNotHasKey('supannEntiteAffectation', $users[1]);
        $this->assertArrayNotHasKey('supannEntiteAffectation', $users[2]);

        $search_u->affectation = true;
        $users = $search_u->search("i");
        $this->assertCount(3, $users);
        $this->assertEquals(array(), $users[0]['supannEntiteAffectation']);
        $this->assertEquals(array('Italiens UP1'), $users[1]['supannEntiteAffectation']);
        $this->assertEquals(array('Italiens UP1'), $users[2]['supannEntiteAffectation']);
    }
}
