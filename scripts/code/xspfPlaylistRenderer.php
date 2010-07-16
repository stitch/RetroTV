<?php
// create an XSPF file with content crap... (spiff is a horrible name when you also need to pronounce the X)

class xspfPlaylistRenderer {
    private $tracks = array();
    private $playListMetadata = array();
    
    public function addTrack(xspfTrack $track){
        if (empty($track->location))
            print "Warning: XSPF playlist renderer cannot add tracks without locations. The location is the only mandatory information in a track. ";
            
        $this->tracks[] = $track;
    }
    
    public function setPlayListMetadata(xspfPlaylistMetadata $metadata){
        $this->playListMetadata = $metadata;
    }
    
    public function render(){
        ob_start();
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo "<playlist version=\"1\" xmlns=\"http://xspf.org/ns/0/\">\n";
        if (!empty($this->playListMetadata->title))
        echo "<title>".$this->playListMetadata->title."</title>\n";
        if (!empty($this->playListMetadata->creator))
        echo "<creator>".$this->playListMetadata->creator."</creator>\n";
        if (!empty($this->playListMetadata->info))
        echo "<info>".$this->playListMetadata->info."</info>\n";
        
        echo "<trackList>\n";
        
        foreach ($this->tracks as $track){
            echo "        <track>\n";
            echo "            <location>".$track->location."</location>\n";
            if (!empty($track->title))
            echo "            <title>".$track->title."</title>\n";
            if (!empty($track->duration))
            echo "            <duration>".$track->duration."</duration>\n";
            if (!empty($track->info))
            echo "            <info>".$track->info."</info>\n";
            if (!empty($track->creator))
            echo "            <creator>".$track->creator."</creator>\n";
            if (!empty($track->album))
            echo "            <album>".$track->album."</album>\n";
            if (!empty($track->annotation))
            echo "            <annotation>".$track->annotation."</annotation>\n";
            if (!empty($track->image))
            echo "            <image>".$track->image."</image>\n";
            echo "        </track>\n";
        }
        
        echo "    </trackList>\n";
        echo "</playlist>";
        return ob_get_clean();
    }

}
?>