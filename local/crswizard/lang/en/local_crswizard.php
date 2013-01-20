<?php
/**
 * Strings for component 'wizard', language 'en'
 *
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Course Wizard';
// capabilities
$string['crswizard:creator'] = 'Create a course with the wizard';
$string['crswizard:validator'] = 'Validate a course created with the wizard';
$string['crswizard:supervalidator'] = 'Validate ANY course created with the wizard';
$string['crswizard:localsupervalidator'] = 'Validate ANY course created with the wizard, local context';

$string['anotherneed'] = 'Un autre besoin';
$string['blocHelloS1'] = 'Hello';
$string['bockhelpE2'] = 'Texte d\'aide et de conseil. Suite du texte d\'aide et de conseil.';
$string['bockhelpE3'] = '<p>Vous avez défini à l\'étape précédente le rattachement principal de votre espace '
    . 'de cours.<br/>Si ce dernier s\'adresse aux étudiants d\'une autre composante et/ou inscrits '
    . 'à un autre niveau de diplôme, il vous est possible de le spécifier ci-dessous.</p>';
$string['bockhelpE3validator'] = 'Texte d\'aide et de conseil concernant la désignation d\'un approbateur.';
$string['bockhelpE4'] = '<p>Cette étape vous permet d\'attribuer à des enseignants des droits de '
    . 'contribution sur cet espace de cours. Il vous sera possible d\'inscrire les groupes d\'étudiants à '
    . 'l\'étape suivante.</p><ol><li>Sélectionnez en premier lieu le rôle à attribuer à l’utilisateur :'
    . '<ul><li>« Enseignant » : ajout d\'activités et de ressources, notation des devoirs ;</li>'
    . '<li>« Enseignant non éditeur » : consultation des ressources, notation des devoirs.</li></ul></li>'
    . '<li>Recherchez ensuite cet utilisateur dans l\'annuaire de l\'université, en saisissant, par exemple, '
    . 'son nom ou son identifiant Paris 1 ou le couple Prénom Nom</li>'
    . '<li>Cliquez sur le symbole « + » pour ajouter cet utilisateur comme contributeur de cet espace.</li></ol></p>'
    . '<p>Notez qu\'il vous est par défaut attribué le rôle « Enseignant ». Si vous n\'êtes pas destiné à '
    . 'être contributeur de cet espace, veillez à supprimer votre nom de la liste des utilisateurs sélectionnés (symbole X).</p>';
$string['bockhelpE5'] = '<p>Cette étape vous permet de sélectionner les groupes d\'utilisateurs qui auront le '
    . 'droit d\'accéder à cet espace de cours. Si vous ne trouvez pas le groupe d\'utilisateurs '
    . 'recherché, notez que vous avez la possibilité de définir une clé d\'inscription à l\'étape suivante.</p>'
    . '<ol><li>Sélectionnez en premier lieu le rôle à attribuer au(x) groupe(s) d\'utilisateurs :'
    . '<ul><li>« Etudiant » : consultation des ressources, participation aux activités ;</li>'
    . '<li>« Visiteur anonyme » : consultation des ressources uniquement.</li></ul></li>'
    . '<li>Recherchez ensuite le groupe d\'utilisateurs dans l\'annuaire de l\'université, en saisissant, '
    . 'par exemple, son intitulé ou une partie de son intitulé ou le nom d\'un étudiant appartenant à ce groupe.</li>'
    . '<li>Cliquez sur le symbole « + » pour inscrire ce groupe d\'utilisateurs à cet espace.</li></ol>';
$string['bockhelpE7'] = '<p>Votre demande de création d\'espace de cours est arrivée à son terme.<br/>'
    . 'Conseil : affichez le récapitulatif de cette dernière de manière à vérifier les éléments que vous '
    . 'avez saisis. En cas d\'erreur ou d\'omission, il vous est possible revenir en arrière en cliquant '
    . 'sur le bouton « Etape précédente ».</p>'
    . '<p>En cliquant sur le bouton « Terminer », vous déclencherez :<ul>'
    . '<li>la création de cet espace, qui sera pour l\'instant masqué à vos utilisateurs, mais que vous '
    . 'pourrez vous-même rendre visible lors de sa première utilisation,</li>'
    . '<li>a transmission de cette demande -pour approbation- aux modérateurs de la plateforme</li>'
    . '<li>et l’envoi d’un courriel récapitulatif de cette demande à l’adresse :';
$string['bockhelpE7s'] = '</li></ul></p>';
$string['blocktitleE4'] = 'Enseignant(s) contributeurs de l\'espace de cours';
$string['blocktitleE5'] = 'Étudiants : inscriptions par cohortes';
$string['categoryblock'] = 'Category';
$string['categoryblockE3'] = 'Rattachement principal de l\'espace';
$string['categoryblockE3s1'] = 'Autre(s) rattachement(s) de l\'espace (optionnel)';
$string['categoryerrormsg1'] = 'Vous devez sélectionner un Niveau.';
$string['categoryerrormsg2'] = 'Aucune catégorie n\'a été sélectionnée.';
$string['cohort'] = 'Cohort';
$string['cohortname'] = 'Cohort name or student name';
$string['cohorts'] = 'Cohorts';
$string['complementdefault'] = 'Cours magistral et TD';
$string['complementlabel'] = 'Complément : ';
$string['confirmation'] = 'Vos remarques ou questions concernant cet espace de cours';
$string['confirmationtitle'] = 'Confirmation';
$string['coursedefinition'] = 'Step 2 - Course definition';
$string['coursedescription'] = 'Step 3 - Course description';
$string['coursegeneralhelp'] = '<p>Le nom complet de l\'espace est affiché en haut de chacune des pages du cours et sur la '
    . 'liste des cours.<br/>Le nom abrégé de l\'espace est affiché dans le menu de navigation (en haut à gauche de '
    . 'l\'écran), dans le fil d\'Ariane et dans l\'objet de certains courriels. Le texte de présentation '
    . 'est en accès public : il est affiché sur la fiche signalétique de l\'espace accessible à partir '
    . 'de la page d\'accueil de la plateforme et dans les résultats d\'une recherche.</p>';
$string['courserequestdate'] = 'Course request date';
$string['coursesettingsblock'] = 'Course settings';
$string['coursesettingshelp'] = 'Texte d’aide et de conseil. Suite du texte d’aide et de conseil. Suite du texte d’aide et de conseil.';
$string['coursestartdate'] = 'Start Date : ';
$string['coursesummary'] = 'Summary : ';
$string['editingteacher'] = 'Editing teacher';
$string['enrolcohorts'] = 'Enrol cohorts';
$string['enrolkey'] = 'Enrolment keys';
$string['enrolteachers'] = 'Enrol teachers';
$string['findcohort'] = 'Find a cohort';
$string['findteacher'] = 'Find a teacher';
$string['findvalidator'] = 'Find a validator';
$string['finish'] = 'Finish';
$string['fullnamecourse'] = 'Fullname : ';
$string['generalinfoblock'] = 'General info';
$string['guest'] = 'Guest';
$string['guestkey'] = 'Guest enrolment keys';
$string['managecourseblock'] = 'Manage course';
$string['messagekeyblock1'] = 'message';
$string['messagekeyblock2'] = '<b>Attention : </b>';
$string['nextstage'] = 'Next';
$string['noeditingteacher'] = 'Non-editing teacher';
$string['pluginname'] = 'Course Wizard';
$string['previousstage'] = 'Previous';
$string['role'] = 'Role';
$string['selectcourse'] = 'Step 1 - Select course';
$string['selectedcohort'] = 'Selected cohorts';
$string['selectedteacher'] = 'Selected teachers';
$string['selectedvalidator'] = 'Approbateur sélectionné';
$string['selectvalidator'] = 'Étape 3 : Désignation d\'un approbateur';
$string['stepkey'] = 'Enrolment keys';
$string['student'] = 'Student';
$string['studentkey'] = 'Student enrolment keys';
$string['shortnamecourse'] = 'Shortname : ';
$string['summaryof'] = 'Summary';
$string['teacher'] = 'Teacher';
$string['teachername'] = 'Teacher name';
$string['teachers'] = 'Enseignants';
$string['up1composante'] = 'Autre(s) composante(s) : ';
$string['up1datefermeture'] = 'Date de fermeture aux étudiants : ';
$string['up1niveau'] = 'Autre(s) type(s) de diplôme(s) : ';
$string['userlogin'] = 'Login';
$string['username'] = 'Username';
$string['validatorname'] = 'Nom de l\'approbateur';
$string['wizardcourse'] = 'Course Wizard';

/** old **/
$string['up1domaine'] = 'Domaine(s) d\'enseignement : ';
$string['up1mention'] = 'Mention(s) : ';
$string['up1parcours'] = 'Parcours(s) : ';
$string['up1specialite'] = 'Spécialité(s) : ';
