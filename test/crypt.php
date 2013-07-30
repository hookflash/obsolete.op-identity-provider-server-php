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
$data = "MY-DATA-AND-HERE-IS-MORE-DATA";

$td = mcrypt_module_open('rijndael-128', '', 'cfb', '');
if (mcrypt_generic_init($td, hex2bin($key), hex2bin($iv)) != -1) {
	$c_t = mcrypt_generic($td, $data);
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	$encrypted = bin2hex($c_t);
}
var_dump($encrypted);

var_dump("-- decrypt --------");

$encrypted = "80eb666a9fc9e263faf71e87ffc94451d7d8df7cfcf2606470351dd5ac3f70bd";

$td = mcrypt_module_open('rijndael-128', '', 'cfb', '');
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

$secretKey = "klksd9887w6uysjkksd89893kdnvbter";
var_dump($secretKey);

$inputData = '{"service":"github","consumer_key":"264ea34924b00a5fa84e","consumer_secret":"6d21988222de0f9cc3c0257b70357a5b22bd23b8","token":"ffd648ab7b9461bbfc48405dd26e0fc12aedbb57"}';
var_dump($inputData);

$encryptedData = encrypt($inputData, $secretKey);
var_dump($encryptedData);

$outputData = decrypt($encryptedData, $secretKey);
var_dump($outputData);

var_dump("-- js --------");

$encryptedData = "f5641be9d451ba13be27e6300ece8137-11982815e1a25816cdcac59b9282814084b212a812253ce88e1d156af52a56ce7c2a6adff0b4cb95f6200e6f33b337ca474d92a4ae6537072e1b862cf6aecb84f53cc940040ce362de11c4ef4fdfb2689028486924abbdf74982cf77ae25ffbadf8ed9e452b61aad064498790976c48885bac482343e92946066bb7a78b8ad2f79c3083e1517ba64f7a31a855bea8cdc5ba9f47608573d8506e3de221b92f62a833af593c22c100a15fa";
$secretKey = "klksd9887w6uysjkksd89893kdnvbter";

$outputData = decrypt($encryptedData, $secretKey);
var_dump($outputData);



// NOTE: This is the implementation compatible with JS cifre.

var_dump("-- phpseclib --------");

include('../server/var/www/idprovider.com/app/php/libs/seclib/Crypt/AES.php');

$cipher = new Crypt_AES(CRYPT_AES_MODE_CFB);
// keys are null-padded to the closest valid size
// longer than the longest key and it's truncated
//$cipher->setKeyLength(128);
$cipher->setKey(hex2bin($key));
$cipher->setIV(hex2bin($iv));

var_dump(bin2hex($cipher->encrypt($data)));
var_dump($cipher->decrypt($cipher->encrypt($data)));
