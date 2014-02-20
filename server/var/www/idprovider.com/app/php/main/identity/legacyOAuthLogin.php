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
require (APP . 'php/main/utils/loginUtil.php');
require (APP . 'php/main/identity/user.php');
require_once (APP . 'php/main/utils/cryptoUtil.php');
require_once (APP . 'php/config/config.php');
require_once (APP . 'php/main/utils/requestUtil.php');
require_once (APP . 'php/libs/mySQL/class-mysqlidb.php');

/**
 * Class LegacyOAuthLogin provides all the needed features 
 * for the social networks (facebook, linkedin, twitter, github) login scenarios
 */
class LegacyOAuthLogin {

    public $DB = null;
    public $sIdentityType = '';
    public $aRequest = null;
    public $oUser = null;

    /**
     * A constructor setting all the needed instance variables
     *
     * @param $sIdentityType could be either 'facebook', 'linkedin' or 'twitter'
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
     *      redirect immediately after in order to perfrom an OAuth login
     *
     * @return array $aAuthenticationResult An array that just has the redirectURL set
     */
    public function authentication() {
        // Take data from the request
        $aRequestData = RequestUtil::takeOAuthProviderAuthenticationRequestData($this->aRequest);

        // Store data in session for later use
        $_SESSION['appid'] = $aRequestData['appid'];
        $_SESSION['clientAuthenticationToken'] = $aRequestData['clientAuthenticationToken'];
        $_SESSION['callbackURL'] = $aRequestData['callbackURL'];
        $_SESSION['identity'] = $aRequestData['identity'];

        // Return the redirect URL 
        $aAuthenticationResult['redirectURL'] = 'http://' . DOMAIN . '/php/oauth/oauthLogin.php';
        APIEventLog('aAuthenticationResult[redirectURL]=' . $aAuthenticationResult['redirectURL']);

        LOG_EVENT('$aAuthenticationResult: ' . var_export($aAuthenticationResult, true));

        return $aAuthenticationResult;
    }
	
