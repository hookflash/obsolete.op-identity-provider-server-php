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
 * Response class provides all the needed features for generation of valid responses for the requests that hit the server.
 *
 */

class Response {
	
	public $aPars = array();
	public $sError = false;
	public $bIsJson = true;
	public $sResponse = '';
	public $iIdent = 1;
	public $lastParsedName = '';
	public $lastParsedArrName = '';
	public $errorCode = 200;
	public $responseID = false;
	public $XMLSecurityDSig = false;
	public $resultMethod = '';
	public $domain = '';
	public $handler = '';
        public $appid = '';
	
	
	/**
	 * Constructor is setting the format and the attributes of the response
	 *
	 * @param Request $oRequest A request that the response is being created from
	 */
	public function __construct( $oRequest ) {
		$this->bIsJson = $oRequest->bIsJson;
		$this->domain = isset($oRequest->aPars['request_attr']['domain']) ? $oRequest->aPars['request_attr']['domain'] : '';
		$this->responseID = isset($oRequest->aPars['request_attr']['id']) ? $oRequest->aPars['request_attr']['id'] : false;
		$this->resultMethod = isset($oRequest->aPars['request_attr']['method']) ? $oRequest->aPars['request_attr']['method'] : '';
		$this->handler = isset($oRequest->aPars['request_attr']['handler']) ? $oRequest->aPars['request_attr']['handler'] : '';
                $this->appid = isset($oRequest->aPars['request_attr']['appid']) ? $oRequest->aPars['request_attr']['appid'] : '';
	}

/*------------------
  Public functions
------------------*/
	
	/**
	 * This function should be used whenever another parameter should be put into the result.
	 *
	 * @param string $name Name of the parameter
	 * @param string $value Value of the parameter
	 * @param boolean $attr A boolean indicating whether the parameter is an attribute or not
	 * 
	 * @example 
	 * Let's assume that a result should look like this for XML:
	 * <result id="asdf" domain="some.domain" handler="identity-provider" method="some-method"> <!-- response attributes are added elsewhere -->
	 *     <foo>some data</foo>
	 * </result>
	 * or like this for JSON:
	 * { "result": {
	 * 		"$id": "asdf"
	 * 		"$domain": "some.domain"
	 * 		"$handler": "identity-provider"
	 * 		"$method": "some-method"
	 * 		"foo": "some data"
	 * 		}
	 * }
	 * This is how we ad foo element to the result:
	 * $oResponse->addPar('foo', 'some data');
	 */
	public function addPar($name, $value, $attr=false) {
		$this->aPars[$name] = $value;
		if ($attr) $this->aPars[$name . '_attr'] = $attr;
	}

	/**
	 * This function is used to return an error as a result for given request.
	 *
	 * @param string $sErrCode Code of an error to be thrown
	 * @param string $sErrCustomMessage Custom message of an error to be returned instead of default one
	 * 
	 * @example 
	 * To throw a missing parameters error one should call:
	 * $oResponse->errorResponse('002');
	 * 
	 * To throw a custom missing parameters error one should call:
	 * $oResponse->errorResponse('002', 'Some custom error message');
	 */
	public function errorResponse( $sErrCode = '', $sErrCustomMessage = '', $sErrMessageFormat = 'message' ) {
		
		if ( key_exists($sErrCode, $this->aErrorCodes) ) {
			
			if ( $sErrCustomMessage == '' ) {
				if ( $this->bIsJson ) {
					$reason = '{"reason":{"$id":"' . $this->aErrorCodes[$sErrCode]['errorcode'] . '","' . $sErrMessageFormat . '":"' . $this->aErrorCodes[$sErrCode]['error'] . '"}}';
				} else {
					$reason = '<reason id="' . $this->aErrorCodes[$sErrCode]['errorcode'] . '">' . $this->aErrorCodes[$sErrCode]['error'] . '</reason>';
				}
			} else {
				if ( $this->bIsJson ) {
					$reason = '{"reason":{"$id":"' . $this->aErrorCodes[$sErrCode]['errorcode'] . '","' . $sErrMessageFormat . '":"' . $sErrCustomMessage . '"}}';
				} else {
					$reason = '<reason id="' . $this->aErrorCodes[$sErrCode]['errorcode'] . '">' . $sErrCustomMessage . '</reason>';
				}
			}
			
			$this->addPar('error',$reason);	
		} else {
			if ( $this->bIsJson ) {
				$reason = '{"reason":{"$id":"400","message":"This error is unexplained... yet"}}';
			} else {
				$reason = '<reason id="400">This error is unexplained... yet</reason>';
			}
			$this->addPar('error', $reason);
		}

	}
	
