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
if ( !defined('APP') ) {
	define('APP', ROOT . '/app/');
}

require_once ( APP . 'php/config/config.php');
require_once ( APP . 'php/libs/smtp/class.phpmailer.php');
require_once ( APP . 'php/libs/smtp/class.smtp.php');

/**
 * Class SmtpUtil provides the mail sending functionality
 *
 */
class SmtpUtil {

	/**
	 * Try sending a generic PIN validation e-mail
	 * using given pin and e-mail as a receiver of the message and
	 * generic predefined values for body and subject. 
	 *
	 * @param string $sEmail E-mail to send the message to
	 * @param string $sPin A PIN number to send with the message
	 * @return boolean Returns true if the mail was sent, otherwise returns false
	 */
	public static function sendPinValidationEmail ( $sEmail, $sPin ) {
		$oMailer = new PHPMailer();
		$oMailer->IsSMTP();
		$oMailer->SMTPSecure = 'tls';
		
		$oMailer->Host = SMTP_HOST;
		$oMailer->Port = SMTP_PORT;
		$oMailer->SMTPAuth = true;
		$oMailer->Username = SMTP_USER;
		$oMailer->Password = SMTP_PASSWORD;
		
		$oMailer->SetFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
		$oMailer->AddAddress( $sEmail );
		
		$oMailer->MsgHTML(SMTP_MSG_PIN_HTML . $sPin);
		$oMailer->Subject = SMTP_MSG_PIN_SUBJECT;
		$oMailer->Body = SMTP_MSG_PIN_BODY . $sPin;
		
		return $oMailer->Send();
	}

}

?>