    /**
     * Logs the user in with a given 'legacyOAuth' identity
     *
     * @return array $aLoginResult Returns array of data to be returnd to the client that performed the login request in the first place
     */
    public function login() {		
        // Take data from the request
        $aRequestData = RequestUtil::takeLoginRequestData($this->aRequest);

        // Try logging the user in using the given identity
        $aUser = $this->oUser->signInUsingLegacyOAuth(
            $aRequestData['identity']['type'],
            $aRequestData['identity']['identifier']
        );

        // Return 'No such identity' error code
        if ($aUser['user_id'] == '') {
            throw new RestServerException('003', array(
                'type' => $aRequestData['identity']['type'],
                'identifier' => $aRequestData['identity']['identifier'])
            );
        }

        // Validate the client that is performing the login request
        require_once(APP . 'php/main/utils/cryptoUtil.php');
        $bVerificationResult = CryptoUtil::verifyServerAuthenticationToken( 
            $aRequestData['proof']['clientAuthenticationToken'],
            $aRequestData['proof']['serverAuthenticationToken'],
            $aRequestData['identity']['type'],
            $aRequestData['identity']['identifier']
        );
        if ( !$bVerificationResult ) {
            throw new RestServerException('007', array(
                'parameter' => 'serverAuthenticationToken',
                'parameterValue' => $aRequestData['serverAuthenticationToken'])
            );
        }

        // Generate and store accessToken and accessSecret for logged in identity
        $aIdentityAccessResult = CryptoUtil::generateIdentityAccess(
            $aRequestData['identity']['type'],
            $aRequestData['identity']['identifier']
        );
        $aIdentityAccessResult['updated'] = $aUser['updated'];

        // Keep info about logged in identity in session
        $_SESSION['logged-in-identity'] = $aRequestData['identity'];
        
        // Inform the identity service about the identity registration/
        $aHostingData = LoginUtil::generateHostingData('hosted-identity-update');
        $aRequestData['identity']['uri'] = LoginUtil::calculateIdentityUri($aRequestData);
        $aRequestData['identity']['profile'] = $aUser['profile_url'];
        $aRequestData['identity']['displayName'] = $aUser['full_name'];
        
        // Send hookflash-login-confirm request to the IdentityService server
        $aIdentityUpdateResult = LoginUtil::sendHostedIdentityUpdate( 
            CryptoUtil::generateRequestId(), 
            $aRequestData, 
            $aHostingData, 
            $aUser 
        );

        LOG_EVENT('legacyOAuthLogin - login - $aIdentityUpdateResult: ' . var_export($aIdentityUpdateResult, true));

        // Return 'Login failed' error
        if ( $aIdentityUpdateResult == null || key_exists( 'error', $aIdentityUpdateResult ) ) {
            throw new RestServerException('005', 
                array(
                    'message' => $aIdentityUpdateResult['error']['reason']
                )
            );
        }

        // Since everything went well, return no error code and fill loginResult with the rest of the data
        return array(
            'identity'      => $aIdentityAccessResult,
            'lockboxReset'  => 'false',
            'lockboxKey'    => !empty($aUser['lockbox_half_key_encrypted']) ? $aUser['lockbox_half_key_encrypted'] : ''
        );
    }
	
//	/**
//	 * Exchange the JavaScript OAuth 2.0 compatible access token that is being red from the cookie
//	 * for an OAuth 1.0a compatible access token.
//	 * 
//	 * This has two benefits:
//	 * 1. An exchanged token will expire much later then the first one
//	 * 2. A php side will be able to fetch the logged-in user profile data directly from LinkedIn using it's REST API
//	 *
//	 * @param array $aRequestData Array of data that is red out of the request
//	 * @return array $aTokenExchangeResult = ( 'errorIndicator' => 'none', 					// If there is no error...
//	 * 										   'errorMessage', 								// Empty by deafult...
//	 *                                         'identitySecretSalt',
//	 * 										   'serverPasswordSalt', 						// These two are set only if there is some data about these in the database for the given identity.
//	 *                                         'nonce' 										// A nonce for the client to be able to verify the login request
//	 * 																						// that is about to happen immediatelly after this one.
//	 * 										  )
//	 */
//	public function performTokenExchange( $aRequestData ) {
//		// Set required imports
//		require (APP . 'php/libs/oauth/linkedin/LinkedIn.php');
//		require_once (APP . 'php/main/rest/request.php');
//		
//		// Create the object to be returned by this method
//		$aTokenExchangeResult = array(
//		'errorIndicator' => 'none',
//		);
//		
//		// Read the cookie
//		$sCookieName = 'linkedin_oauth_' . LINKEDIN_CONSUMER_KEY;
//		$oCredentials = json_decode($_COOKIE[$sCookieName]);
//		
//		// Validate the signature
//		$aSignatureValidationResult = $this->validateSignatureForTokenExchange(LINKEDIN_CONSUMER_KEY, LINKEDIN_CONSUMER_SECRET, $oCredentials);
//		if ( $aSignatureValidationResult['validationSucceeded'] === 'false' ) {
//			$aTokenExchangeResult['errorIndicator'] = '007';
//			$aTokenExchangeResult['errorMessage'] = $aSignatureValidationResult['validationMessage'];
//			return $aTokenExchangeResult;
//		}
//		
//		// Try fetching the token
//		$linkedin = new LinkedInOAuth(LINKEDIN_CONSUMER_KEY, LINKEDIN_CONSUMER_SECRET);
//		$token = $linkedin->exchangeAccessToken($oCredentials->access_token);
//		if ( !key_exists('oauth_token', $token) || $token['oauth_token'] == '' ||
//			 !key_exists('oauth_token_secret', $token) || $token['oauth_token_secret'] == '' )
//		{
//			$aTokenExchangeResult['errorIndicator'] = '007';
//			return $aTokenExchangeResult;
//		}
//		
//		// Save the tokens for future use (login request that is expected to happen immediatelly after, will need these tokens)
//		$_SESSION['oauth_token'] = $token['oauth_token'];
//		$_SESSION['oauth_token_secret'] = $token['oauth_token_secret'];
//		
//		// Fetch the profile
//		$linkedin = new LinkedInOAuth(
//			LINKEDIN_CONSUMER_KEY,
//			LINKEDIN_CONSUMER_SECRET,
//			$token['oauth_token'],
//			$token['oauth_token_secret']
//		);
//		$profile_result = $linkedin->oAuthRequest('http://api.linkedin.com/v1/people/~:(id,first-name,last-name,public-profile-url,headline,location,picture-url,date-of-birth,phone-numbers,summary)');
//		$oRequest = new Request($profile_result);
//		$aProfile = $oRequest->aPars;
//		
//		// Validate the profile
//		$aProfileValidationResult = $this->validateProfileForTokenExchange( $aRequestData['identity']['identifier'], $aProfile );
//		if ( $aProfileValidationResult['validationSucceeded'] == 'false' ) {
//			$aTokenExchangeResult['errorIndicator'] = '007';
//			$aTokenExchangeResult['errorMessage'] = $aSignatureValidationResult['validationMessage'];
//			return $aTokenExchangeResult;
//		}
//		
//		$aIdentitySalts = array (
//		'identitySecretSalt' => '',
//		'serverPasswordSalt' => '',
//		);
//		// If this is not the first time this user appears, there should be something about him in the database, 
//		// otherwise we don't even try fetching user's salts
//		if ( $this->oUser->isThereSuchUser('linkedin', $aProfile['person']['id']) ) {
//			// Try fetching the salts based on given identifier
//			$aIdentitySalts = $this->oUser->getIdentitySalts('linkedin', $aProfile['person']['id']);
//		}
//		if ( $aIdentitySalts['identitySecretSalt'] != '' && $aIdentitySalts['serverPasswordSalt'] != '' ) {
//			$aTokenExchangeResult = array_merge( $aTokenExchangeResult, 
//												 array( 'identitySecretSalt' => $aIdentitySalts['identity_secret_salt'],
//								   						'serverPasswordSalt' => $aIdentitySalts['server_password_salt']
//								  						)
//						   						);
//		}
//		
//		// Add nonce to the result
//		$aTokenExchangeResult['nonce'] = CryptoUtil::generateSelfValidatingNonce(10);
//		
//		return $aTokenExchangeResult;
//	}
	