	public function errorResult ( $sErrorCode = '', $sErrorMessage = '', $sErrorMessageFormat = '' ) {
		if ( $this->bIsJson ) {
                    if ($sErrorMessageFormat === 'message') {
			$reason = '{"reason":{"$id":"' . $sErrorCode . '","' . $sErrorMessageFormat . '":"' . $sErrorMessage . '"}}';
                    } elseif ($sErrorMessageFormat === '#text') {
                        $reason = '{"$id":"' . $sErrorCode . '","' . $sErrorMessageFormat . '":"' . $sErrorMessage . '"}';
                    }
		} else {
			$reason = '<reason id="' . $sErrorCode . '">' . $sErrorMessage . '</reason>';
		}
		$this->addPar('error', $reason);
	}

	/**
	 * This function is called when the result is all set and should be provided to the request initiator
	 *
	 * @param boolean $bEcho A boolean indicating whether the result should be echoed or not
	 * @param boolean $bResultEnvelope A boolean indicating whether the result should be enveloped or not
	 */
	public function run($oException = null, $bEcho = true, $bResultEnvelope = true) {
		// Generate id
		if ( $this->responseID == false ) {
			$this->responseID = mt_rand();
		}
		
		// Error check
		if ($oException != null){
                        $messageFormat = $oException->sErrorMessage === 'Not Found' ? '#text' : 'message';
			$this->errorResult($oException->sHttpErrorCode, $oException->sErrorMessage, $messageFormat);
		}
		
		// Generate result string
		$this->createResponse( $this->aPars );
		if ( $bResultEnvelope ) {
			$this->resultEnvelope( $this->sResponse );
		}

		// Log into database
		APIEventLog( $this->sResponse, $this->errorCode );
			
		// Show the response
		if ($bEcho) {
			echo $this->sResponse;
			die();
		}
	}
	
	
//--------------------------------------------------------------------------------------------------------------------------


/*-------------------
  Private functions
-------------------*/ 
	
	private function resultEnvelope($sEnveloplessResponse) {
		if ( $this->bIsJson ) {
			if ( $sEnveloplessResponse == '' ) {
				$this->sResponse = '{"result":{"$domain":"' . $this->domain . '",' .
                                                                '"$appid":"' . $this->appid . '",' .
                                                                '"$id":"' . $this->responseID . '",' .
                                                                '"$handler":"' . $this->handler . '",' .
                                                                '"$method":"' . $this->resultMethod . '",' .
                                                                '"$epoch":"' . date('U') . '"' .
                                                                $sEnveloplessResponse . '}' .
                                                                '}';
			} else {
				$this->sResponse = '{"result":{"$domain":"' . $this->domain . '",' .
                                                                '"$appid":"' . $this->appid . '",' .
                                                                '"$id":"' . $this->responseID . '",' .
                                                                '"$handler":"' . $this->handler . '",' .
                                                                '"$method":"' . $this->resultMethod . '",' .
                                                                '"$epoch":"' . date('U') . '",' .
                                                                $sEnveloplessResponse . '}' .
                                                                '}';
			}
		} else {
		$this->sResponse = "<result domain=\"{$this->domain}\" id=\"{$this->responseID}\" handler=\"{$this->handler}\" method=\"{$this->resultMethod}\" epoch=\"" . date('U') . "\">
$sEnveloplessResponse
</result>";		
		}
	}

