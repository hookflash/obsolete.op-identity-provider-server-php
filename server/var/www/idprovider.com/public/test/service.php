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

// Set required imports
if ( !defined('ROOT') ) {
	define('ROOT', dirname(dirname(dirname(__FILE__))));
}
if ( !defined('APP') ) {
	define('APP', ROOT . '/app/');
}
 
require (APP . 'php/config/config.php');
require (APP . 'php/libs/mySQL/class-mysqldb.php');

performTests();

function performTests() {
    // Service tests
    $sTestsOutcome = '';
    
    $sTestsOutcome .= 'Checking MySQL driver...<br/>';
    if (!function_exists('mysql_get_host_info')) {
        $sTestsOutcome .= 'MySQL driver FAILURE!<br/>';
        addCriticalFailureEnd($sTestsOutcome);
    }
    $sTestsOutcome .= 'MySQL driver working!<br/>';
    try {
        $DB = new mysqldb(APP_DB_NAME, APP_DB_HOST, APP_DB_USER, APP_DB_PASS);
    } catch (Exception $ex) {
        $sTestsOutcome .= 'MySQL driver FAILURE!<br/>';
    }
    $sTestsOutcome .= 'MySQL driver working!<br/>';
    $sTestsOutcome .= '<br/>';
     
    $sTestsOutcome .= '<br/>';
    $sTestsOutcome .= '--------------------------------------------------------<br/>';
    $sTestsOutcome .= 'All tests succeeded!<br/>';
}

function addCriticalFailureEnd($sTestsOutcome) {
    $sTestsOutcome .= '<div id="danger">--------------------------------------------------------<br/></div>';
    $sTestsOutcome .= '<div id="danger">Testing failed due to CRITICAL FAILURE!<br/></div>';
    die($sTestsOutcome);
}


?>

<html>
<head>
<title>Example Identity Provider - Test Service</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	<div id="tests_outcome">
		<?php echo $tests_outcome; ?>
	</div>
	
</body>
</html>
