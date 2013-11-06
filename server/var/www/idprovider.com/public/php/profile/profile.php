<!-- 

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

-->


<?php 
	define('ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
	require (ROOT . '/app/php/config/config.php');
	require (ROOT . '/app/php/main/utils/cryptoUtil.php');
	require (ROOT . '/app/php/main/utils/profileUtil.php');
        require (ROOT . '/app/php/main/utils/jsonUtil.php');
	
	$oResultObject = ProfileUtil::sendProfileGet( CryptoUtil::generateRequestId(),
                                                        $_GET['vprofile'],
                                                        $_GET['identifier'] );
	
	if ( isset($_GET['vprofile']) && $_GET['vprofile'] ) {
		print(JsonUtil::arrayToJson($oResultObject)); die();
	}
	$aProfile = array();
	
	if ( ( $oResultObject != null ) ) {
		if ( isset($oResultObject['request']['error']) ) {
			array_push($aProfile, $oResultObject['error']);
		} else {
			array_push($aProfile, $oResultObject['identity']);
		}
	}
	
?>

<html>
<head>
<title>Example Identity Provider - Public Profile</title>
</head>
<body>
	<div id="profile">
		<div id="avatar">
			<img src="<?php echo $aProfile['0']['avatars']['0']['url']; ?>">
		</div>
		<div id="text">
			<p id="identifier"><?php echo $aProfile['0']['identifier']; ?></p>
			<br/>
			<p id="displayName"><?php echo $aProfile['0']['displayName']; ?></p>
			<br/>
		</div>
	</div>
	
</body>
</html>