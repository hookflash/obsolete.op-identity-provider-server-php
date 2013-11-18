<?php

if ( !defined('ROOT') ) {
    define('ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
}

require (ROOT . '/app/php/config/config.php');

print '{
  "result": {
    "$domain": "' . DOMAIN . '",
    "$handler": "bootstrapper",
    "$method": "services-get",

    "error": {
      "$id": 302,
      "#text": "Found",
      "location": "' . HF_SERVICE_DOMAIN . 'services-get"
    }
  }
}';

?>