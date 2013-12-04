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
 * This is the configuration file idprovider-javascript.hookflash.me deployment.
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
define('MY_DOMAIN', 'https://' . $_SERVER['SERVER_NAME'] . '/');

// Here you set your database
define('APP_DB_NAME', 'provider_db');
define('APP_DB_HOST', '127.0.0.1');
define('APP_DB_USER', 'user');
define('APP_DB_PASS', 'DHs83dSJCJDJj27274847OPCEYscd');

// Here you set your Hookflash service domain
define('DOMAIN', $_SERVER['HTTP_HOST']);
define('HF_SERVICE_DOMAIN', siteURL('hfservice-v1-adriano-maljkovic-i.hcs.io/'));

// Here you set your OAuth keys and secrets
define('LINKEDIN_CONSUMER_KEY', 'zlum98nx6wnr');
define('LINKEDIN_CONSUMER_SECRET', 'jlV0VHI4znPB0Ofk');

define('FACEBOOK_APP_ID', '658774394142826');
define('FACEBOOK_APP_SECRET', 'ad51f0ef56ec731c441a3d8620c11d38');

define('TWITTER_APP_ID', 'KD0Vhsu4VqsC7AesfjlBFA');
define('TWITTER_APP_SECRET', 'qiJuRKmHAqvhJTDcqi50JlHcWkuh1EekuXKIre49Nwc');

// Here you set your SMTP service parameters
require(APP . 'php/config/special/smtp_config.php');

// Here you set your SMS service parameters
require(APP . 'php/config/special/sms_config.php');

// Here you set your specific cryptographically random values
define('PROVIDER_MAGIC_VALUE', '');

// Here you set your domain hosting secret
define('DOMAIN_HOSTING_SECRET', '');

// Here you set your users' avatars uploading location
define('UPLOAD_LOCATION', ROOT . '/public/php/service/avatars/');

// Where to send JS logs
define('HF_LOGGER_HOST', 'logger-v1-adriano-maljkovic-i.hcs.io');


//^^ IMPORTANT ^^//

function siteURL($domainName="")
{
    $protocol = ((!empty( $_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    if ($domainName=="") {
	$domainName = $SERVER['HTTP_HOST'];
    }
    return $protocol.$domainName;
}

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
