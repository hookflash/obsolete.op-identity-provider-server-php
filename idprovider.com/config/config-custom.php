<?php

//-- IMPORTANT --//

// Here you set your application's standard protocol
define('PROTOCOL', 'https://');

// Here you set your domain
define('MY_DOMAIN', PROTOCOL . $_SERVER['HTTP_HOST']);

// Here you set your database
define('APP_DB_NAME', 'provider_db');
define('APP_DB_HOST', 'localhost');
define('APP_DB_USER', 'root');
define('APP_DB_PASS', '*************');

// Here you set your Hookflash service domain
define('DOMAIN', $_SERVER['HTTP_HOST']);
define('HF_SERVICE_DOMAIN', PROTOCOL . 'unstable.hookflash.me/');

// Here you set your OAuth keys and secrets
define('LINKEDIN_CONSUMER_KEY', '***********');
define('LINKEDIN_CONSUMER_SECRET', '******************');

define('FACEBOOK_APP_ID', '**************');
define('FACEBOOK_APP_SECRET', '**********************************');

define('TWITTER_APP_ID', '************');
define('TWITTER_APP_SECRET', '**********************************');

// Here you set your SMTP service parameters
require(ROOT . 'config/special/smtp_config.php');

// Here you set your SMS service parameters
require(ROOT . 'config/special/sms_config.php');

// Here you set your specific cryptographically random values
define('PROVIDER_MAGIC_VALUE', '******************************');

// Here you set your domain hosting secret
define('DOMAIN_HOSTING_SECRET', '***************');

// Here you set your users' avatars uploading location
define('UPLOAD_LOCATION', ROOT . 'public/php/service/avatars/');

// Where to send JS logs
define('HF_LOGGER_HOST', 'logger.hookflash.me');

define('HF_PASSWORD1_BASEURI', '***************');
define('HF_PASSWORD2_BASEURI', '***************');

//^^ IMPORTANT ^^//