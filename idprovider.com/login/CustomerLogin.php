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

// Set session_id
if ( session_id() === '' ) {
	session_start();
}
// Make sure session expires in 30 minutes
if ( !isset( $_SESSION['created'] ) ) {
    $_SESSION['created'] = time();
} else if ( time() - $_SESSION['created'] > 1800 ) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
// Set required imports
require (ROOT . 'utils/loginUtil.php');
require (ROOT . 'model/User.php');
require_once (ROOT . 'utils/cryptoUtil.php');
require_once (ROOT . 'config/config.php');
require_once (ROOT . 'utils/requestUtil.php');
require_once (ROOT . 'libs/mySQL/class-mysqlidb.php');
require_once (ROOT . 'libs/http/http.php');
require_once (ROOT . 'libs/oauth/oauth_client.php');

/**
 * Class SocialLogin provides all the needed features 
 * for the social networks and oAuth services
 * (facebook, linkedin, twitter, github...) login scenarios
 */
class CustomerLogin {

    public $DB = null;
    public $sIdentityType = '';
    public $aRequest = null;
    public $oUser = null;

    /**
     * A constructor setting all the needed instance variables
     *
     * @param $sIdentityType could be only 'customer'
     * @param $aRequest The request that has all the needed data to perform a login
     */
    public function __construct($sIdentityType, $aRequest) {
        $this->sIdentityType = $sIdentityType;
        $this->aRequest = $aRequest;

        $this->DB = new mysqldb(APP_DB_NAME, APP_DB_HOST, APP_DB_USER, APP_DB_PASS);
        $this->oUser = new User($this->DB);
    }
	
    /**
     * This function does two basic things:
     * 1. It reads the request data and places it into the session for later use
     * 2. It genererates and returns the URL for the client to 
     *      redirect immediately after in order to perfrom a social login
     *
     * @return array $aAuthenticationResult An array that just has the redirectURL set
     */
    public function authentication() { 
        // Take data from the request
        $aRequestData = RequestUtil::takeSocialProviderAuthenticationRequestData($this->aRequest);

        // Store data in session for later use
        $_SESSION['appid'] = $aRequestData['appid'];
        $_SESSION['callbackURL'] = $aRequestData['callbackURL'];
        $_SESSION['identity'] = $aRequestData['identity'];

        // Return the redirect URL 
        $aAuthenticationResult['redirectURL'] = 'http://' . DOMAIN . '/get/social/customerLogin.php';

		LOG_EVENT('$aAuthenticationResult: ' . var_export($aAuthenticationResult, true));

        return $aAuthenticationResult;
    }
	
    // /**
    //  * Logs the user in with a given 'social' identity
    //  *
    //  * @return array $aLoginResult Returns array of data to be returnd to the client that performed the login request in the first place
    //  */
    // public function login() {		
    //     // Take data from the request
    //     $aRequestData = RequestUtil::takeLoginRequestData($this->aRequest);

    //     // Try logging the user in using the given identity
    //     $aUser = $this->oUser->signInUsingSocial(
    //         $aRequestData['identity']['type'],
    //         $aRequestData['identity']['identifier']
    //     );

    //     // Return 'No such identity' error code
    //     if ($aUser['user_id'] == '') {
    //         throw new RestServerException('003', array(
    //             'type' => $aRequestData['identity']['type'],
    //             'identifier' => $aRequestData['identity']['identifier'])
    //         );
    //     }

    //     // Validate the client that is performing the login request
    //     require_once(ROOT . 'utils/cryptoUtil.php');
    //     $bVerificationResult = CryptoUtil::verifyServerAuthenticationToken( 
    //         $aRequestData['proof']['clientAuthenticationToken'],
    //         $aRequestData['proof']['serverAuthenticationToken'],
    //         $aRequestData['identity']['type'],
    //         $aRequestData['identity']['identifier']
    //     );
    //     if ( !$bVerificationResult ) {
    //         throw new RestServerException('007', array(
    //             'parameter' => 'serverAuthenticationToken',
    //             'parameterValue' => $aRequestData['serverAuthenticationToken'])
    //         );
    //     }

    //     // Generate and store accessToken and accessSecret for logged in identity
    //     $aIdentityAccessResult = CryptoUtil::generateIdentityAccess(
    //         $aRequestData['identity']['type'],
    //         $aRequestData['identity']['identifier']
    //     );
    //     $aIdentityAccessResult['updated'] = $aUser['updated'];

    //     // Keep info about logged in identity in session
    //     $_SESSION['logged-in-identity'] = $aRequestData['identity'];
        
    //     // Inform the identity service about the identity registration/
    //     $aHostingData = LoginUtil::generateHostingData('hosted-identity-update');
    //     $aRequestData['identity']['uri'] = LoginUtil::calculateIdentityUri($aRequestData);
    //     $aRequestData['identity']['profile'] = $aUser['profile_url'];
    //     $aRequestData['identity']['displayName'] = $aUser['full_name'];
        
