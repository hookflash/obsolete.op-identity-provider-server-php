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

<script type="text/javascript" src="js/lib/cryptojs/rollups/sha1.js"></script>
<script type="text/javascript" src="js/lib/cryptojs/rollups/sha256.js"></script>
<script type="text/javascript" src="js/lib/cryptojs/rollups/hmac-sha1.js"></script>
<script type="text/javascript" src="js/lib/cryptojs/rollups/aes.js"></script>
<script type="text/javascript" src="js/lib/jquery/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/lib/jquery/jquery-mobile-1.3.0.js"></script>
<script type="text/javascript" src="js/lib/ajaxfileupload.js"></script>
<script type="text/javascript" src="js/lib/base64.js"></script>

<script type="text/javascript" src="js/HF.js"></script>
<script src="js/lib/cifre/aes.js"></script>
<script src="js/lib/cifre/utils.js"></script>

<script type="text/javascript" src="//logger.hookflash.me/tools/logger/logger.js"></script>

<link rel="stylesheet" href="js/lib/jquery/jquery.mobile-1.3.0.min.css" />

<style>
DIV.error {
    margin: 10px;
    padding: 5px;
    border: 1px solid #FF0000;
    color: #FF0000;
    font-weight: bold;
    white-space: nowrap;
}
DIV.hidden {
    display: none;
}
</style>

<script type="text/javascript">

    window.__LOGGER.setUrl("//logger.hookflash.me/tools/logger/record");
    window.__LOGGER.setChannel("identity-js-all");

    var HF = new HF_LoginAPI();
    var initBundle = {
        identityServiceAuthenticationURL: "<?php echo $_SESSION['identityServiceAuthenticationURL']; unset($_SESSION['identityServiceAuthenticationURL']); ?>",
        $identityProvider: "idprovider-javascript.hookflash.me",
        federatedId: "federated",
        pinvalidationId: "pinvalidation",
        login: {
            click: "loginClick",
            id: "loginId",
            password: "loginPassword"
        },
        signup: {   
            click: "signupClick",
            id: "signUpId",
            password: "signUpPassword",
            displayName: "signUpDisplayName",
            uploadClick: "uploadClick"
        },
        pinClick: "pinClick"
    };

    // Show sign up div / hide login div
    function showSignUp() {
        $("#signupDiv").css("display", "block");
        $("#loginDiv").css("display", "none");
    }

    // Hide sign up div / show login div
    function showLogin() {
        $("#signupDiv").css("display", "none");
        $("#loginDiv").css("display", "block");
    }

</script>
</head>

<body onload='HF.init(initBundle);'>
    <div id="federated" style="display: none;">
        <div id="loginDiv" style="display: block;">
            <div data-role="header">
                <h1>Enter credentials</h1>
            </div>
            <div class="error hidden"></div>
            Username <input type="text" size="40" id="loginId" />
            Password <input type="password" size="20" id="loginPassword" />
            <button id="loginClick">
                ok
            </button>
            <a href="#" onclick="showSignUp();">Sign Up</a>
        </div>

        <div id="signupDiv" style="display: none;">
            <div data-role="header">
                <h1>Enter credentials</h1>
            </div>
            <div class="error hidden"></div>
            Filename <input type="file" name="file" id="file" />
            <button id="uploadClick">Upload</button>
            Display Name <input type="text" size="40" id="signUpDisplayName" />
            Username <input type="text" size="40" id="signUpId" />
            Password <input type="password" size="20" id="signUpPassword">
            <button id="signupClick">
                Sign up
            </button>
            <a href="#" onclick="showLogin();">Log In</a>
        </div>
    </div>

    <div id="pinvalidation" style="display: none;">
        <div data-role="header">
            <h1>Enter PIN</h1>
        </div>
        <input type="text" size="6" id="pin" />
        <button onclick="pinClick" >
            Validate PIN
        </button>
        <div id="pinexpired">&nbps;</div>
    </div>
</body>
</html>
