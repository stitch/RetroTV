<?php
// clock sync?
class programmingCreator {
    private $shows = array(); // list of shows, the complete schedule to make a programming.
    private $playlistPerShow = array();
    private $announcementCreator = ""; // object
    private $contentFinder = ""; // object
    private $program = array(); // lijst met playlistitems die daadwerkelijk de inhoud vormen.
    
    // logica:
    // 1: ik krijg een tijd
    // 2: deze moet ik vullen tot de volgende show (des noods 23 uur)
    // 3: aan de hand van het timeslot de juiste bumpers, commercials en funny movies en thema anouncements

    // de files komen uit de shows directory, bestaat niet: dan blackscreen.
    public function __construct(array $shows){
        $this->shows = $shows;
        $this->announcementCreator = new announcementCreator($shows);
        $this->contentFinder = new contentFinder();
        $this->createProgram();
    }
    
    // nears the playlist definition. So a filename is enough
    private function createProgram(){
        for ($showCounter = 0; isset($this->shows[$showCounter]); $showCounter++){
            $playListItems = $this->fillShowTime($this->shows[$showCounter]);
            
            // for individual playlists per show (recommended usage: just start playing when a show starts...)
            foreach($playListItems as $playListItem){
                $this->playlistPerShow[$showCounter][] = $playListItem;
            }
            
            // for one big playlist
            foreach($playListItems as $playListItem){
                $this->program[] = $playListItem;
            }
        }
    }
    
    
    public function renderShowsToXSPF($dirToSaveFile){
        for ($showCounter = 0; isset($this->shows[$showCounter]); $showCounter++){
            $program = $this->playlistPerShow[$showCounter];
            $fileName = $dirToSaveFile.date("Ymd")."_".$this->shows[$showCounter]->get_begin()."_".$this->shows[$showCounter]->get_name().".xspf";
            $this->renderXSPF($program, $fileName);
        }
    }
    
    // this is the complete program including the commercials etc all...
    // maybe these playlists should be rendered per "show"...
    public function renderProgramToXSPF($fileName) {
        $this->renderXSPF($this->program, $fileName);
    }
    
    private function renderXSPF($ourTracks, $saveToFileName){
        $xspfPlaylistRenderer = new xspfPlaylistRenderer();
        
        // info for the stream, has to be loaded from a configuraiton file depending on the station.
        $xspfPlaylistMetadata = new xspfPlaylistMetadata();
        $xspfPlaylistMetadata->title = "Retro TV";
        $xspfPlaylistMetadata->creator = "Elger Jonker";
        $xspfPlaylistMetadata->info = "http://hack42.nl";
        
        $xspfPlaylistRenderer->setPlayListMetadata($xspfPlaylistMetadata);
        
        foreach($ourTracks as $ourTrack){
            // format it to XSPF track... which is EXTREMELY HARD!
            $xspfTrack = new xspfTrack();
            //$xspfTrack->duration = $ourTrack->duration * 1000; // milliseconds
            // Bad:   /Users/stitch/Documents/tv/content/shows/other/Penn & Tellers Bullshit!/Penn.and.Teller.Bullshit.S05E01.PDTV.XviD-LOL.avi (geen conversie)
            // Bad:   %2FUsers%2Fstitch%2FDocuments%2Ftv%2Fcontent%2Fshows%2Fother%2FPenn+%26+Tellers+Bullshit%21%2FPenn.and.Teller.Bullshit.S05E10.PDTV.XviD-NoTV.avi (urlencode)
            // Bad: (htmlentities) Werkt, deels... geen ( ) !! :)
            // Good:  /Users/stitch/Documents/tv/content/shows/other/Penn%20%26%20Tellers%20Bullshit%21/Penn.and.Teller.Bullshit.S05E01.PDTV.XviD-LOL.avi (save as in vlc)
            $xspfTrack->location = "file://".$this->safeForXML($ourTrack->fileLocation); // has to be a URI AND valid XML, this is where our problems begin?
            //$xspfTrack->annotation = htmlentities($ourTrack->debugInfo);
            $xspfPlaylistRenderer->addTrack($xspfTrack);
        }
        $playlist = $xspfPlaylistRenderer->render();
        file_put_contents($saveToFileName, $playlist);
    }
    
    
    //http://php.net/manual/en/function.htmlentities.php
    /// de + is ook niet toegestaan...
    /**
        2x deze file == niet meer verder playlist lezen... rar
        eaccent niet, en de # niet..
        <track>
            <location>file:///Users/stitch/Documents/tv/content/funny movies/other/Contorsionist in South Bank London.m4v</location>
        </track>
    */
    private function safeForXML($strin){
        $strout = null;

        for ($i = 0; $i < strlen($strin); $i++) {
                $ord = ord($strin[$i]);

                if (($ord > 0 && $ord < 32) || ($ord >= 127)) {
                        $strout .= "%".$ord;
                }
                else {
                        switch ($strin[$i]) {
                                case '+':
                                        $strout .= '%2B';
                                        break;
                                case ' ':
                                        $strout .= '%20';
                                        break;
                                case '#':
                                        $strout .= '%23';
                                        break;                                
                                case '<':
                                        $strout .= '&lt;';
                                        break;
                                case '>':
                                        $strout .= '&gt;';
                                        break;
                                case '&':
                                        $strout .= '&amp;';
                                        break;
                                case '"':
                                        $strout .= '%22';
                                        break;
                                default:
                                        $strout .= $strin[$i];
                        }
                }
        }

        return $strout;
    }
    
    
    public function debug(){
        print_r($this->program);
    }
    
