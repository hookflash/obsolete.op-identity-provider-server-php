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
require_once(ROOT . 'utils/jsonUtil.php');

define('PROFILE_ULR_BASE_CONST', DOMAIN . '/get/profile/profile.php?');
define('VPROFILE_ULR_BASE_CONST', DOMAIN . '/get/profile/profile.php?vprofile=1');

/**
 * Class ProfileUtil is a utility class that provides public profile data for given input.
 */
class ProfileUtil {
	
    // Usefull public constants
    const PROFILE_ULR_BASE = PROFILE_ULR_BASE_CONST;
    const VPROFILE_ULR_BASE = VPROFILE_ULR_BASE_CONST;
	
    /**
     * Try sending a profile-get request using cURL, for given data
     * 
     * @param string $sIdentityType could be: 'custom', 'email', 'phone', 'facebook', 'linkedin', 'twitter'
     * @param string $sIdentifier An identifier of the identity (such as username or e-mail)
     * @return array $aResultObject An array created from JSON result
     */
    public static function sendProfileGet ( $nRequestId, $sIdentifier ) {
        // import necessary files
        require_once(ROOT . 'utils/curlUtil.php');
		
        // URL of identityService server
        $url = DOMAIN . '/api.php';
		
        // Request data
        $requestData = '' .
        '{' .
            '"request": {' .
                '"$domain": "provider.com",' .
                '"$id": ' . $nRequestId . ',' .
                '"$handler": "identity-provider",' .
                '"$method": "profile-get",' .
                '"identity": {' .
                    '"identifier": "' . $sIdentifier . '"' .
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
	
}

?>