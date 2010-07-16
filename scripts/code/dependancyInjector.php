<?php
// override php's autoload handler
function __autoload($classname){
    $dependancyInjection = dependancyInjector::instance();
    $loaded = $dependancyInjection->load($classname);
    if (!$loaded) trigger_error(E_USER_ERROR, "Class ".$classname." was not registered.");
}


/**
    Loads classes into the global namespace. Can be configured to look in certain directories for the 
    classes to load. Note that this is a case sensitive operation on most platforms.
*/
class dependancyInjector{
    protected $shelves = array();
    protected $shelve_count = 0;

    // resembles a directory of directories
    protected $bookcases = array();
    
    function &instance() {
        static $me;

        if (is_object($me) == true) {
                return $me;
        }

        $me = new dependancyInjector;
        return $me;
    }
   
    public function debug(){
        print_r($this->shelves);
    }
   
    public function add($lib,$postfix = ""){
        if (!in_array($lib,$this->shelves)){
            $this->shelves[] = array("lib" => $lib, "postfix" => $postfix);
            $this->shelve_count = count($this->shelves);
        }
    }
    
    public function addDir($dirname){
        $this->bookcases[] = $dirname;
    }
    
    public function load($classname){
        
        for($i=0;$i<$this->shelve_count;$i++){
            //print "checking bookshelf ".$this->shelves[$i]['lib'].$classname.$this->shelves[$i]['postfix'].'.php'."\n<br>";
            if (file_exists($this->shelves[$i]['lib'].$classname.$this->shelves[$i]['postfix'].'.php')){
                require_once $this->shelves[$i]['lib'].$classname.$this->shelves[$i]['postfix'].'.php';
                if (class_exists($classname) or interface_exists($classname)) return true;
            } else {
                //print "Autoloader, checked: ".$this->shelves[$i]['lib'].$classname.$this->shelves[$i]['postfix'].'.php';
            }
        }
        
        // not in shelves? then look through all other bookcases (which is slower)
        for($i=0;$i<count($this->bookcases);$i++){
            $directories = array();
            if (is_dir($this->bookcases[$i])) {
                if ($dh = opendir($this->bookcases[$i])) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != '.' and $file != '..' and is_dir($this->bookcases[$i].$file))
                            $directories[] = $file;
                    }
                    closedir($dh);
                }
            }

            foreach($directories as $directory){
                if (file_exists($this->bookcases[$i].$directory."/".$classname.'.php')){
                    require_once $this->bookcases[$i].$directory."/".$classname.'.php';
                    if (class_exists($classname) or interface_exists($classname)) return true;
                }
            }
        }
        exit('Class '.$classname." not registered. Check your classlibraries. \n Loaded libraries:".$this->debug().". \n\n Stacktrace: ".debug_print_backtrace()." \n");     
        return false; // class not found
    }
}
?>