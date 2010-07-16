<?php
// dto for a show, with horrible validation. // it is a programmed show... the one people talk about.
// for humans a show takes up 30 minutes, while in reality its 20 minutes, plus commercials etc etc etc.
class show {
    public function __construct($timeslot, $begin, $name, 
                                    $parental_advisory_age = "", 
                                    $parental_advisory_content = ""){
        $this->set_timeslot($timeslot);
        $this->set_begin($begin);
        $this->set_name($name);
        $this->set_parental_advisory_age($parental_advisory_age);
        $this->set_parental_advisory_content($parental_advisory_content);
    }
    
    public function set_timeslot($timeslot){
        $this->timeslot = $timeslot;
    }
    
    // dayhours 0000 to 2400
    public function set_begin($begin){
        $this->begin = $begin;
    }
    
    public function set_name($name){
        $this->name = $name;
    }
    
    public function set_parental_advisory_age($parental_advisory_age){
        $this->parental_advisory_age = $parental_advisory_age;
    }
    
    public function set_parental_advisory_content($parental_advisory_content){
    
        // single value
        $this->parental_advisory_content = array($parental_advisory_content);
        
        // CSV value
        if (strpos($parental_advisory_content,","))
            $this->parental_advisory_content = str_split(",", $parental_advisory_content);
        
        // array value
        if (is_array($parental_advisory_content))
            $this->parental_advisory_content = $parental_advisory_content;
    }
    
    // seconds
    public function set_scheduled_duration ($scheduled_duration){
        $this->scheduled_duration = $scheduled_duration;
    }
    
    private $timeslot = ""; // timeslot type
    private $begin = ""; // point in a 24 hour time
    private $name = ""; // string
    private $parental_advisory_age = ""; // age type 
    private $parental_advistory_content = ""; // content type, array
    private $scheduled_duration = ""; // time in seconds
    
    public function get_timeslot(){return $this->timeslot;}
    public function get_begin(){ return $this->begin; }
    public function get_name(){ return $this->name; }
    public function get_parental_advisory_age() { return $this->parental_advisory_age; }
    public function get_parental_advistory_content() { return $this->parental_advisory_content; }
    public function get_scheduled_duration(){ return $this->scheduled_duration; }
}
?>