    /**
     * Just a switch that branches out the execution based on given identity type
     * 
     */
    public function startOAuthLogin() {
        // Switch the execution based on OAuth provider
        switch ( $this->sIdentityType ){
            case 'linkedin':
                $this->loginUsingLinkedIn();
                break;
            case 'facebook':
                $this->loginUsingFacebook();
                break;
            case 'twitter':
                $this->loginUsingTwitter();
                break;
        }
    }
	
    /**
     * Just a switch that branches out the execution based on given identity type
     * 
     */
    public function afterSuccessfullOAuthLogin() {
        // Switch the execution based on OAuth provider
        switch ( $this->sIdentityType ){
            case 'linkedin':
                $this->afterSuccessfullLinkedinLogin();
                break;
            case 'facebook':
                $this->afterSuccessfullFacebookLogin();
                break;
            case 'twitter':
                $this->afterSuccessfullTwitterLogin();
                break;
        }
    }
	
    //--------------------------------------------------------------------------------------------------------------------------//
	
    /*-----------------------------
      OAuth login start functions
    -----------------------------*/
	
    private function loginUsingLinkedIn () {
        // Set required imports
        require (APP . 'php/libs/oauth/linkedin/LinkedIn.php');
        require_once (APP . 'php/main/rest/request.php');

        // Create LinkedIn object
        $linkedin = new LinkedIn(
                LINKEDIN_CONSUMER_KEY,
                LINKEDIN_CONSUMER_SECRET
                );

        // Save token and secret into the session
        $aRequest_token = $linkedin->getRequestToken();
        $_SESSION['oauth_token'] = $aRequest_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $aRequest_token['oauth_token_secret'];

        // Redirect client application to linkedin login page
        $url = $linkedin->getAuthorizeURL(
                $aRequest_token, $_SERVER['HTTP_HOST'] . '/php/oauth/index.php'
                );
        header('Location: ' . $url);
    }
	