    // programming is structured like this, for an example hour:
    /*
        the showtime know how much time it has to fill. This function will make sure that time is filled accurately.
        
        .45:00 bumper (begin 1 minuut blok)
        .45:10 announce
        .45:50 bumper (eind 1 minuut blok)
        .46:00 reclame (flexibel, minimaal 2 minuten, max 5)
        .48:00 bumper, mogelijk annonce filling om tijd vol te maken naar volgende 10 min blok
        .48:10 leuke filmpjes (flexibel, minimaal 6 minuten)
        .FILL bumper, anounce
        .56:00 reclame (3 minuten), tot max 59:00
        .59:00 1 minuut annonce blok
        
        it is an array of shows and content information.
        
        This fills the timeslot for a scenario where shows are used..
        other scenarios would be: funny movies only, clips only, or commercials only
    */
    
    private function fillShowTime ($show) {
        // this gets filled with actual content... Actuall filling is dependant on time.
        $timeslotContent = array();
        
        print "Trying to find content for the show ".$show->get_name()." planned for timeslot ".$show->get_timeslot()." beginning at ".$show->get_begin()."\n";
        
        // first thing is to get the planned show. We have to broadcast this, this is what people expect.
        $playListItem = $this->contentFinder->findPlayListItem(CONTENT_SHOW, $show->get_timeslot(), $show);
        $timeslotContent[] = $playListItem;
        
        print "The loaded show is located at ".$playListItem->fileLocation." and takes up a time of ".$playListItem->duration." seconds\n";
        
        // possibly the next show has to be canceled if this show has a longer-than-planned runtime. Exceptional cases.
        // just make sure this doesn't happen. We don't support special-day planning (planning per date)
        if ($playListItem->duration > $show->get_scheduled_duration()){
            // ... to implement: cancel next show. Or just get the next one untill it fits... no action now.
        }
        
        $secondsLeft = $show->get_scheduled_duration() - $playListItem->duration;
        print "We must fill another ".$secondsLeft." seconds\n";
        
        // Depeding on secondsLeft, another format is chosen for filling airtime.
        
        // Filling Scenario One: 
        // Very short, exceptional Scenario, dependant on the time left, get bumper...
        // you may run a few seconds in the hour... content and syncing will make up for that
        // this is very cheap filling...
        if ($secondsLeft < 10) {
            while ($secondsLeft > 0) {
                $timeslotContent[] = $timeFillingBumper = $this->contentFinder->findPlayListItem(CONTENT_BUMPER, $show->get_timeslot(), "", BUMPER_PROMO, "", $secondsLeft);
                if (empty($timeFillingBumper->fileLocation)) continue;
                $secondsLeft -=  $timeFillingBumper->duration;
            }
        }
    
        // Filling Scenario Two: 
        // more likely: more seconds left, but about a minute...  till 2 minutes...
        if ($secondsLeft > 9 and $secondsLeft < 120) {
            // start filling... with schedule // maybe Bumper Schedule Bumper...
            $preBumper = $this->contentFinder->findPlayListItem(CONTENT_BUMPER, $show->get_timeslot(), "", BUMPER_PROMO, "", 0);
            $secondsLeft -= $preBumper->duration; 
            $postBumper = $this->contentFinder->findPlayListItem(CONTENT_BUMPER, $show->get_timeslot(), "", BUMPER_PROMO);
            $secondsLeft -= $postBumper->duration; 
                  
            $timeslotContent[] = $preBumper;
            $timeslotContent[] = $this->createAnnouncement($secondsLeft, $show);
            $timeslotContent[] = $postBumper;
        } // all time should now be taken...
        
        // Filling Scenario Three: 
        // Announcements, funny movies, annouceemtns. (countdown or picture?) // from 2 till 10 minutes... 
        // could just fill about an half hour, then a new show has to start!
        // maybe add some bumpers in longer commercialblocks or funnymovie blocks...
        // for debugging just no upper limit...  and $secondsLeft < 1900
        if ($secondsLeft > 119) {
            print "Filling a large gap in the programming, time left: ".$secondsLeft." seconds\n";
            // station promo
            $preBumper1 = $this->contentFinder->findPlayListItem(CONTENT_BUMPER, $show->get_timeslot(), "", BUMPER_PROMO, "", 0);
            $secondsLeft -= $preBumper1->duration; 
            $postBumper1 = $this->contentFinder->findPlayListItem(CONTENT_BUMPER, $show->get_timeslot(), "", BUMPER_PROMO);
            $secondsLeft -= $postBumper1->duration; 
            
            $preBumper2 = $this->contentFinder->findPlayListItem(CONTENT_BUMPER, $show->get_timeslot(), "", BUMPER_PROMO, "", 0);
            $secondsLeft -= $preBumper2->duration; 
            $postBumper2 = $this->contentFinder->findPlayListItem(CONTENT_BUMPER, $show->get_timeslot(), "", BUMPER_PROMO);
            $secondsLeft -= $postBumper2->duration; 
            
            // the first thing you want to know when a show ends: whats next!
            // maybe we could add some of those scrolling banners on VLC...
            $announcement1 = $this->createAnnouncement(40, $show);
            $secondsLeft -= $announcement1->duration; 
        
            // a commercialblock fills 22,5% to 30% of available time. with a maximum of 5 minutes.
            // an exception is that times < 2 minutes are filled with pure commercials... 
            $commercialBlockOne = array();
            $commercialBlockTwo = array();
            if ($secondsLeft > 120 && $secondsLeft < 240){
                $commercialBlockOne = $this->createCommercialsBlock($secondsLeft - 120, $show->get_timeslot());
                $secondsLeft -= $commercialBlockOne["filledTime"];
                $commercialBlockTwo = array();
            } else {
                // make two blocks... fill the remaining time (-1 minute of annoucements) with funny movies
                $commercialBlockOne = $this->createCommercialsBlock($secondsLeft, $show->get_timeslot());
                $commercialBlockTwo = $this->createCommercialsBlock($secondsLeft, $show->get_timeslot());
                $secondsLeft -= $commercialBlockOne["filledTime"];
                $secondsLeft -= $commercialBlockTwo["filledTime"];
            }
            
            print "Commercial blocks have been generated, time left: ".$secondsLeft." seconds\n";
            
            // fill any remainingtime, excluding announcementtime, with funny movies.
            // not loading longer funny movies, 
            // make sure the $secondsLeft in this while loop is long enough to overlap the while loop.
            $funnyMovies = array();
            $funnyMovieCounter = 0;
            while ($secondsLeft >= 120) {
                $funnyMovie = $this->contentFinder->findPlayListItem(CONTENT_FUNNY, $show->get_timeslot(), "", "", "", $secondsLeft);
                
                // its possible that we just not completely fill up the timeslot. No problem, announcements will do that for us
                // Some files get an empty duration: at indexing the metadata of time is not available to ffmpeg (or doesn't get parsed right)
                // basically there shouldn't be 0 second videos. Files wihtout a filelocation mean that there is no movie for our criteria, whichi
                // is nearly impossible; unless everything is larger than 2 minutes.
                if (empty($funnyMovie->duration) or empty($funnyMovie->fileLocation)) {
                    print "impossible content detected...";
                    continue 1;
                }
                
                $funnyMovies[] = $funnyMovie; 
                $secondsLeft -= $funnyMovie->duration; 
                // maybe here we can do some picture with "x minutest left till X?", or a stations logo in every 3 videos... todo..
                // every N funny movies we add a station bumper.
                if ($funnyMovieCounter % 4 == 0 && $funnyMovieCounter != 0 && $secondsLeft > 200){
                    $funnyMovie = $this->contentFinder->findPlayListItem(CONTENT_BUMPER, $show->get_timeslot(), "", BUMPER_PROMO, "", $secondsLeft);
                    $funnyMovies[] = $funnyMovie;
                    $secondsLeft -= $funnyMovie->duration; 
                }
                // add some commercials 
                if ($funnyMovieCounter % 10 == 0 && $secondsLeft > 200  && $funnyMovieCounter != 0){
                    $arbitraryCommercials = $this->createCommercialsBlock($secondsLeft, $show->get_timeslot());
                    $secondsLeft -= $arbitraryCommercials["filledTime"];
                    foreach ($arbitraryCommercials["commercials"] as $commercial){
                        $funnyMovies[] = $commercial;
                    }
                }
                $funnyMovieCounter++;
                //print "Funny movie added... Time left:".$secondsLeft."\n";
            }
            
            print "Funny movies have been added, one announcement left: ".$secondsLeft." seconds\n";
            
            // lastly, we have about one or two minutes left...
            $announcement2 = $this->createAnnouncement($secondsLeft, $show);
            $secondsLeft -= $announcement2->duration; 
            
            // create programming... lets glue everything together.
            $timeslotContent[] = $preBumper1;
            $timeslotContent[] = $announcement1;
            $timeslotContent[] = $postBumper1;
            
            foreach ($commercialBlockOne["commercials"] as $commercial){
                $timeslotContent[]  = $commercial;
            }
            
            // can be empty
            foreach ($funnyMovies as $funnyMovie) {
                $timeslotContent[] = $funnyMovie;
            }
            
            // can be empty
            foreach ($commercialBlockTwo["commercials"] as $commercial){
                $timeslotContent[]  = $commercial;
            }
            
            $timeslotContent[] = $preBumper2;
            $timeslotContent[] = $announcement2;
            $timeslotContent[] = $postBumper2;
        } // and 5 minutes are filled...
        
        //print_r($timeslotContent);
        
        // depending on our scenario and time, we have found some nice content.
        return $timeslotContent;
    }
    
