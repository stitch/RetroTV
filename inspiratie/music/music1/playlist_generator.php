<?
/**
* Produce an XPSF playlist on the fly from files in a given folder
*
* Code by Lewis Smith www.lasmit.com
* Code released under GNU General Public Licence - http://www.gnu.org/licenses/gpl.html
* 
* If you use this software and find it useful please drop me an email - lewis@lasmit.com
* I'd like to build a list of people using the script which I'll publish on my site, so please do get in touch.

* Code uses snippets from http://getid3.sourceforge.net/source2/demo.browse.dhtml.phps
* Using getID3 library from http://getid3.sourceforge.net/
*/
$base_url = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);
// Definitions
define("ROOT", 					$_SERVER["HTTP_HOST"]); 	// This is your domain name, can probably be left
define("PATH",					$_SERVER["DOCUMENT_ROOT"]);	// This is the full physical root to your folder, can probably be left
define("MP3_PATH",				"$base_url/mp3");					// This is the virtual path to the folder containing your MP3s, 
															// e.g. if your files are at http://www.me.com/files/mp3s then this should
															// be set to files/mp3s

require_once('../getid3/getid3.php');					// Include the getId3 library

// Create ID3 object
$getid3 = new getID3;
$getid3->encoding = 'ISO 8859-1';

$dir = PATH . "/" . MP3_PATH;
// Read directory
if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            $full_name = "$dir/$file";
            if (is_file($full_name)) {
                $files[$full_name] = $file;
            }
            elseif ($file[0] != '.') {
                $dir[$full_name] = $file;
            }
       }
       closedir($dh);
   }
}

// Produce output
if ($files) {
	
	print <<< END_HEADER
<?xml version="1.0" encoding="UTF-8"?>
<playlist version="1" xmlns="http://xspf.org/ns/0/">
	<!-- Playlist generated automatically using script from www.lasmit.com -->
    
    <trackList>

END_HEADER;

	if ($_REQUEST['shuffle']=="true") {
		shuffle($files);
	} else {
    	asort($files);
	}
	
	$counter = $i = 0;
    foreach ($files as $full_name => $short_name) {

	    $getid3->Analyze($dir . "/" . $short_name);	
	    
        $artist = $title = '';
            if (@$getid3->info['tags']) {
            foreach ($getid3->info['tags'] as $tag => $tag_info) {
                if (@$getid3->info['tags'][$tag]['title']) {
                    $artist = @$getid3->info['tags'][$tag]['artist'][0];
                    $title  = @$getid3->info['tags'][$tag]['title'][0];
                    $album  = @$getid3->info['tags'][$tag]['album'][0];
                    break;
                } else {
				$title  = basename($short_name, ".mp3");
				}
            }
        } else {
		$title = basename($short_name, ".mp3");
		}
		
        $url = ROOT . "/" . MP3_PATH . "/" . $short_name;
        
        if (strpos($url, "http://") === false) {
	        $url = "http://" . $url;
        }
        
        $info = ROOT;
        
        if (strpos($info, "http://") === false) {
	        $info = "http://" . $info;
        }
        
        print <<< END_TRACK
		<track>
			<location>{$url}</location>
			
			<!-- artist or band name -->
			<creator>{$artist}</creator>
			
			<!-- album name -->
			<album>{$album}</album>
			
			<!-- name of the song -->
			<title>{$title}</title>
		</track>	

END_TRACK;
	}
	print <<< END_FOOTER
	</trackList>
</playlist>
END_FOOTER;
}
?>
	 