    private function loginUsingFacebook () {
        // Set required imports
        require_once (APP . 'php/libs/oauth/facebook/facebook.php');

        // Create the Facebook object
        $facebook = new Facebook(array(
            'appId'	 => FACEBOOK_APP_ID,
            'secret' => FACEBOOK_APP_SECRET,
        ));

        // Redirect to www.facebook.com
        $url = $facebook->getLoginUrl(
                array(
                    'scope' => 'email, read_stream, publish_stream, offline_access, status_update, share_item', 
                    'redirect_uri' => 'http://' . $_SERVER['HTTP_HOST'] . '/php/oauth/index.php'
                )
                );								  

        LOG_EVENT('facebook redirect URL: ' . var_export($url, true));

        header('Location: ' . $url);
    }
	
    private function loginUsingTwitter () {
        // Set required imports
        require_once (APP . 'php/libs/oauth/twitter/twitter.php');

        // Create Twitter object and get the request token
        $twitter = new TwitterOAuth(TWITTER_APP_ID, TWITTER_APP_SECRET);
        $aRequest_token = $twitter->getRequestToken();

        // Save token and secret into the session
        $_SESSION['oauth_token'] = $aRequest_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $aRequest_token['oauth_token_secret'];

        // Redirect client application to twitter login page
        $url = $twitter->getAuthorizeURL($aRequest_token);	
        header('Location: ' . $url);
    }
	
    /*-----------------------------------------
      after successfull OAuth login functions
    -----------------------------------------*/

    private function afterSuccessfullLinkedinLogin () {
        // Set required imports
        require (APP . 'php/libs/oauth/linkedin/LinkedIn.php');
        require_once (APP . 'php/main/rest/request.php');
        require_once (APP . 'php/main/utils/jsonUtil.php');

        // Create LinkedIn object with a request token
        $linkedin = new LinkedIn(
                LINKEDIN_CONSUMER_KEY,
                LINKEDIN_CONSUMER_SECRET,
                $_SESSION['oauth_token'],
                $_SESSION['oauth_token_secret']
                );

        $accessToken = $linkedin->getAccessToken($_GET['oauth_verifier']);

        // Create LinkedIn object with an access token
        $linkedin = new LinkedIn(
                LINKEDIN_CONSUMER_KEY,
                LINKEDIN_CONSUMER_SECRET,
                $accessToken['oauth_token'],
                $accessToken['oauth_token_secret']
                );

        // Fetch the profile
        $profile_result = $linkedin->oAuthRequest('http://api.linkedin.com/v1/people/~:(id,first-name,last-name,public-profile-url,headline,location,picture-url,date-of-birth,phone-numbers,summary)?format=json');

        // Convert the result to an array
        $aProfile = JsonUtil::generalJsonToArray($profile_result, false);

        // Call identityServiceLogin function
        $sProviderUsername = str_replace( 'http://www.linkedin.com/in/', '', $aProfile['publicProfileUrl'] );
        $sProviderUsername = str_replace( 'http://www.linkedin.com', '', $sProviderUsername );

        $sFirstname = isset( $aProfile['firstName'] ) ? $aProfile['firstName'] : '';
        $sLastname = isset( $aProfile['lastName'] ) ? $aProfile['lastName'] : '';
        $sName = $sFirstname . ' ' . $sLastname;

        $this->identityServiceAuthentication(
                'linkedin.com/' . $aProfile['id'], 
                $sProviderUsername, 
                $sName, 
                $aProfile['publicProfileUrl'], 
                $aProfile['pictureUrl'],
                $accessToken['oauth_token'], 
                $accessToken['oauth_token_secret']
                );
    }

