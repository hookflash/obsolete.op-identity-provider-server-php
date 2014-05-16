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
require_once(ROOT . 'utils/cryptoUtil.php');

/**
 * Class LoginUtil is a utility class that is being used by other login classes to perform outgoing requests.
 */
class LoginUtil {
	
    /**
     * Generate nonce, hostingProof, hostingProofExpires and uri
     *
     * @param CryptUtil $oCryptoUtil A CryptoUtil object to be used for cryptographic purposes
     * @return array Returns nonce, hostingProof, hostingProofExpires
     */
    public static function generateHostingData ( $sMethodName ) {
        // Generate the nonce
        $sNonce = CryptoUtil::generateNonce();

        // Generate the hostingProof
        $aHostingProofData = CryptoUtil::generateHostingProof($sMethodName, $sNonce, DOMAIN_HOSTING_SECRET);

        return array(
        'nonce'=>$sNonce,
        'hostingProof'=>$aHostingProofData['hostingProof'],
        'hostingProofExpires'=>$aHostingProofData['hostingProofExpires']
        );
    }
        
    /**
     * TODO document
     */
    public static function calculateIdentityUri ( $aRequestData ) {
        $sUri = '';
        $sMyDomain = str_replace('https://','',DOMAIN);
        switch ($aRequestData['identity']['type']) {
            case 'federated':
                $sUri = 'identity://' . $sMyDomain . '/' . $aRequestData['identity']['identifier'];
                break;
            case 'email':
                // TODO
                break;
            case 'phone':
                // TODO
                break;
            case 'facebook':
                $sUri = 'identity://facebook.com/' . $aRequestData['identity']['identifier'];
                break;
            case 'twitter':
                // TODO
                break;
            case 'linkedin':
                // TODO
                break;
        }
        return $sUri;
    }
	
    /**
     * Try sending a hosted-identity-update request using cURL, for given data
     * 
     * @param number $nRequestId Id of the request to be sent (and of the result to be received, too)
     * @param array $aRequestData Array of data being previously given to the server through an initial request (sign-up or profil--update)
     * @param array $aHostingData Array of data that server generated upon receiving the login request
     * (such as: nonce, hostingProof, hostingProofExpires)
     * @param array $aUser Array of data taken from the user table
     * @return array $aResultObject An array created from JSON result
     */
    public static function sendHostedIdentityUpdate ( $nRequestId, $aRequestData, $aHostingData, $aUser ) {
        // import necessary files
        require_once(ROOT . 'utils/curlUtil.php');
        require_once(ROOT . 'utils/jsonUtil.php');

        // URL of identityService server
        $url = HF_SERVICE_DOMAIN . 'hostedidentity';

        $sAvatars = '';
        $nAvatar = 0;
        while ( isset($aRequestData['identity']['avatars'][$nAvatar]) ){
            $sAvatars .= '' .
            '{' .
                '"name": "' . LoginUtil::setOptional($aRequestData['identity']['avatars'][$nAvatar]['name']) . '",' .
                '"url": "' . LoginUtil::setOptional($aRequestData['identity']['avatars'][$nAvatar]['url']) . '",' .
                '"width": "' . LoginUtil::setOptional($aRequestData['identity']['avatars'][$nAvatar]['width']) . '",' .
                '"height": "' . LoginUtil::setOptional($aRequestData['identity']['avatars'][$nAvatar]['height']) . '"' .
            '}';
            $nAvatar++;
            if ( isset($aRequestData['identity']['avatars'][$nAvatar]) ) {
                $sAvatars .= ',';
            }
        }

        // Request data
        $requestData = '' .
        '{' .
            '"request": {' .
                '"$domain": "' . DOMAIN . '",' .
                '"$id": "' . $nRequestId . '",' .
                '"$handler": "identity-service",' .
                '"$method": "hosted-identity-update",' .

                '"nonce": "' . $aHostingData['nonce'] . '",' .
                '"hostingProof": "' . $aHostingData['hostingProof'] . '",' .
                '"hostingProofExpires": "' . $aHostingData['hostingProofExpires'] . '",' .
                '"identities": {' .
                    '"identity": [' .
                        '{' .
                            '"uri": "' . $aRequestData['identity']['uri'] . '",' .
                            '"updated": "' . $aUser['updated'] . '"';
        if ( isset($aRequestData['identity']['displayName']) ) {
            $requestData .= ',"name": "' . LoginUtil::setOptional($aRequestData['identity']['displayName']) . '"';
        }
        if ( isset($aRequestData['identity']['profile']) ) {
            $requestData .= ',"profile": "' . LoginUtil::setOptional($aRequestData['identity']['profile']) . '"';
        }
        if ( isset($aRequestData['identity']['vprofile']) ) {
            $requestData .= ',"vprofile": "' . LoginUtil::setOptional($aRequestData['identity']['vprofile']) . '"';
        }
        if ( isset($aRequestData['identity']['feed']) ) {
            $requestData .= ',"feed": "' . LoginUtil::setOptional($aRequestData['identity']['feed']) . '"';
        }
        $requestData .=	',' .
                            '"avatars": {' .
                                '"avatar": [' .
                                    $sAvatars .
                                ']' .	
                            '}' .
                        '}' .
                    ']' .
                '}' .
            '}' .
        '}';

        // Send cURL request
        $sResult = CurlUtil::sendPostRequest($url, $requestData);

        // Convert the result to an array
        $aResultObject = JsonUtil::jsonToArray($sResult, false);

        // If the id of the request matches with the id of the result, return the marshalled result, otherwise return null
        if ( ( $aResultObject != null ) && ( key_exists ( 'id', $aResultObject['request_attr'] ) ) && ( $aResultObject['request_attr']['id'] == $nRequestId ) ) {
            return $aResultObject['request'];
        } else {
            return null;
        }
    }
	
