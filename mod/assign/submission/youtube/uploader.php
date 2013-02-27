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
 * This file is loaded in an iframe in the YouTube assignment submission
 * Development funded by: Global Awakening (@link http://www.globalawakening.com)
 *
 * @package    assignsubmission_youtube
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/assign/locallib.php');
require_once($CFG->dirroot.'/mod/assign/submission/youtube/locallib.php');

global $PAGE, $USER;


// we get the request parameters:
$showform = optional_param('showform', 0,PARAM_INT); // to show the form(lets rcord) or not(recording finished)
$status = optional_param('status', 0,PARAM_INT); // request status
$video_id = optional_param('id', '', PARAM_TEXT); // youtube id of video
$code = optional_param('code', 0,PARAM_INT); // error code
$video_title = optional_param('videotitle', 'a youtube assignment',PARAM_TEXT); // title of video

//we need to set the page context
require_login();
$PAGE->set_context(get_context_instance(CONTEXT_USER, $USER->id));

//if we are returning from a youtube upload we need to process the returned info in JS
if($showform==0){
	if($status==200){
		?>
		<html>
			<head>
				<script type="text/javascript">
					function process_youtube_return()
					{
						var vfield = parent.document.getElementById('id_youtubeid');
						vfield.value = '<?php echo $video_id; ?>';
						//if auto saving, uncomment this
						//parent.document.getElementById('id_submitbutton').click();
					}
				</script>
			</head>

			<body onload="process_youtube_return()" >
			<h3><?php echo get_string('uploadsuccessful', 'assignsubmission_youtube'); ?></h3>
			<b><?php echo get_string('pleasesave', 'assignsubmission_youtube'); ?></b>
			</body>
		</html>
		<?php
	}else{
		echo get_string('uploadfailed', 'assignsubmission_youtube');
		echo "<br />code:" . $code;
	}
	
//if we are going to return we prepare the page and recorder
}else {

	// load the youtube submission plugin
	$youtubesub = new assign_submission_youtube(new assign(null,null,null),null);
	if(empty($youtubesub)) {
		die;
	}
	//set up the page
	$PAGE->set_context(get_context_instance(CONTEXT_USER, $USER->id));
	$PAGE->set_url($CFG->wwwroot.'/mod/assign/submission/youtube/uploader.php');
	?>

	<div style="text-align: center;">
	<?php 
				$yt = $youtubesub->init_youtube_api();
				echo $youtubesub->fetch_youtube_uploadform($yt,$video_title,$video_title);

	?>
	</div>
	<?php
	}