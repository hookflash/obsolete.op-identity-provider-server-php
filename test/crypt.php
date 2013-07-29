<?php

/*
<sharedSecret> = 52+ plain text characters
<iv> = MD5 random hash (16 bytes)
token = hex(<iv>) + "-" + hex(AES.encrypt(sha256(<sharedSecret>), <iv>, <credentials>))

<credentials> = JSON.stringify({
    service: <name (github|twitter|linkedin|facebook)>
    consumer_key: <OAuth consumer/api key provided by service>,
    consumer_secret: <OAuth consumer/api secret provided by service>,
    token: <OAuth access token>,
    token_secret: <OAuth access token secret>
})
*/

function encrypt($string, $key) {
	srand((double) microtime() * 1000000);
	$td = mcrypt_module_open('rijndael-128', '', 'nofb', '');
	$iv = mcrypt_create_iv(16, MCRYPT_RAND);
	if (mcrypt_generic_init($td, hex2bin(hash('sha256', $key)), $iv) != -1) {
		$c_t = mcrypt_generic($td, $string);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return bin2hex($iv) . '-' . bin2hex($c_t);
	}
}

function decrypt($string, $key) {
	$td = mcrypt_module_open('rijndael-128', '', 'nofb', '');
	list($iv, $string) = explode("-", $string);
	if (mcrypt_generic_init($td, hex2bin(hash('sha256', $key)), hex2bin($iv)) != -1) {
		$c_t = mdecrypt_generic($td, hex2bin($string));
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $c_t;
	}
}

var_dump("-- sha256 --------");

var_dump(hash('sha256', "MY-DATA"));

var_dump("-- in --------");

$key = hash('sha256', "01234567890123456789012345678901");
var_dump('$key', $key);
$iv = hash('md5', "0123456789012345");
var_dump('$iv', $iv);
var_dump("-- encrypt --------");
$data = "MY-DATA";

$td = mcrypt_module_open('rijndael-128', '', 'nofb', '');
if (mcrypt_generic_init($td, hex2bin($key), hex2bin($iv)) != -1) {
	$c_t = mcrypt_generic($td, $data);
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	$encrypted = bin2hex($c_t);
}
var_dump($encrypted);

var_dump("-- decrypt --------");

$td = mcrypt_module_open('rijndael-128', '', 'nofb', '');
if (mcrypt_generic_init($td, hex2bin($key), hex2bin($iv)) != -1) {
	$c_t = mdecrypt_generic($td, hex2bin($encrypted));
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	var_dump($c_t);
}

var_dump("-- openssl --------");

/*
openssl enc -aes-128-cfb -in raw.txt -out raw.txt.enc -K 861009ec4d599fab1f40abc76e6f89880cff5833c79c548c99f9045f191cd90b -iv d927ad81199aa7dcadfdb4e47b6dc694
*/
var_dump(bin2hex(file_get_contents("raw.txt.enc")));

var_dump("-- api --------");

$secretKey = "MY-SECRET-KEY";
var_dump($secretKey);

$inputData = "MY-DATA";
var_dump($inputData);

$encryptedData = encrypt($inputData, $secretKey);
var_dump($encryptedData);

$outputData = decrypt($encryptedData, $secretKey);
var_dump($outputData);

var_dump("-- js --------");

$encryptedData = "d927ad81199aa7dcadfdb4e47b6dc694-80eb666a9fc9e2";
$secretKey = "01234567890123456789012345678901";

$outputData = decrypt($encryptedData, $secretKey);
var_dump($outputData);
