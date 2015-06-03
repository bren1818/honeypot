<?php
error_reporting(E_ALL);
$remoteIP = $_SERVER['REMOTE_ADDR'];
$newLine = '\n\n';
//check if in honeypot
if( strpos(file_get_contents("/disallow.txt"),$remoteIP) !== false) {
	
}else{
	file_put_contents("./disallow.txt",PHP_EOL.$_SERVER['REMOTE_ADDR'],FILE_APPEND);
}
	
ob_clean();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include "hp.php";
exit;

?>