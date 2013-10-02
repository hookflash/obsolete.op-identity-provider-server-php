<?php

/**
 
 Copyright (c) 2012, SMB Phone Inc.
 All rights reserved.
 
 Redistribution and use in source and binary forms, with or without
 modification, are permitted provided that the following conditions are met:
 
 1. Redistributions of source code must retain the above copyright notice, this
 list of conditions and the following disclaimer.
 2. Redistributions in binary form must reproduce the above copyright notice,
 this list of conditions and the following disclaimer in the documentation
 and/or other materials provided with the distribution.
 
 THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 
 The views and conclusions contained in the software and documentation are those
 of the authors and should not be interpreted as representing official policies,
 either expressed or implied, of the FreeBSD Project.
 
 */


/**
 * This is the configuration file for example.unstable.hookflash.me deployment.
 *
 */

define('LOG', true);

if ( !defined('ROOT') ) {
	define('ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
}
if ( !defined('APP') ) {
	define('APP',  ROOT . '/app/');
}

//-- IMPORTANT --//

// Here you set your domain
define('MY_DOMAIN', 'https://idprovider-javascript.hookflash.me/');

// Here you set your database
define('APP_DB_NAME', 'provider_db');
define('APP_DB_HOST', 'localhost');
define('APP_DB_USER', 'root');
define('APP_DB_PASS', '************');

// Here you set your Hookflash service domain
define('DOMAIN', 'jsunstable.hookflash.me');
define('HF_SERVICE_DOMAIN', 'https://hcs-javascript.hookflash.me/');

// Here you set your OAuth keys and secrets
define('LINKEDIN_CONSUMER_KEY', '*************');
define('LINKEDIN_CONSUMER_SECRET', '****************');

define('FACEBOOK_APP_ID', '**************');
define('FACEBOOK_APP_SECRET', '********************************');

define('TWITTER_APP_ID', '*********************');
define('TWITTER_APP_SECRET', '******************************************');

// Here you set your SMTP service parameters
require(APP . 'php/config/special/smtp_config.php');

// Here you set your SMS service parameters
require(APP . 'php/config/special/sms_config.php');

// Here you set your specific cryptographically random values
define('PROVIDER_MAGIC_VALUE', '*******************************');

// Here you set your domain hosting secret
define('DOMAIN_HOSTING_SECRET', '***************');

// Here you set your users' avatars uploading location
define('UPLOAD_LOCATION', ROOT . '/public/php/service/avatars/');

//^^ IMPORTANT ^^//



// Log events
function APIEventLog($sText, $iErrorCode='200', $sAPISessionID='') {
	global $DB;
	date_default_timezone_set('UTC');
	$user_agent = '';
	if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
	}
	return $DB->insert('api_event_log', array(
		'created'=>date('Y:m:d H:i:s'),
		'ip_address'=>$_SERVER['REMOTE_ADDR'],
		'http_client'=>$user_agent,
		'error_code'=>$iErrorCode,
		'message'=>$sText,
		'session_id'=>session_id(),
		'session_dump'=>var_export($_SESSION, true),
	));
}

?>