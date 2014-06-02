<?php

/*

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
    define('ROOT', dirname(dirname(__FILE__)) . "/");
}

require (ROOT . 'config/config.php');


// We collect some configuration values and populate the template before
// we return it to the browser.

$config['HF_LOGGER_HOST'] = constant('HF_LOGGER_HOST');
$config['SESSION_identityServiceAuthenticationURL'] = '';
if (isset($_SESSION['identityServiceAuthenticationURL'])) {
    $config['SESSION_identityServiceAuthenticationURL'] = $_SESSION['identityServiceAuthenticationURL'];
    unset($_SESSION['identityServiceAuthenticationURL']);
}
$config['HF_PASSWORD1_BASEURI'] = constant('HF_PASSWORD1_BASEURI');
$config['HF_PASSWORD2_BASEURI'] = constant('HF_PASSWORD2_BASEURI');

if (isset($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $query);
    if (isset($query['view']) && $query['view'] === 'choose') {
        $IGNORE_BASE = true;
    }
    if (isset($query['custom']) && $query['custom'] === 'false') {
        $HIDE_CUSTOM = true;
    }
    if (isset($query['skin'])) {
        echo '<link rel="stylesheet" href="style-' . $query['skin'] . '.css" />';
        if ($query['skin'] === 'xfinity') {
            $IGNORE_BASE = true;
        }
    }
}
$config['IGNORE_BASE'] = (isset($IGNORE_BASE) && $IGNORE_BASE) ? 'true' : 'false';
$config['HIDE_CUSTOM'] = (isset($HIDE_CUSTOM) && $HIDE_CUSTOM) ? 'true' : 'false';

$config['ASSET_PATH'] = INNER_FRAME_ROOT;
$config['HF_LOGGER_HOST'] = HF_LOGGER_HOST;

$template = file_get_contents(LOGIN_TEMPLATE_PATH);

foreach ( $config as $name => $value ) {
    $template = str_replace('{{ config.' . $name . ' }}', $value, $template);
}

echo $template;

