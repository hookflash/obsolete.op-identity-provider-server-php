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
 * This script provides the dispatching functionality for the rest server.
 * Also it implements all the API functions.
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
if ( !defined('ROOT') ) {
	define('ROOT', dirname(dirname(__FILE__)));
}
if ( !defined('APP') ) {
	define('APP', ROOT . '/app/');
}

require (APP . 'php/config/config.php');
require (APP . 'php/libs/mySQL/class-mysqldb.php');

require (APP . 'php/main/rest/restServer.php');
require (APP . 'php/main/rest/request.php');
require (APP . 'php/main/rest/response.php');
require (APP . 'php/main/utils/requestUtil.php');
require_once ( APP . 'php/main/rest/restServerException.php' );

// Create database object
$DB = new mysqldb(APP_DB_NAME, APP_DB_HOST, APP_DB_USER, APP_DB_PASS);

// Create RestServer object and define the relationship between a request method and a php function
$server = new RestServer();
$server->registerPostMethod('internal_cslfp', 'internal_calculateServerLoginFinalProof');
$server->registerPostMethod('internal_thfs', 'internal_tryHFService');
$server->registerPostMethod('internal_ciasp', 'internal_calculateIdentityAccessSecretProof');
$server->registerPostMethod('internal_prct', 'internal_parseRolodexCredentialsToken');
$server->registerPostMethod('internal_erct', 'internal_encryptRolodexCredentialsToken');
$server->registerPostMethod('services-get', 'servicesGet');
$server->registerPostMethod('sign-up', 'signUp');
$server->registerPostMethod('login', 'login');
$server->registerPostMethod('server-nonce-get', 'serverNonceGet');
$server->registerPostMethod('identity-salts-get', 'identitySaltsGet');
$server->registerPostMethod('identity-salts-set', 'identitySaltsSet');
$server->registerPostMethod('oauth-provider-authentication', 'oAuthProviderAuthentication');
$server->registerPostMethod('pin-validation', 'pinValidation');
$server->registerPostMethod('linkedin-token-exchange', 'linkedinTokenExchange');
$server->registerPostMethod('profile-get', 'profileGet');
$server->registerPostMethod('profile-update', 'profileUpdate');
$server->registerPostMethod('password-change', 'passwordChange');
$server->registerPostMethod('lockbox-half-key-store', 'lockboxHalfKeyStore');
$server->registerPostMethod('identity-access-validate', 'identityAccessValidate');
$server->registerPostMethod('identity-access-rolodex-credentials-get', 'identityAccessRolodexCredentialsGet');

// Create Request and Response objects, and RequestUtils as well
$oRequest = $server->oRequest;
$oResponse = new Response($oRequest);

// Start executing relevant function based on method given in the request attributes
$server->run();

//------------------------------------------------------------------------------------------------------------------//

// TODO delete
function internal_tryHFService()
{
	global $oRequest;
	global $oResponse;
	require_once(APP . 'php/main/utils/loginUtil.php');
	$aResult = LoginUtil::sendProviderDelete();
	$oResponse->addPar('result', $aResult);
	$oResponse->run();
}

// TODO delete
function internal_calculateServerLoginFinalProof()
{
	global $oRequest;
	global $oResponse;
	
	require_once(APP . 'php/main/utils/cryptoUtil.php');
	$sServerLoginInnerProof = CryptoUtil::generateServerLoginInnerProof ( $oRequest->aPars['request']['identity']['identifier'],
																	 	  $oRequest->aPars['request']['identity']['passwordHash'],
																	 	  $oRequest->aPars['request']['identity']['secretSalt'],
																 	 	  $oRequest->aPars['request']['identity']['serverPasswordSalt'] );
	$sServerLoginProof = CryptoUtil::generateServerLoginProof ( $sServerLoginInnerProof,
																$oRequest->aPars['request']['serverNonce'] );
																			
	$oResponse->addPar('serverLoginProof', $sServerLoginProof);
	$oResponse->run();
}

