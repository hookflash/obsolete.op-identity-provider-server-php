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


// Set time
date_default_timezone_set('UTC');

// Set required imports
require (APP . 'php/main/utils/loginUtil.php');
require (APP . 'php/main/identity/user.php');
require_once (APP . 'php/main/utils/cryptoUtil.php');
require_once (APP . 'php/main/utils/requestUtil.php');

/**
 * Class LegacyLogin provides all the needed features for the email/phone number login scenarios
 */
class LegacyLogin {

	public $DB = null;
	public $sIdentityType = '';
	public $aRequest = null;
	public $oUser = null;

	/**
	 * A constructor setting all the needed instance variables
	 *
	 * @param $DB mysql database
	 * @param $sIdentityType can be either 'email' or 'phone'
	 * @param $aRequest The request that has all the needed data to perform a login
	 */
	public function __construct($DB, $sIdentityType, $aRequest) {
		$this->DB = $DB;
		$this->sIdentityType = $sIdentityType;
		$this->aRequest = $aRequest;
		
		$this->oUser = new User($DB);
	}
	
	/**
	 * Logs the user in with a given 'legacy' identity
	 *
	 * @return array Returns array of data which consists of data about user from database and
	 * data received as a result of hosted-identity-login-confirm request.
	 */
	public function login() {		
		// Take data from the request (or session, in case of a post pin validation login)
		$aRequestData = RequestUtil::takeLoginRequestData($this->aRequest);
		if ( isset( $aRequestData['afterPinValidation'] ) && $aRequestData['afterPinValidation'] == 'true' ) {
			$aRequestData = $_SESSION['requestData'];
		}
		
		// Try fetching the identity from local database and if there is no user with the given identity return no such user error
		// (wich should indicate that such user need first to be signed up).
		$aUserFromLocalDatabase = $this->fetchLocalUser( $aRequestData['identity']['type'], $aRequestData['identity']['identifier'] );
		
		// Try fetching the user from lookup service
		$aUserFromLookup = $this->fetchLookupUser( $aRequestData['identity']['type'], $aRequestData['identity']['identifier'] );
		
		// Set up scenarioDistinguish switch
		$sLoginScenario = $this->distinguishScenario( $aRequestData, $aUserFromLocalDatabase, $aUserFromLookup );
		// Return 'Login failed' error since there is no login scenario that is appropriate for the given situation
		if ( $sLoginScenario == 'undefined' ) {
			throw new RestServerException('006', array(
												 'reason' => 'undefinedScenario'
												 ));
		}
		// Switch the login execution based on scenario
		$aScenarioSpecificLoginResult = array();
		switch ( $sLoginScenario ) {
			case 'freshStart-freshAccount-unverified':
			case 'just-pin-validated':
				$aScenarioSpecificLoginResult = $this->startScenario_NoPinValidationRequired ( $aRequestData, $aUserFromLocalDatabase );
				break;
			// These three cases are pretty much the same when it comes to the identity provider server...
			case 'freshStart-freshAccount-lookup':
			case 'freshStart-existingAccount-unverified':
			case 'freshStart-existingAccount-verified':
			case 'force-pin-validation':
				$aScenarioSpecificLoginResult = $this->startScenario_PinValidationRequired ( $aRequestData, $aUserFromLocalDatabase );
				break;
		}
		
		// Set the account_accessed parameter of the identity that is about to log in to 'true' if needed
		$this->oUser->setLegacyAccountAccessed ( $aRequestData['identity']['type'], $aRequestData['identity']['identifier'] );
		
		// Since everything went well, return the data
		return $aScenarioSpecificLoginResult;
	}
	
