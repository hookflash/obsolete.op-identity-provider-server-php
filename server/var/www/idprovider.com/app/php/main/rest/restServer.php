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
 * Class Slim just defines the getInstance() function
 *
 */
class Slim {

	static function getInstance() {
		return new RestServer();
	}

}

/**
 * Class RestServer provides features of creation of a REST server,
 * making of REST methods, creation of results for these methods and
 * handling errors that may occur in the process.
 *
 */
class RestServer {

	public $aRoutes = array();
	public $sMethod = false;
	public $URI = false;
	public $sHTTPProtocol = 'http';
	public $headers = NULL;
	public $sBody = '';

	/**
	 * Enter description here...
	 *
	 * @var Request
	 */
	public $oRequest = null;

	protected $additionalHeaders = array('content-type', 'content-length', 'php-auth-user', 'php-auth-pw', 'auth-type', 'x-requested-with');

	/**
	 * This constructor sets all the default values for a REST server.
	 *
	 */
	public function __construct() {

		set_error_handler(array($this, 'handleErrors'));

		$this->aRoutes = array(
		'GET' => array(),
		'POST' => array(),
		'PUT' => array(),
		'DELETE' => array()
		);

		$this->sMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
		$this->headers = $this->loadHttpHeaders();
		$this->sBody = @file_get_contents('php://input');

		$this->sHTTPProtocol = ( empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ) ? 'http' : 'https';

		$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];

		$aURL = parse_url($this->sHTTPProtocol . '://' . $_SERVER['HTTP_HOST'] . $requestUri, PHP_URL_PATH);

		$this->URI = rtrim($aURL, '/');

		$this->oRequest = new Request( $this->sBody );
	}

	/**
	 * Standard error handler callback function...
	 * 
	 */
	public function handleErrors( $errno, $errstr = '', $errfile = '', $errline = '' ) {
		if ( error_reporting() & $errno ) {
			throw new ErrorHandler($errstr, $errno, 0, $errfile, $errline);
		}
		return true;
	}

	/**
	 * Define a handler function for given path for GET requests
	 * 
	 */
	public function registerGetMethod() {
		$this->aRoutes['GET'][] = array(
		'path'=>func_get_arg(0),
		'handler'=>func_get_arg(1),
		);
	}

	/**
	 * Define a handler function for given path for POST requests
	 * 
	 */
	public function registerPostMethod() {
		$this->aRoutes['POST'][] = array(
		'path'=>func_get_arg(0),
		'handler'=>func_get_arg(1),
		);
	}

	/**
	 * Fires a call on a handler function that is defined for the request the server is being hit with.
	 *
	 */
	public function run() {
		
		if ( isset( $this->oRequest->aPars['request_attr']['method'] ) ) {

			$aRoutes = $this->aRoutes[ $this->sMethod ];

			$aArgs = array();

			foreach ($aRoutes as $iR => $aRoute) {
				if ( $this->oRequest->aPars['request_attr']['method'] != $aRoute['path']  ) {
					unset ($aRoutes[$iR]);
				}
			}


			if ( count($aRoutes)!=1 ) {
				echo 'error - exact route missing';
			} else {
				$aRouteVector = array_pop($aRoutes);

				if ( function_exists($aRouteVector['handler']) ) {
					call_user_func_array($aRouteVector['handler'], $aArgs);
				} else {
					echo 'error - handler is not defined';
				}

			}
		} else {
			echo 'error - invalid request';
		}

	}

	/**
	 * Creates and returns a RestBody object that is created using $this->sBody
	 *
	 * @return object RestBody object
	 */
	public function request() {
		return new RestBody( $this->sBody );
	}

	//-------------------------------------------------------------------------------------------------------------------------//

	protected function loadHttpHeaders() {
		$headers = array();
		foreach ( $_SERVER as $key => $value ) {
			$key = str_replace('_', '-', strtolower($key));
			if ( strpos($key, 'http-') === 0 || in_array($key, $this->additionalHeaders) ) {
				$name = str_replace('http-', '', $key);
				$headers[$name] = $value;
			}
		}
		return $headers;
	}


}

/**
 * Class RestBody is providing type of objects that are created using raw string request body
 *
 */
class RestBody {

	public $sBody = '';

	public function __construct($sBody) {
		$this->sBody = $sBody;
	}

	public function getBody() {
		return $this->sBody;
	}

}

/**
 * Standard implementation of an error handler
 *
 */
class ErrorHandler extends Exception {

	public function __construct($message, $code, $severity, $filename, $lineno) {
		$this->message = $message;
		$this->code = $code;
		$this->severity = $severity;
		$this->file = $filename;
		$this->line = $lineno;

		print_r($this); die();

		$error_log_id = '';
		
		if ( isset($GLOBALS['DB']) ) {
			$error_log_id = $GLOBALS['DB']->insert('api_error_log', array(
				'error_dump' => print_r($this, true)
			)
			);
		}

		if( class_exists('Response') ) {
			$oResp = new Response();
			$oResp->errorResponse( array(
			'errpars' => $code . (($error_log_id)?"; ERROR LOG ID: $error_log_id":''),
			'errcode' => '011',
			)
			);
			$oResp->run();

			die();
		}
	}

}

?>