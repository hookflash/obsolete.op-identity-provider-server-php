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
 * Class FederatedLogin provides all the needed features for the federated identities login scenarios
 * (INFO: An identity that is provided by this identity provider
 *  and is being identified with username/password combination is considered as federated)
 */
class FederatedLogin {

	public $DB = null;
	public $sIdentityType = 'federated';
	public $aRequest = null;
	public $oUser = null;

	/**
	 * A constructor setting all the needed instance variables
	 *
	 * @param $DB mysql database
	 * @param $sIdentityType should always be 'federated'
	 * @param $aRequest The request that has all the needed data to perform a login
	 */
	public function __construct($DB, $sIdentityType, $aRequest) {
		$this->DB = $DB;
		$this->sIdentityType = $sIdentityType;
		$this->aRequest = $aRequest;
		
		$this->oUser = new User($DB);		
	}
	
	/**
	 * Logs the user in with a given federated identity
	 *
	 * @return array $aLoginResult Returns array of data to be returnd to the client that performed the login request in the first place
	 */
	public function login() {
		// Take data from the request
		$aRequestData = RequestUtil::takeLoginRequestData($this->aRequest);
		if ( key_exists( 'reloginKey', $aRequestData['identity'] ) ) {
			$aRequestData = $this->oUser->fetchIdentityBasedOnReloginKey($aRequestData);
		}
		
		// Try logging the user in using the given identity
		$aUser = $this->oUser->signInUsingFederated($aRequestData['identity']['identifier']);
		
		// Return 'No such identity' error code
		if ($aUser['user_id'] == '') {
			throw new RestServerException('003', array(
												 'type' => $aRequestData['identity']['type'],
												 'identifier' => $aRequestData['identity']['identifier']
												 ));
		}
		
		// Validate the client that is performing the login request
		if ( ( key_exists('reloginValidated', $aRequestData['identity']) && !$aRequestData['identity']['reloginValidated']) ||
			 !key_exists('reloginValidated', $aRequestData['identity']) ) {
			$bServerLoginProofValidity = $this->validateClientPerformingLogin ( $aRequestData['proof']['serverNonce'], 
																				$aRequestData['identity']['identifier'],
																				$aUser['password_hash'],
																				$aUser['secret_salt'],
																				$aUser['server_password_salt'],
																				$aRequestData['proof']['serverLoginProof']
																				);
																			
		
			// Return 'Server login proof failed' error code
			if ( !$bServerLoginProofValidity ) {
				throw new RestServerException('007', array(
													 'parameter' => 'serverLoginProof',
													 'parameterValue' => $aRequestData['proof']['serverLoginProof']
													 ));
			}
		}
		
		/**
		 * Due to JS compatibility changes, this should be thrown away, but it is not yet done just in case...
		 */
		/*
		// Generate hosting data
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
		
		// Generate accessToken and accessSecret for logged in identity
		$aIdentityAccessResult = CryptoUtil::generateIdentityAccess($aRequestData['identity']['type'], $aRequestData['identity']['identifier']);
		$aIdentityAccessResult['updated'] = $aUser['updated'];
		
		// Generate and store a federated relogin key if needed, otherwise return the same reloginKey that has just been used
		$sReloginKey = key_exists('reloginKey',$aRequestData['identity']) ?
						$aRequestData['identity']['reloginKey'] : $this->oUser->generateAndStoreReloginKey($aRequestData['identity']);
		$aIdentityAccessResult['reloginKey'] = $sReloginKey;
				
		// Keep info about logged in identity in session
		$_SESSION['logged-in-identity'] = $aRequestData['identity'];
		
		// Since everything went well, return no error code and fill loginResult with the rest of the data
		return array(
		'identity'		=> $aIdentityAccessResult,
		'lockboxReset'	=> 'false'
		);
	}
	
	/**
	 * Create new federated account with the given identity, passwordHash, identitySecretSalt and serverPasswordSalt
	 *
	 * @return array Returns just an error indicator. It's value 'none' indicates a successfull sign up.
	 */
	public function signUp() {
		// Take data from the request
		$aRequestData = RequestUtil::takeSignUpRequestData($this->aRequest);
		
		// Adding profile data to the request data
		require_once (APP . 'php/main/utils/profileUtil.php');
		$aRequestData['identity']['profile'] = ProfileUtil::PROFILE_ULR_BASE . $aRequestData['identity']['identifier'];
		$aRequestData['identity']['vprofile'] = ProfileUtil::VPROFILE_ULR_BASE . '&' . $aRequestData['identity']['identifier'];
		
		// Try creating new user with given parameters
		$aSignUpResult = $this->oUser->signUp( 'federated',
  											   $aRequestData['identity']['identifier'], 
											   $aRequestData['identity']['passwordHash'], 
											   $aRequestData['identity']['secretSalt'], 
											   $aRequestData['identity']['serverPasswordSalt'],
											   $aRequestData['identity']['displayName'],
											   $aRequestData['identity']['profile'],
											   $aRequestData['identity']['vprofile'],
											   $aRequestData['identity']['avatars']
											   );									
		// Return 'Identity already exists' error
		if ( !$aSignUpResult['signUpSucceeded']) {			
			throw new RestServerException('004', array(
												 'type' => 'federated',
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
												 'message' => $aIdentityUpdateResult['error']['reason']
												 ));
		}
		*/
		
		return true;
	}
	
	//--------------------------------------------------------------------------------------------------------------------------//
	
	/*-------------------
	  Private functions
	-------------------*/
	
	private function validateClientPerformingLogin ( $sServerNonce, $sIdentifier, $sPasswordHash, $sIdentitySecretSalt, $sServerPasswordSalt, $sServerLoginProof ) {
		$bServerNonceValidity = CryptoUtil::validateServerNonce($sServerNonce);
		if ( !$bServerNonceValidity ) {
			return false;
		}
		
		$bServerLoginProofValidity = CryptoUtil::validateServerLoginProof(  $sIdentifier,
																			$sPasswordHash,
																			$sIdentitySecretSalt,
																			$sServerPasswordSalt,
																			$sServerNonce,
																			$sServerLoginProof);
				
		return $bServerLoginProofValidity;
	}
	
}

?>