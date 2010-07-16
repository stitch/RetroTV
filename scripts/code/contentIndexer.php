<?php
/**
no db layer here
*/
class contentIndexer {
    private $db = ""; //resource
    
    public function __construct(){
        // maak DB aan.
        global $path;
        
        // initialize a database when it doesnt exist
        if (!file_exists($path['database'])){
            $this->db = sqlite_open($path['database'], 0666, $sqliteerror);
            $sql = "create table content (
                        id INTEGER PRIMARY KEY ASC,
                        filename varchar(400), 
                        showName varchar(50) NULL, 
                        category varchar(30), -- funny, show, commercial, announcement, bumper
                        timeslot varchar(30), -- cartoon, movie, serie... default is other
                        statistics_last_played datetime,
                        statistics_playcount integer,
                        metadata_title vachar(255),
                        metadata_duration_in_seconds integer
                    );";
            sqlite_query($this->db, $sql);
        } else {
            $this->db = sqlite_open($path['database'], 0666, $sqliteerror);
        }
        if (!$this->db)
            die("could not open database, check paths (".$path['database']."). ".$sqliteerror);
        
        // leegmaken die zooi...
        //sqlite_query("delete from content");
    }
    
    public function __destruct(){
        // gooi db weg
        sqlite_close($this->db);
        unset($this->db);
    }
    
    // indexes all files in the content directory.
    public function indexContent(){
        global $path;
        
        // handle Shows...
        $timeslots = $this->getFileList($path['content'].CONTENT_SHOW."/");
        foreach($timeslots as $timeslot) {
            $timeslotPath = $path['content'].CONTENT_SHOW."/".$timeslot."/";
            $shows = $this->getFileList($timeslotPath);
            foreach ($shows as $show){
                $showPath = $path['content'].CONTENT_SHOW."/".$timeslot."/".$show."/";
                $episodes = $this->getFileList($showPath);
                foreach ($episodes as $episode){
                    $episodePath = $path['content'].CONTENT_SHOW."/".$timeslot."/".$show."/".$episode;
                    $playListItem = $this->inspectFile($episodePath);
                    // todo: remove .ds_store and archive.db files.
                    $sql = "INSERT INTO content (id, filename, showName, category, timeslot, statistics_last_played, statistics_playcount, metadata_title, metadata_duration_in_seconds) 
                            VALUES  (NULL, '".sqlite_escape_string($playListItem->fileLocation)."', '".sqlite_escape_string($show)."', '".CONTENT_SHOW."', '".$timeslot."', datetime('now','localtime'), 0, '".sqlite_escape_string($playListItem->title)."', ".$playListItem->duration.")";
                    
                    sqlite_query($this->db, $sql);
                }
            }
        }
        
        // other files are all handled according to the same structure
        $this->indexOther(CONTENT_FUNNY);
        $this->indexOther(CONTENT_COMMERCIAL);  
        $this->indexOther(CONTENT_BUMPER);
        // $this->indexOther(CONTENT_ANNOUNCEMENT); Worden on the fly gemaakt, indexeren heeft geen zin, omdat het dan niet past in de playlist.
    }
    
    // just as shows, less deep level
    // maybe put the filename as the showname... yes i'll do that. 2 niveaus.
    private function indexOther ($contentFolder){
        global $path;
        $timeslots = $this->getFileList($path['content'].$contentFolder."/");
        foreach($timeslots as $timeslot) {
            $timeslotPath = $path['content'].$contentFolder."/".$timeslot."/";
            $episodes = $this->getFileList($timeslotPath);
            foreach ($episodes as $episode){
                $episodePath = $path['content'].$contentFolder."/".$timeslot."/".$episode;
                $playListItem = $this->inspectFile($episodePath);
                $sql = "INSERT INTO content (id, filename, showName, category, timeslot, statistics_last_played, statistics_playcount, metadata_title, metadata_duration_in_seconds) 
                        VALUES  (NULL, '".sqlite_escape_string($playListItem->fileLocation)."', '".sqlite_escape_string(basename($episodePath))."', '".sqlite_escape_string($contentFolder)."', '".$timeslot."', datetime('now','localtime'), 0, '".sqlite_escape_string($playListItem->title)."', ".$playListItem->duration.")";
                sqlite_query($this->db, $sql, SQLITE_ASSOC, $errorMessage);
                if (!empty($errorMessage)){
                    print "SQL error: \n".$errorMessage." in query: \n".$sql."\n\n";
                }
            }
        }
    }
    
    private function getFileList($location){
        $list = array();
        print "Scanning dir: ".$location."\n";
        $dircontent = @opendir($location) or die($location." could not be found. DEATH!");
        while ($path = readdir($dircontent)) {
            if ($path != "." and $path != ".." and $path != ".DS_Store" and $path != "thumbs.db"){
                $list[] = $path;
            }
        }
        return $list;
    }
    
    // get the duration of the file.    
    private function inspectFile($fileLocation){
        //print "Inspecting ".$fileLocation."\n";
        global $getid3;
        /*
        $getid3->analyze($fileLocation);
        getid3_lib::CopyTagsToComments($ThisFileInfo);
        $tmpDuration = @$ThisFileInfo['playtime_string']; // mm:ss
        $tmpDuration = explode(":", $tmpDuration);
        $time = new simpleTime(0, $tmpDuration[0], $tmpDuration[1]);
        
        $episode = new episode();
        $episode->fileLocation = $fileLocation;
        $episode->duration = $time->getSeconds();
        unset($time);
        */
        
        // http://www.longtailvideo.com/support/forums/jw-player/setup-issues-and-embedding/9448/how-to-get-video-duration-with-ffmpeg-and-php
        // Todo: ffmpeg checkt geen files met " en $... waarschijnlijk omdat ze niet goed zijn  aangegeven?
        ob_start();
            passthru("ffmpeg -i \"{$fileLocation}\" 2>&1");
            $duration = ob_get_contents();
        ob_end_clean();
        
        $search='/Duration: (.*?)[.]/';
        $durationString=preg_match($search, $duration, $matches, PREG_OFFSET_CAPTURE, 3);
        if (!isset($matches[1][0])){
            print "warning: could not find a duration for file: ".$fileLocation." \n";
            $matches[0] = 0;
            $matches[1] = 0;
        }
        $tmpDuration = explode(":", $matches[1][0]);
        if (!isset($tmpDuration[1])){
            $tmpDuration[0] = 0;
            $tmpDuration[1] = 0;
            $tmpDuration[2] = 0;
            print "warning: probably not a valid video file: ".$fileLocation." \n";
        }
        $time = new simpleTime($tmpDuration[0], $tmpDuration[1], $tmpDuration[2]);
        
        // create VO
        $playListItem = new playListItem();
        $playListItem->fileLocation = $fileLocation;
        $playListItem->duration = $time->getSeconds();
        $playListitem->title = ""; // deze kan ik niet uitlezen zonder metadata (id3, traag) te gebruiken... ander metadata truukje via ffmpeg?
        unset($time);
        
        //print $fileLocation." lasts ".$matches[1][0]."\n";
        
        return $playListItem;
    }
    
    #sqlite_query($db, 'CREATE TABLE foo (bar varchar(10))');
    #sqlite_query($db, "INSERT INTO foo VALUES ('fnord')");
    #$result = sqlite_query($db, 'select bar from foo');
    #var_dump(sqlite_fetch_array($result)); 
    
}
?>