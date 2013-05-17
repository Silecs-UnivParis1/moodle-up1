Youtube Assignment Submission Type for Moodle Assignments (2.3 and above)
========================================

The YouTube assignment submission plugin allows teachers to set an assignment, for which a submission can be a video. The video is uploaded from the student's pc/device directly to YouTube. The video file is stored on YouTube, though it is accessible from Moodle.

Installation
============
The YouTube assignment is contained in the "youtube" folder. That folder should be placed in the following directory of a Moodle installation: 
[PATH TO MOODLE]/mod/assign/submission
Other folders in that directory will include, "file" and "onlinetext"

Once the folder is in place, Moodle will be able to install the plugin. First login as the site administrator. Moodle should detect the YouTube assignment and present a page with plugin information and the unavoidable option to proceed to install a new plugin. If Moodle does not automatically direct you to this page, you can go there from the Moodle menu:
Site Administration -> Notifications
 
Follow the prompts to install the plugin. On the last step Moodle will show the settings page for the YouTube assignment. The settings can be accessed by the administrator at any time from the Moodle menu: 
Site Administration -> Plugins -> Assignment Plugins->Submission Plugins -> YouTube Submission

The YouTube Assignment Settings Page
=====================================
There are a number of configuration options for the YouTube submission type. If you will plan to use the video upload form to submit videos to YouTube, then you must fill in the YouTube developers key and the authentication settings. You can get a free developer key here:
https://code.google.com/apis/youtube/dashboard

If authenticating via a master YouTube account, you need to set the YouTube master account user name and password. If authenticating student by student, you will need to enter your Google OAuth2 credentials. These are the same credentials you would use for the Google Docs repository and the Google Docs portfolio. Information on these can be found here:
http://docs.moodle.org/24/en/Google_OAuth_2.0_setup


Creating an Assignment that uses the YouTube Submission
=========================================================
To use the YouTube assignment submission, first create a new assignment. Note that there are two different assignments in Moodle. The old assignment is now called Assignment (2.2) because it was superceded in version 2.3, though it can still be used. The new assignment is sometimes called the 2.3 assignment. The YouTube submission only works on the new assignment, and so can only be used with Moodle 2.3 or greater. More information on the Moodle Assignment can be found here:
http://docs.moodle.org/23/en/Assignment_module

To create the assignment, first login as a teacher or administrator and turn on editing mode. Then from the appropriate course section click the "add an activity or resource" link. From the popup menu choose "Assignment" (not Assignment 2.2 or its sub menu options) and click the "add" button.
 
You will need to enter a name and description for the new assignment. Most of the other settings are optional and depend on the submission type(s) you enable for the assignment. It is possible to enable more than one submission type per assignment. For example, an assignment could accept both a YouTube video and a text outline of the contents of the video.  The YouTube submission default settings are those set on the main settings page for the plugin.

Submitting  a Video
===================
When a student displays the assignment they will be presented first with a summary of the assignment and a button to "Add Submission."
 
After clicking "Add Submission" a list of forms will be displayed, one for each of the enabled submission types. In the case of the YouTube submission there will be a set of horizontal tabs, one for each of the enabled YouTube submission methods(upload, record, link). The operation of each of these forms is different.  

The Upload Video Form
The upload form allows the student to choose a video file from their computer/device and upload to YouTube. So it is simply a matter of clicking the "choose" button, followed by the "upload" button. When the file arrives at YouTube, it is titled with the name of the assignment and the user that submitted it. It is also tagged as "unlisted." This means that it will not display in YouTube searches, but that anyone who knows the unlisted URL of the video, can see it. There will probably be a delay of a minute or sometimes several minutes before the submitted video is ready to be played back. So there is usually no cause for concern if the YouTube player reports that the video is unavailable. If however the video is a duplicate upload, or in some way violates the YouTube terms of service, it may not appear and a message to that effect will display somwhere.


The Record Video Form
This form allows the student to record a video directly from their webcam, and to upload that video to YouTube. 
Unlike the upload form, the submitted video is NOT stored in the YouTube master account. Each student will be presented with a Google login form before they can begin recording. The submitted video is stored in the student's YouTube account. 
To display the recording widget, the student will first need to click the "Click to Record a Video" button.  
After the widget displays the student will need to both login to Google and to grant permission to the recording widget to access the device/pc camera and microphone. Submissions from the record form are also tagged "unlisted" and titled with the assignment name and user's full name.

The Submit YouTube URL Form
This form allows the student to enter a YouTube URL directly as their submission. 
It is  designed to allow students who have uploaded/recorded videos successfully but have somehow cleared their submission , to Ågre-linkÅh that video with their assignment. This situation might also occur if a user uploads a video, but forgets to press the assignment's "save changes" button.
 

Grading Assignments
===================
The grading and managing of assignments occurs outside the scope of the YouTube submission plugin. The YouTube submission plugin will simply display the YouTube player (or link) in the appropriate location. The Moodle assignment has a standard interface for grading that applies to all submissions.

This plugin was funded, and contributed back to the Moodle community, by the Apostolic Network of Global Awakening ( http://www.globalawakening.com ). It was written by and is maintained by Justin Hunt ( http://www.poodll.com ) 


Justin Hunt
http://www.poodll.com
poodllsupport@gmail.com
