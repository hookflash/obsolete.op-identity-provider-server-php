<?php

$serviceConfigPath = dirname(__FILE__) . "/../../../../../service.json";

if (file_exists($serviceConfigPath)) {
	header('x-service-uid: ' . json_decode(file_get_contents($serviceConfigPath), true)["uid"]);
}
