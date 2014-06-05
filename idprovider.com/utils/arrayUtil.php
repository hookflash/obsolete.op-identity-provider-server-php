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
 * Class ArrayUtil provides common array utility functions. 
 */

class ArrayUtil {
	
	
	/**
	 * A commonly known object-to-array function
	 *
	 * @param stdClass $object An object to be converted to array
	 * @return array Generated array
	 */
	public static function objectToArray ( $object ) {
		if ( is_object( $object ) ) {
			// Gets the properties of the given object
			$object = get_object_vars($object);
		} 
		if ( is_array( $object ) ) {
			// Return array converted to object using __METHOD__
			return array_map(__METHOD__, $object);
		}
		else {
			// Return array
			return $object;
		}
	}
	
	/**
	 * A commonly known array-to-object function
	 *
	 * @param array $array An array to be converted to object
	 * @return stdClass Generated object
	 */
	public static function arrayToObject ( $array ) {
		if ( is_array( $array ) ) {
			// Return array converted to object
			return (object) array_map(__METHOD__, $array);
		}
		else {
			// Return object
			return $array;
		}
	}
	
	/**
	 * Replaces null values in an array with empty arrays
	 *
	 * @param array $array An array to be modified
	 * @return array $array Returns modified array
	 */
	public static function replaceNullsWithEmptyStrings ( $array ) {
		array_walk_recursive($array, 'ArrayUtil::replacer');
		return $array;
	}
	
	//--------------------------------------------------------------------------------------------------------------//
	
	/*-------------------
	  Private functions
	-------------------*/
	
	private static function replacer(& $item, $key) {
	    if ($item == null) {
	    	$item = '';
	    }
	}
	
}

?>
