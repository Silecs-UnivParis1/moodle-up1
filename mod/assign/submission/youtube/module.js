/**
 * Javascript for assignsubmission_youtube
 * Development funded by: Global Awakening (@link http://www.globalawakening.com)
 *
 * @copyright &copy; 2012 Justin Hunt
 * @author Justin Hunt
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package assignsubmission_youtube
 */

M.assignsubmission_youtube = {}

// Replace designated div with a YUI tab set
M.assignsubmission_youtube.loadyuitabs = function(Y,opts) {
	Y.use('tabview', function(Y) {
		var tabview = new Y.TabView({
			srcNode: '#' + opts['tabviewid']
		});

		tabview.render();
	});
}

// Replace youtube designated divs with youtube players
M.assignsubmission_youtube.loadytplayer = function(Y,opts) {

    //  function onYouTubeIframeAPIReady() {
	directLoadYTPlayer(opts['playerid'],
		opts['width'],
        opts['height'],      
        opts['videoid']);   
	  //}
}

M.assignsubmission_youtube.loadytrecorder = function(Y,opts) {

	directLoadYTRecorder(opts['recorderid'],
		opts['width']);   

}

		function directLoadYTRecorder(recorderid,videoname,width) {
			videotitle = videoname;
			widget = new YT.UploadWidget(recorderid, {
			  width: width,
			  webcamOnly: true,
			  events: {
            'onUploadSuccess': onUploadSuccess,
            'onProcessingComplete': onProcessingComplete,
            'onApiReady': onApiReady
			}
		});
		
		}
	 
	    function directLoadYTPlayer(playerid,width,height,videoid){
			new YT.Player(playerid, {
			width: width,
			height: height,      
			videoId: videoid,
			events: {
            'onReady': onYTPlayerReady,
            'onStateChange': onYTPlayerStateChange		
          }
        });
		
		}

	   function onYTPlayerReady(event) {
			//do something, eg event.target.playVideo();
	  }
	    function onYTPlayerStateChange(event) {
			//do something, eg event.target.playVideo();
	  }
	  
	     function onUploadSuccess(event) {
			document.getElementById('id_youtubeid').value=event.data.videoId;
	  }
	    function onProcessingComplete(event) {
			//document.getElementById('id_youtubeid').value=event.data.videoId;
	  }
	  
	   function onApiReady(event) {
			//var widget = event.target; //this might work, if global "widget" doesn't
			widget.setVideoTitle(videotitle);
			widget.setVideoDescription(videotitle);
			widget.setVideoPrivacy('unlisted'); 
	  }

  

	 
 
