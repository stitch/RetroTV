<?php
class simpleTime {
    private $hours = 0;
    private $minutes = 0;
    private $seconds = 0;
    
    public function __construct($hours = 0, $minutes = 0, $seconds = 0){
        $this->hours = $hours;
        $this->minutes = $minutes;
        $this->seconds = $seconds;
    }
    
    // gets the hour and minutes and seconds according to 24 h day format
    public function loadDayPosition(){
        $this->hours = date("H");
        $this->minutes = date("i");
        $this->seconds = date("s");
    }
    
    // dayhours are in normal human format
    public function getDayHours(){
        return $this->hours;
    }
    
    public function getDayMinutes(){
        return $this->minutes;
    }
    
    // these functions are in countdown format.
    public function getMinutes(){
        return $this->hours * 60 + $this->minutes;
    }
    
    public function getSeconds(){
        return ($this->getMinutes() * 60) + $this->seconds;
    }
    
    public function getTime(){
        return $this->hours.$this->minutes.$this->seconds;
    }
    
    public function getDifferenceInSeconds($hours = 0, $minutes = 0, $second = 0){
        $tmpTime = new time($hours, $minutes, $seconds);
        $difference = $this->getSeconds - $tmpTime->getSeconds();
        unset($tmpTime);
        return $difference;
    }
    
    public function getDifferenceInMinutes($hours = 0, $minutes = 0){
        $tmpTime = new time($hours, $minutes);
        $difference = $this->getSeconds - $tmpTime->getMinutes();
        unset($tmpTime);
        return $difference;
    }
}
?>