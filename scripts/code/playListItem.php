<?php

// value object for creating playlists
// was named Episode before, should be named something like contentItem or physicalContent. / showtimeitem.
class playListItem {
    public $duration = 0; //time in seconds
    public $fileLocation = ""; // location
    public $otherMetadata; // other metadata
    public $debugInfo = ""; // a string containing information how the file was instantiated... which is somewhat harder to trace.
    public $title = ""; // metadata title, not used yet...
}

?>