    // commercials are always part of a block programming: meaning leading to funny movies or announcements.
    // therefore they may not dominate, but will take up some of the time. Absolute max is 5 minutes. Min is 30 seconds.
    // Since commercial blocks have to be synchronous in length, two commercial blocks have to be created.
    // Most of the time this function is called twice, and will try to make a nice match on the time at hand.
    private function createCommercialsBlock($secondsLeft, $timeSlot){
            // load up some commercials... (mostly 15, 30 or 60 seconds)
            // more time means longer commercials... We fill up about half of available time...
            
            $filledTime = 0;
            $CommercialBumperStart = $this->contentFinder->findPlayListItem(CONTENT_BUMPER, $timeSlot, "", BUMPER_COMMERCIAL_START, "", 0);
            $CommercialBumperEnd = $this->contentFinder->findPlayListItem(CONTENT_BUMPER, $timeSlot, "", BUMPER_COMMERCIAL_END, "", 0);
            
            if (!empty($CommercialBumperStart->fileLocation)){
                $filledTime += $CommercialBumperStart->duration;
            }
            
            if (!empty($CommercialBumperEnd->fileLocation)){
                $filledTime += $CommercialBumperEnd->duration;
            }
            
            // now find our beloved commercials...
            $commercials = array();
            switch ($secondsLeft) {
                // exception: fill the entire time with commercials, 2 minutes.
                // This is used when there is a single commercial block, or the time is too small for other content.
                case $secondsLeft < 120;   
                        $maxCommercialTime = 120;  
                        $commercialTimeVariation = 40; break;
                // fill about 22.5% to 30% with commercials              
                case $secondsLeft < 200;   
                        $maxCommercialTime = 45;  
                        $commercialTimeVariation = 40; break; // 90 seconds of filling...
                case $secondsLeft < 400;   
                        $maxCommercialTime = 90;  
                        $commercialTimeVariation = 54; break; // about 5, 3 minutes of total 6, is about 50%
                case $secondsLeft < 600;   
                        $maxCommercialTime = 135; 
                        $commercialTimeVariation = 76; break; //  2x 2.41 minutes  4,5 minutes...
                case $secondsLeft < 800;   
                        $maxCommercialTime = 180;  
                        $commercialTimeVariation = 95;  break; // 2x 3 minutes
                case $secondsLeft < 1000;  
                        $maxCommercialTime = 225;  
                        $commercialTimeVariation = 110; break; // 2x 3.45 minutes..
                case $secondsLeft < 1201;  
                        $maxCommercialTime = 270;  
                        $commercialTimeVariation = 140; break; // 2x large 4 minutes
                case $secondsLeft > 1200;  
                        $maxCommercialTime = 300;  
                        $commercialTimeVariation = 140; break; // Huge blocks, primetime? 20 minutes of nothing...
            }
            
            // larger commercials will float by automatically as they are picked less often... i assume smaller blocks are more frequent.
            if (!empty($CommercialBumperStart->fileLocation))
                $commercials[] = $CommercialBumperStart;       
                
            while ($filledTime < $maxCommercialTime){
                $commercial = $this->contentFinder->findPlayListItem(CONTENT_COMMERCIAL, $timeSlot, "", "", "", $commercialTimeVariation);
                if (empty($commercial->fileLocation)) continue;
                $commercials[] = $commercial;
                $filledTime += $commercial->duration;
            }
            
            if (!empty($CommercialBumperEnd->fileLocation))
                $commercials[] = $CommercialBumperEnd;
            return array("commercials" => $commercials, "filledTime" => $filledTime);
    }
    
    // creates an announcement and converts it to a playlistitem
    private function createAnnouncement($seconds, $show){
        $file = $this->announcementCreator->createAnnouncement($seconds, $show);
        $playListItem = new playListItem();
        $playListItem->fileLocation = $file;
        $playListItem->duration = $seconds;
        return $playListItem;
    }
        
}
?>