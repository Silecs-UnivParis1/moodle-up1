<?php
require_once __DIR__ . '/../../config.php';

/* @var $PAGE page_base */
/* @var $OUTPUT core_renderer */

global $CFG, $PAGE, $OUTPUT, $USER;

define('SHIBB_DEFAULT_IDP', 'urn:mace:cru.fr:federation:univ-paris1.fr');

require_once $CFG->dirroot . "/auth/shibboleth/auth.php";

redirect_if_major_upgrade_required();

if (!empty($_COOKIE['shibb_remember'])) {
    $_POST['idp'] = SHIBB_DEFAULT_IDP;
    require $CFG->dirroot . "/auth/shibboleth/login.php";
    exit;
}

//HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_url("$CFG->httpswwwroot/local/login-multi/index.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');
$PAGE->requires->css(new moodle_url('/local/login-multi/login.css'));

$site = get_site();
$loginsite = get_string("loginsite");
$shiburl = new moodle_url('/auth/shibboleth/');

$PAGE->navbar->add($loginsite);

// make sure we really are on the https page when https login required
$PAGE->verify_https_required();

$PAGE->set_title("$site->fullname: $loginsite");
$PAGE->set_heading($site->fullname);

$PAGE->requires->js_init_code('
    var localLoginShown = true;
    function toggleLocalLogin() {
        if (localLoginShown) {
            document.getElementsByClassName("local-login")[0].className = "box generalbox local-login hide-form";
            document.getElementById("toggle-local").innerHTML = "&#x25BD;";
        } else {
            document.getElementsByClassName("local-login")[0].className = "box generalbox local-login";
            document.getElementById("toggle-local").innerHTML = "&#x25B3;";
        }
        localLoginShown = !localLoginShown;
    }
    toggleLocalLogin();
    document.getElementById("toggle-local").addEventListener("click", toggleLocalLogin, true);

    var checkboxes = document.getElementsByClassName("shibb_remember");
    for (var i=0; i < checkboxes.length; i++) {
        checkboxes[i].addEventListener("click", function(){
            if (this.checked) {
                var cookie = "shibb_remember=" + this.name;
                if (this.name == "always") {
                    var expiration_date = new Date();
                    expiration_date.setFullYear(expiration_date.getFullYear() + 1);
                    cookie = cookie + ";expires=" + expiration_date.toGMTString();
                }
                document.cookie = cookie;
            } else {
                document.cookie = "shibb_remember=;expires=Thu, 01 Jan 1970 00:00:01 GMT";
            }
        });
    }

    (function(){
        var options = document.getElementById("login-other").getElementsByTagName("option");
        for (var i = 0; i < options.length ; i++) {
            if (options[i].value == "' . SHIBB_DEFAULT_IDP . '") {
                options[i].style.setProperty("display", "none", "");
                options[i].parentNode.removeChild(options[i]);
            }
        }
    })();
');



echo $OUTPUT->header();

if (isloggedin() and !isguestuser()) {
    // prevent logging when already logged in, we do not want them to relogin by accident because sesskey would be changed
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url($CFG->httpswwwroot.'/login/logout.php', array('sesskey'=>sesskey(),'loginpage'=>1)), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url($CFG->httpswwwroot.'/login/index.php', array('cancel'=>1)), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('alreadyloggedin', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
}


echo $OUTPUT->heading("Se connecter avec :", 2, '');

echo $OUTPUT->box_start('generalbox shiblogin first');
echo $OUTPUT->heading("Votre compte Paris 1", 3);
?>
<div class="loginbox clearfix onecolumn">
<form name="login-up1" id="login-up1" method="post" action="<?php echo $shiburl; ?>login.php">
    <div class="form-submit">
        <input type="hidden" name="idp" value="<?php echo SHIBB_DEFAULT_IDP; ?>" />
        <button type="submit">Valider</button>
    </div>
    <div class="form-input">
        <label>
            <input name="session" class="shibb_remember" type="checkbox" />
            Se souvenir de mon choix pour cette session
        </label>
        <label>
            <input name="always" class="shibb_remember" type="checkbox" />
            Se souvenir de mon choix définitivement
        </label>
    </div>
</form>
</div>
<?php
echo $OUTPUT->box_end();

echo $OUTPUT->box_start('generalbox shiblogin');
echo $OUTPUT->heading("Les identifiants d'un autre établissement", 3);
?>
<div class="loginbox clearfix onecolumn">
<form name="login-other" id="login-other" method="post" action="<?php echo $shiburl; ?>login.php">
    <div class="form-submit">
        <button type="submit">Valider</button>
    </div>
    <div class="form-input">
        <select name="idp" id="idp">
            <option value="">                                        </option>
            <?php print_idp_list(); ?>
        </select>
    </div>
</form>
</div>
<?php
echo $OUTPUT->box_end();

echo $OUTPUT->box_start('generalbox local-login');
echo '<div id="toggle-local">&#x25BD;</div>';
echo $OUTPUT->heading("Un compte invité", 3);
display_local_login();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();

function display_local_login() {
    global $CFG, $PAGE, $OUTPUT;

    /// Initialize variables
   $errormsg = '';
   $errorcode = 0;

   $frm = new stdClass();
    if (!empty($_GET["username"])) {
        $frm->username = clean_param($_GET["username"], PARAM_RAW); // we do not want data from _POST here
    } else {
        $frm->username = get_moodle_cookie();
    }
    $frm->password = "";

    if (!empty($frm->username)) {
        $focus = "password";
    } else {
        $focus = "username";
    }

    if (!empty($CFG->registerauth) or is_enabled_auth('none') or !empty($CFG->auth_instructions)) {
        $show_instructions = true;
    } else {
        $show_instructions = false;
    }

    require $CFG->dirroot . "/login/index_form.html";
    if ($errormsg) {
        $PAGE->requires->js_init_call('M.util.focus_login_error', null, true);
    } else if (!empty($CFG->loginpageautofocus)) {
        //focus username or password
        $PAGE->requires->js_init_call('M.util.focus_login_form', null, true);
    }
}