    //     // Send hookflash-login-confirm request to the IdentityService server
    //     $aIdentityUpdateResult = LoginUtil::sendHostedIdentityUpdate( 
    //         CryptoUtil::generateRequestId(), 
    //         $aRequestData, 
    //         $aHostingData, 
    //         $aUser 
    //     );

    //     LOG_EVENT('SocialLogin - login - $aIdentityUpdateResult: ' . var_export($aIdentityUpdateResult, true));

    //     // Return 'Login failed' error
    //     if ( $aIdentityUpdateResult == null || key_exists( 'error', $aIdentityUpdateResult ) ) {
    //         throw new RestServerException('005', 
    //             array(
    //                 'message' => $aIdentityUpdateResult['error']['reason']
    //             )
    //         );
    //     }

    //     // Since everything went well, return no error code and fill loginResult with the rest of the data
    //     return array(
    //         'identity'      => $aIdentityAccessResult,
    //         'lockboxReset'  => 'false',
    //         'lockboxKey'    => !empty($aUser['lockbox_half_key_encrypted']) ? $aUser['lockbox_half_key_encrypted'] : ''
    //     );
    // }
	
    /**
     * TODO
     */
    public function performCustomerLogin() {
        $client = new oauth_client_class;
	    $client->debug = true;
	    $client->debug_http = true;
	    $client->redirect_uri = 'http://' . DOMAIN . '/get/social/customerLogin.php';

	    $client->client_id = CUSTOMER_OAUTH_SERVICE;
	    $client->client_secret = CUSTOMER_OAUTH_SERVICE_SECRET;
        $client->dialog_url = CUSTOMER_OAUTH_SERVICE_URL;

	    if(strlen($client->client_id) == 0
			|| strlen($client->client_secret) == 0) {
		    die('OAuth consumer id or consumer secret is empty.');
		}
    
        if(($success = $client->Initialize()))
		{     
		    if(($success = $client->Process()))
		    {
		        if(strlen($client->access_token))
		        {
		            $success = $client->CallAPI(
		                CUSTOMER_OAUTH_SERVICE_TOKEN_URL, 
		                'GET', array(), array(), $user);
		        }
		    }
		    $success = $client->Finalize($success);
		}
		if($client->exit)
		    exit;
		if($success)
		{
            die('prosao');
			// TODO
		}
    }
	
    //--------------------------------------------------------------------------------------------------------------------------//

    // private function identityServiceAuthentication( $sProviderUserId, $sProviderUsername, $sProfileFullname, $sProfileUrl, $sProfileAvatarUrl, $sToken, $sSecret ) {
    //     // Create a default result
    //     $aAuthenticationResult = array (
    //         'errorMessage' => 'none'
    //     );

    //     // Set the initial value of the redirectURL
    //     $sRedirectURL = $_SESSION['callbackURL'];
    //     if ( !strpos( $sRedirectURL, '?' ) ) {
    //         $sRedirectURL .= '?'; // add '?' if no parameters added to the url
    //     } else {
    //         $sRedirectURL .= '&'; // add '&' if the url already contains some parameters
    //     }
    //     // Verify the validity of given parameters
    //     if ( $sProviderUserId == '' || $sProfileFullname == '' ) {
    //         $aAuthenticationResult['errorMessage'] = 'Sign in failed due to missing parameter values';
    //         $sRedirectURL .= urlencode('{"reason":{"error":"' . $aAuthenticationResult['errorMessage'] . '"}}');
    //         $_SESSION['identityServiceAuthenticationURL'] = $sRedirectURL;
    //         header ( 'Location: ' . $_SESSION['callbackURL'] );
    //     }
    //     // Get the parameters given as a successful login indication
    //     $aSignInResult = $this->oUser->signInAfterSocialProviderLogin(
    //             $_SESSION['identity']['type'],
    //             $sProviderUserId,
    //             $sProviderUsername,
    //             $sProfileFullname,
    //             $sProfileUrl,
    //             $sProfileAvatarUrl,
    //             $sToken,
    //             $sSecret
    //             );
    //     // If there is no identity returned by signInAfterSocialProviderLogin()
    //     if ( !$aSignInResult || empty( $aSignInResult ) ) {
    //         $aAuthenticationResult['errorMessage'] = 'Sign in failed';
    //         $sRedirectURL .= urlencode('{"reason":{"error":"' . $aAuthenticationResult['errorMessage'] . '"}}');
    //     }
    //     // Since everything went well:
    //     else {			
    //         // Generate and store the authenticationNonce and serverAuthenticationToken
    //         $sAuthenticationNonce = CryptoUtil::generateNonce();
    //         $_SESSION['authenticationNonce'] = $sAuthenticationNonce;
    //         $sServerAuthenticationToken = CryptoUtil::generateServerAuthenticationToken(
    //                 $_SESSION['clientAuthenticationToken'],
    //                 $aSignInResult['providerType'],	
    //                 $aSignInResult['identifier'],
    //                 $sAuthenticationNonce
    //                 );
    //         // Fill the result with valid values
    //         require_once (ROOT . 'utils/loginUtil.php');
    //         $aAuthenticationResult['loginState'] = LoginStates::SOCIAL_AUTHENTICATION_SUCCEEDED;				
    //         $aIdentity = array (
    //             'type'			=> $aSignInResult['providerType'],
    //             'identifier'	=> $aSignInResult['identifier']
    //         );
    //         $aAuthenticationResult['identity'] = $aIdentity;				
    //         $aAuthenticationResult['serverAuthenticationToken'] = $sServerAuthenticationToken;

