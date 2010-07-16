<?php

// todo: save the playlist for VLC:
// http://www.xspf.org/quickstart/
// http://www.videolan.org/doc/streaming-howto/en/ch12.html
// http://www.videolan.org/doc/streaming-howto/en/ch05.html
// http://wiki.videolan.org/Common_Problems
// ffmpeg kan plaatjes naar films omzetten, er is bewijs van.
// http://electron.mit.edu/~gsteele/ffmpeg/

// can create announcements for the next shows in an appropriate style and specified duration
// creates movie files with specific duration showing the next few upcomming shows, and possible highlights.
// highlights are derived from a show popularity or just a setting in the schedule.?
// maybe we also get the pointers to the files of the shows, so this class can make thumbnails.
class announcementCreator {
    private $shows = array();
    
    // movie basics
    private $framesPerSecond = 10; // keep it low in debug modus
    private $movieLength = 1;
    private $totalFrames = 0;
    private $currentFrame = 0;
    
    // aimation framestates for fadein and out
    private $currentAnimationFrameFadeIn = 1;
    private $currentAnimationFrameFadeOut = 1;
    
    // content basics
    private $showsToDisplayCounterCounter = 6;
    private $showsToDisplay = array();
    
    // filesystem basics
    private $renderDumpDir = ""; // where to dump a rendering of the movie
    private $tmpRenderDumpDir = ""; // where to dump individual frames
    private $designDir = ""; // location of stuff to use in our movie 
    
    
    public function __construct($shows){
        global $path;
        
        $this->shows = $shows;
        $this->designDir = $path['design'];
        $this->renderedDir = $path['announcements']."rendered/";
        $this->showsToDisplayCounter = 6;
        
        // a little tool to speed upcutting music, it doesn't do fadeouts, but well, its faster than by hand.
        //$this->cutAudioToPreferedLengths();
        define("DEFAULT_FONT_LARGE", 5);
    }
    
    private function determineAnnouncementContent($show){
        // DEBUG:
        // for debugging we just return a filename, which is lots faster than creating somethign real.
        // return "NoAnnouncements.mp4";
        
        $simpletime = new simpletime();
        $simpletime->loadDayPosition();
        
        if (empty($this->shows)){
            print "Warning: could not create announcements due to no shows in schedule...";
            return "";
        }
        
        // get the first 6 shows that will be broadcasted, from the time of the current show...
        // when the day is over, it will look up for "tomorrows" shows (meaning the first shows get loaded)
        // horizontal programming, and one day only.
        $selectedShows = array();
        $currentShowBegin = $show->get_begin();
        foreach($this->shows as $potentialShow){
            if (count($selectedShows) < $this->showsToDisplayCounter && $potentialShow->get_begin() > $currentShowBegin)
                $selectedShows[] = $potentialShow;
        }
        
        // add rest of shows, start from the beginning
        for ($potentialShow = 0; count($selectedShows) < $this->showsToDisplayCounter; $potentialShow++) {
            $selectedShows[] = $this->shows[$potentialShow];
        }

        $this->showsToDisplay =  $selectedShows;    
    }
    
    // 2 steps:
    // 1: create an image with the appropriate shows
    // 2: convert this image into a movie
    // the minimum time for announcements is about 50 seconds, so we can make it special :)
    // generation of announcements isn't very fast, but hey; its a proof of concept
    // should also include the stationname or something.. 
    // with some intros you can also make movies insinde the announcements. like promo's
    public function createAnnouncement($length = 1, $show){
        global $path;
        
        $this->determineAnnouncementContent($show);
        
        // debug!
        global $config; // discusting a global...
        if (!$config["createAnnouncements"]) {
           return "AnnouncementsDisabledDueToDebugging.avi"; 
        }
        
        $this->movieLength = $length;
        $this->totalFrames = $this->movieLength * $this->framesPerSecond;
        
        $simpletime = new simpletime();
        $simpletime->loadDayPosition();
        $this->tmpRenderDumpDir =  $path['announcements']."dump/".$simpletime->getTime()."/";
        mkdir($this->tmpRenderDumpDir);
        
        print "Creating announcements for ".$this->showsToDisplay[0]->get_name()." at ".$this->showsToDisplay[0]->get_begin()." hour.\n";
        print "Announcements will last ".$this->movieLength." seconds and contains ".($this->movieLength * $this->framesPerSecond)." frames. \n";
        print "Rendering frames can take a while... \n";
        
        // animate
        $this->createFrames();
        
        // save and return.
        return $this->renderFramesToMovie();

    }