    private function afterSuccessfullFacebookLogin () {
        // Set required imports
        require_once (APP . 'php/libs/oauth/facebook/facebook.php');

        // Create Facebook object
        $facebook = new Facebook(array(
            'appId'  => FACEBOOK_APP_ID,
            'secret' => FACEBOOK_APP_SECRET,
        ));		

        // Get user ID
        $nUser = $facebook->getUser();

        // Try getting the profile of the user
        if ($nUser) {
            try {
                $aResult = $facebook->api('/me');
                $facebook->setExtendedAccessToken();
            } catch (FacebookApiException $e) {
                error_log($e);
                $nUser = null;
                exit();
            }
        }

        // Call identityServiceLoginFunction if there is an authenticated user, 
        // else redirect to login page
        if ($nUser) {						
            $this->identityServiceAuthentication(
                    $aResult['id'], 
                    $aResult['username'], 
                    $aResult['name'], 
                    $aResult['link'], 
                    '', 
                    $facebook->getAccessToken(), 
                    '');
        } else {	
            header( 'Location: ' . 
                $facebook->getLoginUrl( 
                        array( 
                            'scope' => 'email, read_stream, publish_stream, offline_access, status_update, share_item'
                            ) 
                        )
            );
        }
    }

    private function afterSuccessfullTwitterLogin () {
        // Set required imports
        require_once (APP . 'php/libs/oauth/twitter/twitter.php');

        // Throw error if oauth_token from the request
        // does not match the one from the session
        if (isset($_REQUEST['oauth_token']) && 
                $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
            die('Session expired');
        }

        // Create Twitter object and get the access token		
        $twitter = new TwitterOAuth(
                TWITTER_APP_ID, 
                TWITTER_APP_SECRET, 
                $_SESSION['oauth_token'], 
                $_SESSION['oauth_token_secret']
                );
        $token_credentials = $twitter->getAccessToken();

        // Get the profile parameters of the logged in user
        // and call identityServiceAuthentication function
        $oResult = $twitter->get('account/verify_credentials');
        $aResult = (array) $oResult;

        $this->identityServiceAuthentication(
                $aResult['id'], 
                $aResult['screen_name'], 
                $aResult['name'], 
                'https://twitter.com/' . $aResult['screen_name'], 
                $aResult['profile_image_url']
                );	
    }

    /*------------------------
      Common login functions
    ------------------------*/

