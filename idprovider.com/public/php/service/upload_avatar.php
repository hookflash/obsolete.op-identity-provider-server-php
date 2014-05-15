<?php

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
	define('ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
}
if ( !defined('APP') ) {
	define('APP', ROOT . '/app/');
}
require_once (APP . 'php/config/config.php');
require_once (APP . 'php/main/utils/uploadUtil.php');

// Start the uploadAvatar function
UploadUtil::uploadAvatar();

?>