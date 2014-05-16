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


// Set required imports
if ( !defined('ROOT') ) {
	define('ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
}

require_once ( ROOT . 'config/config.php');

/**
 * Class SmsUtil provides the SMS sending functionality
 *
 */
class SmsUtil {
	
	/**
	 * Try sending a generic PIN validation SMS
	 * using given pin and phone number as a receiver of the message and
	 * generic predefined values for the message. 
	 *
	 * @param string $sEmail E-mail to send the message to
	 * @param string $sPin A PIN number to send with the message
	 * @return boolean Returns true if the mail was sent, otherwise returns false
	 */
	public static function sendPinValidationEmail ( $sPhoneNumber, $sPin ) {
		// Set general parameters
		$user = SMS_SERVICE_USER;
		$password = SMS_SERVICE_PASSWORD;
		$api_id = SMS_SERVICE_API_ID;
		$baseurl = SMS_SERVICE_BASEURL;
		
		$text = urlencode(SMS_SERVICE_MESSAGE . $sPin);
		$to = $sPhoneNumber;
		
		// Set auth call
		$sUrl = "$baseurl/http/auth?user=$user&password=$password&api_id=$api_id";
		
		// Perform auth call
		$ret = file($sUrl);
		
		// Explode the response.
		// ( INFO: return string is on first line of the data returned )
		$sess = explode(":",$ret[0]);
		if ($sess[0] == "OK") {
		
			$sess_id = trim($sess[1]); // remove any whitespace
			$url = "$baseurl/http/sendmsg?session_id=$sess_id&to=$to&text=$text";
		
			// Send the message
			$ret = file($url);
			$send = explode(":",$ret[0]);
		
			if ($send[0] == "ID") {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

}

?>