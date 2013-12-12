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
    $sTestsOutcome = '';

    trigger_error("Test error to ensure error log works");
    
    // Configuration tests
    $sTestsOutcome = addInfo($sTestsOutcome, 
            'Checking: Configuration files existance...');
    if (!file_exists(APP.'php/config/config.php')) {
        $sTestsOutcome = addCriticalFailureEnd($sTestsOutcome, 
                'Missing configaration file:' . APP.'php/config/config.php');
        return $sTestsOutcome;
    }
    $sTestsOutcome = addSuccess($sTestsOutcome, 
            'File found: '.APP.'php/config/config.php');
    require(APP . 'php/config/config.php');
    if (!file_exists(APP.'php/libs/mySQL/class-mysqlidb.php')) {
        $sTestsOutcome = addCriticalFailureEnd($sTestsOutcome, 
                'Missing configaration file:' . 
                APP.'php/libs/mySQL/class-mysqlidb.php');
        return $sTestsOutcome;
    }
    $sTestsOutcome = addSuccess($sTestsOutcome, 
            'File found: '.APP.'php/libs/mySQL/class-mysqlidb.php');
    require(APP . 'php/libs/mySQL/class-mysqlidb.php');
    //------------------------------------------------------------------------//
    
    // Driver support tests
    $sTestsOutcome = addNewLine($sTestsOutcome);
    $sTestsOutcome = addInfo($sTestsOutcome, 'Checking: Driver support...');
    if (!function_exists('mysqli_get_host_info')) {
        $sTestsOutcome = addCriticalFailureEnd($sTestsOutcome, 
                'MySQL driver failure!');
        return $sTestsOutcome;
    }
    $sTestsOutcome = addSuccess($sTestsOutcome, 'MySQL driver working!');
    //------------------------------------------------------------------------//
    
    // Database setup tests
    $sTestsOutcome = addNewLine($sTestsOutcome);
    $sTestsOutcome = addInfo($sTestsOutcome, 'Checking: Database setup...');

    $mysqli = new mysqli(APP_DB_HOST, APP_DB_USER, APP_DB_PASS, APP_DB_NAME);
    if ($mysqli->connect_errno) {
        $sTestsOutcome = addFailure($sTestsOutcome, "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'MySQL host info: \''.$mysqli->host_info.'\'');
        $sql="SHOW DATABASES";
        $result = mysqli_query($mysqli, $sql);
        if (!mysqli_query($mysqli, $sql)) {
            $sTestsOutcome = addFailure($sTestsOutcome, "Error: %s", mysqli_error($mysqli));
        }
        $row_getRS = mysqli_fetch_assoc($result);
        while( $row = mysqli_fetch_row( $result ) ):
            if (($row[0]!="information_schema") && ($row[0]!="mysql")) {
                $sTestsOutcome = addSuccess($sTestsOutcome, 'Found database table: \''.$row[0].'\'');
            }
        endwhile;
        $mysqli->close();
    }

    $DB = new mysqldb(APP_DB_NAME, APP_DB_HOST, APP_DB_USER, APP_DB_PASS);
    $dbcheck = $DB->select_single_to_array('INFORMATION_SCHEMA.SCHEMATA', 'SCHEMA_NAME', "WHERE SCHEMA_NAME='".APP_DB_NAME."'");
    if ($dbcheck['SCHEMA_NAME'] != APP_DB_NAME) {
        $sTestsOutcome = addFailure($sTestsOutcome, 'Database \''.APP_DB_NAME.'\' not found!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Table \''.APP_DB_NAME.'\' found!');
    }
    $dbcheck = $DB->query_to_array("SHOW TABLES LIKE 'user'");
    if ($dbcheck[0]['Tables_in_provider_db (user)'] != 'user') {
        $sTestsOutcome = addFailure($sTestsOutcome, 'Table \'user\' not found!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Table \'user\' found!');
    }
    $dbcheck = $DB->query_to_array("SHOW TABLES LIKE 'avatar'");
    if ($dbcheck[0]['Tables_in_provider_db (avatar)'] != 'avatar') {
        $sTestsOutcome = addFailure($sTestsOutcome, 'Table \'avatar\' not found!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Table \'avatar\' found!');
    }
    $dbcheck = $DB->query_to_array("SHOW TABLES LIKE 'federated'");
    if ($dbcheck[0]['Tables_in_provider_db (federated)'] != 'federated') {
        $sTestsOutcome = addFailure($sTestsOutcome, 'Table \'federated\' not found!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Table \'federated\' found!');
    }
    $dbcheck = $DB->query_to_array("SHOW TABLES LIKE 'legacy_oauth'");
    if ($dbcheck[0]['Tables_in_provider_db (legacy_oauth)'] != 'legacy_oauth') {
        $sTestsOutcome = addFailure($sTestsOutcome, 'Table \'legacy_oauth\' not found!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Table \'legacy_oauth\' found!');
    }
    $dbcheck = $DB->query_to_array("SHOW TABLES LIKE 'legacy_phone'");
    if ($dbcheck[0]['Tables_in_provider_db (legacy_phone)'] != 'legacy_phone') {
        $sTestsOutcome = addFailure($sTestsOutcome, 'Table \'legacy_phone\' not found!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Table \'legacy_phone\' found!');
    }
    $dbcheck = $DB->query_to_array("SHOW TABLES LIKE 'legacy_email'");
    if ($dbcheck[0]['Tables_in_provider_db (legacy_email)'] != 'legacy_email') {
        $sTestsOutcome = addFailure($sTestsOutcome, 'Table \'legacy_email\' not found!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Table \'legacy_email\' found!');
    }
    //------------------------------------------------------------------------//
    
     
    if ($numErrors > 0) {
        $sTestsOutcome = addEndWithErrors($sTestsOutcome, $numErrors);
    } else {
        $sTestsOutcome = addSuccessfulEnd($sTestsOutcome);
    }
    
    return $sTestsOutcome;
}

