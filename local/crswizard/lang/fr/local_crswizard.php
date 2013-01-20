<?php
/**
 * Strings for component 'wizard', language 'fr'
 *
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Assistant de création de cours';
// capabilities
$string['crswizard:creator'] = 'Créer un cours avec l\'assistant';
$string['crswizard:validator'] = 'Approuver un cours créé avec l\'assistant';
$string['crswizard:supervalidator'] = 'Approuver n\'importe quel cours créé avec l\'assistant';
$string['crswizard:localsupervalidator'] = 'Approuver n\'importe quel cours créé avec l\'assistant, contexte local';

$string['anotherneed'] = 'Un autre besoin';
$string['blocHelloS1'] = '<p>Bienvenue dans l\'assistant de création d\'espace de cours. '
    . 'Laissez-vous guider et définissez en quelques étapes les caractéristiques, les contributeurs '
    . 'et le public visé de votre EPI (Espace Pédagogique Interactif).</p>'
    . '<p>Pour commencer, choisissez si votre espace :'
    .'<ul><li>concerne un élément pédagogique de l\'offre de formation (diplôme, enseignement, groupe de TD, etc.)</li>'
    . '<li>ou répond à un autre besoin (projet particulier, formation pour les personnels, etc.).</li></ul></p>';
$string['bockhelpE2'] = '<p>Cette étape fondamentale vous permet de situer votre espace dans l\'organisation générale des EPI.<br/>'
    . 'Il est important de bien réfléchir aux informations que vous allez compléter ci-dessous, car '
    . 'elles ont une incidence sur la facilité avec laquelle vos étudiants retrouveront votre espace de cours.<br/>'
    . 'Notez que si votre espace doit être rattaché à plusieurs composantes / services, il vous sera '
    . 'possible de le spécifier à l\'étape suivante.</p>';
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
$string['blocktitleE4'] = 'Enseignant(s) contributeur(s) de l\'espace de cours';
$string['blocktitleE5'] = 'Étudiants : inscriptions par cohorte(s)';
$string['categoryblock'] = 'Catégorie (rattachement principal de l\'espace de cours)';
$string['categoryblockE3'] = 'Rattachement principal de l\'espace';
$string['categoryblockE3s1'] = 'Autre(s) rattachement(s) de l\'espace (optionnel)';
$string['categoryerrormsg1'] = 'Le niveau sélectionné est invalide.';
$string['categoryerrormsg2'] = 'Aucun niveau n\'a été sélectionné, alors que le champ est requis.';
$string['cohort'] = 'Cohorte';
$string['cohortname'] = 'Libellé de groupe ou nom d\'étudiant';
$string['cohorts'] = 'Groupes';
$string['complementdefault'] = 'Cours magistral et TD';
$string['complementlabel'] = 'Complément : ';
$string['confirmation'] = 'Vos remarques ou questions concernant cet espace de cours';
$string['confirmationtitle'] = 'Étape 7 - Finalisation de la demande';
$string['coursedefinition'] = 'Étape 2 - Identification de l\'espace de cours';
$string['coursedescription'] = 'Étape 3 - Description de l\'espace de cours';
$string['coursegeneralhelp'] = '<p>Le nom complet de l\'espace est affiché en haut de chacune des pages du cours et sur la '
    . 'liste des cours.<br/>Le nom abrégé de l\'espace est affiché dans le menu de navigation (en haut à gauche de '
    . 'l\'écran), dans le fil d\'Ariane et dans l\'objet de certains courriels. Le texte de présentation '
    . 'est en accès public : il est affiché sur la fiche signalétique de l\'espace accessible à partir '
    . 'de la page d\'accueil de la plateforme et dans les résultats d\'une recherche.</p>';
$string['courserequestdate'] = 'Date de la demande de création : ';
$string['coursesettingsblock'] = 'Paramétrage de l\'espace de cours';
$string['coursesettingshelp'] = 'Les dates ci-dessous sont purement informatives et correspondent au début '
    . 'et à la fin de la période d\'enseignement.';
$string['coursestartdate'] = 'Date d\'ouverture : ';
$string['coursesummary'] = 'Texte de présentation : ';
$string['editingteacher'] = 'Enseignant';
$string['enrolcohorts'] = 'Étape 5 - Inscription des utilisateurs à l\'espace de cours (étudiants)';
$string['enrolkey'] = 'Clé d\'inscription';
$string['enrolteachers'] = 'Étape 4 - Inscription des utilisateurs à l\'espace de cours (enseignants)';
$string['findcohort'] = 'Rechercher un groupe d\'étudiants';
$string['findteacher'] = 'Rechercher un enseignant';
$string['findvalidator'] = 'Rechercher un approbateur';
$string['finish'] = 'Terminer';
$string['fullnamecourse'] = 'Nom complet de l\'espace : ';
$string['generalinfoblock'] = 'Informations générales de l\'espace de cours';
$string['guest'] = 'Visiteur';
$string['guestkey'] = 'Clé d\'inscription pour le rôle "visiteur anonyme"';
$string['managecourseblock'] = 'Informations concernant la demande';
$string['messagekeyblock1'] = '<p>Si vous n\'avez trouvé aucun groupe d\'utilisateurs à l\'étape précédente, vous '
    . 'avez la possibilité de communiquer à vos étudiants un code (appelé « clé d\'inscription ») leur '
    . 'permettant de s\'inscrire eux-mêmes à l\'espace de cours lors de leur premier accès.<br/>'
    . 'Conseil : prenez note des clés d\'inscription que vous aurez définies à cette étape et conservez-les précieusement.</p>';
$string['messagekeyblock2'] = '<b>Attention : </b>Il faut renseigner le champ "<b>clé d\'inscription'
			. '</b>" pour que la clé soit créée.';
$string['nextstage'] = 'Étape suivante';
$string['noeditingteacher'] = 'Enseignant non éditeur';
$string['previousstage'] = 'Étape précédente';
$string['rofselected1'] = 'Rattachement de référence';
$string['rofselected2'] = 'Rattachement(s) secondaire(s)';
$string['role'] = 'Rôle';
$string['selectcourse'] = 'Étape 1 - Pour quel enseignement souhaitez-vous ouvrir un espace sur la plateforme ?';
$string['selectedcohort'] = 'Groupes sélectionnés';
$string['selectedteacher'] = 'Enseignants sélectionnés';
$string['selectedvalidator'] = 'Approbateur sélectionné';
$string['selectvalidator'] = 'Étape 3 : Désignation d\'un approbateur';
$string['shortnamecourse'] = 'Nom abrégé de l\'espace : ';
$string['summaryof'] = 'Récapitulatif de la demande';
$string['student'] = 'Étudiant';
$string['stepkey'] = 'Étape 6 - Clé d\'inscription';
$string['studentkey'] = 'Clé d\'inscription pour le rôle "étudiant"';
$string['teachername'] = 'Nom de l\'enseignant';
$string['teacher'] = 'Enseignant';
$string['up1composante'] = 'Autre(s) composante(s) : ';
$string['up1datefermeture'] = 'Date de fermeture : ';
$string['up1niveau'] = 'Autre(s) type(s) de diplôme(s) : ';
$string['userlogin'] = 'Login du demandeur : ';
$string['username'] = 'Nom du demandeur : ';
$string['teachers'] = 'Enseignants';
$string['validatorname'] = 'Nom de l\'approbateur';
$string['wizardcourse'] = 'Assistant ouverture/paramétrage coursMoodle';

/** old **/
$string['up1domaine'] = 'Domaine(s) d\'enseignement : ';
$string['up1mention'] = 'Mention(s) : ';
$string['up1parcours'] = 'Parcours(s) : ';
$string['up1specialite'] = 'Spécialité(s) : ';
