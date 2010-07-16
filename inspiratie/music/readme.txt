/**
* PHP XSPF Generator v1.1
* http://sourceforge.net/projects/php-xspf-gen
*
* Contributors:
* Lewis Smith - www.lasmit.co.uk - Concept and original code
* Jonathan Leonard - www.jonathanleonard.com - Robustness, expansion and documentation
*
* This code is released under the GNU GPL:
*    http://www.gnu.org/copyleft/gpl.html
*
* PHP-XSPF-Generator uses getID3 from http://www.getid3.org/ and XSPF Player from http://musicplayer.sourceforge.net/
* both of which are also released under the GNU GPL but have been included in this download.
*/
     
Thanks for downloading the PHP XSPF generator.  Here's some instructions to get you started.  
If you'd like any help please visit the forums at http://sourceforge.net/projects/php-xspf-gen.

Installation
------------
   
   1. Upload the entire music folder to your webhost root directory
   2. Upload your mp3s to the music/music1/mp3 folder
   3. Visit www.yoursite.com/music/music1  - you should see a player with your mp3s listed
   4. Upload more mp3 to the /music/music2/mp3 folder*
   5. Visit www.yoursite.com/music/  - you will see links to music1 and music2 folders


Customization
_____________

   1. Change name of music1 folder to 'mychoice'
   2. Edit music/mychoice/index.html to show mychoice
   3. Edit music/index.html and change music1 to mychoice
   4. *Reserve music2 folder as a template for adding mp3 later


Expanding
_________

   1. Copy/Paste music/music2 to /music/newfolder
   2. Upload mp3 to /music/newfolder/mp3
   3. Edit top level /music/index.html to show newfolder


Exporting Playlists
___________________

   1. In a browser visit www.yoursite.com/music/music1/playlist_generator.php
   2. Save Web Page as html only with .xml extension


Script Features
_______________

The playlist generator script provides a dynamic XSPF playlist that can be used by an open source flash player that you may embed in your own pages.  Instead of maintaining static playlists, the script reads the contents of an mp3 folder and any available ID3 tags, and produces a playlist an XSPF player may use.  The playlist_generator script is written to be robust when id3 tags are missing or partially present.  If a title tag is available, then the artist and title tags are shown in the player.  If the title tag is missing, then neither tag is used and the filename is shown instead.  If all tags are missing for a file, the filename is also used.  Since tags are inconsistently applied in some cases this behaviour was included in the script to avoid 'undefined' entries or long rows of 'artist'.  If your folder contains a mixture of tags, this script will produce the best output for your audience.  If you get undefined entries, check the tags in another program to see if they contain special characters like & or $ that may interfere.

 