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
 * Class DatabaseUtil is responsible to provide common database utility functionality
 *
 */
class DatabaseUtil
{

	/**
	 * Cut off every string that could be some kind of SQL injection
	 *
	 * @param string $sParam String to be protected from SQL injection
	 * @return string Returns the given string, but without anything dangerous in it
	 */
	public static function protectFromSqlInjection($sParam)
	{
		$badWords = array("/revoke /i","/select /i","/delete /i", "/update /i","/union /i","/insert /i","/drop /i","/http /i","/--/i");
		$sResult = preg_replace($badWords, "", DatabaseUtil::mysql_escape_mimic((string) $sParam));
		return $sResult;
	}
	
	//--------------------------------------------------------------------------------------------------------------------------------//
	
	/*-------------------
	  Private functions
	-------------------*/
	
	private static function mysql_escape_mimic($inp) { 
	    if(is_array($inp)) 
	        return array_map(__METHOD__, $inp); 
	
	    if(!empty($inp) && is_string($inp)) { 
	        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp); 
	    } 
	
	    return $inp; 
	}

}

?>