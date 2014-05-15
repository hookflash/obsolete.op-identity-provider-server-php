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
 * Class JsonUtil provides common JSON utility functions. 
 */

class JsonUtil {

	/**
	 * Check if the string is JSON
	 *
	 * @param string $string A string to be checked 
	 * @return boolean Returns true if the string is JSON, otherwise returns false
	 */
	public static function isJsonString( $string ) {
	    try {
	        $object = json_decode( $string );
	    } catch (Exception $e) {
	    	return false;
	    }
	    return ( is_object($object) ) ? true : false;
	}
	
	/**
	 * TODO
	 *
	 * @param unknown_type $sBody
	 * @return unknown
	 */
	public static function jsonToArray( $sBody, $bIsRequest = true ) {
		// Set required imports
		require_once ( APP . 'php/main/utils/arrayUtil.php' );
		
		// Challange JSON
		if ( !( JsonUtil::isJsonString($sBody) ) ) {
			return false;
		}
		
		// Get the array from given json
		$oRawFromJson = json_decode($sBody);
		$aRawArrayFromJson = ArrayUtil::objectToArray($oRawFromJson);
		
		$sMessageType = '';
		if ( $bIsRequest ) {
			$sMessageType = 'request';
		} else {
			$sMessageType = 'result';
		}
		
		// Challange attributes existance
		if ( !( key_exists( '$domain', $aRawArrayFromJson[$sMessageType] ) ) ||
			 !( key_exists( '$id', $aRawArrayFromJson[$sMessageType]) ) ||
			 !( key_exists( '$handler', $aRawArrayFromJson[$sMessageType]) ) ||
			 !( key_exists( '$method', $aRawArrayFromJson[$sMessageType]) )
			)
		{ 
			return null;
		}
		
		// Deal with the attributes (get rid of the dollar sign prefix)
		$sDomain 	= $aRawArrayFromJson[$sMessageType]['$domain'];
		$sId 		= $aRawArrayFromJson[$sMessageType]['$id'];
		$sHandler	= $aRawArrayFromJson[$sMessageType]['$handler'];
		$sMethod 	= $aRawArrayFromJson[$sMessageType]['$method'];
                $sAppId 	= isset($aRawArrayFromJson[$sMessageType]['$appid']) ? 
                        $aRawArrayFromJson[$sMessageType]['$appid'] : '';
		if ( $sDomain == '' || $sId == '' || $sHandler == '' || $sMethod == ''){
			return null;
		}		
		$aRequestAttr = array (
			'domain' 	=> $sDomain,
                        'appid' 	=> $sAppId,
			'id' 		=> $sId,
			'handler' 	=> $sHandler,
			'method' 	=> $sMethod
		);
		// Deal with the request body
		$aRequest = array_slice($aRawArrayFromJson[$sMessageType], 4);
		
		// Create the resulting array and return it
		$aFinalArray = array (
		'request'	=> $aRequest,
		'request_attr' 	=> $aRequestAttr
		);
		$aFinalArray = ArrayUtil::replaceNullsWithEmptyStrings($aFinalArray);
		return $aFinalArray;
	}
	
	/**
	 * TODO
	 *
	 * @param unknown_type $sBody
	 * @return unknown
	 */
	public static function generalJsonToArray ( $sBody ) {
		// Set required imports
		require_once ( APP . 'php/main/utils/arrayUtil.php' );
		
		// Challange JSON
		if ( !( JsonUtil::isJsonString($sBody) ) ) {
			return false; 
		}
		
		// Get the array from given json
		$oRawFromJson = json_decode($sBody);
		$aRawArrayFromJson = ArrayUtil::objectToArray($oRawFromJson);
		return $aRawArrayFromJson;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $array
	 * @return unknown
	 */
	public static function arrayToJson ( $array ) {
		return substr( substr( json_encode($array) , 0, -1 ), 1 );
	}
	
}

?>