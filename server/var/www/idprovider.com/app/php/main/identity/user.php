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


function OUR_hex2bin($h)
  {
  if (!is_string($h)) return null;
  $r='';
  for ($a=0; $a<strlen($h); $a+=2) { $r.=chr(hexdec($h{$a}.$h{($a+1)})); }
  return $r;
}

function OUR_bin2hex($str) {
    $hex = "";
    $i = 0;
    do {
        $hex .= sprintf("%02x", ord($str{$i}));
        $i++;
    } while ($i < strlen($str));
    return $hex;
}

/**
 * Class User is responsible for storage and management of the users and their identities
 * using common database techniques.
 */
class User {

	public $DB;

	/**
	 * Setting only the database
	 *
	 * @param $DB mysql database
	 */
	public function __construct($DB) {
		$this->DB = $DB;
	}
	
	/**
	 * Try getting the identitySecretSalt and the serverPasswordSalt of a user from the database
	 * and return these two as a result 
	 *
	 * @param string $sIdentityType could be: federated, email, phone, linkedin, facebook, twitter
	 * @param string $sIdentifier An identity to fetch the salts for
	 * @return array Returns the salts of the given identity
	 */
	public function getIdentitySalts ( $sIdentityType, $sIdentifier ) {
		global $DB;
		$aSalts = array();
		
		// Decide what table to look at
		$sDBTable = $this->getAppropriateDatabaseTable($sIdentityType);
		
		// Get the identity from the database
		if ( $sDBTable != 'legacy_oauth' ) {
			$aIdentity = $DB->select_single_to_array($sDBTable, '*', 'where identifier="' . $sIdentifier . '"');
		} else {
			$aIdentity = $DB->select_single_to_array($sDBTable, '*', 'where provider_type="' . $sIdentityType . '" and identifier="' . $sIdentifier . '"');
		}
		
		if ( $aIdentity == null || empty($aIdentity) ) {
			// Generate and return a serverPasswordSalt to be used in incomming sign-up request 
			require_once(APP . 'php/main/utils/cryptoUtil.php');
			$aSalts['serverPasswordSalt'] = CryptoUtil::generateServerPasswordSalt();
		} else {
			// Since everything went well, return no error code and fill identitySaltsResult with the rest of the data
			$aSalts['serverPasswordSalt'] = $aIdentity['server_password_salt'];
			$aSalts['secretSalt'] = $aIdentity['secret_salt'];
			require_once(APP . 'php/main/utils/cryptoUtil.php');			
		}
		$aSalts['reloginEncryptionKey'] = CryptoUtil::gimmeHash(PROVIDER_MAGIC_VALUE . '-' . $sIdentifier);
		
		return $aSalts;
	}
	
	/**
	 * Returns the public profile information of a given user
	 *
	 * @param Request $oRequest a request to get the user data from
	 * @return array Returns the profile data in an array
	 */
	public function getPublicProfile ( $oRequest ) {
		global $DB;
		
		// Take data from the request
		$aRequestData = RequestUtil::takeProfileGetRequestData($oRequest);
		
		// Challange user's existance
		if ( !$this->isThereSuchUser('federated', $aRequestData['identity']['identifier']) ) {
			throw new RestServerException('003', array(
                                                                    'type' => 'federated',
                                                                    'identifier' => $aRequestData['identity']['identifier']
                                                                    ));
		}
		
		// Get the data out of the db
		$aIdentity = $DB->select_single_to_array('federated', '*', 'where identifier="' . $aRequestData['identity']['identifier'] . '"');
		$aAvatars = $DB->select_to_array('avatar', '*', 
										 'where identity_type="' . 'federated' . '" and identity_id="' . $aRequestData['identity']['identifier'] . '"');
		$nAvatar = 0;
		$aAvatarsInProfile = array();
		while ( isset($aAvatars[$nAvatar]) ) {
			$aAvatar = array(
			'name' 		=> $aAvatars[$nAvatar]['name'],
			'url' 		=> $aAvatars[$nAvatar]['url'],
			'width' 	=> $aAvatars[$nAvatar]['width'],
			'height' 	=> $aAvatars[$nAvatar]['height'],
			);
			array_push($aAvatarsInProfile, $aAvatar);
		}
		$aProfile = array (
		'identifier'	=> $aIdentity['identifier'],
		'profile'		=> $aIdentity['profile_url'],
		'displayName' 	=> $aIdentity['display_name'],
		'avatars'		=> $aAvatarsInProfile
		);
		return $aProfile;
		
	}
	
