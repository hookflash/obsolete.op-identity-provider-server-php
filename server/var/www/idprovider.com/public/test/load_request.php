<?php

$sFileExtension = $_GET['fileType'] == 'JSON' ? '.txt' : '.xml';

$sFile = dirname(__FILE__) . "/request_templates/" . str_replace("..", "", $_GET['method']) . $sFileExtension;

if ( file_exists($sFile) ) {
	print file_get_contents($sFile);
} else {

	print '<request xmlns="http://www.example.com/openpeer/1.0/message" id="' . md5(rand(0, 1e5)) . '" method="' . $_GET['method'] . '">
</request>
';

}


?>