    // now we add those 6 shows to an image (no moving cool stuff, just horrid things.
    // maybe we could do with building a template movie and burning this image on top of it.
    // create a lot of frames just for the fun of it, with some fadeins... so fmpeg can make a nice 1 second movie. 
    // 3 states: fadein, display, fadeout   
    private function createFrames(){
        
        for ($this->currentFrame = 0; $this->currentFrame < $this->totalFrames; $this->currentFrame++) {
            $image = $this->createImage();
                        
            // figure out where we are
            $renderstate = "display";
            if ($this->currentFrame < $this->getFramesForSeconds(5))
                $renderstate = "fadein";
                
            if (($this->totalFrames - $this->currentFrame) < $this->getFramesForSeconds(5))
                $renderstate = "fadeout";
            
            //print $renderstate;
            switch ($renderstate){
                case "display"; $this->animateDisplay($image); break;
                case "fadein"; $this->animateFadeIn($image, 5); break;
                case "fadeout"; $this->animateDisplay($image); break;
            }
            
            $this->saveImage($image);
        }
        return;
    }
    
    private function createImage(){
        $image = imagecreatefromjpeg($this->designDir."retro.jpg"); // fresh image resource
        //var_dump($image);  // -> resource of type gd.
        return $image;
    }
    
    private function saveImage(&$imageResource){
        // low quality jpegs
        imagejpeg($imageResource, $this->tmpRenderDumpDir."/".$this->currentFrame."_announcement.jpg", 10);
        imagedestroy($imageResource);
    }
    
    private function getFramesForSeconds($seconds){
        return $this->framesPerSecond * $seconds;
    }
    
    private function getSecondsLeft(){
        $framesLeft = $this->totalFrames - $this->currentFrame;
        return ceil($framesLeft / $this->framesPerSecond);
    }
    
    // je kan altijd nog een gradient licht laten roteren en moeilijke animaties maken.   
    private function animateDisplay(&$imageResource){
        $white = imagecolorallocatealpha($imageResource, 255, 255, 255, 0); // 0 indicates completely opaque while 127 indicates completely transparent.         
        // 1 --> <=   0 --> <
        for ($show = 1; $show <= count($this->showsToDisplay) && is_object($this->showsToDisplay[($show - 1)]); $show++){
            $TimexPosition = 100;
            $TextxPosition = 200;
            $rowYPosition = (50 * $show);
        
            imagestring($imageResource, DEFAULT_FONT_LARGE, $TimexPosition, $rowYPosition, $this->showsToDisplay[($show - 1)]->get_begin(), $white);
            imagestring($imageResource, DEFAULT_FONT_LARGE, $TextxPosition, $rowYPosition, $this->showsToDisplay[($show - 1)]->get_name(), $white);
            
            // tellertje...
            imagestring($imageResource, DEFAULT_FONT_LARGE, 400, 600, "More RetroTV in ".$this->getSecondsLeft()." seconds.", $white);
        }
        // je kan hier de totale tijd left berekenen en mss wat leuke effectjes toepassen terwijl je wacht... lapsharp ofzo.
        return $imageResource;
    }
    
