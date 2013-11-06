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
require_once ( APP . 'php/main/rest/restServerException.php' );
require_once ( APP . 'php/main/utils/databaseUtil.php' );


/**
 * RequestUtil class is responsible for validation of the requests that hit the server
 * and reading the data out of the request objects.
 */

class RequestUtil {
	
	
	/*----------------------
	  Validation functions
	----------------------*/
	
	/**
	 * Validates the login request.
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateLoginRequest ( $oRequest ) {
		$req = $oRequest->aPars['request'];
		if ( key_exists( 'afterPinValidation', $req ) ) {
			if ( $req['afterPinValidation'] != 'true' ) {
				throw new RestServerException('002', array(
													 'parameter' => 'afterPinValidation'
													 ));
			}
		} elseif ( !key_exists( 'reloginKeyServerPart', $req['identity'] ) ) {
			if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
				throw new RestServerException('002', array(
													 'parameter' => 'identity'
													 ));
			}
			if ( !( key_exists( 'type', $req['identity'] ) && $req['identity']['type'] != null ) ) {
				throw new RestServerException('002', array(
													 'parameter' => 'type'
													 ));
			}
			if ( !( $req['identity']['type'] == 'federated' || 
					$req['identity']['type'] == 'email' || 
					$req['identity']['type'] == 'phone' || 
					$req['identity']['type'] == 'facebook' || 
					$req['identity']['type'] == 'linkedin' || 
					$req['identity']['type'] == 'twitter' ) ) {
				throw new RestServerException('001', array(
													 'parameter' => 'type',
													 'parameterValue' => $req['identity']['type']
													 ));
			}
			if ( !( key_exists( 'identifier', $req['identity'] ) && $req['identity']['identifier'] != null ) ) {
				throw new RestServerException('002', array(
													 'parameter' => 'identifier'
													 ));
			}
			if ( $req['identity']['type'] == 'federated' || $req['identity']['type'] == 'email' || $req['identity']['type'] == 'phone' ) {
				if ( !( key_exists( 'proof', $req ) && $req['proof'] != null ) ) {
					throw new RestServerException('002', array(
														 'parameter' => 'proof'
														 ));
				}
				if ( !( key_exists( 'serverNonce', $req['proof'] ) && $req['proof']['serverNonce'] != null ) ) {
					throw new RestServerException('002', array(
														 'parameter' => 'serverNonce'
														 ));
				}			
				if ( !( key_exists( 'serverLoginProof', $req['proof'] ) && $req['proof']['serverLoginProof'] != null ) ) {
					throw new RestServerException('002', array(
														 'parameter' => 'serverLoginProof'
														 ));
				}
			} elseif ( $req['identity']['type'] == 'facebook' || $req['identity']['type'] == 'linkedin' || $req['identity']['type'] == 'twitter') {
				if ( !( key_exists( 'proof', $req ) && $req['proof'] != null ) ) {
					throw new RestServerException('002', array(
														 'parameter' => 'proof'
														 ));
				}
				if ( !( key_exists( 'clientAuthenticationToken', $req['proof'] ) && $req['proof']['clientAuthenticationToken'] != null ) ) {
					throw new RestServerException('002', array(
														 'parameter' => 'clientAuthenticationToken'
														 ));
				}			
				if ( !( key_exists( 'serverAuthenticationToken', $req['proof'] ) && $req['proof']['serverAuthenticationToken'] != null ) ) {
					throw new RestServerException('002', array(
														 'parameter' => 'serverAuthenticationToken'
														 ));
				}
			}
		}
		
	}
	
	/**
	 * Validates the signUp request
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateSignUpRequest ( $oRequest ){
		$req = $oRequest->aPars['request'];
		if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identity'
												 ));
		}
		if ( !( key_exists( 'type', $req['identity'] ) && $req['identity']['type'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'type'
												 ));
		}
		if ( !( $req['identity']['type'] == 'federated' || 
				$req['identity']['type'] == 'email' || 
				$req['identity']['type'] == 'phone' || 
				$req['identity']['type'] == 'facebook' || 
				$req['identity']['type'] == 'linkedin' || 
				$req['identity']['type'] == 'twitter' ) ) {
			throw new RestServerException('001', array(
												 'parameter' => 'type',
												 'parameterValue' => $req['identity']['type']
												 ));
		}
		if ( !( key_exists( 'identifier', $req['identity'] ) && $req['identity']['identifier'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identifier'
												 ));
		}
		if ( !( key_exists( 'passwordHash', $req['identity'] ) && $req['identity']['passwordHash'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'passwordHash'
												 ));
		}
		if ( !( key_exists( 'secretSalt', $req['identity'] ) && $req['identity']['secretSalt'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'secretSalt'
												 ));
		}
		if ( !( key_exists( 'serverPasswordSalt', $req['identity'] ) && $req['identity']['serverPasswordSalt'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'serverPasswordSalt'
												 ));
		}
		if ( key_exists( 'avatars', $req['identity'] ) ) {
			if ( empty($req['identity']['avatars']) ) {
				throw new RestServerException('002', array(
													 'parameter' => 'avatar'
													 ));
			}
			$nAvatar = 0;
			while ( isset( $req['identity']['avatars']['avatar'][$nAvatar] ) ) {
				if ( !( key_exists( 'url', $req['identity']['avatars']['avatar'][$nAvatar] ) && $req['identity']['avatars']['avatar'][$nAvatar]['url'] != null ) ) {
					throw new RestServerException('002', array(
														 'parameter' => 'url'
														 ));
				}
				if ( key_exists( 'height', $req['identity']['avatars']['avatar'][$nAvatar] ) && !( is_numeric( $req['identity']['avatars']['avatar'][$nAvatar]['height'] ) ) ) {
					throw new RestServerException('010', array(
														 'parameter' => 'height',
														 'format' => 'a number'
														 ));
				}
				if ( key_exists( 'width', $req['identity']['avatars']['avatar'][$nAvatar] ) && !( is_numeric( $req['identity']['avatars']['avatar'][$nAvatar]['width'] ) ) ) {
					throw new RestServerException('010', array(
														 'parameter' => 'width',
														 'format' => 'a number'
														 ));
				}
				$nAvatar++;
			}
		}
		
	}
	
	/**
	 * Validates if the identitySaltsGet request has already set not empty identity type and identifier.
	 * Also, it validates if identity type has one of six allowed values (federated, email, phone, facebook, linkedin and twitter).
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateIdentitySaltsGetRequest ( $oRequest ){
		$req = $oRequest->aPars['request'];
		if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identity'
												 ));
		}
		if ( !( key_exists( 'type', $req['identity'] ) && $req['identity']['type'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'type'
												 ));
		}
		if ( !( $req['identity']['type'] == 'federated' || 
				$req['identity']['type'] == 'email' || 
				$req['identity']['type'] == 'phone' || 
				$req['identity']['type'] == 'facebook' || 
				$req['identity']['type'] == 'linkedin' || 
				$req['identity']['type'] == 'twitter' ) ) {
			throw new RestServerException('001', array(
											     'parameter' => 'type',
											     'parameterValue' => $req['identity']['type']
											     ));
		}
		if ( !( key_exists( 'identifier', $req['identity'] ) && $req['identity']['identifier'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identifier'
												 ));
		}
	}
	
	/**
	 * Validates if the identitySaltsSet request has already set not empty authentication tokens and the identity with salts.
	 * Also, it validates if identity type has one of three allowed values (facebook, linkedin and twitter) out of total six
	 * (this limitation is made because the only sequnce where this request needs to be called if the FreshAccount/LegacyOAuth sequence).
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateIdentitySaltsSetRequest ( $oRequest ){
		$req = $oRequest->aPars['request'];		
		if ( !( key_exists( 'clientAuthenticationToken', $req ) && $req['clientAuthenticationToken'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'clientAuthenticationToken'
												 ));
		}
		if ( !( key_exists( 'serverAuthenticationToken', $req ) && $req['serverAuthenticationToken'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'serverAuthenticationToken'
												 ));
		}
		if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identity'
												 ));
		}		
		if ( !( key_exists( 'type', $req['identity'] ) && $req['identity']['type'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'type'
												 ));
		}
		if ( !( $req['identity']['type'] == 'facebook' || 
				$req['identity']['type'] == 'linkedin' || 
				$req['identity']['type'] == 'twitter' ) ) {
			throw new RestServerException('001', array(
											     'parameter' => 'type_oauth-only',
											     'parameterValue' => $req['identity']['type']
											     ));
		}
		if ( !( key_exists( 'identifier', $req['identity'] ) && $req['identity']['identifier'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identifier'
												 ));
		}
		if ( !( key_exists( 'secretSalt', $req['identity'] ) && $req['identity']['secretSalt'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'secretSalt'
												 ));
		}
		if ( !( key_exists( 'serverPasswordSalt', $req['identity'] ) && $req['identity']['serverPasswordSalt'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'serverPasswordSalt'
												 ));
		}
	}
	
	/**
	 * Validates if the oAuthProviderAuthentication request has already set not empty clientAuthenticationToken and the identity type.
	 * Also, it validates if identity type has one of three allowed values (facebook, linkedin and twitter) out of total six
	 * (this limitation is made because the only sequnce where this request needs to be called if the FreshAccount/LegacyOAuth sequence).
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateOAuthProviderAuthenticationRequest ( $oRequest ){
		$req = $oRequest->aPars['request'];
		if ( !( key_exists( 'clientAuthenticationToken', $req ) && $req['clientAuthenticationToken'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'clientAuthenticationToken'
												 ));
		}
		if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identity'
												 ));
		}		
		if ( !( key_exists( 'type', $req['identity'] ) && $req['identity']['type'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'type'
												 ));
		}
		if ( !( $req['identity']['type'] == 'facebook' || 
				$req['identity']['type'] == 'linkedin' || 
				$req['identity']['type'] == 'twitter' ) ) {
			throw new RestServerException('001', array(
											     'parameter' => 'type_oauth-only',
											     'parameterValue' => $req['identity']['type']
											     ));
		}
		if ( !( key_exists( 'callbackURL', $req ) && $req['callbackURL'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'callbackURL'
												 ));
		}
	}
	
	/**
	 * Validates the pinValidation request
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validatePinValidationRequest ( $oRequest ){
		$req = $oRequest->aPars['request'];
		if ( !( key_exists( 'pin', $req ) && $req['pin'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'pin'
												 ));
		}
	}
	
	/**
	 * Validates the profileGet request
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateProfileGetRequest ( $oRequest ){
		$req = $oRequest->aPars['request'];
		if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identity'
												 ));
		}		
		if ( !( key_exists( 'identifier', $req['identity'] ) && $req['identity']['identifier'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identifier'
												 ));
		}
	}
	
	/**
	 * Validates the profileUpdate request
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateProfileUpdateRequest ( $oRequest ){
		$req = $oRequest->aPars['request'];
		if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identity'
												 ));
		}		
		if ( !( key_exists( 'identifier', $req['identity'] ) && $req['identity']['identifier'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identifier'
												 ));
		}		
		if ( !( key_exists( 'uri', $req['identity'] ) && $req['identity']['uri'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'uri'
												 ));
		}
		if ( key_exists( 'avatars', $req['identity'] ) ) {
			if ( empty($req['identity']['avatars']) ) {
				throw new RestServerException('002', array(
													 'parameter' => 'avatar'
													 ));
			}
			$nAvatar = 0;
			while ( isset( $req['identity']['avatars']['avatar'][$nAvatar] ) ) {
				if ( !( key_exists( 'url', $req['identity']['avatars']['avatar'][$nAvatar] ) && $req['identity']['avatars']['avatar'][$nAvatar]['url'] != null ) ) {
					throw new RestServerException('002', array(
														 'parameter' => 'url'
														 ));
				}
				if ( key_exists( 'height', $req['identity']['avatars']['avatar'][$nAvatar] ) && !( is_numeric( $req['identity']['avatars']['avatar'][$nAvatar]['height'] ) ) ) {
					throw new RestServerException('010', array(
														 'parameter' => 'height',
														 'format' => 'a number'
														 ));
				}
				if ( key_exists( 'width', $req['identity']['avatars']['avatar'][$nAvatar] ) && !( is_numeric( $req['identity']['avatars']['avatar'][$nAvatar]['width'] ) ) ) {
					throw new RestServerException('010', array(
														 'parameter' => 'width',
														 'format' => 'a number'
														 ));
				}
				$nAvatar++;
			}
		}
		if ( key_exists( 'removeAvatars', $req['identity'] ) ) {
			if ( empty($req['identity']['removeAvatars']) ) {
				throw new RestServerException('002', array(
													 'parameter' => 'avatar'
													 ));
			}
			$nAvatar = 0;
			while ( isset( $req['identity']['removeAvatars']['avatar'][$nAvatar] ) ) {
				if ( !( key_exists( 'url', $req['identity']['removeAvatars']['avatar'][$nAvatar] ) && $req['identity']['removeAvatars']['avatar'][$nAvatar]['url'] != null ) ) {
					throw new RestServerException('002', array(
														 'parameter' => 'url'
														 ));
				}
				$nAvatar++;
			}
		}
	}
	
	/**
	 * Validates the passwordChange request
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validatePasswordChangeRequest ( $oRequest ) {
		$req = $oRequest->aPars['request'];
		if ( !( key_exists( 'clientToken', $req ) && $req['clientToken'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'clientToken'
												 ));
		}
		if ( !( key_exists( 'serverToken', $req ) && $req['serverToken'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'clientToken'
												 ));
		}
		if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identity'
												 ));
		}	
		if ( !( key_exists( 'identifier', $req['identity'] ) && $req['identity']['identifier'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identifier'
												 ));
		}		
		if ( !( key_exists( 'passwordHash', $req['identity'] ) && $req['identity']['passwordHash'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'passwordHash'
												 ));
		}
		if ( !( key_exists( 'uri', $req['identity'] ) && $req['identity']['uri'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'uri'
												 ));
		}
		if ( !( key_exists( 'uriEncrypted', $req['identity'] ) && $req['identity']['uriEncrypted'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'uriEncrypted'
												 ));
		}
		if ( !( key_exists( 'secretDecryptionKeyEncrypted', $req['identity'] ) && $req['identity']['secretDecryptionKeyEncrypted'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'secretDecryptionKeyEncrypted'
												 ));
		}
		if ( !( key_exists( 'reloginAccessKeyEncrypted', $req['identity'] ) && $req['identity']['reloginAccessKeyEncrypted'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'reloginAccessKeyEncrypted'
												 ));
		}
	}
	
	/**
	 * Validates the lockboxHalfKeyStore request
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateLockboxHalfKeyStoreRequest ( $oRequest ) {
		$req = $oRequest->aPars['request'];
		if ( !( key_exists( 'nonce', $req ) && $req['nonce'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'nonce'
												 ));
		}
		if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identity'
												 ));
		}
		if ( !( key_exists( 'type', $req['identity'] ) && $req['identity']['type'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'type'
												 ));
		}
		if ( !( $req['identity']['type'] == 'federated' || 
				$req['identity']['type'] == 'email' || 
				$req['identity']['type'] == 'phone' || 
				$req['identity']['type'] == 'facebook' || 
				$req['identity']['type'] == 'linkedin' || 
				$req['identity']['type'] == 'twitter' ) ) {
			throw new RestServerException('001', array(
											     'parameter' => 'type',
											     'parameterValue' => $req['identity']['type']
											     ));
		}	
		if ( !( key_exists( 'identifier', $req['identity'] ) && $req['identity']['identifier'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identifier'
												 ));
		}
		if ( !( key_exists( 'uri', $req['identity'] ) && $req['identity']['uri'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'uri'
												 ));
		}
		if ( !( key_exists( 'lockbox', $req ) && $req['lockbox'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'lockbox'
												 ));
		}
		if ( !( key_exists( 'keyEncrypted', $req['lockbox'] ) && $req['lockbox']['keyEncrypted'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'keyEncrypted'
												 ));
		}
	}
	
	/**
	 * Validates the identityAccessValidate request
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateIdentityAccessValidateRequest ( $oRequest ) {
		$req = $oRequest->aPars['request'];
		if ( !( key_exists( 'nonce', $req ) && $req['nonce'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'nonce'
												 ));
		}
		if ( !( key_exists( 'purpose', $req ) && $req['purpose'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'purpose'
												 ));
		}
		if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identity'
												 ));
		}
		if ( !( key_exists( 'accessToken', $req['identity'] ) && $req['identity']['accessToken'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'accessToken'
												 ));
		}
		if ( !( key_exists( 'accessSecretProof', $req['identity'] ) && $req['identity']['accessSecretProof'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'accessSecretProof'
												 ));
		}
		if ( !( key_exists( 'accessSecretProofExpires', $req['identity'] ) && $req['identity']['accessSecretProofExpires'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'accessSecretProofExpires'
												 ));
		}
		if ( !( key_exists( 'uri', $req['identity'] ) && $req['identity']['uri'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'uri'
												 ));
		}
	}
	
	/**
	 * Validates if the linkedinTokenExchange has already set not empty identifier.
	 * 
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateIdentityAccessRolodexCredentialsGetRequest ( $oRequest ){
		$req = $oRequest->aPars['request'];
		if ( !( key_exists( 'clientNonce', $req ) && $req['clientNonce'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'clientNonce'
												 ));
		}
		if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'identity'
												 ));
		}
		if ( !( key_exists( 'accessToken', $req['identity'] ) && $req['identity']['accessToken'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'accessToken'
												 ));
		}
		if ( !( key_exists( 'accessSecretProof', $req['identity'] ) && $req['identity']['accessSecretProof'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'accessSecretProof'
												 ));
		}
		if ( !( key_exists( 'accessSecretProofExpires', $req['identity'] ) && $req['identity']['accessSecretProofExpires'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'accessSecretProofExpires'
												 ));
		}
		if ( !( key_exists( 'uri', $req['identity'] ) && $req['identity']['uri'] != null ) ) {
			throw new RestServerException('002', array(
												 'parameter' => 'uri'
												 ));
		}
	}
        
        /**
	 * Validates if the hostingDataGet has already set not empty identifier.
	 * 
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateHostingDataGetRequest ( $oRequest ) {
            $req = $oRequest->aPars['request']; 
            if ( !( key_exists( 'purpose', $req ) && $req['purpose'] != null ) ) {
		throw new RestServerException('002', array(
                                                            'parameter' => 'purpose'
                                                            ));
            }
        }
        
        /**
	 * Validates if the federatedContactsGet has already set not empty identifier.
	 * 
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
	public static function validateFederatedContactsGetRequest ( $oRequest ) {
            $req = $oRequest->aPars['request']; 
            if ( !( key_exists( 'nonce', $req ) && $req['nonce'] != null ) ) {
		throw new RestServerException('002', array(
                                                            'parameter' => 'nonce'
                                                            ));
            }
            if ( !( key_exists( 'hostingProof', $req ) && $req['hostingProof'] != null ) ) {
		throw new RestServerException('002', array(
                                                            'parameter' => 'hostingProof'
                                                            ));
            }
            if ( !( key_exists( 'hostingProofExpires', $req ) && $req['hostingProofExpires'] != null ) ) {
		throw new RestServerException('002', array(
                                                            'parameter' => 'hostingProofExpires'
                                                            ));
            }
            if ( !( key_exists( 'identity', $req ) && $req['identity'] != null ) ) {
		throw new RestServerException('002', array(
                                                            'parameter' => 'identity'
                                                            ));
            }
            if ( !( key_exists('uri', $req['identity']) && $req['identity']['uri'] != null ) ) {
                throw new RestServerException('002', array(
                                                            'parameter' => 'uri'
                                                            ));
            }
        }
	
        /**
	 * Validates the devtoolsDBClean request
	 *
	 * @param array $req Parameters of the request
	 * @return boolean Returns true if the request is valid, otherwise returns false
	 */
        public static function validateDevtoolsDatabaseCleanProviderRequest ( $oRequest ) {
            $req = $oRequest->aPars['request'];
            if ( !( key_exists( 'nonce', $req ) && $req['nonce'] != null ) ) {
		throw new RestServerException('002', array(
                                                            'parameter' => 'nonce'
                                                            ));
            }
            if ( !( key_exists( 'hostingProof', $req ) && $req['hostingProof'] != null ) ) {
		throw new RestServerException('002', array(
                                                            'parameter' => 'hostingProof'
                                                            ));
            }
            if ( !( key_exists( 'hostingProofExpires', $req ) && $req['hostingProofExpires'] != null ) ) {
		throw new RestServerException('002', array(
                                                            'parameter' => 'hostingProofExpires'
                                                            ));
            }
            if ( !( key_exists( 'purpose', $req ) && $req['purpose'] != null ) ) {
		throw new RestServerException('002', array(
                                                            'parameter' => 'purpose'
                                                            ));
            }
            if ( key_exists('appids', $req) ) {
                if ( empty($req['appids']) ) {
                    throw new RestServerException('002', array(
                        'parameter' => 'appids'
                        ));
                }
            } 
        }
		