// TODO delete
function internal_calculateIdentityAccessSecretProof () {
	global $oRequest;
	global $oResponse;
	require_once(APP . 'php/main/utils/cryptoUtil.php');
	$sIdentityAccessSecretProof = CryptoUtil::generateAccessSecretProof ( $oRequest->aPars['request']['identity']['uri'],
																		  $oRequest->aPars['request']['clientNonce'],
																		  $oRequest->aPars['request']['identity']['accessSecretExpires'],
																		  $oRequest->aPars['request']['identity']['accessToken'],
																		  $oRequest->aPars['request']['purpose'],
																		  $oRequest->aPars['request']['identity']['accessSecret'] );
	$oResponse->addPar('identityAccessSecretProof', $sIdentityAccessSecretProof);
	$oResponse->run();
}

// TODO delete
function internal_parseRolodexCredentialsToken () {
    /*
        global $oRequest;
	require_once(APP . 'php/main/utils/cryptoUtil.php');
        $sSecret = 'klksd9887w6uysjkksd89893kdnvbter';
        $sTokenEncrypted = $oRequest->aPars['request']['rolodexCredentialsTokenEncrypted'];
        $aTokenEncrypted = explode('-', $sTokenEncrypted);
        $sToken = CryptoUtil::decrypt(
                CryptoUtil::hextobin($aTokenEncrypted[1]),
                CryptoUtil::hextobin($aTokenEncrypted[0]),
                hash('sha256',$sSecret,TRUE));
        die($sToken);
     */
    global $oRequest;
    require_once(APP . 'php/main/utils/cryptoUtil.php');
    include(APP . 'php/libs/seclib/Crypt/AES.php');
    $key = hash('sha256', "01234567890123456789012345678901");
    
    $sTokenEncrypted = $oRequest->aPars['request']['rolodexCredentialsTokenEncrypted'];
    $aTokenEncrypted = explode('-', $sTokenEncrypted);
    
    $cipher = new Crypt_AES(CRYPT_AES_MODE_CFB);
    $cipher->setKey(DOMAIN_HOSTING_SECRET);
    $cipher->setIV(CryptoUtil::hexToStr($aTokenEncrypted[0]));
    
    echo CryptoUtil::hexToStr(CryptoUtil::decrypt($aTokenEncrypted[1],CryptoUtil::hexToStr($aTokenEncrypted[0]),DOMAIN_HOSTING_SECRET));
}

// TODO delete
function internal_encryptRolodexCredentialsToken () {
	/*
        require_once(APP . 'php/main/utils/cryptoUtil.php');
        $sSecret = 'klksd9887w6uysjkksd89893kdnvbter';
        $sIV = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_NOFB));
        $sServerTokenCredentials = '{"service":"github","consumer_key":"264ea34924b00a5fa84e","consumer_secret":"6d21988222de0f9cc3c0257b70357a5b22bd23b8","token":"ffd648ab7b9461bbfc48405dd26e0fc12aedbb57"}';
	$sServerToken = CryptoUtil::encrypt(
                                $sServerTokenCredentials,
                                $sIV,
                                hash('sha256',$sSecret,TRUE));
        $sTokenEncrypted = bin2hex($sIV) . '-' . bin2hex($sServerToken);
        die($sTokenEncrypted);
        ///////////////////////////////////
        $aTokenEncrypted = explode('-', $sTokenEncrypted);
        $sToken = CryptoUtil::decrypt(
                CryptoUtil::hextobin($aTokenEncrypted[1]),
                CryptoUtil::hextobin($aTokenEncrypted[0]),
                hash('sha256',$sSecret,TRUE));
        die($sToken);*/
    //------------------------------------------------------------//
    include(APP . 'php/libs/seclib/Crypt/AES.php');

    $cipher = new Crypt_AES(CRYPT_AES_MODE_CFB);
    $key = hash('sha256', "01234567890123456789012345678901");
    $iv = hash('md5', "0123456789012345");
    $cipher->setKey($key);
    $cipher->setIV($iv);

    //$plaintext = '{"service":"github","consumer_key":"264ea34924b00a5fa84e","consumer_secret":"6d21988222de0f9cc3c0257b70357a5b22bd23b8","token":"ffd648ab7b9461bbfc48405dd26e0fc12aedbb57"}';
    $plaintext = 'MY-DATA-AND-HERE-IS-MORE-DATA';
    echo $cipher->decrypt($cipher->encrypt($plaintext));
}