	/**
	 * Create new legacy account with the given identity, passwordHash, identitySecretSalt and serverPasswordSalt
	 *
	 * @return array Returns just an error indicator. It's value 'none' indicates a successsfull sign up.
	 */
	public function signUp() {	
		// Take data from the request
		$aRequestData = RequestUtil::takeSignUpRequestData($this->aRequest);
		
		// Try creating new user with given parameters
		$aSignUpResult = $this->oUser->signUp( $aRequestData['identity']['type'], 
											   $aRequestData['identity']['identifier'], 
											   $aRequestData['identity']['passwordHash'], 
											   $aRequestData['identity']['secretSalt'], 
											   $aRequestData['identity']['serverPasswordSalt']
											   );									
		
		// Return 'Identity already exists' error
		if ( !$aSignUpResult['signUpSucceeded']) {
			throw new RestServerException('004', array(
												 'type' => $aRequestData['identity']['type'],
												 'identifier' => $aRequestData['identity']['identifier']
												 ));
		}
		
		/**
		 * Due to JS compatibility changes, this should be thrown away, but it is not yet done just in case...
		 */
		/*
		// Inform the identity service about the identity registration
		// Generate hosting data
		$aHostingData = LoginUtil::generateHostingData('hosted-identity-update');
		
		// Send hookflash-login-confirm request to the IdentityService server
		$aIdentityUpdateResult = LoginUtil::sendHostedIdentityUpdate( CryptoUtil::generateRequestId(), $aRequestData, $aHostingData, $aSignUpResult );
		
		// Return 'Login failed' error
		if ( $aIdentityUpdateResult == null || key_exists( 'error', $aIdentityUpdateResult ) ) {
			throw new RestServerException('005', array(
												 'message' => $aIdentityUpdateResult['error']['reason']['#text']
												 ));
		}
		*/
		
		// Keep sign-up indicator in the session
		$_SESSION['just-signed-up'] = true;		
		
		return true;
	}
	
	//--------------------------------------------------------------------------------------------------------------------------//
	
	/*-----------------------------
	  Scenario-specific functions
	-----------------------------*/
	
	private function startScenario_NoPinValidationRequired ( $aRequestData, $aUser ) {
		// Validate the client that is performing the login request
		$bServerLoginProofValidity = $this->validateClientPerformingLogin ( $aRequestData['proof']['serverNonce'], 
																			$aRequestData['identity']['identifier'],
																			$aUser['password_hash'],
																			$aUser['secret_salt'],
																			$aUser['server_password_salt'],
																			$aRequestData['proof']['serverLoginProof']
																			);																			
		// Return 'Server login proof failed' error
		if ( !$bServerLoginProofValidity ) {
			throw new RestServerException('007', array(
												 'parameter' => 'serverLoginProof',
												 'parameterValue' => $aRequestData['proof']['serverLoginProof']
												 ));
		}
		
		/*
		// Generate data that should be generated now
		$aHostingData = LoginUtil::generateHostingData('hosted-identity-login-confirm');
		// Send hookflash-login-confirm request to the IdentityService server
		$aLoginConfirmResult = LoginUtil::sendHostedLoginConfirm( CryptoUtil::generateRequestId(), $aRequestData, $aHostingData, $aUser );
		// Return 'Login failed' error
		if ( $aLoginConfirmResult == null || key_exists( 'error', $aLoginConfirmResult ) ) {
			throw new RestServerException('005', array(
												 'message' => is_array($aLoginConfirmResult['error']['reason']) ?
												 			  $aLoginConfirmResult['error']['reason']['#text'] :
												 			  $aLoginConfirmResult['error']['reason']
												 ));
		}
		*/
		
		// Generate and store accessToken and accessSecret for logged in identity
		$aIdentityAccessResult = CryptoUtil::generateIdentityAccess($aRequestData['identity']['type'], $aRequestData['identity']['identifier']);
		$aIdentityAccessResult['updated'] = $aUser['updated'];
		
		// Keep info about logged in identity in session
		$_SESSION['logged-in-identity'] = $aRequestData['identity'];
		
		// Since everything went well, return the result
		return array(
		'identity'		=> $aIdentityAccessResult,
		'lockboxReset'	=> 'true'
		);
	}
	
