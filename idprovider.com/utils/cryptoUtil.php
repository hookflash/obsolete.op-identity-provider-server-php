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
 * Class CryptoUtil provides various cryptographic-purposed features,
 * such as performing hashes, generation of nonces or salts, all types of validations... 
 */

class CryptoUtil {
	
	/**
	 * Generates some provider specific salts
	 *
	 * @return unknown
	 */
	public static function generateServerPasswordSalt () {
		return CryptoUtil::gimmeHash( PROVIDER_MAGIC_VALUE . ':' . CryptoUtil::makeRandomString() );
	}
	
	/**
	 * Returns a basic sha1 hash of a given string
	 *
	 * @param string $stillNotHashed A string to be hashed
	 * @return string Returns hashed string
	 */
	public static function gimmeHash ( $stillNotHashed ) {
		return sha1($stillNotHashed);
	}
	
	/**
	 * Returns a hmac sha1 hash of a given string, using given key
	 *
	 * @param string $stillNotHashed A string to be hashed
	 * @param string $key A key to be used for hashing
	 * @return string Returns hmac hashed string
	 */
	public static function gimmeHmac ( $stillNotHashed, $key ) {
		return hash_hmac('sha1', $stillNotHashed, $key);
	}
	
	/**
	 * Generate a 20 bytes long random
	 *
	 */
	public static function generateIv () {
		return CryptoUtil::makeRandomString(16 * 8);
	}
        
    /**
     * Convert hex to string
     * @param type $hex
     * @return type
     */
    public static function hexToStr($hex)
    {
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2)
        {
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }

	/**
	 * Encrypts given value using secret key
	 *
	 * @param string $sValue Value to be encrypted
	 * @param string $iv Initialization vector
	 * @param string $sSecretKey Key to be used for encryption
	 * @return string 
	 */
	public static function encrypt($sValue, $iv, $sSecretKey)
	{
        require_once(ROOT . 'libs/seclib/Crypt/AES.php');

        $cipher = new Crypt_AES(CRYPT_AES_MODE_CFB);
        $key = hash('sha256', $sSecretKey);
        $iv = hash('md5', $iv);
        $cipher->setKey($key);
        $cipher->setIV($iv);
        return $cipher->encrypt($sValue);
	}
	
	/**
	 * Decrypts given value using secret key
	 *
	 * @param string $sValue Value to be decrypted
	 * @param string $iv Initialization vector
	 * @param string $sSecretKey Key to be used for decryption
	 * @return string 
	 */
	public static function decrypt($sValue, $iv, $sSecretKey)
	{
        require_once(ROOT . 'libs/seclib/Crypt/AES.php');

        $cipher = new Crypt_AES(CRYPT_AES_MODE_CFB);
        $key = hash('sha256', $sSecretKey);
        $iv = hash('md5', $iv);
        $cipher->setKey($key);
        $cipher->setIV($iv);
        return $cipher->decrypt($sValue);
	}
	
	/**
	 * Returns just a random requestId
	 *
	 * @return number Returns random requestId
	 */
	public static function generateRequestId () {
		return rand(1000000000, 9999999999);
	}
	
	/**
	 * Generate some one-time-use nonce
	 * 
	 * @return string Generated nonce
	 */
	public static function generateNonce () {
		return CryptoUtil::gimmeHash((CryptoUtil::makeRandomString()));
	}
	
	/**
	 * Generates a nonce that is tied to the provider providing it and to the given expiry time frame in minutes
	 *
	 * @param integer $nExpiryMinutes Number of minutes for a nonce to be valid within
	 * @return string Returns generated nonce with the provider's signature and the expiry within
	 */
	public static function generateSelfValidatingNonce ( $nExpiryMinutes ) {
		$sExpiry = time() + 60 * $nExpiryMinutes;
		$sNonce = CryptoUtil::generateNonce();
		$sHashValidation = CryptoUtil::gimmeHmac('validate:' . PROVIDER_MAGIC_VALUE . ':' . $sExpiry . ':' . $sNonce, PROVIDER_MAGIC_VALUE);
		return $sNonce . '-' . $sExpiry . '-' . $sHashValidation;
	}
	
