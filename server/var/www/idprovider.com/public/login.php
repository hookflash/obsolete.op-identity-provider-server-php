<?php


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
    define('ROOT', dirname(dirname(__FILE__)));
}
if ( !defined('APP') ) {
    define('APP', ROOT . '/app/');
}

require (APP . 'php/config/config.php');


?><!-- 

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

 -->

<!--
  Example Identity Provider Login page.
-->

<html>
<head>
<title>Example Identity Provider - Login/Sign up</title>

<script type="text/javascript" src="//logger.hookflash.me/tools/logger/logger.js"></script>

<script type="text/javascript" src="js/lib/cryptojs/rollups/sha1.js"></script>
<script type="text/javascript" src="js/lib/cryptojs/rollups/sha256.js"></script>
<script type="text/javascript" src="js/lib/cryptojs/rollups/hmac-sha1.js"></script>
<script type="text/javascript" src="js/lib/cryptojs/rollups/aes.js"></script>
<script type="text/javascript" src="js/lib/jquery/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/lib/ajaxfileupload.js"></script>
<script type="text/javascript" src="js/lib/base64.js"></script>
<!--
<script type="text/javascript" src="js/lib/cifre/aes.js"></script>
<script type="text/javascript" src="js/lib/cifre/utils.js"></script>
-->
<script type="text/javascript" src="js/HF.js"></script>

<link rel="stylesheet" href="style.css"/>
<?php
if (isset($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $query);
    if (isset($query['skin'])) {
        echo '<link rel="stylesheet" href="style-' . $query['skin'] . '.css" />';
        if ($query['skin'] === 'xfinity') {
            $IGNORE_BASE = true;
        }
    }
}
?>

<script type="text/javascript">

    window.__LOGGER.setUrl("//<?php echo constant('HF_LOGGER_HOST'); ?>/tools/logger/record");
    window.__LOGGER.setChannel("identity-provider-js-all");

    var HF = new HF_LoginAPI();
    var initBundle = {
        identityServiceAuthenticationURL: "<?php
          if (isset($_SESSION['identityServiceAuthenticationURL'])) {
              echo $_SESSION['identityServiceAuthenticationURL'];
              unset($_SESSION['identityServiceAuthenticationURL']);
          }
        ?>",
        // TODO: Don't use `document.domain` here. Should use config variable instead.
        $identityProvider: document.domain,
        login: {
            click: "loginClick",
            id: "loginId",
            password: "loginPassword"
        },
        signup: {   
            click: "signupClick",
            id: "signUpId",
            password: "signUpPassword",
            displayName: "signUpDisplayName"
        },
        pinClick: "pinClick",
        ignoreBase: <?php if (isset($IGNORE_BASE) && $IGNORE_BASE) echo 'true'; else echo 'false'; ?>
    };

    $(document).ready(function() {
        if (/dev=true/.test(window.location.search)) {
            $("HEAD").append('<link rel="stylesheet" href="style-dev.css"/>');
            $("BODY").prepend('<div class="op-view-label">' + window.location.pathname + '</div>');
        }
    });

</script>
</head>

<body onload='HF.init(initBundle);'>
    <div class="op-centered">
        <div id="op-logo"></div>
        <div id="op-spinner"></div>
        <div id="op-federated-login-view" class="op-hidden">
            <div class="op-view">
                <h1>Login</h1>
                <div class="op-error op-hidden"></div>
                <div class="op-fieldset"><input type="text" id="loginId" placeholder="username" autocorrect="off" autocapitalize="off"/></div>
                <div class="op-fieldset"><input type="password" id="loginPassword" placeholder="password" autocorrect="off" autocapitalize="off"/></div>
                <div class="op-fieldset">
                    <button id="op-federated-login-button">Login</button>
                    <div class="op-fieldset-actions"><a class="op-buttonlink" href="#" onclick="HF.showView('federated-signup');">Sign Up</a></div>
                </div>
            </div>
        </div>

        <div id="op-federated-signup-view" class="op-hidden">
            <div class="op-view">
                <h1>Create Account</h1>

                <div class="op-headerlink"><a class="op-headerlink" href="#" onclick="HF.showView('federated-login');">Back</a></div>

                <div class="op-error op-hidden"></div>
<!--                
                <div class="op-fieldset"><label>Avatar</label><input type="file" name="file" id="file" /><button id="op-federated-signup-upload-button">Upload</button></div>
-->
                <div class="op-fieldset"><label>Display Name</label><input type="text" id="signUpDisplayName" autocorrect="off" autocapitalize="off"/></div>
                <div class="op-fieldset"><label>Username</label><input type="text" id="signUpId" autocorrect="off" autocapitalize="off"/></div>
                <div class="op-fieldset"><label>Password</label><input type="password" id="signUpPassword" autocorrect="off" autocapitalize="off"/></div>
                <div class="op-fieldset">
                    <button id="op-federated-signup-button">Sign up</button>
                    <div class="op-fieldset-actions"><a class="op-buttonlink" href="#" onclick="HF.showView('federated-login');">Log In</a></div>
                </div>
            </div>
        </div>

        <div id="op-social-facebook-view" class="op-hidden">
            <div class="op-view">
                <button id="op-social-facebook-button"><img src="images/iPhone_signin_facebook@2x.png"></button>
            </div>
        </div>

        <div id="op-pinvalidation-view" class="op-hidden">
            <div class="op-view">
                <h1>Enter PIN</h1>
                <input type="text" size="6" id="pin" autocorrect="off" autocapitalize="off"/>
                <button onclick="op-pinvalidation-button" >Validate PIN</button>
                <div id="pinexpired">&nbps;</div>
            </div>
        </div>
    </div>
</body>
</html>
