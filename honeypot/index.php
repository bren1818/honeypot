<?php
error_reporting(E_ALL);
$remoteIP = $_SERVER['REMOTE_ADDR'];
$newLine = '\n\n';
//check if in honeypot
if( strpos(file_get_contents("/disallow.txt"),$remoteIP) !== false) {
	include "hp.php";
}else{
	ob_clean();
	file_put_contents("./disallow.txt",PHP_EOL.$_SERVER['REMOTE_ADDR'],FILE_APPEND);
	include "hp.php";
}
?>