/**
 * Implementation of services-get method on identity provider side
 *
 */
function servicesGet()
{
	global $oResponse;
	$aError = array (
		'$id' 		=> 302,
		'#text' 	=> 'Found',
		'location' 	=> HF_SERVICE_DOMAIN . '.well-known/openpeer-services-get'
	);
	$oResponse->addPar('error', $aError);
	$oResponse->run();
}

/**
 * Implementation of sign-up method
 *
 */
function signUp()
{
	global $DB;
	global $oRequest;
	global $oResponse;
	
	try {
		//Challange request validity
		RequestUtil::validateSignUpRequest( $oRequest );
		
		// Set the loginType based on given identity type
		$sIdentityType = $oRequest->aPars['request']['identity']['type'];
		$sIdentityCategory = getIdentityCategory($sIdentityType);
		
		// Perform appropriate signUp
		$oLogin = createLogin($DB, $sIdentityCategory, $sIdentityType, $oRequest);
		$aSignUpResult = $oLogin->signUp();
		
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}

	// Fill the response with data and fire it
	$oResponse->run();
}

/**
 * Implementation of login method
 *
 */
function login()
{
	global $DB;
	global $oRequest;
	global $oResponse;
	
	try {
		// Check request validity
		RequestUtil::validateLoginRequest( $oRequest );
		
		// Determine if it's a regulare login or a login after the pin validation
		// (in which case we look for login data in the session).
		if ( !( isset($oRequest->aPars['request']['afterPinValidation']) ) ) {
			if ( isset($oRequest->aPars['request']['identity']['reloginKeyServerPart']) ) {
				// Fetch identity type from relogin key
				$aReloginKey = explode('-',$oRequest->aPars['request']['identity']['reloginKeyServerPart']);
				$sIdentityType = $aReloginKey[0];
			} else {
				// Set the loginType based on given identity type
				$sIdentityType = $oRequest->aPars['request']['identity']['type'];
			}
		} else {
			if ( isset($_SESSION['requestData']['identity']['type']) ) {
				$sIdentityType = $_SESSION['requestData']['identity']['type'];
			} else {
				throw new RestServerException('008', null);
			}
		}
		$sIdentityCategory = getIdentityCategory($sIdentityType);
		
		// Perform appropriate login
		$oLogin = createLogin($DB, $sIdentityCategory, $sIdentityType, $oRequest);
		$aLoginResult = $oLogin->login();
		
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}
	
	require_once (APP . 'php/main/utils/loginUtil.php');
	
	// In case of a successful login
	if ( key_exists( 'identity', $aLoginResult ) &&
		 $aLoginResult['identity']['accessToken'] != '' ) {
		 	
		$aLockbox = array (
		'reset'	=> $aLoginResult['lockboxReset']
		);
		if ( key_exists( 'lockboxKey', $aLoginResult ) && $aLoginResult['lockboxKey'] != '' ) {
			$aLockbox['key'] = $aLoginResult['lockboxKey'];
		}
		 	
		$oResponse->addPar('loginState', LoginStates::SUCCEEDED );
		$oResponse->addPar('identity', $aLoginResult['identity']);
		$oResponse->addPar('lockbox', $aLockbox);	
	}
	// In case of unsuccessful login due to required pin validation
	elseif ( key_exists( 'pinExpiry', $aLoginResult ) ) {
		
		$oResponse->addPar('loginState', LoginStates::PIN_VALIDATION_REQUIRED );
		$aPin = array (
		'pinExpiry' 					=> $aLoginResult['pinExpiry'],
		'nextValidPinGenerationTime'	=> $aLoginResult['nextValidPinGenerationTime'],
		'pinGenerationCooldown' 		=> $aLoginResult['pinGenerationCooldown'],
		'pin'							=> $aLoginResult['pin'] // TODO: make sure pin is not returned here, but through some 3rd party service
		);
		$oResponse->addPar('pin', $aPin);
	}
	
	$oResponse->run();	
}

/**
 * Implementation of server-nonce-get method
 *
 */