	private function startScenario_PinValidationRequired ( $aRequestData, $aUser ) {		
		// Validate the client that is performing the login request
		$bServerLoginProofValidity = $this->validateClientPerformingLogin ( $aRequestData['proof']['serverNonce'], 
																			$aRequestData['identity']['identifier'],
																			$aUser['password_hash'],
																			$aUser['secret_salt'],
																			$aUser['server_password_salt'],
																			$aRequestData['proof']['serverLoginProof']
																		   );																			
		// Return 'Server login proof failed' error
		if ( !$bServerLoginProofValidity ) {
			throw new RestServerException('007', array(
												 'parameter' => 'serverLoginProof',
												 'parameterValue' => $aRequestData['proof']['serverLoginProof']
												 ));
		}
		
		// Try generating new PIN
		require_once( APP . 'php/main/identity/pinValidation.php' );
		$oPinValidation = new PinValidation();
		$aPinGenerationResult = $oPinValidation->generatePIN($aUser);
		
		// Store the PIN generation results in the database
		$nAffected = $this->oUser->insertNewPIN( $aRequestData['identity']['type'],
											 	 $aRequestData['identity']['identifier'],
											 	 $aPinGenerationResult['pin'],
											 	 $aPinGenerationResult['pinGenerationDailyCounter'],
											 	 $aPinGenerationResult['nextValidPinGenerationTime'],
											 	 $aPinGenerationResult['expiry']
											 	);
		// Return 'No such idetity' if needed
		if ( $nAffected == 0 || $nAffected == '0' ) {
			throw new RestServerException('003', array(
												 'type' => $aRequestData['identity']['type'],
												 'identifier' => $aRequestData['identity']['identifier']
												 ));
		}
		
		// Send the newly generated PIN to the user via 3rd party service
		//$aSendPinResult = $this->sendPIN( $aRequestData['identity']['type'], $aRequestData['identity']['identifier'], $aPinGenerationResult['pin'] );		
		//print_r($aSendPinResult); die();
		// Store all the data that will be needed for a login to be performed after a successfull pin validation
		$_SESSION['requestData'] = $aRequestData;
		$_SESSION['user'] = $aUser;
		
		// Since everything went well, return the result
		return array (
		'pinExpiry'						=> $aPinGenerationResult['expiry'],
		'nextValidPinGenerationTime'	=> $aPinGenerationResult['nextValidPinGenerationTime'],
		'pinGenerationCooldown'			=> isset($aPinGenerationResult['pinGenerationCooldown']) ? $aPinGenerationResult['pinGenerationCooldown'] : '',
		'pin'							=> $aPinGenerationResult['pin']
		);
	}
	
	/*------------------
	  Common functions
	------------------*/
	
	private function fetchLocalUser ( $sIdentityType, $sIdentifier ) {		
		// Try fetching the user from local database
		$aUser = $this->oUser->signInUsingLegacy($sIdentityType, $sIdentifier);
		if ( !$aUser && $aUser == null ) {
			throw new RestServerException('003', array(
												 'type' => $sIdentityType,
												 'identifier' => $sIdentifier
												 ));
		}		
		return $aUser;
	}
	
	private function fetchLookupUser ( $sIdentityType, $sIdentifier ) {
		if ( isset( $_SESSION['just-signed-up'] ) && $_SESSION['just-signed-up'] ) {
			// Identity is being added to lookup upon sign-up, so it will always be returned.
			// So in a case of an identity that is just signed up, we don't even check the lookup.
			return null;
		} else {
			// Try fetching the user from Identity Lookup service
			$aUser = LoginUtil::sendIdentityLookup( CryptoUtil::generateRequestId(), $sIdentityType, $sIdentifier );
			if ( $aUser != null && key_exists( 'error', $aUser ) ) {
				throw new RestServerException('005', array(
													 'message' => $aUser['error']['reason']
													 ));
			}
			return $aUser;
		}
	}
	