	/**
	 * Updates the profile of a logged in identity
	 *
	 * @param Request $oRequest The request that hit the server
	 */
	public function updateProfile ( $oRequest ) {
		global $DB;
		
		// Take datat from the request
		$aRequestData = RequestUtil::takeProfileUpdateRequestData($oRequest);
		
		// Challange user's existance
		if ( !$this->isThereSuchUser('federated', $aRequestData['identity']['identifier']) ) {
			throw new RestServerException('003', array(
												 'type' => 'federated',
												 'identifier' => $aRequestData['identity']['identifier']
												 ));
		}
		
		// Check if the user that is trying to update it's profile is logged in at the moment, 'cause it has to be...
		if ( !( isset( $_SESSION['logged-in-identity'] ) ) || ( $_SESSION['logged-in-identity']['identifier'] != $aRequestData['identity']['identifier'] ) ) {
			throw new RestServerException('009', array(
												 'identifier' => $aRequestData['identity']['identifier']
												 ));
		}
		
		// Update the federated table data
		if ( isset( $aRequestData['identity']['displayName'] ) ) {
			$DB->update( 'federated',
						array (
						  	'display_name' 	=> $aRequestData['identity']['displayName'],
						  	'updated'		=> time()
						),
						'where identifier="' . $aRequestData['identity']['identifier'] . '"'
			);
		}
		// Update the avatars table data
		$aIdentity = $DB->select_single_to_array('federated', 'federated_id', 'where identifier="' . $aRequestData['identity']['identifier'] . '"');
		$aCouldNotAdd = array();
		if ( !(empty( $aRequestData['identity']['avatars'] )) ) {
			$nAvatar = 0;
			while ( isset($aRequestData['identity']['avatars'][$nAvatar]) ) {
				$aAvatarFromDB = $DB->select_single_to_array('avatar', '*', 'where url="' . $aRequestData['identity']['avatars'][$nAvatar]['url'] . '"');
				if ( $aAvatarFromDB == null ) {
					$DB->insert('avatar', array (
											'identity_id' => $aIdentity['federated_id'],
											'name' => $aRequestData['identity']['avatars'][$nAvatar]['name'],
											'url' => $aRequestData['identity']['avatars'][$nAvatar]['url'],
											'width' => $aRequestData['identity']['avatars'][$nAvatar]['width'],
											'height' => $aRequestData['identity']['avatars'][$nAvatar]['height'],
											) 
					);				
				} else {
					array_push($aCouldNotAdd, $aRequestData['identity']['avatars'][$nAvatar]['url']);
				}
				$nAvatar++;
			}
		}
		$aCouldNotDelete = array();
		if ( !(empty( $aRequestData['identity']['removeAvatars'] )) ) {
			$nAvatar = 0;
			while ( isset($aRequestData['identity']['removeAvatars'][$nAvatar]) ) {
				$deleted = $DB->delete('avatar', 'where identity_id="' . $aIdentity['federated_id'] . '" and url="' . $aRequestData['identity']['removeAvatars'][$nAvatar]['url'] . '"');
				if ( !$deleted ) {
					array_push($aCouldNotDelete, $aRequestData['identity']['removeAvatars'][$nAvatar]['url']);
				}
				$nAvatar++;
			}
		}
		
		// Update the identity service
		$this->updateIdentityService($aRequestData, $DB);
		
		return array(
		'couldNotAdd'		=> $aCouldNotAdd,
		'couldNotDelete'	=> $aCouldNotDelete
		);
	}
	
	/**
	 * TODO
	 *
	 * @param unknown_type $sIdentityType
	 * @param unknown_type $sIdentifier
	 * @return unknown
	 */
	public function changePassword ( $oRequest ) {
		global $DB;
		
		// Take datat from the request
		$aRequestData = RequestUtil::takePasswordChangeRequestData($oRequest);
		
		// Challange user's existance
		if ( !$this->isThereSuchUser('federated', $aRequestData['identity']['identifier']) ) {
			throw new RestServerException('003', array(
												 'type' => 'federated',
												 'identifier' => $aRequestData['identity']['identifier']
												 ));
		}
		
		// Check if the user that is trying to update it's profile is logged in at the moment, 'cause it has to be...
		if ( !( isset( $_SESSION['logged-in-identity'] ) ) || ( $_SESSION['logged-in-identity']['identifier'] != $aRequestData['identity']['identifier'] ) ) {
			throw new RestServerException('009', array(
												 'identifier' => $aRequestData['identity']['identifier']
												 ));
		}
		
		// Get the identity from the database
		$aIdentity = $DB->select_single_to_array('federated', '*', 'where identifier="' . $aRequestData['identity']['identifier'] . '"');
		
		// Challange the password - just a little more security
		if ( $aRequestData['identity']['passwordHash'] != $aIdentity['password_hash'] ) {
			throw new RestServerException('007', array(
 												 'parameter'		=> 'passwordHash',													
												 'parameterValue' 	=> $aRequestData['identity']['passwordHash']
												 ));
		}
		
		// Change the password hash
		$time = time();
		$DB->update('federated', array( 'password_hash' => $aRequestData['identity']['newPasswordHash'], 'updated' => $time ),
					'where identifier="' . $aRequestData['identity']['identifier'] . '"');
		$aIdentity['updated'] = $time;
		
		// Generate hosting data
		require_once(APP . 'php/main/utils/loginUtil.php');
		$aHostingData = LoginUtil::generateHostingData('hosted-identity-login-confirm');
		
		// Send hookflash-login-confirm request to the IdentityService server
		$aRequestData['identity']['secretSalt'] = $aIdentity['secret_salt'];
		$aRequestData['identity']['secretEncrypted'] = $aIdentity['secret_encrypted'];
		$aLoginConfirmResult = LoginUtil::sendHostedLoginConfirm( CryptoUtil::generateRequestId(), $aRequestData, $aHostingData, $aIdentity, true );
		
		// Return 'Login failed' error
		if ( $aLoginConfirmResult == null || key_exists( 'error', $aLoginConfirmResult ) ) {
			throw new RestServerException('005', array(
												 'message' => $aLoginConfirmResult['error']['reason']
												 ));
		}
		
		// Keep info about logged in identity in session
		$_SESSION['logged-in-identity'] = $aRequestData['identity'];
		
		// Since everything went well, return no error code and fill loginResult with the rest of the data
		return array_merge( $aHostingData,
							$aLoginConfirmResult,
							array ( 'updated' => $aIdentity['updated'] )
						   );
	}
	
