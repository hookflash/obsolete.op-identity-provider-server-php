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


if (file_exists("/opt/data/config/identity-provider-config.php")) {
    require_once("/opt/data/config/identity-provider-config.php");
} else {
    if ( !defined('ROOT') ) {
       define('ROOT', dirname(dirname(__FILE__)) . "/");
    } 
    require_once(ROOT . 'config/config-custom.php');
}


if (isset($_SERVER['HTTP_ORIGIN'])) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Keep-Alive,User-Agent,X-Requested-With,Cache-Control,Content-Type,If-Modified-Since');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        header("HTTP/1.0 204 No Content");
        exit(0);
}


// Debug logging system

require ( ROOT . 'vendor/autoload.php');

use Monolog\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;

$LOGGER = new Logger('name');
$stream = new StreamHandler( (dirname(ROOT)) . '/debug.log', Logger::DEBUG);
//$stream->setFormatter(new JsonFormatter());
$LOGGER->pushHandler($stream);


function LOG_EVENT($message) {
    global $LOGGER;
    // TODO fix LOGGER
    //$LOGGER->debug($message);
}

ob_start();

LOG_EVENT($LOGGER, 'Request: ' . $_SERVER['REQUEST_URI']);
LOG_EVENT('SESSION: ' . var_export($_SESSION, true));
LOG_EVENT('POST DATA: ' . file_get_contents('php://input'));

function siteURL($domainName="") {
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