function serverNonceGet()
{
	global $oRequest;
	global $oResponse;
	
	// Perform generation of the nonce
	require_once(APP . 'php/main/utils/cryptoUtil.php');
	$sServerNonce = CryptoUtil::generateSelfValidatingNonce(10);
	
	// Fill the response with data and fire it
	$oResponse->addPar('serverNonce', $sServerNonce);
	$oResponse->run();
}

/**
 * Implementation of identity-salts-get method
 *
 */
function identitySaltsGet()
{
	global $DB;
	global $oRequest;
	global $oResponse;
	
	try {
		// Check request validity
		RequestUtil::validateIdentitySaltsGetRequest( $oRequest );
		
		// Take data from request
		$aRequestData = RequestUtil::takeIdentitySaltsGetRequestData( $oRequest );
		
		// Get the salts from the database
		require_once(APP . 'php/main/identity/user.php');
		$oUser = new User($DB);
		$aSalts = $oUser->getIdentitySalts($aRequestData['identity']['type'], $aRequestData['identity']['identifier']);
		
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}
	
	// Prepare the result data
	$aIdentity = array (
	'type'					=> $aRequestData['identity']['type'],
	'identifier' 			=> $aRequestData['identity']['identifier'],
	'serverPasswordSalt'	=> $aSalts['serverPasswordSalt'],
	'reloginEncryptionKey'	=> $aSalts['reloginEncryptionKey']
	);
	if ( key_exists( 'secretSalt', $aSalts ) ) {
		$aIdentity['secretSalt'] = $aSalts['secretSalt'];
	}
	
	// Fill the response with data and fire it	
	$oResponse->addPar('identity', $aIdentity);
	$oResponse->addPar('serverMagicValue', PROVIDER_MAGIC_VALUE);
	$oResponse->run();
}

/**
 * Implementation of identity-salts-set method
 *
 */
function identitySaltsSet()
{
	global $DB;
	global $oRequest;
	global $oResponse;
	
	try {
		// Check request validity
		RequestUtil::validateIdentitySaltsSetRequest( $oRequest );
		
		// Take data from request
		$aRequestData = RequestUtil::takeIdentitySaltsSetRequestData( $oRequest );
		
		// Try setting the salts into the database (after token authentication succeeds)
		require_once(APP . 'php/main/identity/user.php');
		$oUser = new User($DB);
		$aIdentitySaltsSettingResult = $oUser->setIdentitySalts($aRequestData);
		
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}
	
	// Fill the response with data and fire it	
	$oResponse->addPar('identitySaltsSettingSucceeded', 'true');
	$oResponse->run();
}

/**
 * Implementation of oauth-provider-authentication method
 *
 */
function oAuthProviderAuthentication()
{
	global $DB;
	global $oRequest;
	global $oResponse;
	
	try {
		// Check request validity
		RequestUtil::validateOAuthProviderAuthenticationRequest( $oRequest );

		// Create LegacyOAuthLogin object
		$sIdentityType = $oRequest->aPars['request']['identity']['type'];
		require_once(APP . 'php/main/identity/legacyOAuthLogin.php');
		$oLegacyOAuthLogin = new LegacyOAuthLogin( $sIdentityType, $oRequest );
		
		// Try starting an OAuth authentication process
		$aAuthenticationResult = $oLegacyOAuthLogin->authentication();
			
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}
	
	$oResponse->addPar('providerRedirectURL', $aAuthenticationResult['redirectURL']);
	$oResponse->run();
}

/**
 * Implementation of pin-validation method
 *
 */
function pinValidation ()
{
	global $oRequest;
	global $oResponse;
	global $DB;
	
	try {
		// Check request validity
		RequestUtil::validatePinValidationRequest( $oRequest );
		
		// Take data from request
		$aRequestData = RequestUtil::takePinValidationRequestData( $oRequest );
		
		// Challange the given pin
		require_once(APP . 'php/main/identity/pinValidation.php');
		$oPinValidation = new PinValidation();
		$oPinValidation->validatePin($aRequestData['pin'], $DB);
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}

	// Fill the response with the data and fire it
	$oResponse->addPar('pinValidationSucceeded', 'true');
	$oResponse->run();	
}

/**
 * Implementation of linkedin-token-exchange method
 *
 */
