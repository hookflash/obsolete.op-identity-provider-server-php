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

// Set required imports
require_once (APP . 'php/main/utils/cryptoUtil.php');

/**
 * Class PinValidation provides all the needed features for the pin validation scenarios
 */
class PinValidation {
	
	const PIN_EXPIRY = 30; // set in minutes
	
		
	/**
	 * Just an empty constructor
	 *
	 */
	public function __construct () {}
	
	/**
	 * TODO
	 */
	public function generatePIN ( $aUser ) {
		if ( $this->didPinExpire( isset($aUser['pin_expiry']) ? $aUser['pin_expiry'] : '' ) &&
			 $this->validatePinGenerationTime($aUser['next_valid_pin_generation_time']) ){
			$aPinGenerationResult = $this->generateNewPIN($aUser);
		}
		
		if ( isset( $aPinGenerationResult ) ) {
			return $aPinGenerationResult;
		} else {
			return array (
			'pin' 							=> $aUser['temporary_pin'],
			'pinGenerationDailyCounter' 	=> $aUser['pin_daily_generation_counter'],
			'nextValidPinGenerationTime' 	=> $aUser['next_valid_pin_generation_time'],
			'expiry'						=> isset($aUser['pin_expiry']) ? $aUser['pin_expiry'] : ''
			);
		}
	}
	
	/**
	 * TODO
	 */
	public function validatePin ( $sPin, $DB ) {
		// Challange the SESSION
		if ( !( isset( $_SESSION['requestData'] ) || !( isset( $_SESSION['user'] ) ) ) ) {
			throw new RestServerException('020', array(
												 'parameter' => 'noPinValidationRequiredUserInSession'
												 ));
		}
		
		// Get the user from database
		require_once (APP . 'php/main/identity/user.php');
		$oUser = new User($DB);
		$aUser = $oUser->signInUsingLegacy($_SESSION['requestData']['identity']['type'], $_SESSION['requestData']['identity']['identifier']);
		
		// Challange PIN validity
		if ( $aUser['temporary_pin'] != $sPin ) {
			throw new RestServerException('020', array(
												 'parameter' => 'pinInvalid'
												 ));
		}
		
		// Challange PIN expiry
		if ( $aUser['pin_expiry'] < time() ) {
			throw new RestServerException('020', array(
												 'parameter' => 'pinExpired'
												 ));
		}
		
		$_SESSION['pinValidated'] = true;
		
		return;
	}
	
	//------------------------------------------------------------------------------------------------------------------------//
	
	/*-------------------
	  Private Functions
	-------------------*/
	
	private function didPinExpire ( $sExpiry ) {
		if ( $sExpiry == '' || time() > $sExpiry ) {
			return true;
		} else {
			return false;
		}
	}
	
	private function validatePinGenerationTime ( $sNextValidPinGenerationTime ) {
		if ( time() > $sNextValidPinGenerationTime ) {
			return true;
		} else {
			return false;
		}
	}
	
	private function generateNewPIN ( $aUser ) {
		// Generate new PIN and calculatethe time when it could be generated again
		$sPin = CryptoUtil::generatePin();
		$aCalculateNextValidPinGenerationTimeResult = $this->calculateNextValidPinGenerationTime( $aUser['pin_daily_generation_counter'], $aUser['next_valid_pin_generation_time'] );
		$sExpiry = time() + $this->toSeconds('min', self::PIN_EXPIRY);
		return array (
		'pin'							=> $sPin,
		'pinGenerationDailyCounter' 	=> $aCalculateNextValidPinGenerationTimeResult['pinGenerationRequestsDailyCounter'],
		'nextValidPinGenerationTime' 	=> $aCalculateNextValidPinGenerationTimeResult['nextValidPinGenerationTime'],
		'pinGenerationCooldown'		 	=> $aCalculateNextValidPinGenerationTimeResult['pinGenerationCooldown'],
		'expiry'						=> $sExpiry
		);
	}
	
	private function calculateNextValidPinGenerationTime ( $nPinGenerationRequestsDailyCounter, $sNextValidPinGenerationTime ) {
		// Set the current time and some initial values...
		$sNow = time();
		$sNextValidPinGenerationTime = $sNow;
		$sPinGenerationCooldown = '';
		
		// Calculate number of today pin generation requests in case that it's bigger then 0 but last request was not today.
		// In this case we are just facing the situation where we have bogus counter value in the database and we shall fix it now.
		if ( $nPinGenerationRequestsDailyCounter > 0 && 
			 $sNextValidPinGenerationTime + $this->toSeconds('day', 1) < $sNow
		    )
		{
		    $nPinGenerationRequestsDailyCounter = 0;
		    $sNextValidPinGenerationTime = $sNow;
		}
		
		if ( $nPinGenerationRequestsDailyCounter == 0 ) {
			$nPinGenerationRequestsDailyCounter += 1;
			$sNextValidPinGenerationTime += $this->toSeconds('min', 30);
			$sPinGenerationCooldown = '30 minutes';
		}
		elseif ( $nPinGenerationRequestsDailyCounter == 1 ) {
			$nPinGenerationRequestsDailyCounter += 1;
			$sNextValidPinGenerationTime += $this->toSeconds('min', 45);
			$sPinGenerationCooldown = '45 minutes';
		}
		elseif ( $nPinGenerationRequestsDailyCounter == 2 ) {
			$nPinGenerationRequestsDailyCounter += 1;
			$sNextValidPinGenerationTime += $this->toSeconds('min', 60);
			$sPinGenerationCooldown = '60 minutes';
		}
		else {
			$nPinGenerationRequestsDailyCounter += 1;
			$sNextValidPinGenerationTime += $this->toSeconds('day', 1);
			$sPinGenerationCooldown = '24 hours';
		}
		
		// Return the values to be stored in the database
		return array (
		'pinGenerationRequestsDailyCounter' => $nPinGenerationRequestsDailyCounter,
		'nextValidPinGenerationTime' 		=> $sNextValidPinGenerationTime,
		'pinGenerationCooldown'				=> $sPinGenerationCooldown
		);
	}
	
	private function toSeconds ( $sWhatToConvert, $nHowMuch ) {
		switch ( $sWhatToConvert ) {
			case 'min':
				return $nHowMuch*60;
			case 'huor':
				return $nHowMuch*3600;
			case 'day':
				return $nHowMuch*3600*24;
		}
	}
	
}

?>
