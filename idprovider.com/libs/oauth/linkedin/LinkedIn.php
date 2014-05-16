<?php

/**
 * A class handling the OAuth verification procedure for LinkedIn
 *
 * Based on the Twitter version by Abraham Williams (abraham@abrah.am) http://abrah.am
 *
 */

require_once(ROOT . 'libs/oauth/OAuth.php');

/**
 * This class acts as a library for the LinkedIn OAuth REST API calls
 *
 */
class LinkedIn {
    // Contains the last HTTP status code returned
    private $http_status;

    // Contains the last API call
    private $last_api_call;

    // The base of the LinkedIn OAuth URLs
    public $LINKEDIN_API_ROOT = 'https://api.linkedin.com/';

    public $request_options = array();

    /**
     * Set API URLS
     */
    function requestTokenURL() { return $this->LINKEDIN_API_ROOT.'uas/oauth/requestToken'; }
    function authorizeURL() { return $this->LINKEDIN_API_ROOT.'uas/oauth/authorize'; }
    function accessTokenURL() { return $this->LINKEDIN_API_ROOT.'uas/oauth/accessToken'; }

    /**
     * Debug helpers
     */
    function lastStatusCode() { return $this->http_status; }
    function lastAPICall() { return $this->last_api_call; }

    /**
     * Standard OAuth consumer constructor that can create an OAuth object
     * using just API key + API secret, or using the oauth token + oauth token secret.
     *
     * @param string $consumer_key LinkedIn API key for the application
     * @param string $consumer_secret LinkedIn API secret for the application
     * @param string $oauth_token OAuth token
     * @param string $oauth_token_secret OAuth token secret
     */
    function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
        $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
        $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
        if (!empty($oauth_token) && !empty($oauth_token_secret)) {
            $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
        } else {
            $this->token = NULL;
        }
    }

    /**
     * Get a request_token from LinkedIn
     *
     * @return array $token a key/value array containing oauth_token and oauth_token_secret
     */
    function getRequestToken() {
    	// Here we hardcode the scope 'cause we know exactly what we need in our app, but the scope should really be created programatically...
        $requesturl = $this->requestTokenURL() . '?scope=r_fullprofile%20r_contactinfo%20r_emailaddress%20r_network%20rw_nus%20w_messages%20rw_groups';
        $r = $this->oAuthRequest($requesturl, $this->request_options, 'GET');
        error_log('OAuth request: '.$requesturl);
        error_log('OAuth Response: '.print_r($r, true));
        $token = $this->oAuthParseResponse($r);
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
        return $token;
    }

    /**
     * Parse a URL-encoded OAuth response
     * 
     * @param string $responseString A response to be parsed that LinkedIn created as a result for some request. 
     *
     * @return array $r A key/value array
     */
    function oAuthParseResponse($responseString) {
        $r = array();
        foreach (explode('&', $responseString) as $param) {
            $pair = explode('=', $param, 2);
            if (count($pair) != 2) continue;
            $r[urldecode($pair[0])] = urldecode($pair[1]);
        }
        return $r;
    }

    /**
     * Get the authorize URL
     *
     * @param array $token A key/value array that holds the ouath_token and the oauth_token_secret data
     * @param string $callbackurl An URL to be redirected after the authorization process
     * 
     * @return string $result Generated authorizeURL
     */
    function getAuthorizeURL($token, $callbackurl) {
        if (is_array($token)) $token = $token['oauth_token'];
        $result = $this->authorizeURL();
        $result .= '?oauth_token=' . $token;
        $result .= '&oauth_callback=' . urlencode($callbackurl);
        
        return $result;
    }

    /**
     * Exchange the request token and secret for an access token and
     * secret, to sign API calls.
     * 
     * @param string $verifier Standard OAuth verifer
     *
     * @return array $token = ( "oauth_token" => the access token,
     *                			"oauth_token_secret" => the access secret )
     */
    function getAccessToken($verifier) {
        $r = $this->oAuthRequest($this->accessTokenURL(), array('oauth_verifier' => $verifier), 'GET');
        error_log('$r: '.print_r($r, true));
        $token = $this->oAuthParseResponse($r);
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
        return $token;
    }
    
    /**
     * Exchange the access token given by JavaScript side (that is compatible with OAuth 2.0)
     * for an OAuth 1.0a access token that lasts longer and allows a server to fetch profile data of the logged in user
     *
     * @param string $accessToken2_0 Standard OAuth 2.0 access token that is about to be exchanged for a OAuth 1.0a access token
     * 
     * @return array $token Array that has oauth_token and oauth_token_secret set 
     */
    function exchangeAccessToken($accessToken2_0) {
    	$r = $this->oAuthRequest($this->accessTokenURL(), array('xoauth_oauth2_access_token' => $accessToken2_0), 'POST');
    	error_log('$r: '.print_r($r, true));
    	$token = $this->oAuthParseResponse($r);
    	$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    	return $token;
    }

    /**
     * Format and sign an OAuth / API request
     * 
     * @param string $url An URL for the request to be sent to
     * @param array $args An array of arguments to be added to the request, initially set to empty array
     * @param string $method Could only be "GET" or "POST", initially set to NULL
     */
    function oAuthRequest($url, $args = array(), $method = NULL) {
        if (empty($method)) $method = empty($args) ? "GET" : "POST";
        $req = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $args);
        $req->sign_request($this->sha1_method, $this->consumer, $this->token);
        switch ($method) {
            case 'GET': return $this->http($req->to_url());
            case 'POST': return $this->http($req->get_normalized_http_url(), $req->to_postdata());
        }
    }

    /**
     * Make an HTTP request
     * 
     * @return API results
     */
    function http($url, $post_data = null) {
        error_log("Calling '$url'");
        $ch = curl_init();
        if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if (isset($post_data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        $response = curl_exec($ch);
        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_api_call = $url;
        curl_close ($ch);
        return $response;
    }
}