function linkedinTokenExchange ()
{	
	global $oRequest;
	global $oResponse;
	
	// Check request validity
	if ( !( RequestUtil::validateLinkedinTokenExchangeRequest( $oRequest ) ) )
	{
		// Throw Missing parameters error
		$oResponse->errorResponse('002');
		$oResponse->run(); die();
	}
	
	// Take data from request
	$aRequestData = RequestUtil::takeLinkedinTokenExhangeRequestData( $oRequest );
	
	// Create a LegacyOAuthLogin object
	require_once(APP . 'php/main/identity/legacyOAuthLogin.php');
	$oLegacyOAuthLogin = new LegacyOAuthLogin('linkedin', $aRequestData);
	
	// Perform the exchangeToken method
	$aTokenExchangeResult = $oLegacyOAuthLogin->performTokenExchange($aRequestData);
	
	if ( $aTokenExchangeResult['errorIndicator'] != 'none' ) {
		// Throw whatever error occured during the process
		$oResponse->errorResponse($aTokenExchangeResult['errorIndicator'], $aTokenExchangeResult['errorMessage']);
		$oResponse->run(); die();
	}
	
	// Fill the response with the data and fire it
	$oResponse->addPar('nonce', $aTokenExchangeResult['nonce']);
	if ( key_exists('identitySecretSalt', $aTokenExchangeResult) && key_exists('serverPasswordSalt', $aTokenExchangeResult) ) {
		$oResponse->addPar('identitySecretSalt', $aTokenExchangeResult['identitySecretSalt']);
		$oResponse->addPar('serverPasswordSalt', $aTokenExchangeResult['serverPasswordSalt']);	
	}
	$oResponse->run();
}

/**
 * Implementation of profile-get method
 *
 */
function profileGet ()
{
	global $oRequest;
	global $oResponse;
	global $DB;
	
	try {
		// Check request validity
		RequestUtil::validateProfileGetRequest( $oRequest );
		
		// Try getting the data about user's public profile
		require_once(APP . 'php/main/identity/user.php');
		$oUser = new User($DB);
		$aProfile = $oUser->getPublicProfile($oRequest);
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}
	
	// Prepare the result data
	$aIdentity = array (
	'identifier' 			=> $aProfile['identifier'],
	'displayName' 			=> $aProfile['displayName'],
	'avatars'			 	=> $aProfile['avatars']
	);

	// Fill the response with data and fire it	
	$oResponse->addPar('identity', $aIdentity);
	$oResponse->run();
}

/**
 * Implementation of profile-update method
 *
 */
function profileUpdate ()
{
	global $oRequest;
	global $oResponse;
	global $DB;
	
	try {
		// Check request validity
		RequestUtil::validateProfileUpdateRequest( $oRequest );
		
		// Try updateing users public profile
		require_once(APP . 'php/main/identity/user.php');
		$oUser = new User($DB);
		$aUpdateResult = $oUser->updateProfile($oRequest);
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}
	
	// Fill the response with data and fire it
	$oResponse->addPar('profileUpdateSucceeded', 'true');
	if ( !empty( $aUpdateResult['couldNotAdd'] ) ) {
		$oResponse->addPar('couldNotAdd', $aUpdateResult['couldNotAdd']);
	}
	if ( !empty( $aUpdateResult['couldNotDelete'] ) ) {
		$oResponse->addPar('couldNotDelete', $aUpdateResult['couldNotDelete']);
	}
	$oResponse->run();
}

/**
 * Implementation of password-change method
 *
 */
function passwordChange ()
{
	global $oRequest;
	global $oResponse;
	global $DB;
	
	try {
		// Check request validity
		RequestUtil::validatePasswordChangeRequest( $oRequest );
		
		// Try changing password
		require_once(APP . 'php/main/identity/user.php');
		$oUser = new User($DB);
		$aUpdateResult = $oUser->changePassword($oRequest);
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}
	
	// Prepare and fire the result
	$oResponse->addPar('passwordChangeSucceeded', 'true' );	
	$aHostingData = array (
	'nonce' 				=> $aUpdateResult['nonce'],
	'hostingProof' 			=> $aUpdateResult['hostingProof'],
	'hostingProofExpires' 	=> $aUpdateResult['hostingProofExpires']
	);
	$oResponse->addPar('hostingData', $aHostingData);	
	$aIdentity = array (
	'accessToken' 			=> $aUpdateResult['identity']['accessToken'],
	'accessSecret'			=> $aUpdateResult['identity']['accessSecret'],
	'accessSecretExpires'	=> $aUpdateResult['identity']['accessSecretExpires'],
	'updated'				=> $aUpdateResult['updated']
	);
	$oResponse->addPar('hostingData', $aHostingData);
	$oResponse->addPar('identity', $aIdentity);
	$oResponse->run();
}