	//----------------------------------------------------------------------------------------------------------------------------------------//
	
	/*------------------------
	  Getting data functions
	------------------------*/
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $post The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeLoginRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		if ( key_exists( 'afterPinValidation', $req ) ) {
			return array (
                        'appid'     => $appid,
			'afterPinValidation'				=> DatabaseUtil::protectFromSqlInjection( $req['afterPinValidation'] )
			);
		} elseif ( key_exists( 'reloginKeyServerPart', $req['identity'] ) ) {
			$aIdentity = array (
			'reloginKeyServerPart'				=> DatabaseUtil::protectFromSqlInjection( $req['identity']['reloginKeyServerPart'] )
			);
			return array (
                        'appid'     => $appid,
			'identity'							=> $aIdentity
			);
		} else {
			$aIdentity = array (
			'type'								=> DatabaseUtil::protectFromSqlInjection( $req['identity']['type'] ),
			'identifier'						=> DatabaseUtil::protectFromSqlInjection( $req['identity']['identifier'] )
			);
			if ( $req['identity']['type'] == 'federated' || $req['identity']['type'] == 'phone' || $req['identity']['type'] == 'email' ) {
				$aProof = array (		
				'serverNonce'						=> DatabaseUtil::protectFromSqlInjection( $req['proof']['serverNonce'] ),
				'serverLoginProof'					=> DatabaseUtil::protectFromSqlInjection( $req['proof']['serverLoginProof'] )
				);
			} else {
				$aProof = array (		
				'clientAuthenticationToken'			=> DatabaseUtil::protectFromSqlInjection( $req['proof']['clientAuthenticationToken'] ),
				'serverAuthenticationToken'			=> DatabaseUtil::protectFromSqlInjection( $req['proof']['serverAuthenticationToken'] )
				);
			}
			return array (
                        'appid'     => $appid,
			'proof'								=> $aProof,
			'identity'							=> $aIdentity
			);
		}
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $post The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeSignUpRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		$aAvatars = array();
		$nAvatar = 0;
		while ( isset($req['identity']['avatars']['avatar'][$nAvatar]) ) {
			$aAvatar = array (
			'name'								=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['avatars']['avatar'][$nAvatar]['name'] ) ?
																						  $req['identity']['avatars']['avatar'][$nAvatar]['name'] : '' ),
			'url'								=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['avatars']['avatar'][$nAvatar]['url'] ) ?
																						  $req['identity']['avatars']['avatar'][$nAvatar]['url'] : '' ),
			'width'								=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['avatars']['avatar'][$nAvatar]['width'] ) ?
																						  $req['identity']['avatars']['avatar'][$nAvatar]['width'] : '' ),
			'height'							=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['avatars']['avatar'][$nAvatar]['height'] ) ?
																						  $req['identity']['avatars']['avatar'][$nAvatar]['height'] : '' ),
			);
			array_push($aAvatars, $aAvatar);
			$nAvatar++;
		}		
		$aIdentity = array(
		'type'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['type'] ),
		'identifier' 							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['identifier'] ),
		'passwordHash'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['passwordHash'] ),
		'secretSalt'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['secretSalt'] ),
		'serverPasswordSalt'					=> DatabaseUtil::protectFromSqlInjection( $req['identity']['serverPasswordSalt'] ),
		'displayName'							=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['displayName'] ) ?
																						  $req['identity']['displayName'] : '' ),
		'avatars' 								=> $aAvatars
		);
		return array(
                'appid'     => $appid,
		'identity' => $aIdentity
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $post The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeIdentitySaltsGetRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		$aIdentiy = array(
		'type'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['type'] ),
		'identifier' 							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['identifier'] ),
		);
		return array(
                'appid'     => $appid,
		'identity' => $aIdentiy
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $post The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeIdentitySaltsSetRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		$aIdentity = array(
		'type'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['type'] ),
		'identifier' 							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['identifier'] ),
		'secretSalt'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['secretSalt'] ),
		'serverPasswordSalt'					=> DatabaseUtil::protectFromSqlInjection( $req['identity']['serverPasswordSalt'] ),
		);
		return array(
                'appid'     => $appid,
		'clientAuthenticationToken'				=> DatabaseUtil::protectFromSqlInjection( $req['clientAuthenticationToken'] ),
		'serverAuthenticationToken'				=> DatabaseUtil::protectFromSqlInjection( $req['serverAuthenticationToken'] ),
		'identity' 								=> $aIdentity
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeLinkedinTokenExhangeRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		return array(
                'appid'     => $appid,
		'identifier'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['identifier'] )
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeOAuthProviderAuthenticationRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		$aIdentity = array(
		'type'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['type'] )
		);
		return array(
                'appid'     => $appid,
		'clientAuthenticationToken'				=> DatabaseUtil::protectFromSqlInjection( $req['clientAuthenticationToken'] ),
		'callbackURL'							=> DatabaseUtil::protectFromSqlInjection( $req['callbackURL'] ),
		'identity' 								=> $aIdentity
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takePinValidationRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		return array(
                'appid'     => $appid,
		'pin'									=> DatabaseUtil::protectFromSqlInjection( $req['pin'] )
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeProfileGetRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		$aIdentity = array(
		'identifier'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['identifier'] )
		);
		return array(
                'appid'     => $appid,
		'identity' 								=> $aIdentity
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeProfileUpdateRequestData ( $oRequest ){ 
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		$aAvatars = array();
		$nAvatar = 0;
		while ( isset($req['identity']['avatars']['avatar'][$nAvatar]) ) {
			$aAvatar = array (
			'name'								=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['avatars']['avatar'][$nAvatar]['name'] ) ?
																						  $req['identity']['avatars']['avatar'][$nAvatar]['name'] : '' ),
			'url'								=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['avatars']['avatar'][$nAvatar]['url'] ) ?
																						  $req['identity']['avatars']['avatar'][$nAvatar]['url'] : '' ),
			'width'								=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['avatars']['avatar'][$nAvatar]['width'] ) ?
																						  $req['identity']['avatars']['avatar'][$nAvatar]['width'] : '' ),
			'height'							=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['avatars']['avatar'][$nAvatar]['height'] ) ?
																						  $req['identity']['avatars']['avatar'][$nAvatar]['height'] : '' ),
			);
			array_push($aAvatars, $aAvatar);
			$nAvatar++;
		}
		$aRemoveAvatars = array();
		$nAvatar = 0;
		while ( isset($req['identity']['removeAvatars']['avatar'][$nAvatar]) ) {
			$aAvatar = array (
			'url'								=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['removeAvatars']['avatar'][$nAvatar]['url'] ) ?
																						  $req['identity']['removeAvatars']['avatar'][$nAvatar]['url'] : '' ),
			);
			array_push($aRemoveAvatars, $aAvatar);
			$nAvatar++;
		}
		$aIdentity = array(
		'identifier'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['identifier'] ),
		'uri'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['uri'] ),
		'displayName'							=> DatabaseUtil::protectFromSqlInjection( isset( $req['identity']['displayName'] ) ? 
																						  $req['identity']['displayName'] : '' ),
		'avatars'								=> $aAvatars,
		'removeAvatars'							=> $aRemoveAvatars
		);
		return array(
                'appid'     => $appid,
		'identity' 								=> $aIdentity
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takePasswordChangeRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		$aIdentity = array(
		'identifier'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['identifier'] ),
		'uri'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['uri'] ),		
		'passwordHash'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['passwordHash'] ),
		'uriEncrypted'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['uriEncrypted'] ),
		'secretDecryptionKeyEncrypted'			=> DatabaseUtil::protectFromSqlInjection( $req['identity']['secretDecryptionKeyEncrypted'] ),
		'reloginAccessKeyEncrypted'				=> DatabaseUtil::protectFromSqlInjection( $req['identity']['reloginAccessKeyEncrypted'] ),
		'newPasswordHash'						=> DatabaseUtil::protectFromSqlInjection( $req['identity']['newPasswordHash'] )
		);
		return array(
                'appid'     => $appid,
		'clientToken'							=> DatabaseUtil::protectFromSqlInjection( $req['clientToken'] ),
		'serverToken'							=> DatabaseUtil::protectFromSqlInjection( $req['serverToken'] ),
		'identity' 								=> $aIdentity
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeLockboxHalfKeyStoreRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		$aIdentity = array(
		'accessToken'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['accessToken'] ),
		'accessSecretProof'						=> DatabaseUtil::protectFromSqlInjection( $req['identity']['accessSecretProof'] ),
		'accessSecretProofExpires'				=> DatabaseUtil::protectFromSqlInjection( $req['identity']['accessSecretProofExpires'] ),
		'type'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['type'] ),
		'identifier'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['identifier'] ),
		'uri'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['uri'] )
		);
		$aLockbox = array (
		'keyEncrypted'							=> DatabaseUtil::protectFromSqlInjection( $req['lockbox']['keyEncrypted'] )
		);
		return array(
                'appid'     => $appid,
		'clientNonce'							=> DatabaseUtil::protectFromSqlInjection( $req['nonce'] ),
		'identity' 								=> $aIdentity,
		'lockbox'								=> $aLockbox
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeIdentityAccessValidateRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		$aIdentity = array(
		'accessToken'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['accessToken'] ),
		'accessSecretProof'						=> DatabaseUtil::protectFromSqlInjection( $req['identity']['accessSecretProof'] ),
		'accessSecretProofExpires'				=> DatabaseUtil::protectFromSqlInjection( $req['identity']['accessSecretProofExpires'] ),
		'uri'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['uri'] )
		);
		return array(
                'appid'     => $appid,
		'clientNonce'							=> DatabaseUtil::protectFromSqlInjection( $req['nonce'] ),
		'purpose'								=> DatabaseUtil::protectFromSqlInjection( $req['purpose'] ),
		'identity' 								=> $aIdentity
		);
	}
	
	/**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeIdentityAccessRolodexCredentialsGetRequestData ( $oRequest ){
		$req = $oRequest->aPars['request'];
                $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
		$aIdentity = array(
		'accessToken'							=> DatabaseUtil::protectFromSqlInjection( $req['identity']['accessToken'] ),
		'accessSecretProof'						=> DatabaseUtil::protectFromSqlInjection( $req['identity']['accessSecretProof'] ),
		'accessSecretProofExpires'				=> DatabaseUtil::protectFromSqlInjection( $req['identity']['accessSecretProofExpires'] ),
		'uri'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['uri'] )
		);
		return array(
                'appid'     => $appid,
		'clientNonce'							=> DatabaseUtil::protectFromSqlInjection( $req['clientNonce'] ),
		'identity' 								=> $aIdentity
		);
	}
        
        /**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeHostingDataGetRequestData ( $oRequest ) {
            $req = $oRequest->aPars['request'];
            $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
            return array(
                'appid'     => $appid,
                'purpose' => DatabaseUtil::protectFromSqlInjection( $req['purpose'] )
            );
        }
        
        /**
	 * Take data from the request in a safe manner and return an array of it
	 *
	 * @param array $req The request to take data from
	 * @return array of needed given-by-request data
	 */
	public static function takeFederatedContactsGetRequestData ( $oRequest ) {
            $req = $oRequest->aPars['request'];
            $appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
            $aIdentity = array(
            'uri'									=> DatabaseUtil::protectFromSqlInjection( $req['identity']['uri'] )
            );
            return array(
            'appid'                 => $appid,
            'nonce'                 => DatabaseUtil::protectFromSqlInjection( $req['nonce'] ),
            'hostingProof'          => DatabaseUtil::protectFromSqlInjection( $req['hostingProof'] ),
            'hostingProofExpires'   => DatabaseUtil::protectFromSqlInjection( $req['hostingProofExpires'] ),
            'identity'              => $aIdentity
            );
        }
        
        /**
         * Take data 
         */
        public static function takeDevtoolsDatabaseCleanProviderRequestData ( $oRequest ) {
            $req = $oRequest->aPars['request'];
            $aAppids = array();
            $nAppid = 0;
            while ( isset($req['appids'][$nAppid]) ) {
                $sAppid = DatabaseUtil::protectFromSqlInjection( $req['appids'][$nAppid] );
                array_push($aAppids, $sAppid);
                $nAppid++;
            }
            return array(
                'purpose'                   => DatabaseUtil::protectFromSqlInjection( $req['purpose'] ),
                'nonce'                     => DatabaseUtil::protectFromSqlInjection( $req['nonce'] ),
                'hostingSecretProof'        => DatabaseUtil::protectFromSqlInjection( $req['hostingProof'] ),
                'hostingSecretProofExpires' => DatabaseUtil::protectFromSqlInjection( $req['hostingProofExpires'] ),
                'appids'                    => $aAppids
            );
        }
}

?>