	/**
	 * Checks if given nonce is valid.
	 * For a serverNonce to be valid, it must not expire and must not fail the validation hash challange
	 *
	 * @param string $sServerNonce A server nonce to be validated
	 * @return boolean Returns true if the serverNonce is valid, otherwise return false
	 */
	public static function validateServerNonce ( $sServerNonce ) {
		// Break the nonce into three separate parts: the innerNonce, the expiry and the hash validation
		$aServerNonce = explode('-', $sServerNonce);
		
		// Challange the nonce's size (in terms of how many things are in there in order to prevent the code to break if there is no $aServerNonce[1])
		if ( sizeof($aServerNonce) < 3 ) {
			return false;
		}
		
		// Challange the nonce's expiry
		if ( time() > $aServerNonce[1] ) {
			return false;
		}
		
		// Challange the nonce's validation hash
		$sHashValidation = CryptoUtil::gimmeHmac('validate:' . PROVIDER_MAGIC_VALUE . ':' . $aServerNonce[1] . ':' . $aServerNonce[0], PROVIDER_MAGIC_VALUE);
		if ( $sHashValidation != $aServerNonce[2] ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Checks if the given serverLoginFinalProof is valid by challenging it to match with serverLoginFinalProofCalculated.
	 * ServerLoginFinalProofCalculated is calculated here using the same (hereby given) data and the same algorythm that serverLoginFinalProof has been calculated on the client.
	 *
	 * @param string $sIdentifier Identity to be used to generate serverLoginFinalProofCalculated
	 * @param string $sPasswordHash PasswordHash of the identity
	 * @param string $sIdentitySecretSalt IdentitySecretSalt of the identity
	 * @param string $sServerNonce ServerNonce that the client have got from the server just before the login request, and that expires within ten minutes
	 * @param string $sServerLoginFinalProof 
	 * @return boolean 
	 */
	public static function validateServerLoginProof ( $sIdentifier, $sPasswordHash, $sIdentitySecretSalt, $sServerPasswordSalt, $sServerNonce, $sServerLoginProof ) {
		// Generate serverLoginProofCalculated
		$sServerLoginInnerProof = CryptoUtil::generateServerLoginInnerProof($sIdentifier, $sPasswordHash, $sIdentitySecretSalt, $sServerPasswordSalt);
		$sServerLoginProofCalculated = CryptoUtil::generateServerLoginProof($sServerLoginInnerProof, $sServerNonce);
		
		// Check serverLoginFinalProof and return true if it's valid, otherwise return false
		if ( $sServerLoginProof != $sServerLoginProofCalculated ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Generates a hosting proof
	 *
	 * @param string $nonce One time used nonce just to generate this proof (nonce will also be sent in the same request, so the request receiver will be able to check if the proof is real)
	 * @param string $domainHostingSecret This parameter is known to both sides already
         * @param string hosting secret
	 * @return array Returnes hostingProof and hostingProofExpires
	 */
	public static function generateHostingProof ( $sMethodName, $sNonce, $sDomainHostingSecret ) {
		// Create hostingProof raw string to be hashed
		$sHostingProofExpires = time() + ( 3600 * 24 ); // Will expire after a full day
		$sHostingProofRaw = $sMethodName . ':' . $sNonce . ':' . $sDomainHostingSecret . ":{$sHostingProofExpires}";
		// Hash hostingProof raw string
		$sHostingProof = sha1($sHostingProofRaw);
		
		return array(
		'hostingProof'        => $sHostingProof,
		'hostingProofExpires' => $sHostingProofExpires,
		);
	}
        
        /**
	 * Validates a hosting proof secret
	 *
         * @param string $sPurpose Purpose of validation
	 * @param string $sNonce One time used nonce just to generate this proof (nonce will also be sent in the same request, so the request receiver will be able to check if the proof is real)
	 * @param string $sExpires hosting secret proof expiry
         * @param string $sDomainHostingSecret This parameter is known to both sides already
         * @param string $sHostingProof hosting proof to be validated
	 * @return array Returnes true if valid, otherwise returns false
	 */
	public static function validateHostingProof ( $sPurpose, $sNonce, $sExpires, $sDomainHostingSecret, $sHostingProof ) {
            // Check if timed out
            if (time() > $sExpires) {
                return false;
            }
            
            // Check if is equal by calculating
            $sHostingProofCalcRaw = $sPurpose . ':' . $sNonce . ':' . $sDomainHostingSecret . ":{$sExpires}";
            $sHostingSecretProofCalculated = sha1($sHostingProofCalcRaw);
            if ($sHostingSecretProofCalculated != $sHostingProof) {
                return false;
            }
            
            return true;
	}
	
	/**
	 * Generates the server authentication token that is valid for 30 minutes for the legacy OAuth login scenarios
	 *
	 * @param string $sClientAuthenticationToken The authentication token generated and sent by the client
	 * @param string $sIdentityType Could be: facebook, twitter or linkedin
	 * @param string $sIdentifier The identity to be authenticated
	 * @param string $sAuthenticationNonce Some server-generated crypto-random string
	 * @return string $sServerAuthenticationToken The server authentication token generated using given parameters
	 */
	public static function generateServerAuthenticationToken ( $sClientAuthenticationToken, $sIdentityType, $sIdentifier, $sAuthenticationNonce ) {
		// Generate separate building blocks of serverAuthenticatonToken
		$sInnerToken = CryptoUtil::generateInnerToken( $sClientAuthenticationToken, $sIdentityType, $sIdentifier, $sAuthenticationNonce );
		$sExpiry = time() + 60 * 30; // Expires in 30 minutes
		$sHashValidation = CryptoUtil::gimmeHmac('validate:' . PROVIDER_MAGIC_VALUE . ':' . $sExpiry . ':' . $sInnerToken, PROVIDER_MAGIC_VALUE);
		
		// Calculate and return the token
		$sServerAuthenticationToken = $sInnerToken . '-' . $sExpiry . '-' . $sHashValidation;
		return $sServerAuthenticationToken;
	}
	
	/**
	 * Checks if given serverAuthenticationToken is valid by double challanging it
	 * (first the innerToken validity, and then the hashValidation validity)
	 *
	 * @param string $sClientAuthenticationToken Token generated by the client
	 * @param string $sServerAuthenticationToken Token Generated by the server
	 * @param string $sIdentityType Type of the identity
	 * @param string $sIdentifier Identity
	 * @return boolean Returns true if the token is valid, otherwise returns false
	 */
	public static function verifyServerAuthenticationToken( $sClientAuthenticationToken, $sServerAuthenticationToken, $sIdentityType, $sIdentifier ) {
		// Break the serverAuthenticationToken into three separate parts: the innerToken, the expiry and the hash validation
		$aServerAuthenticationToken = explode('-', $sServerAuthenticationToken);
		
		$sAuthenticationNonce = '';
		// Challange authenticationNonce existance
		if ( !(key_exists( 'authenticationNonce', $_SESSION )) ) {
			return false;
		} else {
			$sAuthenticationNonce = $_SESSION['authenticationNonce'];
		}
		
		// Challange serverAuthenticationToken expiry
		if ( $aServerAuthenticationToken[1] < time() ) {
			return false;
		}
		
		// Challange innerToken's validity
		$sInnerTokenCalculated = CryptoUtil::generateInnerToken( $sClientAuthenticationToken, $sIdentityType, $sIdentifier, $sAuthenticationNonce );
		if ( $aServerAuthenticationToken[0] != $sInnerTokenCalculated ) {
			return false;
		}
		
		// Challange hashValidation's validity
		$sHashValidationCalculated = CryptoUtil::gimmeHmac('validate:' . PROVIDER_MAGIC_VALUE . ':' . $aServerAuthenticationToken[1] . ':' . $sInnerTokenCalculated, PROVIDER_MAGIC_VALUE);
		if ( $aServerAuthenticationToken[2] != $sHashValidationCalculated ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Generate and update the database with identity access data
	 *
	 * @param string $sIdentityType type of the identity
	 * @param string $sIdentifier identity
	 * @return array Returns an array which is consisted of identity access data
	 */
	public static function generateIdentityAccess ( $sIdentityType, $sIdentifier ) {
		// Generate token and secret
                $sAccessSecretExpires = time() + 60*1440*60; // 60secs * 1440mins/day * 60days = 2months
		$sAccessToken = $sIdentityType . '-' . $sIdentifier . '-' . $sAccessSecretExpires . '-' . CryptoUtil::generateNonce();
		$sAccessSecret = CryptoUtil::calculateAccessSecret($sAccessToken);
		
		// return the identity access data
		return array (
		'accessToken'			=> $sAccessToken,
		'accessSecret'			=> $sAccessSecret,
		'accessSecretExpires'	=> $sAccessSecretExpires
		);		
	}
	
	/**
	 * Calculate AccessSecret based on given accessToken
	 *
	 * @param string $sAccessToken Access token
	 * @return string Returns calculated accessSecret
	 */
	public static function calculateAccessSecret ( $sAccessToken ) {
		$sAccessSecretBase = "identity-access-secret:" . $sAccessToken . '-' . PROVIDER_MAGIC_VALUE;
		return CryptoUtil::gimmeHash($sAccessSecretBase); 
	}
	
	/**
	 * Validates the accessSecretProof
	 *
	 * @param string $sClientNonce A nonce the client generated for this purpose only
	 * @param string $sAccessToken The accessToken that client received from the server earlier
	 * @param string $sAccessSecretProof The proof the client calculated. That's what the server also calculates to challange it
	 * @param string $sAccessSecretExpires The window in which the proof is valid
	 * @param string $sIdentityType Could be: 'federated', 'email', 'phone', 'facebook', 'twitter' or 'linkedin'
	 * @param string $sIdentifier The identity that is performing the validation
	 * @param string $sUri Full identity uri
	 * @param string $sPurpose A string we use to calculate the proof (different requests have different purpose values)
	 * @return boolean Returns true if validation succeeded. Otherwise returns false.
	 */
	public static function validateIdentityAccessSecretProof ( $sClientNonce, $sAccessToken, $sAccessSecretProof, $sAccessSecretExpires, $sIdentityType, $sIdentifier, $sUri, $sPurpose ) {
		APIEventLog('function call: validateIdentityAccessSecretProof(' .
					'clientNonce=' . $sClientNonce . ' accessToken=' . $sAccessToken . ' accessSecretProof=' . $sAccessSecretProof . ' accessSecretExpires=' . $sAccessSecretExpires . ' uri=' . $sUri . ' purpose=' . $sPurpose);
		// Challange the token first
                $aAccessToken = explode('-', $sAccessToken);
                if ($aAccessToken[0] != $sIdentityType ||           // type must match
                        $aAccessToken[1] != $sIdentifier ||         // identifier must match
                        $aAccessToken[2] < time() ) {               // token must not be expired
                    return false;
                }

                // Calculate accessSecret
		$sAccessSecret = CryptoUtil::calculateAccessSecret($sAccessToken);
		APIEventLog('accessSecret=' . $sAccessSecret);
											
		// Calculate accessSecretProof
		$sAccessSecretProofCalculated = CryptoUtil::generateAccessSecretProof( $sUri , $sClientNonce , $sAccessSecretExpires , $sAccessToken , $sPurpose, $sAccessSecret );
		APIEventLog('accessSecretProofCalculated=' . $sAccessSecretProofCalculated);
			
		// Challange accessSecretProof
		if ( $sAccessSecretProof != $sAccessSecretProofCalculated ) {
			return false;
		}
		return true;
	}
	
	//----------------------------------------------------------------------------------------------------------------------------------------------------//
	
	/*-------------------
	  Private functions
	-------------------*/
	
	private static function makeRandomString( $bits = 256 ) {
	    $bytes = ceil($bits / 8);
	    $return = '';
	    for ($i = 0; $i < $bytes; $i++) {
	        $return .= chr(mt_rand(0, 255));
	    }
	    return $return;
	}
	
	private static function generateServerLoginInnerProof ( $sIdentifier, $sPasswordHash, $sIdentitySecretSalt, $sServerPasswordSalt ) {
		$sServerLoginInnerProofInnerHmac = CryptoUtil::gimmeHmac('password-hash:' . $sIdentifier . ':' . base64_encode($sServerPasswordSalt), $sPasswordHash);
		$sIdentitySaltHash = CryptoUtil::gimmeHash('salt:' . $sIdentifier . ':' . base64_encode($sIdentitySecretSalt));
		return CryptoUtil::gimmeHash(PROVIDER_MAGIC_VALUE . ':' . $sServerLoginInnerProofInnerHmac . ':' . $sIdentitySaltHash);
	}
	
	private static function generateServerLoginProof ( $sServerLoginInnerProof, $sServerNonce ) {
		return CryptoUtil::gimmeHmac('final:' . $sServerLoginInnerProof . ':' . $sServerNonce, PROVIDER_MAGIC_VALUE);
	}
	
	private static function generateInnerToken ( $sClientAuthenticationToken, $sIdentityType, $sIdentifier, $sAuthenticationNonce ) {
		return CryptoUtil::gimmeHash('innerToken:' . $sClientAuthenticationToken . ':' . $sIdentityType . $sIdentifier . ':' . $sAuthenticationNonce . ':' . PROVIDER_MAGIC_VALUE);
	}
	
	private static function generateAccessSecretProof ( $sUri, $sClientNonce, $sAccessSecretExpires, $sAccessToken, $sPurpose, $sAccessSecret ) {
		$sMessage = 'identity-access-validate:' . $sUri . ':' . $sClientNonce . ':' . $sAccessSecretExpires . ':' . $sAccessToken . ':' . $sPurpose;
		APIEventLog('identityAccessSecretProof generation message=' . $sMessage);
		return CryptoUtil::gimmeHmac( $sMessage, $sAccessSecret );
	}
	
}

?>

