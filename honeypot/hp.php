<?php
/* PHP HTTP Tarpit
 * Purpose: Confuse and waste bot scanners time.
 * Use: Url rewrite unwanted bot traffic to this file. It is important you use Url rewrites not redirects as most bots ignore location headers.
 * Version: 1.1.6
 * Author: Chaoix
 *
 * Change Log:
 *	-Changed default defense to Random by the minute. (1.1.6)
 *	-Added Random Defense by the minute option. (1.1.6)
 *	-Replaced rand calls with mt_rand calls to make the script more efficent. (1.1.5)
 *	-Forced validation of $times_redirected in Chained Redirection defense. (1.1.4)
 *	-Changed random prefix to a random word in content generation. (1.1.3)
 *	-Improved random content generation. (1.1.2)
 *	-Fixed bug in Chained Redirection defense (1.1.1)
 *	-Added Chained Redirection defense. (1.1.0)
 *	-Added Unix control characters to the list of prefixes. (1.0.5)
 *	-Added random delay before headers are sent. (1.0.5)
 *	-Fixed bug in Random defense selection. (1.0.4)
 *	-Weighted Random defense to use HTTP Tarpit more often. (1.0.2)
 *	-Changed default defense to Random. (1.0.1)
 */
 
//Basic Options
$random_content_length = 2048; //In characters. Used to fill up the size of the scanner's log files.
$defense_number = 5; //1 is Blinding Mode, 2 is Ninja Mode, 3 is HTTP Tarpit, 4 is a Chained Redirection, 5 is a Random defense for each request, 6 is a Random Defense by the minute.
$responce_delay_min = 1000000; //Range of delay in microseconds before headers are sent. You want a range of delays so the introduced latentcy can not be detected by the scanner.
$responce_dalay_max = 5000000;
$times_redirected_max = 9; //Maximum number of times to redirect (0-9).
$debug = false; //Echo messages for testing the script.

function rand_content() {
	global $random_content_length;
	
	$random_words = array( '', 
						//Send them down a wild goose chase.
						'Public Key:', 
						'Private Key:',
						'Password',
						'Username',
						//Piss off people who aren't escaping content correctly in Unix or piping to Grep.
						"\x03", //Interupt
						"\x04", //Logout
						"\x07", //Beep
						"\x21", //Communcation Error
						" | shutdown -r now",
						//Exploit grep debian bug #736919 for those running out of date software and put grep in an infinite loop
						"\xe9\x65\n\xab\n"
						);
	
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789\t\n\r\s";	

	$size = strlen( $chars );
	$random_word_point = mt_rand( 0, $random_content_length - 1 );
	for( $i = 0; $i < $random_content_length; $i++ ) {
		if( $i == $random_word_point )
			echo $random_words[ mt_rand( 0, count($random_words) - 1 ) ];
		echo $chars[ mt_rand( 0, $size - 1 ) ];
	}
}

function self_url(){
    $url = 'http';
    
    if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || $_SERVER['SERVER_PORT'] == '443')
        $url .= 's';

    $url .= '://';
    if ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443'):
        $url .= $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'];
    else:
        $url .= $_SERVER['HTTP_HOST'];
    endif;

    return $url;
}

function validate_integer ($numeric_string) {
	return preg_match('/^(\d+)$/', $numeric_string);
}

//Delay for a random number of microseconds
usleep( mt_rand($responce_delay_min, $responce_dalay_max) );

//Enforce Endless Redirection
$times_redirected = 0;
if( !empty($_SERVER['REQUEST_URI']) ) {
	$refered_page = substr($_SERVER['REQUEST_URI'], strripos($_SERVER['REQUEST_URI'], '/')+1);
	if( !empty($refered_page) ) {
		$refered_page = substr($refered_page, 0, strlen($refered_page)-5);
		$key_number = substr($refered_page, 0, strlen($refered_page)-1);
	 	if( !empty($refered_page) && 0 == $key_number%4242 ) {
	 		$times_redirected = substr($refered_page, -1);
	 		if( validate_integer($times_redirected) ) {
	 			if( $times_redirected < $times_redirected_max )
	 				$defense_number = 4;
	 			elseif( $defense_number == 4 )
	 				$defense_number = 1;
	 		} elseif( $defense_number == 4 )
	 			$times_redirected = 0;
	 	}
	}
}

//Randomize defense
if (5 == $defense_number) {
	//Weight random selection to use the Tarpit more often
	$number_sample = array(1, 2, 3, 4);
	$defense_number = $number_sample[ array_rand( $number_sample ) ];
} elseif (6 == $defense_number) {
	//Randomize defense based on the current minute. This makes the random return of the server harder to identify.
	mt_srand(date('i'));
	$defense_number = mt_rand(1, 4);
	mt_srand();
}


switch ($defense_number) {
	//Blinding Mode
	//Add false positives and fill up bot scanners results with junk.
	case 1:
		header("HTTP/1.1 200 OK");
		rand_content();
		break;
	
	//Ninja Mode
	//Add false negatives to bot scanners results.
	case 2:
		header("HTTP/1.1 404 Not Found");
		echo 'HTTP/1.1 404 Not Found';
		if( mt_rand(0,1) )
			rand_content();
		break;
	
	//HTTP Tarpit
	//Slows down bot scanners.
	case 3:
		$rand_num = mt_rand(0, 3);
		//if (3 == $rand_num) {
			//Ask for unneccessary authentication.
			ob_clean();
			header("HTTP/1.1 401 Not Authorized");
			header('WWW-Authenticate: realm="My Realm"');
			echo 'HTTP/1.1 401 Not Authorized'."\n";
			rand_content();
			break;
		//}
		//Reply with random keep conection open status code.
		if (!$debug) {
			header("HTTP/1.1 10$rand_num"); 
			if(1 == $rand_num)
				header("Upgrade: HTTP/2.0"); //Ask client to request the page again.
		} else
			echo "HTTP/1.1 10$rand_num";
		break;
	
	//Endless Redirect
	//Punishses crawlers that don't respect robots.txt.
	case 4:
		//Down the rabbit hole
		if( $times_redirected >= $times_redirected_max || !validate_integer($times_redirected) )
			$times_redirected = 0;
		$times_redirected++;
		//Random redirect status
		ob_clean();
		$redirect_statuses = array('301 Moved Permanently', 
								'302 Found', 
								'307 Temporary Redirect'
								);
		header('HTTP/1.1 '.$redirect_statuses[ array_rand( $redirect_statuses ) ]);
		header('Location: ' . self_url() . '/' . mt_rand(1, 1000) * 4242 . $times_redirected . '.html');
		if( mt_rand(0,1) )
			rand_content();
		break;
		
}
if ($debug) {
	echo $defense_number;
}
die(); //Stop kill php process to reduce resource overhead