    /**
     * Try sending an identity-lookup request using cURL, for given data
     * 
     * @param string $sIdentityType could be 'email' or 'phone'
     * @param string $sIdentifier e-mail or phone number
     * @return array $aResultObject An array created from JSON result
     */
    public static function sendIdentityLookup ( $nRequestId, $sIdentityType, $sIdentifier ) {
        // import necessary files
        require_once(ROOT . 'utils/curlUtil.php');

        // URL of identityService server
        $url = HF_SERVICE_DOMAIN . 'identity';

        // Request data
        $requestData = '' .
        '{' .
            '"request": {' .
                '"$domain": "' . DOMAIN . '",' .
                '"$id": "' . $nRequestId . '",' .
                '"$handler": "identity",' .
                '"$method": "identity-lookup",' .

                '"providers": {' .
                    '"provider": [' .
                        '{' .
                            '"base": "identity:' . $sIdentityType . ':",' .
                            '"separator": ",",' .
                            '"identities": "' . $sIdentifier . '"' .
                        '}' .
                    ']' .
                '}' .
            '}' .
        '}';

        // Send cURL request
        $sResult = CurlUtil::sendPostRequest($url, $requestData);

        // Convert the result to an array
        $aResultObject = JsonUtil::jsonToArray($sResult, false);

        // If the id of the request matches with the id of the result, return the marshalled result, otherwise return null
        if ( ( $aResultObject != null ) && ( key_exists ( 'id', $aResultObject['request_attr'] ) ) && ( $aResultObject['request_attr']['id'] == $nRequestId ) ) {
            return $aResultObject['request'];
        } else {
            return null;
        }
    }
	
    public static function sendProviderDelete () {
        // import necessary files
        require_once(ROOT . 'utils/curlUtil.php');
        require_once(ROOT . 'utils/cryptoUtil.php');

        // URL of identityService server
        $url = HF_SERVICE_DOMAIN . 'registration';

        // Request data
        $requestData = '' .
        '{' .
            '"request": {' .
                '"$domain": "' . DOMAIN . '",' .
                '"$id": "' . CryptoUtil::generateRequestId() . '",' .
                '"$handler": "customer-service",' .
                '"$method": "provider-delete",' .

                '"provider": {' .
                    '"providerId": 100' .
                '}' .
            '}' .
        '}';

        // Send cURL request
        $sResult = CurlUtil::sendPostRequest($url, $requestData);
        die($sResult);
        // Convert the result to an array
        $aResultObject = JsonUtil::jsonToArray($sResult, false);

        // If the id of the request matches with the id of the result, return the marshalled result, otherwise return null
        if ( ( $aResultObject != null ) && ( key_exists ( 'id', $aResultObject['request_attr'] ) ) && ( $aResultObject['request_attr']['id'] == $nRequestId ) ) {
            return $aResultObject['request'];
        } else {
            return null;
        }
    }
	
    //--------------------------------------------------------------------------------------------------------------------------//
	
    /*-------------------
      Private functions
    -------------------*/
	
    private static function setOptional ( $sOptional ) {
        if ( $sOptional == '' || $sOptional == 'Array' || $sOptional == null ) {
            return '';
        } else {
            return $sOptional;
        }
    }
	
}

/**
 * Class LoginStates is enum-like class that provides needed constant login states
 *
 */
class LoginStates {
	 
    const SUCCEEDED = 'Succeeded';
    const OAUTH_AUTHENTICATION_SUCCEEDED = 'OAuthAuthenticationSucceeded';
    const PIN_VALIDATION_REQUIRED = 'PinValidationRequired';
	
}

?>