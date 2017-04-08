**WARNING: THIS CONTAINS MY CLIENT ID FOR IMGUR. DO NOT MAKE PUBLIC**

# INFORMATION

imgur plugin (2.2) for MyBB 1.8
Created by: CrazyCat

This plugin adds a drag & drop zone in the new post and full edit interface
The pictures are uploaded and the corresponding MyCode is inserted in the message


The resulting url is inserted in the post.

# REQUIREMENT
Just declare a new app in imgur, set the Client ID in
the settings (MyBB ACP) and run :)

# UPGRADE
Deactivate the plugin, upload the new version and reactivate

# CHANGELOG
2.2 :
* Corrected a JS bug making some pictures uploaded several times
* Reintroduction of the popup (modal) upload. When clicking on the "imgur", the modal appears. Drag&drop always working

2.1 :
* Added a new setting allowing to create a link to the original picture when the displayed one is resized.
@TODO: check if the picture is really resized to add the link only if needed

2.0 :
* The button is now a zone where you can drag & drop the pictures to upload.
* You can upload several pictures in one time.

1.1 :
* JS modification (from waldo) to make work with other editors
1.0 :
* Modal version
* Using JQuery functions for the ajax
0.4.5 :
* Changed EOL from windows to Unix
0.4.4 :
* Corrected the deactivate function, now templates are well suppressed
0.4.3 :
* Corrected typo error
0.4.2 :
* Corrected the upgrade procedure: the language wasn't loaded 
0.4.1 :
* Corrected typo error
0.4 :
* Added an option to choose the size of the displayed picture:
	- original size
	- small thumb (160x160 max)
	- medium thumb (320x320 max)
	- large thumb (640x640x max)
0.3 :
* Corrected tablename in uninstall procedure
* Added the uploader in private messaging
0.2 : 
* Small modifications concerning the (un)installation and (de)activation