	private function distinguishScenario ( $aRequestData, $aUserFromLocalDatabase, $aUserFromLookup ) {
		$aScenarioDistinguishSwitch = array (
		'lookupIdentity' 	=> ( ( $aUserFromLookup['identities']['identity'] != null ) ? 'true' : 'false' ),
		'accountAccessed' 	=> ( ( $aUserFromLocalDatabase['account_accessed'] == '1' ) ? 'true' : 'false' ),
		'pinValidated' 		=> ( ( $aUserFromLocalDatabase['pin_validated'] == '1' ) ? 'true' : 'false' ),
		);
		
		// TODO (This just for the first version of code)
		if ( isset( $_SESSION['pinValidated'] ) && $_SESSION['pinValidated'] ) {
			return 'just-pin-validated';
		} else {
			return 'force-pin-validation';
		}
		
		/*
		if ( isset( $_SESSION['pinValidated'] ) && $_SESSION['pinValidated'] ) {
			return 'just-pin-validated';
		} 
		elseif ( $aScenarioDistinguishSwitch['lookupIdentity'] == 'false' &&
				 $aScenarioDistinguishSwitch['accountAccessed'] == 'false' &&
				 $aScenarioDistinguishSwitch['pinValidated'] == 'false'
				)
		{
			return 'freshStart-freshAccount-unverified';
		}
		elseif ( $aScenarioDistinguishSwitch['lookupIdentity'] == 'true' &&
			 	 $aScenarioDistinguishSwitch['accountAccessed'] == 'false' &&
			 	 $aScenarioDistinguishSwitch['pinValidated'] == 'false'
				)
		{
			return 'freshStart-freshAccount-lookup';
		}
		elseif ( $aScenarioDistinguishSwitch['lookupIdentity'] == 'true' &&
			 	 $aScenarioDistinguishSwitch['accountAccessed'] == 'true' &&
			 	 $aScenarioDistinguishSwitch['pinValidated'] == 'false'
				)
		{
			return 'freshStart-existingAccount-unverified';
		}
		elseif ( $aScenarioDistinguishSwitch['lookupIdentity'] == 'true' &&
			 	 $aScenarioDistinguishSwitch['accountAccessed'] == 'true' &&
			 	 $aScenarioDistinguishSwitch['pinValidated'] == 'true'
				)
		{
			return 'freshStart-existingAccount-verified';
		}
		
		return 'undefined';
		*/
	}
	
	private function sendPIN( $sIdentityType, $sIdentifier, $sPIN ) {
		switch ( $sIdentityType ) {
			case 'email':
				
				require ( APP . 'php/main/utils/smtpUtil.php');
				$bSendPinValidationEmailResult = SmtpUtil::sendPinValidationEmail($sIdentifier, $sPIN);
				
				// If anything went wrong set 'PIN sending failed' error indicator
				if (!$bSendPinValidationEmailResult) {
					throw new RestServerException('020', array(
															   'parameter' => 'pinSendingFailed'
															   ));
				}
				
				break;
			case 'phone':
				
				require ( APP . 'php/main/utils/smsUtil.php');
				$bSendPinValidationEmailResult = SmsUtil::sendPinValidationEmail($sIdentifier, $sPIN);
				
				// If anything went wrong set 'PIN sending failed' error indicator
				if (!$bSendPinValidationEmailResult) {
					throw new RestServerException('020', array(
															   'parameter' => 'pinSendingFailed'
															   ));
				}
				
				break;
		}		
		return $aSendPinResult;
	}
	
	private function validateClientPerformingLogin ( $sServerNonce, $sIdentifier, $sPasswordHash, $sIdentitySecretSalt, $sServerPasswordHash, $sServerLoginProof ) {
		$bServerNonceValidity = CryptoUtil::validateServerNonce($sServerNonce);
		if ( !$bServerNonceValidity ) {
			return false;
		}
		
		$bServerLoginProofValidity = CryptoUtil::validateServerLoginProof($sIdentifier, $sPasswordHash, $sIdentitySecretSalt, $sServerPasswordHash, $sServerNonce, $sServerLoginProof);
		if ( !$bServerLoginProofValidity ) {
			return false;
		}
		
		return true;
	}
	
}

?>