****************************************************************
* Thu Nov 17 11:16:44 CET 2011
* Goran Josic goran.josic@usi.ch
* eLab - http://www.elearninglab.org
****************************************************************

Thank you for using Moodle Notifications block. 

About: 
This block notifies changes and updates on Moodle courses via
three channels: e-mail, sms and rss.
For the license please check LICENSE.txt file.


Note:
the SMS functionality depends on your provider. To enable SMS
channel please extend lib/AbstractSMS.php class. Call the new
class SMS. Check lib/SMS.php.sample if you need a starting
point.

Installation:
To install please perform the following steps:
	
	1.	rename the block folder to "notifications" if you have
		chosen a different name during the repository cloning
		(in case you have downloaded the compressed archive
		expand the archive and rename the folder to "notifications")

	2.	move the folder to the blocks directory of your Moodle
		installation

	3.	Login in your Moodle platform as Administrator and
		click on Notifications inside Site Administration block.

At this point the tables should be created and the block should
be available in the Blocks list. This block is available only
inside courses. It is not possible add it to the frontpage.

Settings: 
This block has three levels of settings:
	- Global settings are managed by Administrators	 
	- Course settings are managed by Teachers and assistants
	- Personal settings are managed by Students

Global settings have priority on Course settings and the
Course settings have priority on Personal Settings.
The e-mail and sms channels can be enabled and disabled on 
every level. Only the rss channel is managed Globally or on
Course level.

Presets:
You can set the default user channel preferences by using presets.
The presets can be enabled both globally and on the course level.
The course presets have precedence over the global presets.
Finally, the user preferences have precedence over the course
and global presets.

Bugs:
If you find a bug please submit it here:
https://github.com/arael/moodle_notifications_20/issues

Please provide the bug description and don't forget Moodle and 
notifications block version.