function addInfo($sTestsOutcome, $sMessage) {
    $sTestsOutcome .= '<div id="info">' . $sMessage . '<br/></div>';
    return $sTestsOutcome;
}

function addFailure($sTestsOutcome, $sMessage) {
    global $numErrors;
    
    $numErrors += 1;
    $sTestsOutcome .= '<div id="danger">' . $sMessage . '<br/></div>';
    return $sTestsOutcome;
}

function addSuccess($sTestsOutcome, $sMessage) {
    $sTestsOutcome .= '<div id="success">' . $sMessage . '<br/></div>';
    return $sTestsOutcome;
}

function addCriticalFailureEnd($sTestsOutcome, $sMessage) {
    $sTestsOutcome .= '<div id="danger">' . $sMessage . '<br/></div>';
    $sTestsOutcome .= UNDERLINE_DIV;
    $sTestsOutcome .= '<div id="danger">Testing failed due to CRITICAL FAILURE!<br/></div>';
    return $sTestsOutcome;
}

function addSuccessfulEnd($sTestsOutcome) {
    $sTestsOutcome .= NEW_LINE_DIV;
    $sTestsOutcome .= UNDERLINE_DIV;
    $sTestsOutcome .= '<div id="success">All tests succeeded!<br/></div>';
    return $sTestsOutcome;
}

function addEndWithErrors($sTestsOutcome, $numErrors) {
    $sTestsOutcome .= NEW_LINE_DIV;
    $sTestsOutcome .= UNDERLINE_DIV;
    $sTestsOutcome .= '<div id="warning">Tests completed with ' . $numErrors . ' errors!<br/></div>';
    return $sTestsOutcome;
}

function addNewLine($sTestsOutcome) {
    $sTestsOutcome .= NEW_LINE_DIV;
    return $sTestsOutcome;
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
