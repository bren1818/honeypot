<?php
error_reporting(0);

function endsWith($haystack, $needle) {
	// search forward starting from end minus needle length characters
	return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}

$remoteIP = $_SERVER['REMOTE_ADDR'];	
	
if( strpos(file_get_contents("honeypot/disallow.txt"),$remoteIP) !== false) {
	//echo "You're now in the honey pot you bad bot you.";
	include "honeypot/hp.php";
	exit;
}	
	
//check if above is a folder or a page and if exists
$path = $_SERVER['REQUEST_URI'];

$cwd = getcwd();

$fullPath = ($cwd.$path);
$fullPath = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $fullPath);


//check if path to a folder
if( endsWith($fullPath, "\\") && is_dir(realpath($fullPath) ) ){
	//We want to adjust the path to \index...
	echo '<pre>'.print_r($_SERVER,true).'</pre>';
	$files = scandir($fullPath);
	foreach($files as $file){
		if( $file !="." && $file != ".."){
			if( $file == "index.php" || $file == "index.html"){
				$fullPath = $fullPath.$file;
				break;
			}
		}
	}
}

if( file_exists( realpath($fullPath) ) ){
	ob_clean();
	if( $path != "/"){
		//fix the directory listing
		chdir( dirname(realpath($fullPath) ));
		include $fullPath;
		exit;
	}
}else{
	echo "404";
	exit;
}

//echo $path;

/*Home Page*/

echo "Hello World";
echo '<p><a href="/other">Some other page</a></p>';
echo '<p><a href="/does-not-exist/">Non existant Page</a></p>';
echo '<p>Hidden on this page, we\'d have an anchor which isn\'t visible which links to "<a href="/honeypot/index.php">/honeypot</a>". This link of course should be ignored by bots as it is restricted in our <a href="robots.txt">robots.txt</a> file. When the bad bot follows the link. They\'ll be blocked into oblivion</p>';

?>