	private function serializeInHTMLAttr($aAttr) {
		$sHTML = '';

		$aSplitAttributes = array(explode(" ", $aAttr));

		foreach ((array)$aSplitAttributes as $value)
		{
			foreach ($value as $v)
			{
				$aSplit = explode('=', $v);
				$sHTML .= ' ' . $aSplit[0] . '="' . htmlentities($aSplit[1]) . '"';
			}
		}

		return $sHTML;
	}
	
	private function serializeInHTMLAttrDifferent($aAttr) {

		$sHTML = '';

		foreach ($aAttr as $K=>$V) {
			$sHTML .= ' ' . $K . '="' . htmlentities($V) . '"';
		}

		return $sHTML;

	}

	private function createResponse ( $aResponse ) {
		if ( $this->bIsJson ) {
			$this->createJsonResponse($aResponse);
		} else {
			$this->createXMLResponse($aResponse);
		}
	}
	
	private function createXmlResponse($aResp, $bDifferentSerialize=false) {
		
		foreach ((array)$aResp as $name=>$value) {

			if (substr($name,-5) == '_attr') { //start parse attribute

				$sE = '<' . substr($name, 0, -5) . '>';

				$sENew = '';
				
				if ($bDifferentSerialize)
					$sENew = str_replace('>', $this->serializeInHTMLAttrDifferent($value) . '>', $sE );
				else
					$sENew = str_replace('>', $this->serializeInHTMLAttr($value) . '>', $sE );

				$iEPos = strrpos($this->sResponse, $sE);

				$this->sResponse = substr($this->sResponse, 0, $iEPos) . $sENew . substr( $this->sResponse, $iEPos + strlen($sE) );

			} else { //start parse real element

				if ( is_numeric($name) ) $name = $this->lastParsedName;
				$this->sResponse .= str_repeat("\t", $this->iIdent) . "<$name>";
				$this->lastParsedName = $name;
				if ( is_array( $value ) ) {
					$this->iIdent++;

					if ( !$this->isAssoc($value) ) {

						$this->sResponse .= "\r\n";
						$this->createResponse( $value, $bDifferentSerialize );
						$this->sResponse .= str_repeat("\t", $this->iIdent - 1);
						
					} else {

						if ( is_array($value[0]) ) {

							for ($i=0; $i<count($value); $i++) {
								$this->sResponse .= "\r\n";
								$this->createResponse( $value[$i], $bDifferentSerialize );
								$this->sResponse .= str_repeat("\t", $this->iIdent - 1);
								if ( $i != count($value)-1 ) {
									$this->sResponse .= "</{$name}>\r\n";
									$this->sResponse .= str_repeat("\t", $this->iIdent - 1);
									$this->sResponse .= "<{$name}>";
								}
							}

						} else {
							
							$this->sResponse .= $this->htmlentities($value[0]);

							for ($i=1; $i<count($value); $i++) {
								$this->sResponse .= "</{$this->lastParsedName}>\r\n"
								. str_repeat("\t", $this->iIdent - 1)
								. "<{$this->lastParsedName}>" . $this->htmlentities($value[$i]);
							}

						}

					}

					$this->iIdent--;
					
				} else {
					
					$this->sResponse .= $this->htmlentities( $value );
					
					if ($name=='errorcode') {
						$this->errorCode = $value;
					}
					
				}
				
				$this->sResponse .=  "</$name>\r\n";
			} //end parse real element
		}
	}
	
	private function createJsonResponse ( $aResponse ) {
		if ( key_exists( 'error', $aResponse ) && !is_array( $aResponse['error'] ) ) {
			$this->sResponse = '"error":' . $aResponse['error'];
		}
		else {
			require_once ( ROOT . 'utils/jsonUtil.php' );

//			LOG_EVENT('JSON converted: ' . var_export($aResponse, true));

			$this->sResponse = JsonUtil::arrayToJson( $aResponse );

//			LOG_EVENT('JSON to: ' . var_export($this->sResponse, true));
		}
	}

	private function htmlentities($sString) {
		// added html_entity_decode because on response return special chars
		return html_entity_decode((htmlspecialchars($sString))); 
	}

	private function isAssoc($array) {
		return  ctype_digit( implode('', array_keys( (array) $array) ) );
	}

}


?>