    //         // Generate the redirect URL string
    //         $sIdentityData = '"identity":{"type":"' . $aIdentity['type'] . '","identifier":"' . $aIdentity['identifier'] . '"}';

    //         $sResult = '' .
    //                 '{"result":{' .
    //                 '"loginState":"' . $aAuthenticationResult['loginState'] . '",' .
    //                 $sIdentityData;
    //         if ( key_exists( 'serverAuthenticationToken', $aAuthenticationResult ) ) {
    //             $sResult .= ',"serverAuthenticationToken":"' . $aAuthenticationResult['serverAuthenticationToken'] . '"';
    //         }	
    //         $sResult .= '}}';
    //         $sResult = urlencode($sResult);
    //         $sRedirectURL .= $sResult;
    //     }
    //     // Do the redirect
    //     $_SESSION['identityServiceAuthenticationURL'] = $sRedirectURL;
    //     header ( 'Location: ' . $_SESSION['callbackURL'] );
    // }

    private function informIdentityServiceAboutSignUp ( $sIdentityType, $sProviderUserId, $sProfileFullname, $sProfileUrl, $sProfileAvatarUrl, $sUpdated ) {
        $sIdentityUri = $this->generateIdentityUri($sIdentityType, $sProviderUserId);

        $aRequestData = array (
                'identity' => array (
                        'uri' => $sIdentityUri,
                        'displayName' => $sProfileFullname,
                        'profile' => $sProfileUrl,
                        'vprofile' => '',
                        'avatars' => array (
                                'avatar' => array (
                                        'name' => '',
                                        'url' => $sProfileAvatarUrl,
                                        'width' => '',
                                        'height' => ''
                                )
                        )
                ) 
        );		
        $aHostingData = LoginUtil::generateHostingData('hosted-identity-update');
        $aUser = array (
            'updated' => $sUpdated
        );

        $aIdentityUpdateResult = LoginUtil::sendHostedIdentityUpdate ( CryptoUtil::generateRequestId(), $aRequestData, $aHostingData, $aUser );
        return $aIdentityUpdateResult;
    }

    private function generateIdentityUri( $sIdentityType, $sProviderUserId ) {
        return 'identity://' . $sIdentityType . '.com/' . $sProviderUserId;
    }

    private function validateProfileForTokenExchange( $sIdentifier, $aProfile ) {
        // Create a default result
        $aResult = array (
        'validationSucceeded' => 'false',
        'validationMessage'   => 'Profile data doesn\'t match'
        );

        if ( $sIdentifier == $aProfile['person']['id'] ) {
            $aResult['validationSucceeded'] = 'true';
            $aResult['validationMessage'] = 'Validated';			
        }

        return $aResult;
    }

    private function createHostingDataForURL ( $aHostingData ) {
        $sHostingDataInitial = '(' .
                'nonce=' . $aHostingData['nonce'] . ',' .
                'hostingProof=' . $aHostingData['hostingProof'] . ',' .
                'hostingProofExpires=' . $aHostingData['hostingProofExpires'] . ',' .
                ')';
        return urlencode( base64_encode( $sHostingDataInitial ) );
    }

    private function createIdentityForURL ( $aSignInResult, $aLoginConfirmResult ) {
        $sIdentityInitial = '(' .
                'type=' . $aSignInResult['providerType'] . ',' .
                'identifier=' . $aSignInResult['identifier'] . ',' .
                'updated=' . $aSignInResult['updated'] . ',' .
                'created=' . $aSignInResult['created'] . ',' .
                'accessToken=' . $aLoginConfirmResult['identity']['accessToken'] . ',' .
                'accessSecret=' . $aLoginConfirmResult['identity']['accessSecret'] . ',' .
                'accessSecretExpires=' . $aLoginConfirmResult['identity']['accessSecretExpires'] . ',' .
                ')';
        return urlencode( base64_encode( $sIdentityInitial ) );
    }
	
}
?>