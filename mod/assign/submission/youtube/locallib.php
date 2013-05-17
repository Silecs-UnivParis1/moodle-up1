<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The main class definitions for the youtube submission plugin
 * Development funded by: Global Awakening (@link http://www.globalawakening.com)
 *
 * @package    assignsubmission_youtube
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 


/** Include eventslib.php */
//require_once($CFG->libdir.'/eventslib.php');

defined('MOODLE_INTERNAL') || die();
define('ASSIGNSUBMISSION_YOUTUBE_COMPONENT','assignsubmission_youtube');
define('ASSIGNSUBMISSION_YOUTUBE_FILEAREA','assignsubmission_youtube');
define('ASSIGNSUBMISSION_YOUTUBE_TABLE','assignsubmission_youtube');
define('YOUTUBEAPPID','moodle_youtube_assigsub');

$clientLibraryPath = $CFG->httpswwwroot . '/mod/assign/submission/youtube/ZendGdata-1.12.1/library';
$oldPath = set_include_path(get_include_path() . PATH_SEPARATOR . $clientLibraryPath);
require_once 'Zend/Loader.php';

//Added Justin 20120115 For OAUTH, 
require_once($CFG->libdir.'/googleapi.php');

/**
 * library class for youtube submission plugin extending submission plugin base class
 *
 * @package    assignsubmission_youtube
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_youtube extends assign_submission_plugin {
	private $youtubeoauth = null;

    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('youtube', ASSIGNSUBMISSION_YOUTUBE_COMPONENT);
    }

	    /**
     * Get the settings for Youtube submission plugin form
     *
     * @global stdClass $CFG
     * @global stdClass $COURSE
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

		//get default display size for single player screens
		$displaysizesingle_default = $this->get_config('displaysizesingle');
		if($displaysizesingle_default == null){
			$displaysizesingle_default = get_config('assignsubmission_youtube', 'displaysize_single');
		}
		
		//get default display size for screens with lists of players
		$displaysizelist_default = $this->get_config('displaysizelist');
		if($displaysizelist_default == null){
			$displaysizelist_default = get_config('assignsubmission_youtube', 'displaysize_list');
		}
		
		/*
		//get default authentication type
		$authtype_default = $this->get_config('authtype');
		if(!$authtype_default){
			$authtype_default = get_config('assignsubmission_youtube', 'authtype');
		}
		*/
			
		//The size of the youtube player on the various screens		
		$displaysizes = array('0' => new lang_string('linkonly', 'assignsubmission_youtube'),
				'160' => '160x120', '320' => '320x240','480' => '480x360',
				'640' => '640x480','800'=>'800x600','1024'=>'1024x768');
		
		//add the screen size selectors
		//single
		$mform->addElement('select', 'assignsubmission_youtube_displaysizesingle',
			get_string('displaysizesingle', 'assignsubmission_youtube'), $displaysizes);
		$mform->setDefault('assignsubmission_youtube_displaysizesingle', $displaysizesingle_default);
		$mform->disabledIf('assignsubmission_youtube_displaysizesingle', 'assignsubmission_youtube_enabled', 'eq', 0);
		
		//list
		$mform->addElement('select', 'assignsubmission_youtube_displaysizelist',
			get_string('displaysizelist', 'assignsubmission_youtube'), $displaysizes);
		$mform->setDefault('assignsubmission_youtube_displaysizelist', $displaysizelist_default);
		$mform->disabledIf('assignsubmission_youtube_displaysizelist', 'assignsubmission_youtube_enabled', 'eq', 0);
		

		
		//The authentication type, master user or student by student
		/*
		$authoptions = array('byuser' => new lang_string('byuser', 'assignsubmission_youtube'),
			'bymaster' => new lang_string('bymaster', 'assignsubmission_youtube'));
		$mform->addElement('select', 'assignsubmission_youtube_authtype',
			get_string('authtype', 'assignsubmission_youtube'), $authoptions);
		$mform->setDefault('assignsubmission_youtube_authtype', $authtype_default);
		$mform->disabledIf('assignsubmission_youtube_authtype', 'assignsubmission_youtube_enabled', 'eq', 0);	
		*/

    }
    
    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool 
     */
    public function save_settings(stdClass $data) {
        $this->set_config('displaysizesingle', $data->assignsubmission_youtube_displaysizesingle);
		$this->set_config('displaysizelist', $data->assignsubmission_youtube_displaysizelist);
		/*$this->set_config('authtype', $data->assignsubmission_youtube_authtype);*/
        return true;
    }

	/**
     * Initialize and return Youtube api object
     * This is for uploading and is only called by the uploader.php in an iframe
	 * User should already be authenticated by the time they get here.
	 *
     * @return youtube api object 
     */
	public function init_youtube_api(){
		global $CFG,$USER;
		$devkey = get_config('assignsubmission_youtube', 'devkey');
		//looks like $devkey = "fghfghgsi56prjLHMrIUKTyZaG9KuWOmJ4ifBo3432432453jHnA3-8xFIBwPvxhgr4J6So08E76762767623232";
		Zend_Loader::loadClass('Zend_Gdata_YouTube');
		//get our httpclient
		//oauth2 for authorizing student by student
		//clientlogin for authorizing by masteruser
		switch (get_config('assignsubmission_youtube', 'authtype')){
			case 'byuser':
					$returnurl = new moodle_url('/mod/assign/view.php');		
					$this->initialize_oauth($returnurl);
					$httpclient = $this->get_youtube_httpclient("oauth2");
					break;
			case 'bymaster':
			default:
					$httpclient = $this->get_youtube_httpclient("clientlogin");
		}
		
		// create our youtube object.
		$yt = new Zend_Gdata_YouTube($httpclient,YOUTUBEAPPID,fullname($USER),$devkey);

		return $yt;

		
		
	}
	
	/**
     * Initialize and return Youtube api object
     *
     * @param string authentication method (clientlogin, authsub, oauth2)
     * @return youtube httpclient object 
     */
	public function get_youtube_httpclient($authmethod){
		
		switch ($authmethod){
			case "authsub":
				$httpclient=null;
				break;
			case "oauth2":
				//We have hijacked the AuthSub class, to use OAUTH2. I know, I know ...
				//But its the best way till API V3 is stable
				Zend_Loader::loadClass('Zend_Gdata_AuthSub');
				$httpclient = Zend_Gdata_AuthSub::getHttpClient($this->youtubeoauth->fetch_accesstoken());
				break;
			case "clientlogin":
			default:
				$username = get_config('assignsubmission_youtube', 'youtube_masteruser');
				$userpass = get_config('assignsubmission_youtube', 'youtube_masterpass');
			
				Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
				$authenticationURL= 'https://www.google.com/accounts/ClientLogin';
				$httpclient = Zend_Gdata_ClientLogin::getHttpClient(
					$username = $username,
					$password = $userpass,
					$service = 'youtube',
					$client = null,
					$source = 'Moodle Youtube Assignment Submission', // a short string identifying your application
					$loginToken = null,
					$loginCaptcha = null,
					$authenticationURL);
				
					
		}
		return $httpclient;
	}
	
	
   /**
    * Get Youtube submission information from the database
    *
    * @param  int $submissionid
    * @return mixed
    */
    private function get_youtube_submission($submissionid) {
        global $DB;

        return $DB->get_record(ASSIGNSUBMISSION_YOUTUBE_TABLE, array('submission'=>$submissionid));
    }

    /**
     * Add form elements onlinepoodll submissions
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
		 global $CFG, $USER, $PAGE;
		 
        $elements = array();

		//Get our youtube submission
        $submissionid = $submission ? $submission->id : 0;
        if ($submission) {
            $youtubesubmission = $this->get_youtube_submission($submission->id);
			//if we have a submission already, lets display it for the student
			if($youtubesubmission){
				$ytplayersize =  get_config('assignsubmission_youtube', 'displaysize_single');
				$ytplayer = $this->fetch_youtube_player($youtubesubmission->youtubeid,$ytplayersize);
				$mform->addElement('static', 'currentsubmission', get_string('currentsubmission','assignsubmission_youtube'),$ytplayer);
			}
        }

		//determine a video title
		$videotitle = $this->assignment->get_instance()->name . ': ' . fullname($USER);
		if(strlen($videotitle >90)){$videotitle = substr($videotitle,90);}

		//UPLOADER Tab
		//we will flag this false if admin settings are empty
		$uploadpossible=true;
		//get the header text for our upload tab
		$upload = get_string('uploadavideodetails', 'assignsubmission_youtube');
		
		//check if we have all the credentials we need
		$devkey = get_config('assignsubmission_youtube', 'devkey');
		$authtype = get_config('assignsubmission_youtube', 'authtype');
		$masteru = get_config('assignsubmission_youtube', 'youtube_masteruser');
		$masterp = get_config('assignsubmission_youtube', 'youtube_masterpass');;
		$clientid = get_config('assignsubmission_youtube', 'youtube_clientid');;
		$secret = get_config('assignsubmission_youtube', 'youtube_secret');
		if(empty($devkey)){
				$uploadpossible=false;
				$upload .= '<i>' . get_string('nodevkey', 'assignsubmission_youtube') . '</i>';
		}elseif($authtype=='byuser' && 
			(empty($clientid)||empty($secret))){
				$uploadpossible=false;
				$upload .=  '<i>' . get_string('nooauth2', 'assignsubmission_youtube'). '</i>';
		}elseif($authtype=='bymaster' && 
			(empty($masteru)||empty($masterp))){
				$uploadpossible=false;
				$upload .=  '<i>' . get_string('nomaster', 'assignsubmission_youtube'). '</i>';
		}
		
		//prepare the URL to our uploader page that will be loaded in an iframe
		$src = $CFG->httpswwwroot . '/mod/assign/submission/youtube/uploader.php?showform=1';
		$src .= '&videotitle=' . urlencode($videotitle);
		
		//If we need to log in to google(OAUTH2) we simply show a button with that URL
		//otherwise we load the uploader.php in an iframe .
		if($authtype=='byuser' && $uploadpossible){
			
			//We set the return URL of the youtube login to return us to this page
			$returnurl = new moodle_url('/mod/assign/view.php');
			$returnurl->param('id', required_param('id', PARAM_INT));
			$returnurl->param('action', optional_param('action','', PARAM_TEXT));
			$returnurl->param('sesskey', sesskey());
	
			//get our youtube object
			$this->initialize_oauth($returnurl);
			
			if (!$this->youtubeoauth->is_logged_in()) {
					$loginurl = $this->youtubeoauth->get_login_url();
					$logintext =  get_string('logintext', 'assignsubmission_youtube');
					//we use a JS  button, but a simple link would be as good
					$upload .= "<input type='button' value='" . $logintext  
						. "' onclick='window.location.href=\"" . $loginurl. "\"' />";
					//$upload .= "<a href='" . $loginurl . "'>go to youtube</a>";
			}else{
			/*
				$upload .="<br/>accesstoken:" . $this->youtubeoauth->fetch_accesstoken();
				$upload .="<br/>scope:" . $this->youtubeoauth->fetch_scope();
				$upload .="<br/>logged in:" . $this->youtubeoauth->is_logged_in();
				$upload .="<br/>lurl:" . $this->youtubeoauth->get_login_url();
			*/
				$upload .= "<iframe src='$src' width='500' height='110' frameborder='0'></iframe>";
				}
		}elseif($uploadpossible){
			$upload .= "<iframe src='$src' width='500' height='110' frameborder='0'></iframe>";
		}

		
		//WEBCAM Tab
		//get our youtube recorder
		/* */
		$webcam = get_string('recordavideodetails', 'assignsubmission_youtube');
		$webcam .= $this->fetch_youtube_recorder($videotitle);
		

		//ENTER URL Tab
		/* manual URL submission */
		$manual = get_string('linkavideodetails', 'assignsubmission_youtube');
		$manual .= "<input type=\"text\" size=\"75\" onchange=\"document.getElementById('id_manualurl').value=this.value;\"/>";
		

		//set up the html list elements that will get styled as tabs
		$medialist="";
		$mediadivs="";
		if(get_config('assignsubmission_youtube', 'allow_uploads')){ 
				$medialist .= '<li><a href="#tabupload">' . get_string('uploadavideo', 'assignsubmission_youtube') . '</a></li>';
				$mediadivs .= '<div id="tabupload">' . $upload . '</div>';
		}
		if(get_config('assignsubmission_youtube', 'allow_webcam')){ 
				$medialist .= '<li><a href="#tabwebcam">' . get_string('recordavideo', 'assignsubmission_youtube') . '</a></li>';
				$mediadivs .= '<div id="tabwebcam">' . $webcam . '</div>';
		}
		if(get_config('assignsubmission_youtube', 'allow_manual')){ 
				$medialist .= '<li><a href="#tabmanual">' . get_string('linkavideo', 'assignsubmission_youtube') . '</a></li>';
				$mediadivs .= '<div id="tabmanual">' . $manual . '</div>';
		}
	
		//form the list
		$mediadata ='<div id="youtubeassig_uploadtabs"><ul>';
		$mediadata .= $medialist;			
		$mediadata .= '</ul><div>';
		$mediadata .= $mediadivs;
		$mediadata .= '</div></div>';
		
		//add the controls that will be submitted
		$mform->addElement('static', 'description', '',$mediadata);	
		$mform->addElement('hidden','youtubeid','',array('id'=>'id_youtubeid'));
		$mform->addElement('hidden','manualurl','',array('id'=>'id_manualurl'));
		$mform->setType('youtubeid', PARAM_TEXT); 
		$mform->setType('manualurl', PARAM_TEXT); 
		
		//create tabs
		//configure our options array
		$opts = array(
			"tabviewid"=> "youtubeassig_uploadtabs"
		);
		
		//Set up our JS library
		//			'token'		=> $tokenValue,
		$jsmodule = array(
			'name'     => 'assignsubmission_youtube',
			'fullpath' => '/mod/assign/submission/youtube/module.js',
			'requires' => array('tabview')
		);
		
		
		$PAGE->requires->js_init_call('M.assignsubmission_youtube.loadyuitabs', array($opts),false,$jsmodule);


		return true;

    }
	//Here we init the auth, which will set up stuff for google
	  public function initialize_oauth($returnurl) {
		//the realm is always the same for YouTube api calls
		//and the clientid and secret are set in the admin settings for this plugin
        $clientid = get_config('assignsubmission_youtube', 'youtube_clientid');
        $secret = get_config('assignsubmission_youtube', 'youtube_secret');
		$realm = "http://gdata.youtube.com";
		//create and store our YouTube oauth client
        $this->youtubeoauth = new youtube_oauth($clientid, $secret, $returnurl, $realm);
    }

	  /**
     * Add form elements onlinepoodll submissions
     *
     * @param string $controlid is the id of a hidden form field into which the video id is inserted
     * @return string containing html to embed a recorder on a page
     */
    public function fetch_youtube_recorder($videotitle) {
		global $PAGE;
		
		$PAGE->requires->js(new moodle_url('http://www.youtube.com/iframe_api'));
		$recorderid = "youtuberecorder_id";
		$width=500;
		$ret="";
	
	
		//configure our options array
		//we aren't using this ...
		/*
		$opts = array(
			"recorderid"=> $recorderid,
			"width"=> $width,
			"videotitle"=>$videotitle
		);
		*/
		
		//Set up our JS library
		//			'token'		=> $tokenValue,
		$jsmodule = array(
			'name'     => 'assignsubmission_youtube',
			'fullpath' => '/mod/assign/submission/youtube/module.js'
		);
		
		//The JS init call does not work well in a tab, so we defer load of recorder 
		//to when the button is clicked
		//$PAGE->requires->js_init_call('M.assignsubmission_youtube.loadytrecorder', array($opts),false,$jsmodule);
		$ret .= "<input type='button' value='" . 
				get_string('clicktorecordvideo', 'assignsubmission_youtube') . 
			"' onclick='directLoadYTRecorder(\"" . $recorderid. "\",\"" . $videotitle. "\", " . $width . ");this.style.display=\"none\";' >";
		
		//
		$ret .= "<div id='$recorderid'></div>";
		
		return $ret;
	}
	
	public function fetch_youtube_uploadform($yt,$videotitle,$videodescription){
		global $CFG, $USER;
		
	
		// create a new VideoEntry object
		$myVideoEntry = new Zend_Gdata_YouTube_VideoEntry();

		$myVideoEntry->setVideoTitle($videotitle);
		$myVideoEntry->setVideoDescription($videodescription);
		// The category must be a valid YouTube category!
		$myVideoEntry->setVideoCategory('Education');
		
		//This sets videos private, but then can't view if not logged in as the account owner
		//$myVideoEntry->setVideoPrivate();
		
		//So instead we set them to unlisted(but its more complex)
		$unlisted = new Zend_Gdata_App_Extension_Element( 'yt:accessControl', 'yt',
										'http://gdata.youtube.com/schemas/2007', '' );
		$unlisted->setExtensionAttributes(array(
			array('namespaceUri' => '', 'name' => 'action', 'value' => 'list'),
			array('namespaceUri' => '', 'name' => 'permission', 'value' => 'denied')
		));
		$myVideoEntry->setExtensionElements(array($unlisted));

		// Set keywords. This must be a comma-separated string
		// Individual keywords cannot contain whitespace
		// We are not doing this, but it would be possible
		//$myVideoEntry->SetVideoTags('cars, funny');

		//data is all set, so we get our upload token from google
		$tokenHandlerUrl = 'http://gdata.youtube.com/action/GetUploadToken';
		$tokenArray = $yt->getFormUploadToken($myVideoEntry, $tokenHandlerUrl);
		$tokenValue = $tokenArray['token'];
		$postUrl = $tokenArray['url'];
		
		//Set the URL YouTube should redirect user to after upload
		//that will be the same iframe
		$nextUrl =  $CFG->httpswwwroot . '/mod/assign/submission/youtube/uploader.php';

		// Now that we have the token, we build the form
		$form = '<form action="'. $postUrl .'?nexturl='. $nextUrl .
        '" method="post" enctype="multipart/form-data">'. 
        '<input name="file" type="file"/>'. 
        '<input name="token" type="hidden" value="'. $tokenValue .'"/>'.
        '<input value="Upload Video File" type="submit" onclick="document.getElementById(\'id_uploadanim\').style.display=\'block\';" />'. 
        '</form>';
        
        // We tag on a hidden uploading icon. YouTube gives us no progress events, sigh.
        // So its the best we can do to show an animated gif.
        // But if it fails, user will wait forever.
        $form .= '<img id="id_uploadanim" style="display: none;margin-left: auto;margin-right: auto;" src="' . $CFG->httpswwwroot . '/mod/assign/submission/youtube/pix/uploading.gif"/>';
		
		return $form;
	
	}
	
	/**
     * Sets up js and html elements that will swapped out with a youtube player
     *
     * @param string $videoid is id of video to be played
	 *
     * @return true if elements were added to the form
     */
    public function fetch_youtube_player($videoid,$size) {
		global $PAGE, $CFG;
		
		//if we don't have a video id, we return an empty string
		if(empty($videoid)){return "";}
		
		$PAGE->requires->js(new moodle_url('http://www.youtube.com/iframe_api'));
		
		//$playerid = "ytplayer_" . rand(100000, 999999);
		$playerid = $videoid;

		//get our size profile
		switch($size){
			case "0": $width=320;$height=240;break;
			case "160": $width=160;$height=120;break;
			case "320": $width=320;$height=240;break;
			case "480": $width=480;$height=360;break;
			case "640": $width=640;$height=480;break;
			case "800": $width=800;$height=600;break;
			case "1024": $width=1024;$height=768;break;
			default:$width=320;$height=240;
		}
		
			
				
		//We need this so that we can require the JSON , for json stringify
		$jsmodule = array(
			'name'     => 'assignsubmission_youtube',
			'fullpath' => '/mod/assign/submission/youtube/module.js',
			'requires' => array('json')
		);
		
		//configure our options array
		$opts = array(
			"playerid"=> $playerid,
			"videoid"=> $videoid,
			"width"=> $width,
			"height"=> $height
		);
		
		//setup our JS call
		
		if($size =="0"){
			$PAGE->requires->js(new moodle_url($CFG->httpswwwroot . '/mod/assign/submission/youtube/module.js'));
			$callfunc = "directLoadYTPlayer(\"$playerid\",$width,$height,\"$playerid\")";
			return "<div id='$playerid' onclick='$callfunc'><a href='#$playerid'>" . 
				get_string('clicktoplayvideo', 'assignsubmission_youtube') 
				. "</a></div>";
		}else{
			$PAGE->requires->js_init_call('M.assignsubmission_youtube.loadytplayer', array($opts),false,$jsmodule);
			return "<div id='$playerid'></div>";
		}
	}

	
     /**
      * Save data to the database
      *
      * @param stdClass $submission
      * @param stdClass $data
      * @return bool
      */
     public function save(stdClass $submission, stdClass $data) {
        global $DB;

		//print_r($data);
		
		//if we have a youtube url, use that.
		//if not perhaps the user entered one manually.
		$youtubeid = $data->youtubeid;
		if($youtubeid==''){
			if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', 
				$data->manualurl, $match)) {
				$youtubeid = $match[1];
			}
		}
		
        $youtubesubmission = $this->get_youtube_submission($submission->id);
        if ($youtubesubmission) {
			$youtubesubmission->submission = $submission->id;
            $youtubesubmission->assignment = $this->assignment->get_instance()->id;
		 	$youtubesubmission->youtubeid = $youtubeid;
            return $DB->update_record(ASSIGNSUBMISSION_YOUTUBE_TABLE, $youtubesubmission);
        } else {

            $youtubesubmission = new stdClass();

            $youtubesubmission->submission = $submission->id;
            $youtubesubmission->assignment = $this->assignment->get_instance()->id;
            $youtubesubmission->youtubeid = $youtubeid;
            return $DB->insert_record(ASSIGNSUBMISSION_YOUTUBE_TABLE, $youtubesubmission) > 0;
        }


    }

    /**
     * Display the list of files  in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
    	$showviewlink = false;
		
		$youtubesubmission = $this->get_youtube_submission($submission->id);

        if ($youtubesubmission) {
			$size = $this->get_config('displaysizelist');
			$videoid = $youtubesubmission->youtubeid;	
			$ret = $this->fetch_youtube_player($videoid,$size) ;
		}

        return $ret;
    }
	
	    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        $ret = '';

        $youtubesubmission = $this->get_youtube_submission($submission->id);

        if ($youtubesubmission) {
			$size = $this->get_config('displaysizesingle');
			$videoid = $youtubesubmission->youtubeid;
			$ret = $this->fetch_youtube_player($videoid,$size) ;
		}

        return $ret;
    }

      /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission) {
        $result = array();
		//how to handle this?
        return $result;
    }



     /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {

        return false;
    }



  

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The new submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {

        $youtubeloginfo = '';

        $youtubeloginfo .= "submission id:" . $submission->id . " added.";

        return $youtubeloginfo;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records(ASSIGNSUBMISSION_YOUTUBE_TABLE, array('assignment'=>$this->assignment->get_instance()->id));

        return true;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return $this->view($submission) == '';
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGNSUBMISSION_YOUTUBE_FILEAREA=>$this->get_name());
    }

}

/**
 * OAuth 2.0 client for Youtube
 *
 * @package   assignsubmission_youtube
 * @copyright 2013 Justin Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class youtube_oauth extends google_oauth {
	
	public function fetch_accesstoken(){
		//This should work, but doesn't. Why?
		//return $this->accesstoken->token;
		
		 // We have a token so we are logged in.
		 $at = $this->get_stored_token();
        if (isset($at->token)) {
            return $at->token;
        }else{
			return false;
		}
	}

}