    private function animateFadeIn(&$imageResource, $fadeTimeInSeconds){
        // get the number of shows
        $showCount = count($this->showsToDisplay);
        
        // 1 seconde uitloop
        $totalAnimationFrames = $this->getFramesForSeconds($fadeTimeInSeconds - 1);
        $FramesToAnimatePerShow = floor($totalAnimationFrames / $showCount);
        
        // $this->currentAnimationFrameFadeIn;
        // Volgende animatie begint na 75% van de vorige animatie.
        
        $variantPositionX = array();
        $variantPositionX["from"] = 100;
        $variantPositionX["movement"] = 100;
        
        $variantTransparency = array();
        $variantTransparency["from"] = 127;
        $variantTransparency["to"] = 0; // niet transparant
        
        // animaties zijn verschrikkelijk lastig, en ik kan mijn hoofd er niet omheen krijgen.
        $animationState = array();
        // per show een berekening uitvoeren
        for($show=0; $show < $showCount; $show++) {
            // Fadeins gaan per show. een show kan je gaan animeren wanneer 
            // het huidige frame een van de totale shows benaderd (ofzo...)
            // voorbeeld: totaal 500 frames, huidig frame = 1... FramesPerShow = 83
            // toon 1 show gedeeld door het totaal aantal tijd.
            if (($this->currentAnimationFrameFadeIn / ($FramesToAnimatePerShow * ($show+1))) > 0.99){
                
                    // nu nog animation per show... hij pakt nu het hele blok (aka: alle shows zijn gelijk)
                    // move to final position, (left)
                    $currentVariantPositionX = $variantPositionX["from"] + floor($variantPositionX["movement"] / ceil($this->currentAnimationFrameFadeIn / $FramesToAnimatePerShow));
                    // fade in
                    $currentVariantTransparency = $variantTransparency["to"] +  (floor($variantTransparency["from"] / ceil($this->currentAnimationFrameFadeIn / $FramesToAnimatePerShow)));
                    
                    // draw the show in this case...
                    $rowYPosition = (50 * ($show + 1));
                    $TimexPosition = $currentVariantPositionX; // hangt af van hoe ver we zijn.
                    $TextxPosition = $TimexPosition + 100; // 100 pixels verder staat de show
                    $color = imagecolorallocatealpha($imageResource, 255, 255, 255, $currentVariantTransparency);
                    
                    imagestring($imageResource, DEFAULT_FONT_LARGE, $TimexPosition, $rowYPosition, $this->showsToDisplay[($show)]->get_begin(), $color);
                    imagestring($imageResource, DEFAULT_FONT_LARGE, $TextxPosition, $rowYPosition, $this->showsToDisplay[($show)]->get_name(), $color);
            }
        }
        $this->currentAnimationFrameFadeIn++;
        return $imageResource;
    }
    
    private function animateFadeOut(&$imageResource, $fadeTimeInSeconds){
    
    }
    
    // http://www.catswhocode.com/blog/19-ffmpeg-commands-for-all-needs
    // Mix a video with a sound file
    // ffmpeg -i son.wav -i video_origine.avi video_finale.mpg
    // now convert the image into a movie
    // http://electron.mit.edu/~gsteele/ffmpeg/
    // http://blog.yimingliu.com/2008/10/07/ffmpeg-encoding-gotchas/
    
    //ffmpeg -r 10 -s hd480  -i ~/Documents/tv/content/announcements/dump/155544/%0d_announcement.jpg -i /Users/stitch/Documents/tv/content/announcements/source/backgroundmusic.mp3   ~/Documents/tv/content/announcements/dump/155544/test1800.avi
    // muziekje korter maken: ffmpeg -ss 00:00:00.00 -t 119 -i backgroundmusic.mp3 -acodec copy backgroundmusic119.mp3

    private function renderFramesToMovie(){
        //$this->movieLength
        // Goto $tmpRenderDir...
        $command = "ffmpeg -r 10 -s hd480 -i ".$this->designDir."backgroundMusicClips/backgroundmusic".$this->movieLength.".mp3 -i ".$this->tmpRenderDumpDir."%0d_announcement.jpg ".$this->tmpRenderDumpDir."announcement.avi";
        $this->executeShellCommand($command);
        
                // todo: delete temporary image files, move announcement to proper directory...
                
        return $this->tmpRenderDumpDir."announcement.avi";
    }
    
    private function cutAudioToPreferedLengths(){
    
        for ($i=0;$i<121;$i++){
            $command = "ffmpeg -ss 00:00:00.00 -t ".$i." -i ".$this->designDir."backgroundmusic.mp3 -acodec copy ".$this->designDir."backgroundMusicClips/backgroundmusic".$i.".mp3";
            $this->executeShellCommand($command);
        }
    
    }
    
    private function executeShellCommand($command){
        print $command."\n";
        exec ($command, $output);
    }
}
?>