	/**
	 * Checks if there is a user in database that is suitable
	 *
	 * @param string $sIdentityType Could be: federated, email, phone, linkedin, facebook, twitter
	 * @param string $sIdentifier Identity to look for
	 * @return boolean Returns true if there is such iser, otherwise returns false
	 */
	public function isThereSuchUser ( $sIdentityType, $sIdentifier ) {
		global $DB;
		
		// Decide what table to look at
		$sDBTable = $this->getAppropriateDatabaseTable($sIdentityType);
		
		// Try getting the identity from the database
		if ( $sDBTable != 'legacy_oauth' ) {
			$aIdentity = $DB->select_single_to_array($sDBTable, '*', 'where identifier="' . $sIdentifier . '"');
		} else {
			$aIdentity = $DB->select_single_to_array($sDBTable, '*', 'where provider_type="' . $sIdentityType . '" and identifier="' . $sIdentifier . '"');
		}
		
		if ( empty($aIdentity) ) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Create new identity and attach it to new user.
	 *
	 * @param string $sIdentityType Could be: federated, email, phone, linkedin, facebook, twitter
	 * @param string $sIdentifier Identity to create
	 * @param string $sPasswordHash Hash of the password to store in the database
	 * @param string $sIdentitySecretSalt Identity secret salt to store
	 * @param string $sServerPasswordSalt Server password salt to store
	 * @return boolean Returns true if the identity is successfully created and stored, otherwise returns false
	 */
	public function signUp ( $sIdentityType, $sIdentifier, $sPasswordHash, $sIdentitySecretSalt, $sServerPasswordSalt,
				$sDisplayName = null, $sProfile = null, $sVProfile = null, $aAvatars = null, $appid = '' ) {
		global $DB;
		$aUser = array (
		'signUpSucceeded' => false,
		);
		
		// Decide what table to look at
		$sDBTable = $this->getAppropriateDatabaseTable($sIdentityType);
		
		// Check if the identifier is alredy taken, and if so, return false
		$aIdentity = $DB->select_single_to_array($sDBTable, '*', 'where identifier="' . $sIdentifier . '"');
		if ($aIdentity['user_id'] != '') {
			return $aUser;
		}
		
		// Distinguish federated and legacy identity type insertions
		$sUpdated = time();
		if ( $sDBTable == 'federated' ) {
			$sUser = $DB->insert('user', array( 'appid' => $appid, 'updated' => $sUpdated ) );
			$DB->insert($sDBTable, array(
                                                    'identifier' => $sIdentifier,
                                                    'user_id' => $sUser,
                                                    'password_hash' => $sPasswordHash,
                                                    'secret_salt' => $sIdentitySecretSalt,
                                                    'server_password_salt' => $sServerPasswordSalt,
                                                    'updated' => $sUpdated,
                                                    'display_name' => $sDisplayName,
                                                    'profile_url' => $sProfile,
                                                    'vprofile_url' => $sVProfile
                                                    )
						);
			$aIdentity = $DB->select_single_to_array($sDBTable, '*', 'where identifier="' . $sIdentifier . '"');
			$sIdentityTypeSpecificIdentityIdFieldName = $this->getAppropriateIdentityIdFieldName($sIdentityType);
			$nAvatar = 0;
			while ( isset($aAvatars[$nAvatar]) ) {
				$DB->insert('avatar', array (
											'identity_id' => $aIdentity["$sIdentityTypeSpecificIdentityIdFieldName"],
											'name' => $aAvatars[$nAvatar]['name'],
											'url' => $aAvatars[$nAvatar]['url'],
											'width' => $aAvatars[$nAvatar]['width'],
											'height' => $aAvatars[$nAvatar]['height'],
											) 
							);
				$nAvatar++;
			}
		} else if ( $sDBTable == 'legacy_email' || $sDBTable == 'legacy_phone' ) {
			$sUser = $DB->insert('user', array( 'updated' => $sUpdated ) );
			$DB->insert($sDBTable, array(
										 'identifier' => $sIdentifier,
										 'user_id' => $sUser,
										 'password_hash' => $sPasswordHash,
										 'secret_salt' => $sIdentitySecretSalt,
										 'server_password_salt' => $sServerPasswordSalt,
										 'updated' => $sUpdated
										 )
						);
		}
		
		$aUser['signUpSucceeded'] = true;
		$aUser['updated'] = $sUpdated;					
		return $aUser;
	}
	
	/**
	 * Get the identity based on relogin key
	 * 
	 * @param array $aRequestData request data
	 * @return array $aRequestData Returns request data filled in using all needed stuff to go and login
	 */
	public function fetchIdentityBasedOnReloginKey ( $aRequestData ) {
		// Usage of globals
		global $DB;
		
		// Interpret relogin key
		$aReloginKey = explode('-',$aRequestData['identity']['reloginKeyServerPart']);
                // TODO replace CryptoUtil::hexToStr() with hex2bin() as soon as PHP 5.4. is installed
		$sReloginKey = CryptoUtil::decrypt(CryptoUtil::hexToStr($aReloginKey[2]), CryptoUtil::gimmeHash($aReloginKey[1]), PROVIDER_MAGIC_VALUE);
		$aReloginKeyData = explode('-',$sReloginKey);
		$aRequestData['identity']['type'] = $aReloginKey[0];
		$aRequestData['identity']['identifier'] = $aReloginKey[1];
		
		// Decide what table to look at
		$sDBTable = $this->getAppropriateDatabaseTable($aRequestData['identity']['type']);
		
		// Validate relogin
		$aIdentity = $DB->select_single_to_array($sDBTable, '*', 'where identifier="' . $aReloginKey[1] . '"');
		$this->validateReloginAccess($aReloginKeyData,$aIdentity);
		
		// Fill data inside $aRequestData
		$aRequestData['identity']['reloginValidated'] = true;
		return $aRequestData;
	}
	
	/**
	 * Generate and store relogin key
	 *
	 * @param unknown_type $aIdentity
	 */
	public function generateAndStoreReloginKey ( $aIdentity ) {
		// Usage of globals
		global $DB;
		
		// Generate the relogin key
		$sReloginKey = CryptoUtil::generateNonce();
		$sNow = time();
		$sReloginKeyExires = $sNow + 60 * 60 * 24 * 30; // 30 days long
		
		// Decide what table to look at
		$sDBTable = $this->getAppropriateDatabaseTable($aIdentity['type']);
		
		// Update the db
		$DB->update($sDBTable,
					array (
					'relogin_key'		=> $sReloginKey,
					'relogin_expires'	=> $sReloginKeyExires,
					'updated'			=> $sNow
					),
					'where identifier="' . $aIdentity['identifier'] . '"');
					
		$sReloginKeyInnerEncription = CryptoUtil::encrypt($sReloginKey . '-' . $sReloginKeyExires,
				CryptoUtil::gimmeHash($aIdentity['identifier']), PROVIDER_MAGIC_VALUE);
		$sReloginKeyString = $aIdentity['type'] . '-' . $aIdentity['identifier'] . '-' . bin2hex($sReloginKeyInnerEncription);
				
		return $sReloginKeyString;
	}
	
	/**
	 * Get the identity data out of the database
	 *
	 * @param string $sIdentifier An identity to be logged in
	 * @return array $aFederatedIdentity Returns an array of data taken from database that is attached to given identity
	 */
	public function signInUsingFederated( $sIdentifier, $appid ) {
		// Usage of globals
		global $DB;
		
		// Try getting an identity using given data, and check if that identity is associated with an existing user
		$aFederatedIdentity = $DB->select_single_to_array('federated', '*', 'where identifier="' . $sIdentifier . '"');
		$aUser = $DB->select_single_to_array('user', '*', 'where user_id="' . $aFederatedIdentity['user_id'] . '"');
		
		// Return the user if everything went well
		if ( !( $aFederatedIdentity && $aUser ) ) {
			return null;		
		}
                
                // Update appid upon every login
                $DB->update( 
                        'user',
                        array (
                            'updated'	=> time(),
                            'appid'     => $appid != '' ? $appid : $aUser['appid']
                        ),
			'where user_id="' . $aFederatedIdentity['user_id'] . '"'
		);
		
		return $aFederatedIdentity;
	}
	
	/**
	 * Get the identity data for legacyOAuth identities out of the database
	 *
	 * @param string $sIdentityType Could be: facebook, twitter or linkedin
	 * @param string $sIdentifier An identity to be logged in
	 * @return array $aLegacyOAuthIdentity Returns an array of data taken from database that is attached to given identity
	 */
	public function signInUsingLegacyOAuth( $sIdentityType, $sIdentifier ) {
		// Usage of globals
		global $DB;
		
		// Try getting an identity using given data, and check if that identity is associated with an existing user
		$aLegacyOAuthIdentity = $DB->select_single_to_array('legacy_oauth', '*',
															'where identifier="' . $sIdentifier . '" and provider_type="' . $sIdentityType . '"');
		$aUser = $DB->select_single_to_array('user', '*', 'where user_id="' . $aLegacyOAuthIdentity['user_id'] . '"');
		
		// Return the user if everything went well
		if ( !( $aLegacyOAuthIdentity && $aUser ) ) {
			return null;		
		}
		
		return $aLegacyOAuthIdentity;
	}
	
	/**
	 * Get the identity data for legacy identities out of the database
	 *
	 * @param string $sIdentityType Could be: email or phone
	 * @param string $sIdentifier An identity to be logged in
	 * @return array $aLegacyIdentity Returns an array of data taken from database that is attached to given identity
	 */
	public function signInUsingLegacy( $sIdentityType, $sIdentifier ) {
		// Usage of globals
		global $DB;
		
		// Decide what table to look at
		$sDBTable = $this->getAppropriateDatabaseTable($sIdentityType);
		
		// Try getting an identity using given data, and check if that identity is associated with an existing user
		$aLegacyIdentity = $DB->select_single_to_array($sDBTable, '*', 'where identifier="' . $sIdentifier . '"');
		$aUser = $DB->select_single_to_array('user', '*', 'where user_id="' . $aLegacyIdentity['user_id'] . '"');
		
		// Return the user if everything went well
		if ( !( $aLegacyIdentity && $aUser ) ) {
			return null;		
		}
		
		return $aLegacyIdentity;		
	}
	
	/**
	 * Updates the PIN and the PIN-specific data in the database, next to the given identity
	 *
	 * @param string $sIdentityType Could be: 'email' or 'phone'
	 * @param string $sIdentifier An identity to update PIN-specific data for
	 * @param string $sPIN A PIN to be added into the database
	 * @param string $sCounter A number of PIN generation requests performed today (purpose : anti spam)
	 * @param string $sNextValidGenerationTime A timestamp that indicates when could user perform next pin generation request in case that ongoing pin validation fails (purpose : anti spam)
	 * @return number $nAffected Returnes the number of affected rows in database.
	 */
	public function insertNewPIN ( $sIdentityType, $sIdentifier, $sPIN, $sCounter, $sNextValidGenerationTime, $sExpiry ) {
		// Usage of globals
		global $DB;
		
		// Decide what table to look at
		$sDBTable = $this->getAppropriateDatabaseTable($sIdentityType);
		
		// Update the identity
		$nAffected = $DB->update( $sDBTable,
								  array (
								  	'temporary_pin' 					=> $sPIN,
								  	'pin_daily_generation_counter' 		=> $sCounter,
								  	'next_valid_pin_generation_time'	=> $sNextValidGenerationTime,
								  	'updated'							=> time(),
								  	'pin_expiry'						=> $sExpiry
								  ),
								  'where identifier="' . $sIdentifier . '"'
		);
		
		return $nAffected;
	}
	
	/**
	 * Sets the account_accessed parameter of the given identity to 'true',
	 * in order to enable scenario differentiation functionality to work correctly.
	 *
	 * @param string $sIdentityType Could be 'email' or 'phone'
	 * @param string $sIdentifier An identity to update the account_accessed parameter
	 * @return number $nAffected Returns the number of affected rows
	 */
	public function setLegacyAccountAccessed ( $sIdentityType, $sIdentifier ) {
		// Usage of globals
		global $DB;
		
		// Decide what table to look at
		$sDBTable = $this->getAppropriateDatabaseTable($sIdentityType);
		
		// Update the account in order to enable scenario differentiation functionality
		$nAffected = $DB->update( $sDBTable,
								  array( 'account_accessed' => 1 ),
								  'where identifier="' . $sIdentifier . '" and account_accessed=0'
								 );
		
		return $nAffected;
	}
	
	public function storeLockboxHalfKeyEncrypted( $oRequest, $sPurpose ) {
		// Usage of globals
		global $DB;
		
		// Take datat from the request
		$aRequestData = RequestUtil::takeLockboxHalfKeyStoreRequestData($oRequest);
		
		// Challange the accessSecretProof
		require_once(APP . 'php/main/utils/cryptoUtil.php');
		$bAccessSecretProofValidity = CryptoUtil::validateIdentityAccessSecretProof($aRequestData['clientNonce'],
																					$aRequestData['identity']['accessToken'],
																					$aRequestData['identity']['accessSecretProof'],
																					$aRequestData['identity']['accessSecretProofExpires'],
																					$aRequestData['identity']['type'],
																					$aRequestData['identity']['identifier'],
																					$aRequestData['identity']['uri'],
																					$sPurpose );
		if ( !$bAccessSecretProofValidity ) {
			throw new RestServerException('007', array(
												 'parameter' 		=> 'accessSecretProof',
												 'parameterValue' 	=> $aRequestData['identity']['accessSecretProof']
												 ));
		}
		
		// Decide what table to look at
		$sDBTable = $this->getAppropriateDatabaseTable($aRequestData['identity']['type']);
		
		// Store the encrypted key half
		$nAffected = $DB->update( $sDBTable,
								  array( 'lockbox_half_key_encrypted' => $aRequestData['lockbox']['keyEncrypted'] ),
								  'where identifier="' . $aRequestData['identity']['identifier'] . '"' );
				
		if ( !$nAffected ) {
			throw new RestServerException('003', array(
												 'type' 		=> $aRequestData['identity']['type'],
												 'identifier' 	=> $aRequestData['identity']['identifier']
												 ));
		}
		return;
	}
	
	/**
	 * TODO
	 *
	 * @param unknown_type $oRequest
	 */
	public function validateIdentityAccess ( $oRequest ) {
		// Usage of globals
		global $DB;
		
		// Take datat from the request
		$aRequestData = RequestUtil::takeIdentityAccessValidateRequestData($oRequest);
		
		// Challange the accessSecretProof
		require_once(APP . 'php/main/utils/cryptoUtil.php');
		
		$aIdentity = $this->parseIdentityUri($aRequestData['identity']['uri']);
		
		$bAccessSecretProofValidity = CryptoUtil::validateIdentityAccessSecretProof($aRequestData['clientNonce'],
                                                                                            $aRequestData['identity']['accessToken'],
                                                                                            $aRequestData['identity']['accessSecretProof'],
                                                                                            $aRequestData['identity']['accessSecretProofExpires'],
                                                                                            $aIdentity['type'],
                                                                                            $aIdentity['identifier'],
                                                                                            $aRequestData['identity']['uri'],
                                                                                            $aRequestData['purpose'] );
		if ( !$bAccessSecretProofValidity ) {
			throw new RestServerException('007', array(
												 'parameter' 		=> 'accessSecretProof',
												 'parameterValue' 	=> $aRequestData['identity']['accessSecretProof']
												 ));
		}
		
		// Since everything went well, just return
		return;
	}
	
	public function getIdentityAccessRolodexCredentials ( $oRequest ) {
		// Usage of globals
		global $DB;
		
		// Take datat from the request
		$aRequestData = RequestUtil::takeIdentityAccessRolodexCredentialsGetRequestData($oRequest);
		
		// Challange the accessSecretProof
		require_once(APP . 'php/main/utils/cryptoUtil.php');
		
		$aIdentity = $this->parseIdentityUri($aRequestData['identity']['uri']);
		
		$bAccessSecretProofValidity = CryptoUtil::validateIdentityAccessSecretProof($aRequestData['clientNonce'],
                                                                                            $aRequestData['identity']['accessToken'],
                                                                                            $aRequestData['identity']['accessSecretProof'],
                                                                                            $aRequestData['identity']['accessSecretProofExpires'],
                                                                                            $aIdentity['type'],
                                                                                            $aIdentity['identifier'],
                                                                                            $aRequestData['identity']['uri'],
                                                                                            'rolodex-credentials-get' );
		if ( !$bAccessSecretProofValidity ) {
			throw new RestServerException('007', array(
                                                            'parameter'         => 'accessSecretProof',
                                                            'parameterValue' 	=> $aRequestData['identity']['accessSecretProof']
                                                            ));
		}
                
                $sDBTable = $this->getAppropriateDatabaseTable($aIdentity['type']);
		
		// Get the identity
		$aIdentityFromDB = $DB->select_single_to_array($sDBTable, '*',
				'where identifier="' . $aIdentity['identifier'] . '"');
		if (!$aIdentityFromDB || empty($aIdentityFromDB)) {
			throw new RestServerException('003', array(
												 'type' 		=> $aIdentity['type'],
												 'identifier' 	=> $aIdentity['identifier']
												 ));
		}
		
		
		$sServerTokenCredentials = $this->generateServerTokenCredentials($aIdentityFromDB);

		// NOTE: This is the implementation compatible with [cifre](https://github.com/openpeer/cifre) used in
		//       [opjs](https://github.com/openpeer/opjs) and [rolodex](https://github.com/openpeer/rolodex).

		require_once(APP . 'php/libs/seclib/Crypt/AES.php');
		$key = hash('sha256', DOMAIN_HOSTING_SECRET);
		$iv = hash('md5', CryptoUtil::generateIv());
		$cipher = new Crypt_AES(CRYPT_AES_MODE_CFB);
		$cipher->setKey(OUR_hex2bin($key));
		$cipher->setIV(OUR_hex2bin($iv));
		$sServerToken = $iv . '-' . OUR_bin2hex($cipher->encrypt($sServerTokenCredentials));

		/*
		$sIV = CryptoUtil::generateIv();
		$sServerToken = bin2hex($sIV) . '-' . 
                        bin2hex(CryptoUtil::encrypt($sServerTokenCredentials, $sIV, DOMAIN_HOSTING_SECRET));
        */
		return $sServerToken;
	}

	/**
	 * Create a user and it's identity in the database if the user doesn't already exist.
	 *
	 * @param string $sProviderType Could be 'linkedin', 'facebook' or 'twitter'
	 * @param string $sIdentifier A unique identification of the identity on a provider level (at least - some providers create these ids globally unique)
	 * @param string $sProviderUsername A ussername provider generates for a user
	 * @param string $sProfileFullname First name + last name (some providers have a full name field already)
	 * @return array Returns array of data that will be added into the header URL after a final redirect
	 */
	public function signInAfterOAuthProviderLogin( $sProviderType, $sIdentifier, $sProviderUsername, $sProfileFullname, $sProfileUrl, $sProfileAvatarUrl, $sToken, $sSecret ) {
		// Try getting a user and an identity from the database for the given providerType and identifier
        $aOAuthIdentity = $this->DB->select_single_to_array('legacy_oauth', '*', 'where provider_type="' . $sProviderType . '" and identifier="' . $sIdentifier . '"');
		$aUser = $this->DB->select_single_to_array('user', '*', 'where user_id="' . $aOAuthIdentity['user_id'] . '"');
		
		// Access existing user
		if ( $aUser && !empty( $aUser ) && $aOAuthIdentity && !empty( $aOAuthIdentity ) )
		{			
			$bNew = 0;
                        $sUpdated = time();
			$sUser = $aUser['user_id'];
                        $this->DB->update( 'legacy_oauth',
						array (
                                                    'token' 	=> $sToken,
                                                    'secret'    => $sSecret,
                                                    'updated'   => sUpdated
						),
						'where provider_type="' . $sProviderType . '" and identifier="' . $sIdentifier . '"'
			);
                        $this->DB->update( 'user',
						array (
                                                    'updated'   => sUpdated
						),
						'where user_id="' . $aOAuthIdentity['user_id'] . '"'
			);
		}
		// Create new user
		else {
			$bNew = 1;
			
			// Insert new user and new identity
			$sUpdated = time();
			$sUser = $this->DB->insert('user', array( 
                                                            'appid' => isset($_SESSION['appid']) ? $_SESSION['appid'] : '',
                                                            'updated' => $sUpdated ) );
			$this->DB->insert('legacy_oauth', array( 'user_id' => $sUser,
								'provider_type' => $sProviderType,
								'identifier' => $sIdentifier,
								'provider_username' => $sProviderUsername,
								'full_name' => $sProfileFullname,
								'profile_url' => $sProfileUrl,
								'avatar_url' => $sProfileAvatarUrl,
								'token' => $sToken,
								'secret' => $sSecret,
								'updated' => $sUpdated,
								)
							  );
		}
		
		// Generate the return of a function
		return array(
		'created' => $bNew,
		'providerType' => $sProviderType,
		'identifier' => $sIdentifier,
		'updated' => $bNew ? $sUpdated : $aOAuthIdentity['updated'],
		'identitySecretSalt' => ( !$bNew ) ? $aOAuthIdentity['secret_salt'] : '',
		'serverPasswordSalt' => ( !$bNew ) ? $aOAuthIdentity['server_password_salt'] : '',
		);
	}
        
        /** TODO
         * 
         */
        public function fetchFederatedContacts ( $sUserId ) {
            // Select the user by user_id
            $aUser = $this->DB->select_single_to_array(
                    'user',
                    'appid',
                    'where user_id=' . $sUserId
                    );
            print_r($aUser);
            // Select all the users by appid
            $aUsers = $this->DB->select_to_array(
                    'user',
                    'user_id',
                    'where appid="' . $aUser['appid'] . '"'
                    );
            print_r($aUsers); die();
            // Fetch all federated identities by list of user_id-s
            $aContacts = array();
            foreach($aUsers as $value) {
                $aFederatedIdentity = $this->DB->select_to_array(
                        'federated',
                        '*',
                        'where user_id=' . $value['user_id']
                        );
                array_push($aContacts, $aFederatedIdentity);
            }
            
            // Exclude the identity that requested the list of contacts
            foreach($aContacts as $key => $value) {
                print_r($key);
                print_r($value);
            }
            print_r($aContacts); die();
            // Sort out nice JSON
            // TODO
         }
        
        /**
         * Clean the DB based on given parameters
         * 
         * @param array $aRequestData data parsed from request
         */
        public function cleanDB ( $aRequestData ) {
            // Validate Hosting Secret Proof
            require_once(APP . 'php/main/utils/cryptoUtil.php');
            $bHostingProofValid = CryptoUtil::validateHostingProof(
                                    $aRequestData['purpose'],
                                    $aRequestData['nonce'],
                                    $aRequestData['hostingSecretProofExpires'],
                                    DOMAIN_HOSTING_SECRET,
                                    $aRequestData['hostingSecretProof']);
            if (! $bHostingProofValid) {
                throw new RestServerException('007', array(
                                                            'parameter'         => 'hostingSecretProof',
                                                            'parameterValue' 	=> $aRequestData['hostingSecretProof']
                                                            )); 
            }
            
            // Perform clean
            foreach ( $aRequestData['appids'] as $v ) {
                $this->DB->delete('user', 'where appid like "' . $v . '-%"');
            }
        }
	
	/**
	 * TODO
	 *
	 * @param unknown_type $sUri
	 * @return unknown
	 */
	public function parseIdentityUri ( $sUri ) {
		if ( strpos($sUri, 'identity:') !== 0 ) {
			throw new RestServerException('010', array( 'parameter' => 'uri',
														'format' => '\'identity: ...\''
														)
										  );
		}		
		$aIdentity = array (
		'type'			=> '',
		'identifier'	=> ''
		);		
		$sUriWithoutIdentity = substr( $sUri, 9, strlen($sUri) );
		if ( strpos( $sUriWithoutIdentity, 'email' ) === 0 ) {
			$aIdentity['type'] = 'email';
			$aIdentity['identifier'] = substr( $sUriWithoutIdentity, 6, strlen($sUriWithoutIdentity) );
		} else if ( strpos( $sUriWithoutIdentity, 'phone' ) === 0 ) {
			$aIdentity['type'] = 'phone';
			$aIdentity['identifier'] = substr( $sUriWithoutIdentity, 6, strlen($sUriWithoutIdentity) );
		} else if ( strpos( $sUriWithoutIdentity, '//' ) == 0 ) {
			if ( strpos( $sUriWithoutIdentity, '//facebook.com/' ) === 0 ) {
				$aIdentity['type'] = 'facebook';
				$aIdentity['identifier'] = substr( $sUriWithoutIdentity, 15, strlen($sUriWithoutIdentity) );
			} else if ( strpos( $sUriWithoutIdentity, '//twitter.com/' ) === 0 ) {
				$aIdentity['type'] = 'twitter';
				$aIdentity['identifier'] = substr( $sUriWithoutIdentity, 14, strlen($sUriWithoutIdentity) );
			} else if ( strpos( $sUriWithoutIdentity, '//facebook.com/' ) === 0 ) {
				$aIdentity['type'] = 'facebook';
				$aIdentity['identifier'] = substr( $sUriWithoutIdentity, 15, strlen($sUriWithoutIdentity) );
			} else if ( strpos( $sUriWithoutIdentity, 'linkedin.com' ) ) {
				$aIdentity['type'] = 'linkedin';
				$aIdentity['identifier'] = substr( $sUriWithoutIdentity, strlen(MY_DOMAIN)-6, strlen($sUriWithoutIdentity) );
			} else {
				$aIdentity['type'] = 'federated';
				$aIdentity['identifier'] = substr( $sUriWithoutIdentity, strlen(MY_DOMAIN)-6, strlen($sUriWithoutIdentity) );
			}
		}
		return $aIdentity;
	}
	
	//-----------------------------------------------------------------------------------------------------------------------//
	
	/*-------------------
	  Private functions
	-------------------*/
	
	private function getAppropriateDatabaseTable ( $sIdentityType ) {
		$sDBTable = '';
		switch ($sIdentityType) {
			case 'federated':
				$sDBTable = 'federated';
				break;
			case 'email':
				$sDBTable = 'legacy_email';
				break;
			case 'phone':
				$sDBTable = 'legacy_phone';
				break;
			case 'linkedin':
			case 'facebook':
			case 'twitter':
				$sDBTable = 'legacy_oauth';
				break;
		}
		return $sDBTable;
	}
	
	private function getAppropriateIdentityIdFieldName ( $sIdentityType ) {
		$sIdentityIdFieldName = '';
		switch ($sIdentityType) {
			case 'federated':
				$sIdentityIdFieldName = 'federated_id';
				break;
			case 'email':
				$sIdentityIdFieldName = 'email_id';
				break;
			case 'phone':
				$sIdentityIdFieldName = 'phone_id';
				break;
			case 'linkedin':
			case 'twitter':
			case 'facebook':
				$sIdentityIdFieldName = 'oauth_id';
				break;
		}
		return $sIdentityIdFieldName;
	}
	
	private function updateIdentityService ( $aRequestData, $DB ) {
		require_once(APP . 'php/main/utils/loginUtil.php');
		$aHostingData = LoginUtil::generateHostingData('hosted-identity-update');
		
		$aIdentityFromDB = $DB->select_single_to_array('federated', 'federated_id, updated', 'where identifier="' . $aRequestData['identity']['identifier'] . '"');
		$aAvatarsFromDB = $DB->select_to_array('avatar', '*', 'where identity_id="' . $aIdentityFromDB['federated_id'] . '"');
		$aIdentity = array(
		'uri'			=> $aRequestData['identity']['uri'],
		'displayName'	=> $aRequestData['identity']['displayName']
		);
		if ( $aAvatarsFromDB != null ) {
			$aIdentity['avatars'] = $aAvatarsFromDB;
		}
		$aRequestToBeSent = array(
		'identity'		=> $aIdentity
		);
		
		require_once(APP . 'php/main/utils/cryptoUtil.php');
		LoginUtil::sendHostedIdentityUpdate(CryptoUtil::generateRequestId(), $aRequestToBeSent, $aHostingData, $aIdentityFromDB);
		return;
	}
	
	private function validateReloginAccess( $aReloginKey, $aIdentity ) {
		if ($aIdentity['relogin_key'] != $aReloginKey[0] ||
			$aIdentity['relogin_expires'] != $aReloginKey[1] ||
			$aReloginKey[1] < time() ) {
			throw new RestServerException('007', array (
													'parameter' => 'reloginKey',
													'parameterValue' => implode('-',$aReloginKey) ) );
		}
	}
	
	private function generateServerTokenCredentials( $aIdentity ) {
            $aRolodexSupportedIdentityTypes = array(
                'facebook',
            );    
            
            $sServerToken = '';
            if (! key_exists('provider_type', $aIdentity) ) {
                throw new RestServerException('011', array());
            } elseif (! in_array( $aIdentity['provider_type'], $aRolodexSupportedIdentityTypes ) ) {
                throw new RestServerException('011', array());
            } else {
                $sServerToken = '{"service":"' . $aIdentity['provider_type'] . '",';
                $sServerToken .= '"identifier":"' . $aIdentity['identifier'] . '",';
                switch($aIdentity['provider_type']) {
                    case 'facebook':
                        $sServerToken .= '"token":"' . $aIdentity['token'] . '"';
                        break;
                    case 'linkedin':
			$sServerToken .= '"consumer_key":"' . LINKEDIN_CONSUMER_KEY . '",';
			$sServerToken .= '"consumer_secret":"' . LINKEDIN_CONSUMER_SECRET . '",';
			$sServerToken .= '"token":"' . $aIdentity['token'] . '",';
			$sServerToken .= '"secret":"' . $aIdentity['secret'] . '"';
			break;
                    case 'twitter':
			$sServerToken .= '"consumer_key":"' . TWITTER_APP_ID . '",';
			$sServerToken .= '"consumer_secret":"' . TWITTER_APP_SECRET . '",';
			$sServerToken .= '"token":"' . $aIdentity['token'] . '",';
			$sServerToken .= '"secret":"' . $aIdentity['secret'] . '"';
			break;
                    case 'github':
			// TODO implement
			break;
                }
                $sServerToken .= '}';
            }
            return $sServerToken;		
	}

}


?>