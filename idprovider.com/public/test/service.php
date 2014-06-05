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
	define('ROOT', dirname(dirname(dirname(__FILE__))) . "/");
}

define('UNDERLINE_DIV', '<div id="info">--------------------------------------------------------<br/></div>');
define('NEW_LINE_DIV', '<br/>');

$numErrors = 0;
$sResult = performTests();

function performTests() {
    global $numErrors;
    $sTestsOutcome = '';

    trigger_error("Test error to ensure error log works");
    
    // Configuration tests
    $sTestsOutcome = addInfo($sTestsOutcome, 
            'Checking: Configuration files existance...');
    if (!file_exists(ROOT.'config/config.php')) {
        $sTestsOutcome = addCriticalFailureEnd($sTestsOutcome, 
                'Missing configaration file:' . ROOT.'config/config.php');
        return $sTestsOutcome;
    }
    $sTestsOutcome = addSuccess($sTestsOutcome, 
            'File found: '.ROOT.'config/config.php');
    require(ROOT.'config/config.php');
    if (!file_exists(ROOT.'libs/mySQL/class-mysqlidb.php')) {
        $sTestsOutcome = addCriticalFailureEnd($sTestsOutcome, 
                'Missing configaration file:' . 
                ROOT.'libs/mySQL/class-mysqlidb.php');
        return $sTestsOutcome;
    }
    $sTestsOutcome = addSuccess($sTestsOutcome, 
            'File found: '.ROOT.'libs/mySQL/class-mysqlidb.php');
    require(ROOT.'libs/mySQL/class-mysqlidb.php');
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
    if (!function_exists('curl_init')) {
        $sTestsOutcome = addFailure($sTestsOutcome, 'CURL driver failure!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'CURL driver working!');
    }
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
    $dbcheck = $DB->query_to_array("SHOW TABLES LIKE 'custom'");
    if ($dbcheck[0]['Tables_in_provider_db (custom)'] != 'custom') {
        $sTestsOutcome = addFailure($sTestsOutcome, 'Table \'custom\' not found!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Table \'custom\' found!');
    }
    $dbcheck = $DB->query_to_array("SHOW TABLES LIKE 'social'");
    if ($dbcheck[0]['Tables_in_provider_db (social)'] != 'social') {
        $sTestsOutcome = addFailure($sTestsOutcome, 'Table \'social\' not found!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Table \'social\' found!');
    }
    
    //------------------------------------------------------------------------//
    
    // Persistance layer operability tests
    $sTestsOutcome = addNewLine($sTestsOutcome);
    $sTestsOutcome = addInfo($sTestsOutcome, 'Checking: Persistance layer operability...');
    
    $dbcheck_id = $DB->insert(
        'user',
        array ('updated' => 1)
    );
    if (!($dbcheck_id && $DB->select_single_to_array('user','*','where user_id='.$dbcheck_id))) {
        $sTestsOutcome = addCriticalFailureEnd($sTestsOutcome, 'Function \'insert\' not working!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Function \'insert\' working!');
    }
    $dbcheck_num = $DB->update(
        'user',
        array ('updated' => 2),
        'where user_id='.$dbcheck_id
    );
    $dbcheck = $DB->select_single_to_array('user','*','where user_id='.$dbcheck_id);
    if (!($dbcheck_num && $dbcheck['updated'] == 2)) {
        $sTestsOutcome = addCriticalFailureEnd($sTestsOutcome, 'Function \'update\' not working!');
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Function \'update\' working!');
    }
    $dbcheck_num = $DB->delete(
        'user',
        'where user_id='.$dbcheck_id 
    );
    $dbcheck = $DB->select_single_to_array('user','*','where user_id='.$dbcheck_id);
    if (!$dbcheck_num || $dbcheck) {
        $sTestsOutcome = addCriticalFailureEnd($sTestsOutcome, 'Function \'delete\' not working!');
        return $sTestsOutcome;
    } else {
        $sTestsOutcome = addSuccess($sTestsOutcome, 'Function \'delete\' working!');
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
    http_response_code(500);
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
    http_response_code(500);
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
