<?php
// http://xspf.org/quickstart/
class xspfTrack {
    public $location = "";   // mandatory
    public $creator = "";    // <!-- artist or band name --> 
    public $album = "";      // <!-- album title -->
    public $title = "";      // <!-- name of the song -->
    public $annotation = ""; // <!-- comment on the song -->
    public $duration = 0;    // <!-- song length, in milliseconds -->
    public $image = "";      // <!-- album art --> File or URL, for streaming its an url
    public $info = "";       // <!-- if this is a deep link, URL of the original web page -->
}

?>