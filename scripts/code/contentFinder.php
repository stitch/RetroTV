<?php

// finds episodes to our liking.
// can handle various formats.
// for database layout: see contentindexer...
// more like contentfactory, amirite?
class contentFinder {    
    private $db = ""; //resource
    
    public function __construct(){
        global $path;
        $this->db = sqlite_open($path['database'], 0666, $sqliteerror);
        if (!$this->db)
            die("could not open database, check paths (".$path['database']."). ".$sqliteerror);
            
        // show some statistics about the opened database.
        $sql = "select count(*) as TotalShows, SUM(metadata_duration_in_seconds) as Duration from content";
        $totalShows = sqlite_array_query($this->db, $sql);
        print "total content in databse: ";
        var_dump($totalShows);
    }
    
    public function __destruct(){
        // gooi db weg
        sqlite_close($this->db);
        unset($this->db);
    }
    
    
    // tijdsrestricties zijn mogelijk nog nuttig in de query... je wil soms een maximum tijd aangeven.
    public function findPlayListItem($type = CONTENT_SHOW, $timeslot = "other", $show = "", $bumperType = "", $rotationType = "", $maxTimeToFill = 0){
        
        // 1: search for content
        // 2: select the content that is least played, and first in queue
        // 3: create a playlistitem
        // 4: update the database that this file is queued
        
        // its a show we are after, so lets search one we haven't played yet, and matches the thing we want to see.
        if (!empty($show)){
            // only play the least broadcasted shows. The counter should increment once per season. So when you played everything from one season, it starts over.
            // sqlite doesnt do min and max
            
            /// episode 1 played 5 times
            /// episode 2 played 5 times
            /// episode 3 played 4 times
            /// episode 4 played 4 times
            // will get episode 3. In case all have been played the same number, it gets episode 1. 
            
            // SQLITE doesnt do MIN or MAX in decent queries, so we have to walk through the resultset to determine the minimum play count value
            $foundContent = array();
            $sql = "select * from content 
                    where showName = '".$show->get_name()."' 
                        and timeslot = '".$show->get_timeslot()."' 
                        and category= '".CONTENT_SHOW."' 
                    order by filename ASC, statistics_playcount ASC;";
            $foundContent = sqlite_array_query($this->db, $sql);
            
            // in case the show is not in the crrect timeslot, we have to check the "other" directory...
            /**
                 
                            showName = '".$show->get_name()."' 
                            and timeslot = 'other' 
                            and 
            */
            if (empty($foundShows)){
                $sql = "select * from content 
                        where showName = '".$show->get_name()."' 
                            and timeslot = 'other' 
                            and category = '".CONTENT_SHOW."' 
                        order by filename ASC, statistics_playcount ASC;";
                $foundContent = sqlite_array_query($this->db, $sql);
                //print $sql;
                //print_r($foundContent);
            }
        }
        
        // other content might have a time constraint...
        if ($maxTimeToFill > 0) {
            $timeConstraint = " and metadata_duration_in_seconds <= ".$maxTimeToFill." ";
        } else {
            // no time limitations...
            $timeConstraint = "";
        }
        
        // if its not a show then we might choose from two nearly the same random pools: funny movies and commercials. Both implementations 
        // look a lot like previous code... so an abstraction will be possible. (this has no showname)
        if ($type == CONTENT_COMMERCIAL or $type == CONTENT_FUNNY){
            
            // verwacht hier een paar 100 commercials, jammer dat MIN niet werkt...
            // ordering filenames hoeft niet, het mag random (maar dat ondersteunt sqllite ook niet.)
            $foundContent = array();
            $sql = "select * from content 
                    where timeslot = '".$timeslot."' 
                        and category= '".$type."' 
                        ".$timeConstraint."
                    order by RANDOM(), statistics_playcount ASC;";
            $foundContent = sqlite_array_query($this->db, $sql);
        
            if (empty($foundContent)){
                $sql = "select * from content 
                        where timeslot = 'other' 
                            and category= '".$type."' 
                            ".$timeConstraint."
                        order by RANDOM(), statistics_playcount ASC;";
                $foundContent = sqlite_array_query($this->db, $sql);
            }
        }
        
        // Bumper types:
        // Commercial_Start
        // Commercial_End
        // StationPromo
        if ($type == CONTENT_BUMPER) {
            $foundContent = array();
            $sql = "select * from content 
                    where timeslot = '".$timeslot."' 
                        and category= '".$type."' ";
                    if ($bumperType == BUMPER_COMMERCIAL_END || $bumperType == BUMPER_COMMERCIAL_START)
                       $sql .= "and showName like '%".$bumperType."%'";
                    else 
                        $sql .= "and showName not like '%".$bumperType."%'";
                     $sql .=   "".$timeConstraint."
                    order by RANDOM(), statistics_playcount ASC;";
            $foundContent = sqlite_array_query($this->db, $sql);
            
            if (empty($foundContent)){
                $sql = "select * from content 
                        where timeslot = 'other' 
                            and category= '".$type."' ";
                        if ($bumperType == BUMPER_COMMERCIAL_END || $bumperType == BUMPER_COMMERCIAL_START)
                            $sql .= "and showName like '%".$bumperType."%'";
                        else 
                            $sql .= "and showName not like '%".$bumperType."%'";
                        $sql .=  "".$timeConstraint."
                        order by RANDOM(), statistics_playcount ASC;";
                $foundContent = sqlite_array_query($this->db, $sql);
            }
        }
        
        // announcements worden on the fly gemaakt, met een custom tijdspanne afhankelijk van de lengte van de bumpers enzo. Dat is de dynamische tijdvuller.
        
        
        // noooooo!!! its impossible... just download some stuff from youtube and get your stuff in order
        // no show to our liking? then just fill the time being with commercials and funny movies... 
        // this might call for a better database updating algorithm. (or we can write a file with playcount per show, but thats anoying)
        $showName = "";
        if (!empty($show)){
            $showName = $show->get_name();
        }
            
        if (empty($foundContent)){
            print "Warning: Could not find a contentitem with these parameters: ".
                                                "ContentType: ".$type." - Timeslot: ".$timeslot.
                                                " - Show: ".$showName." -  Bumpertyupe:".$bumperType.
                                                " - Rotation:".$rotationType." - Length: ".$maxTimeToFill." \n";
            
            //debug_print_backtrace();
            
            
            // an empty playlistitem can be used to break out the while loop looking for content.
            $emptyPlayListItem = new playListItem();
            $emptyPlayListItem->fileLocation = "";
            $emptyPlayListItem->debugInfo = "Could not find a contentitem with these parameters: ".
                                                "ContentType: ".$type." - Timeslot: ".$timeslot.
                                                " - Show: ".$showName." -  Bumpertyupe:".$bumperType.
                                                " - Rotation:".$rotationType." - Length: ".$maxTimeToFill."";
            return $emptyPlayListItem;
        }
        
        // this is like an infinite loop now...
        //print "we found content!";
        // var_dump($foundContent); // niet alles tonen, tis nogal veel...
        
        //print_r($foundContent);
        
        // now we have to traverse the shows to see what is the lowest playcount
        $lowestPlayCount = 90000;
        foreach ($foundContent as $content){
            if ($content['statistics_playcount'] < $lowestPlayCount)
                $lowestPlayCount = $content['statistics_playcount'];
        }
        
        // now we get the first show with this awesome low playcount
        foreach($foundContent as $content){
            if ($content['statistics_playcount'] == $lowestPlayCount){
                $playListItem = new playListItem();
                $playListItem->fileLocation = $content['filename'];
                $playListItem->duration = $content['metadata_duration_in_seconds'];
                $playListItem->debugInfo = "Found: ".$content['filename']."  ContentType: ".$type." - Timeslot: ".$timeslot.
                                                " - Show: ".$showName." - Bumpertyupe:".$bumperType.
                                                " - Rotation:".$rotationType." - Length: ".$maxTimeToFill."";
            }
        }
        
        // TODO: filelocation is nog leeg hier...
        
        // debug...
        //print "Found content item. Duration: ".$playListItem->duration." filelocation:".$playListItem->fileLocation." \n";
        if ($playListItem->duration == 0){
            /*print "warning: you are trying to retrieve content that doesn't fill the timeslot \n".
                                                "ContentType: ".$type." - Timeslot: ".$timeslot.
                                                " - Show: ".$showName." -  Bumpertyupe:".$bumperType.
                                                " - Rotation:".$rotationType." - Length: ".$maxTimeToFill." \n";*/
        }
        //var_dump($playListItem);
        
        // update the database
        $sql = "UPDATE content SET statistics_playcount = ".($lowestPlayCount+1)." WHERE id = ".$content['id'].";";
        sqlite_query($this->db, $sql);
     
        // done!
        return $playListItem;
    }
    
}

?>