/**
 * Implementation of lockbox-half-key-store method
 *
 */
function lockboxHalfKeyStore ()
{
	global $oRequest;
	global $oResponse;
	global $DB;
	
	try {
		// Check request validity
		RequestUtil::validateLockboxHalfKeyStoreRequest( $oRequest );
		
		// Try inserting the key
		require_once(APP . 'php/main/identity/user.php');
		$oUser = new User($DB);
		$aUpdateResult = $oUser->storeLockboxHalfKeyEncrypted( $oRequest, 'lockbox-update' );
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}
	
	// Result
	$oResponse->run();
}

/**
 * Implementation of identity-access-validate method
 *
 */
function identityAccessValidate ()
{
	global $oRequest;
	global $oResponse;
	global $DB;
	
	try {
		// Check request validity
		RequestUtil::validateIdentityAccessValidateRequest( $oRequest );
		
		// Try inserting the key
		require_once(APP . 'php/main/identity/user.php');
		$oUser = new User($DB);
		$aUpdateResult = $oUser->validateIdentityAccess( $oRequest );
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}
	
	// Result
	$oResponse->run();
}

/**
 * Implementation if identity-access-rolodex-credentials-get method
 * 
 */
function identityAccessRolodexCredentialsGet ()
{
	global $oRequest;
	global $oResponse;
	global $DB;
	
	try {
		// Check request validity
		RequestUtil::validateIdentityAccessRolodexCredentialsGetRequest( $oRequest );
		
		// Try inserting the key
		require_once(APP . 'php/main/identity/user.php');
		$oUser = new User($DB);
		$sServerToken = $oUser->getIdentityAccessRolodexCredentials( $oRequest );
		$aRolodex['serverToken'] = $sServerToken;
	} catch (Exception $exception) {
		$oResponse->run($exception);
	}
	
	// Result
	$oResponse->addPar('rolodex', $aRolodex);
	$oResponse->run();
}

//------------------------------------------------------------------------------------------------------------------//


/*----------------------------
  Private-purposed functions
----------------------------*/

/**
 * Determine loginType based on identityType
 *
 * @param string $sIdentityType Defines the type of the identity trying to log in
 * @return string Returns 'federated' (for identityType 'federated'), 'legacy' (for identityType 'email' or 'phone') or 'legacyOAuth' (for 'linkedin', 'facebook', 'twiter')
 */
function getIdentityCategory ( $sIdentityType ) {
	switch ($sIdentityType) {
		case 'federated':
			return 'federated';
		case 'email':
		case 'phone':
			return 'legacy';
		case 'facebook':
		case 'linkedin':
		case 'twitter':
			return 'legacyOAuth';
	}
}

/**
 * Based on idCategory, this function creates and returns appropriate login object
 *
 * @param object $DB A database to create the login object with.
 * @param string $sIdentityCategory An id category to switch against.
 * @param string $sIdentityType An identity type to create the login object with
 * @param array $post An array of request data to create the login object with.
 * @return object Could be one of these: FederatedLogin object, LegacyLogin object or LegacyOAuthLogin object.
 */
function createLogin ($DB, $sIdentityCategory, $sIdentityType, $aRequest ) {
	
	switch ($sIdentityCategory) {
		case 'federated':
			require_once(APP . 'php/main/identity/federatedLogin.php');
			return new FederatedLogin($DB, $sIdentityType, $aRequest);
		case 'legacy':
			require_once(APP . 'php/main/identity/legacyLogin.php');
			return new LegacyLogin($DB, $sIdentityType, $aRequest);
		case 'legacyOAuth':
			require_once(APP . 'php/main/identity/legacyOAuthLogin.php');
			return new LegacyOAuthLogin($sIdentityType, $aRequest);
	}
}




?>