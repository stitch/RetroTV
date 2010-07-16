<?php
// information class (DTO) to hold your programming. It has a granularity of 10 minutes.
// the playlistcreator knows how to fill gaps in your programming, so don't worry.
// programming is horizontal. It requires you to have a rough understanding of what you
// are showing. It is overlapping-fault tolerant. When content requires more time it eats
// from the following; probably causing the next item to drop. Uses 24 hour notation.
class showSchedule {
    private $shows = array();

    public function addShow($timeslot, $begin, $name, 
                                $parental_advisory_age = "PARENTAL_ADVICE_AGE_16", 
                                $parental_advisory_content = ""){
        $this->shows[] = new show($timeslot, $begin, $name, $parental_advisory_age, $parental_advisory_content);
    }
    
    // calculates time till next show. Expects that the shows are given in order.
    // Since we don't expect to have linq, or array SQL we cannot do ordering without magic callbacks.
    // just enter your shows in a sensible order and everything will be fine. When we use a database (ever?) this might change.
    public function getShows(){
        $last_show = count($this->shows);
        
        for($current_show=0; $current_show < $last_show; $current_show++) {
            
            $scheduled_show_duration = 0;
            $next_show = $current_show + 1;
            // possibly doesnt exist, then get the first show for the next "day"
            if (!isset($this->shows[$next_show]))
                $next_show = 0;
            
            $scheduled_show_duration = $this->shows[$next_show]->get_begin() - $this->shows[$current_show]->get_begin();
            if ($current_show == ($last_show - 1)){
                // fill day (first item + whole day to see how much time it is)
                $scheduled_show_duration = ($this->shows[0]->get_begin() + 2400) - $this->shows[$current_show]->get_begin();
                print "time for last show is ".$scheduled_show_duration." seconds till next day.\n\n";
            }
            
            $time = new simpletime(0, $scheduled_show_duration);
            $this->shows[$current_show]->set_scheduled_duration($time->getSeconds());
        }
        return $this->shows;
    }
}
?>