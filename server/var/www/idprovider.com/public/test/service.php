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

define(UNDERLINE_DIV, '<div id="info">--------------------------------------------------------<br/></div>');
define(NEW_LINE_DIV, '<br/>');

$numErrors = 0;
$sResult = performTests();

function performTests() {
    global $numErrors;
    $sRes = '';
    
    // Configuration tests
    $sRes = addInfo($sRes, 
            'Checking: Configuration files existance...');
    if (!file_exists(APP.'php/config/config.php')) {
        $sRes = addCriticalFailureEnd($sRes, 
                'Missing configaration file:' . APP.'php/config/config.php');
        return $sRes;
    }
    $sRes = addSuccess($sRes, 
            'File found: '.APP.'php/config/config.php');
    require(APP . 'php/config/config.php');
    if (!file_exists(APP.'php/libs/mySQL/class-mysqldb.php')) {
        $sRes = addCriticalFailureEnd($sRes, 
                'Missing configaration file:' . 
                APP.'php/libs/mySQL/class-mysqldb.php');
        return $sRes;
    }
    $sRes = addSuccess($sRes, 
            'File found: '.APP.'php/libs/mySQL/class-mysqldb.php');
    require(APP . 'php/libs/mySQL/class-mysqldb.php');
    //------------------------------------------------------------------------//
    
    // Driver support tests
    $sRes = addNewLine($sRes);
    $sRes = addInfo($sRes, 'Checking: Driver support...');
    if (!function_exists('mysql_get_host_info')) {
        $sRes = addCriticalFailureEnd($sRes, 
                'MySQL driver failure!');
        return $sRes;
    }
    $sRes = addSuccess($sRes, 'MySQL driver working!');
    //------------------------------------------------------------------------//
    
    // Database setup tests
    $sRes = addNewLine($sRes);
    $sRes = addInfo($sRes, 'Checking: Database setup...');
    $dbcheck = mysql_query("SHOW TABLES LIKE 'user'");
    if (mysql_num_rows($dbcheck) < 1) {
        $sRes = addFailure($sRes, 'Table \'user\' not found!');
    }
    addSuccess($sRes, 'Table \'user\' found!');
    $dbcheck = mysql_query("SHOW TABLES LIKE 'avatar'");
    if (mysql_num_rows($dbcheck) < 1) {
        $sRes = addFailure($sRes, 'Table \'avatar\' not found!');
    }
    addSuccess($sRes, 'Table \'avatar\' found!');
    $dbcheck = mysql_query("SHOW TABLES LIKE 'federated'");
    if (mysql_num_rows($dbcheck) < 1) {
        $sRes = addFailure($sRes, 'Table \'federated\' not found!');
    }
    addSuccess($sRes, 'Table \'federated\' found!');
    $dbcheck = mysql_query("SHOW TABLES LIKE 'legacy_oauth'");
    if (mysql_num_rows($dbcheck) < 1) {
        $sRes = addFailure($sRes, 'Table \'legacy_oauth\' not found!');
    }
    addSuccess($sRes, 'Table \'legacy_oauth\' found!');
    $dbcheck = mysql_query("SHOW TABLES LIKE 'legacy_phone'");
    if (mysql_num_rows($dbcheck) < 1) {
        $sRes = addFailure($sRes, 'Table \'legacy_phone\' not found!');
    }
    addSuccess($sRes, 'Table \'legacy_phone\' found!');
    $dbcheck = mysql_query("SHOW TABLES LIKE 'legacy_email'");
    if (mysql_num_rows($dbcheck) < 1) {
        $sRes = addFailure($sRes, 'Table \'legacy_email\' not found!');
    }
    addSuccess($sRes, 'Table \'legacy_email\' found!');
    
    //$DB = new mysqldb(APP_DB_NAME, APP_DB_HOST, APP_DB_USER, APP_DB_PASS);
    //------------------------------------------------------------------------//
    
     
    if ($numErrors > 0) {
        $sRes = addEndWithErrors($sRes, $numErrors);
    } else {
        $sRes = addSuccessfulEnd($sRes);
    }
    
    return $sRes;
}

function addInfo($sRes, $sMessage) {
    $sRes .= '<div id="info">' . $sMessage . '<br/></div>';
    return $sRes;
}

function addFailure($sRes, $sMessage) {
    global $numErrors;
    
    $numErrors += 1;
    $sRes .= '<div id="danger">' . $sMessage . '<br/></div>';
    return $sRes;
}

function addSuccess($sRes, $sMessage) {
    $sRes .= '<div id="success">' . $sMessage . '<br/></div>';
    return $sRes;
}

function addCriticalFailureEnd($sRes, $sMessage) {
    $sRes .= '<div id="danger">' . $sMessage . '<br/></div>';
    $sRes .= UNDERLINE_DIV;
    $sRes .= '<div id="danger">Testing failed due to CRITICAL FAILURE!<br/></div>';
    return $sRes;
}

function addSuccessfulEnd($sRes) {
    $sRes .= NEW_LINE_DIV;
    $sRes .= UNDERLINE_DIV;
    $sRes .= '<div id="success">All tests succeeded!<br/></div>';
    return $sRes;
}

function addEndWithErrors($sRes, $numErrors) {
    $sRes .= NEW_LINE_DIV;
    $sRes .= UNDERLINE_DIV;
    $sRes .= '<div id="warning">Tests completed with ' . $numErrors . ' errors!<br/></div>';
    return $sRes;
}

function addNewLine($sRes) {
    $sRes .= NEW_LINE_DIV;
    return $sRes;
}


?>

<html>
<head>
<title>Example Identity Provider - Test Service</title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    
    <div id="tests_outcome">
	<?php echo $sResult; ?>
    </div>
	
</body>
</html>
