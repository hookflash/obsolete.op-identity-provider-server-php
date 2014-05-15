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
 * Class Slim just defines the getInstance() function
 *
 */
class RestServerException extends Exception {

	public $sHttpErrorCode = '';
	public $sInternalErrorCode = '';
	public $sErrorMessage = '';
	public $aErrorMessageParameters;
	
	// Hardcoded http messages
	public $HTTP_messages = array(
	'400' => 'Bad Request',
	'401' => 'Unauthorized',
	'403' => 'Forbidden',
        '404' => 'Not Found',    
	'406' => 'Not Acceptable',
	'409' => 'Conflict',
	'420' => 'Enhance You Calm',
	'500' => 'Internal Server Error',
	);
	
	public function __construct ( $sInternalErrorCode, $aErrorMessageParameters ) {
		$this->sInternalErrorCode = $sInternalErrorCode;
		$this->aErrorMessageParameters = $aErrorMessageParameters;
                
		$this->constructErrorMessage();
	}
	
	private function constructErrorMessage () {
		switch ($this->sInternalErrorCode) {
			// Invalid parameter value
			case '001':
				$this->sHttpErrorCode = '403';
				$this->sErrorMessage = 'Invalid parameter value: parameter \'' . $this->aErrorMessageParameters['parameter'] .
									   '\' = \'' . $this->aErrorMessageParameters['parameterValue'] . '\', however, it can only has one of these values: ';
				if ( $this->aErrorMessageParameters['parameter'] == 'type' ) {
					$this->sErrorMessage .= '\'facebook\', \'linkedin\', \'twitter\', \'federated\', \'email\', \'phone\'.';
				}
				if ( $this->aErrorMessageParameters['parameter'] == 'type_oauth-only' ) {
					$this->sErrorMessage .= '\'facebook\', \'linkedin\', \'twitter\'.';
				}
				if ( $this->aErrorMessageParameters['parameter'] == 'type_federated-legacy' ) {
					$this->sErrorMessage .= '\'federated\', \'email\', \'phone\'.';
				}
				break;
			// Missing parameters
			case '002':
				$this->sHttpErrorCode = '406';
				$this->sErrorMessage = 'Missing parameters: parameter \'' . $this->aErrorMessageParameters['parameter'] . '\' does not exist or is null.';
				break;
			// No such identity
			case '003':
				$this->sHttpErrorCode = '401';
				$this->sErrorMessage = 'No such identity: identity of type: \'' . $this->aErrorMessageParameters['type'] .
									   '\' with identifier: \'' . $this->aErrorMessageParameters['identifier'] . '\' does not exist.';
				break;
			// Identity already exists
			case '004':
				$this->sHttpErrorCode = '403';
				$this->sErrorMessage = 'Identity already exists: identity of type: \'' . $this->aErrorMessageParameters['type'] .
									   '\' with identifier: \'' . $this->aErrorMessageParameters['identifier'] . '\' cannot be created.';
				break;
			// Login failure due to external service failure
			case '005':
				$this->sHttpErrorCode = '401';
				if ( $this->aErrorMessageParameters['message'] != '' ) {
					$this->sErrorMessage = 'Login failed due to some external service failure. External service error message: \'' .
										   $this->aErrorMessageParameters['message'] . '\'.';
				}
				if ( $this->aErrorMessageParameters['message'] == '' ) {
					$this->sErrorMessage = 'Login failed due to some external service failure. External service error message: \'' .
										   'INTERNAL_SERVER_ERROR' . '\'.';
				}
				break;
			// Login failure due to internal failure
			case '006':
				$this->sHttpErrorCode = '500';
				$this->sErrorMessage = 'Login failed due to some internal failure. Internal error message: ';
				if ( $this->aErrorMessageParameters['reason'] == 'undefinedScenario' ) {
					$this->sErrorMessage .= '\'Could not calculate the scenario.\'';
				}
				break;
			// Verification failed
			case '007':
				$this->sHttpErrorCode = '403';				
				$this->sErrorMessage = 'Verification failed: verification of parameter: ' . $this->aErrorMessageParameters['parameter'] .
									   ' with value: \'' . $this->aErrorMessageParameters['parameterValue'] . '\' failed.';
				break;
			// Session expired
			case '008':
				$this->sHttpErrorCode = '401';				
				$this->sErrorMessage = 'Session expired or empty.';
				break;
			// Session expired
			case '009':
				$this->sHttpErrorCode = '401';				
				$this->sErrorMessage = 'Identity: \'' . $this->aErrorMessageParameters['identifier'] . '\' is not logged in.';
				break;
			// Wrong format
			case '010':
				$this->sHttpErrorCode = '406';				
				$this->sErrorMessage = 'Wrong parameter format: \'' . $this->aErrorMessageParameters['parameter'] .
										'\' has to be ' . $this->aErrorMessageParameters['format'] . '.';
				break;
                        case '011':
                                $this->sHttpErrorCode = '404';
                                $this->sErrorMessage = 'Not Found';
                                break;
			// PIN-dedicated error
			case '020':
				$this->sHttpErrorCode = '403';
				$this->sErrorMessage = 'PIN error occured: ';
				if ($this->aErrorMessageParameters['parameter'] == 'pinGenerationCooldown') {
					$this->sHttpErrorCode = '403';
					$this->sErrorMessage .= 'PIN generation cooldown did not expire yet.';
				}
				if ($this->aErrorMessageParameters['parameter'] == 'pinSendingFailed') {
					$this->sHttpErrorCode = '500';
					$this->sErrorMessage .= 'PIN sending via third party service failed.';
				}
				if ($this->aErrorMessageParameters['parameter'] == 'noPinValidationRequiredUserInSession') {
					$this->sHttpErrorCode = '420';
					$this->sErrorMessage .= 'No user is in pinValidationRequired Login State.';
				}
				if ($this->aErrorMessageParameters['parameter'] == 'pinInvalid') {
					$this->sHttpErrorCode = '403';
					$this->sErrorMessage .= 'Pin is incorrect.';
				}
				if ($this->aErrorMessageParameters['parameter'] == 'pinExpired') {
					$this->sHttpErrorCode = '403';
					$this->sErrorMessage .= 'Pin expired.';
				}
				break;
		}
	}

}

?>