    private function identityServiceAuthentication( $sProviderUserId, $sProviderUsername, $sProfileFullname, $sProfileUrl, $sProfileAvatarUrl, $sToken, $sSecret ) {
        // Create a default result
        $aAuthenticationResult = array (
            'errorMessage' => 'none'
        );

        // Set the initial value of the redirectURL
        $sRedirectURL = $_SESSION['callbackURL'];
        if ( !strpos( $sRedirectURL, '?' ) ) {
            $sRedirectURL .= '?'; // add '?' if no parameters added to the url
        } else {
            $sRedirectURL .= '&'; // add '&' if the url already contains some parameters
        }

        // Verify the validity of given parameters
        if ( $sProviderUserId == '' || $sProviderUsername == '' || $sProfileFullname == '' ) {
            $aAuthenticationResult['errorMessage'] = 'Sign in failed due to missing parameter values';

            $sRedirectURL .= urlencode('{"reason":{"error":"' . $aAuthenticationResult['errorMessage'] . '"}}');
            $_SESSION['identityServiceAuthenticationURL'] = $sRedirectURL;
            header ( 'Location: ' . $_SESSION['callbackURL'] );
        }

        // Get the parameters given as a successful login indication
        $aSignInResult = $this->oUser->signInAfterOAuthProviderLogin(
                $_SESSION['identity']['type'],
                $sProviderUserId,
                $sProviderUsername,
                $sProfileFullname,
                $sProfileUrl,
                $sProfileAvatarUrl,
                $sToken,
                $sSecret
                );

        // If there is no identity returned by signInAfterOAuthProviderLogin()
        if ( !$aSignInResult || empty( $aSignInResult ) ) {
            $aAuthenticationResult['errorMessage'] = 'Sign in failed';

            $sRedirectURL .= urlencode('{"reason":{"error":"' . $aAuthenticationResult['errorMessage'] . '"}}');
        }
        // Since everything went well:
        else {			
            // Generate and store the authenticationNonce and serverAuthenticationToken
            $sAuthenticationNonce = CryptoUtil::generateNonce();
            $_SESSION['authenticationNonce'] = $sAuthenticationNonce;
            $sServerAuthenticationToken = CryptoUtil::generateServerAuthenticationToken(
                    $_SESSION['clientAuthenticationToken'],
                    $aSignInResult['providerType'],	
                    $aSignInResult['identifier'],
                    $sAuthenticationNonce
                    );
            // Fill the result with valid values
            require_once (APP . 'php/main/utils/loginUtil.php');
            $aAuthenticationResult['loginState'] = LoginStates::OAUTH_AUTHENTICATION_SUCCEEDED;				
            $aIdentity = array (
                'type'			=> $aSignInResult['providerType'],
                'identifier'	=> $aSignInResult['identifier']
            );
            $aAuthenticationResult['identity'] = $aIdentity;				
            $aAuthenticationResult['serverAuthenticationToken'] = $sServerAuthenticationToken;

            // Generate the redirect URL string
            $sIdentityData = '"identity":{"type":"' . $aIdentity['type'] . '","identifier":"' . $aIdentity['identifier'] . '"}';

            $sResult = '' .
                    '{"result":{' .
                    '"loginState":"' . $aAuthenticationResult['loginState'] . '",' .
                    $sIdentityData;
            if ( key_exists( 'serverAuthenticationToken', $aAuthenticationResult ) ) {
                $sResult .= ',"serverAuthenticationToken":"' . $aAuthenticationResult['serverAuthenticationToken'] . '"';
            }	
            $sResult .= '}}';
            $sResult = urlencode($sResult);
            $sRedirectURL .= $sResult;
        }

        // Do the redirect
        $_SESSION['identityServiceAuthenticationURL'] = $sRedirectURL;
        header ( 'Location: ' . $_SESSION['callbackURL'] );
    }

    private function validateSignatureForTokenExchange ( $consumer_key, $consumer_secret, $credentials ) {
        // Create a default result
        $aResult = array (
        'validationSucceeded' => 'false',
        'validationMessage'   => 'Not validated'
        );

        // Official LinkedIn signature validation		
        if ($credentials->signature_version == 1) {
            if ($credentials->signature_order && is_array($credentials->signature_order)) {
                $base_string = '';
                // build base string from values ordered by signature_order
                foreach ($credentials->signature_order as $key) {
                    if (isset($credentials->$key)) {
                        $base_string .= $credentials->$key;
                    } else {
                        $aResult['validationMessage'] = "Missing signature parameter: $key";
                    }
                }
                // hex encode an HMAC-SHA1 string
                $signature =  base64_encode(hash_hmac('sha1', $base_string, $consumer_secret, true));
                // check if our signature matches the cookie's
                if ($signature == $credentials->signature) {
                        $aResult['validationSucceeded'] = 'true';
                    $aResult['validationMessage'] = 'Signature validation succeeded';
                } else {
                    $aResult['validationMessage'] = 'Signature validation failed';    
                }
            } else {
                $aResult['validationMessage'] = 'Signature order missing';
            }
        } else {
            $aResult['validationMessage'] = 'Unknown cookie version';
        }

        return $aResult;
    }

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