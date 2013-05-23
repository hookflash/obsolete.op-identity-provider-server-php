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
	define('ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
}
if ( !defined('APP') ) {
	define('APP', ROOT . '/app/');
}
require_once ( APP . 'php/config/config.php' );


/**
 * Class UploadUtil provides common file upload functionality
 *
 */
class UploadUtil {
	
	const MAX_FILE_SIZE = 100000;
	
	/*------------------
	  Public functions
	------------------*/
	
	/**
	 * Put uploaded file at appropriate location and write needed data about that file in session
	 * since it's going to be used in the ongoing request (whether it's sign-up, login or something else)...
	 * 
	 * As a result, this function prints json-ized response about the uploading of the file.
	 */
	public function uploadAvatar () {
		// Challange the upload of the file
		if ( !( is_uploaded_file( $_FILES['file']['tmp_name'] ) ) ) {
			echo '{"result":{"error":{"reason":{"$id":"500","message":"The file has not been uploaded at all."}}}}'; die();
		}
		
		// Challange uploaded file's type, size and extension
		if ( !( UploadUtil::checkUploadedFileType() ) ) {
			echo '{"result":{"error":{"reason":{"$id":"406","message":"The file has invalid type."}}}}'; die();
		}
		if ( !( UploadUtil::checkUploadedFileSize() ) )	{
			echo '{"result":{"error":{"reason":{"$id":"406","message":"The file is too big. File must be smaller then ' . UploadUtil::$nMaxFileSize . ' bytes."}}}}'; die();
		}		
		if ( !( UploadUtil::checkUploadedFileExtension() ) ) {
			echo '{"result":{"error":{"reason":{"$id":"406","message":"The file has invalid extension."}}}}'; die();
		}
		
		// Check if any error occured in the process
		if ( $_FILES["file"]["error"] > 0 ) {
	    	echo UploadUtil::checkUploadedFileError(); die();
	    }
	    
	    // Set the name for the uploaded file
	    $sFileName = UploadUtil::setUploadedFileName();
	    
	    // Try moving the file and if it couldn't be done, return error message
	    if ( !( move_uploaded_file( $_FILES["file"]["tmp_name"],
		    					 UPLOAD_LOCATION . $sFileName
		    					) ) )
		{
			echo '{"result":{"error":{"reason":{"$id":"406","message":"Could not save the file."}}}}'; die();
		}
		
		// Since everything went well, save the file name to the session and echo the result
		$_SESSION['avatarFileName'] = $sFileName;
		echo '{"result":{"file":{"name":"' . $_FILES["file"]["name"] . '",' . 
			 '"url":"' . UploadUtil::getUploadedFileUrl($sFileName) . '"' .
			 '}}}';		
	}
	
	//------------------------------------------------------------------------------------------------------------------------//
	
	/*-------------------
	  Private functions
	-------------------*/
	
	private function checkUploadedFileType () {
		if ( ( $_FILES["file"]["type"] == "image/gif" )  ||
			 ( $_FILES["file"]["type"] == "image/jpeg" ) ||
			 ( $_FILES["file"]["type"] == "image/png" )  ||
			 ( $_FILES["file"]["type"] == "image/pjpeg" )
			)
		{
			return true;   	
		} else {
			return false;
		}
	}
	
	private function checkUploadedFileSize () {
		if ( $_FILES["file"]["size"] < UploadUtil::MAX_FILE_SIZE ) {
			return true;
		} else {
			return false;
		}
	}
	
	private function checkUploadedFileExtension () {
		$aAllowedExtensions = array( "jpg", "jpeg", "gif", "png" );
		$sExtension = end( explode( ".", $_FILES["file"]["name"] ) );
		if ( in_array( $sExtension, $aAllowedExtensions ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	private function checkUploadedFileError () {
		$nErrorCode = $_FILES["file"]["error"];
		$sResultString = '{"result":{"error":{"reason":{"$id":"500","message":"INTERNAL_SERVER_ERROR"}}}}';
    	if ( $nErrorCode == 1 || $nErrorCode == 1 ) {
    		$sResultString = '{"result":{"error":{"reason":{"$id":"406","message":"The file is too big. File must be smaller then ' . UploadUtil::$nMaxFileSize . ' bytes."}}}}';
    	}
    	if ( $nErrorCode == 3 ) {
    		$sResultString = '{"result":{"error":{"reason":{"$id":"406","message":"The uploaded file was only partially uploaded."}}}}';
    	}
    	if ( $nErrorCode == 4 ) {
    		$sResultString = '{"result":{"error":{"reason":{"$id":"406","message":"No file was uploaded."}}}}';
    	}
    	if ( $nErrorCode == 6 ) {
    		$sResultString = '{"result":{"error":{"reason":{"$id":"406","message":"Missing a temporary folder."}}}}';
    	}
    	if ( $nErrorCode == 7 ) {
    		$sResultString = '{"result":{"error":{"reason":{"$id":"406","message":"Failed to write file to disk."}}}}';
    	}
    	return $sResultString;
	}
	
	private function setUploadedFileName () {
		$sFileName = time() . '_' . $_FILES["file"]["name"];
		if ( file_exists( UPLOAD_LOCATION . $sFileName ) ) {
	    	$sFileName = rand(0, 100) . '_' . $sFileName;
	    }
	    return $sFileName;
	}
	
	private function getUploadedFileUrl ( $sFileName ) {
		$sURL = PROVIDER_HOST . '/php/service/avatars/' . $sFileName;
		return $sURL;
	}

}

?>