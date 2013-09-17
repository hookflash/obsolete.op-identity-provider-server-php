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

/**
 * Hookflash Identity Provider API
 */

(function(window) {

    function generateId() {
        return (Math.floor(Math.random() * 1000000) + 1 + "");
    }

    function log() {
        if (window.__LOGGER) {
            return window.__LOGGER.log.apply(null, arguments);
        } else {
            console.log(arguments);
        }
    }

    var lastPostedMessage = null;
    function postMessage(message, targetOrigin) {
        log("window.parent.postMessage", message, targetOrigin);
        lastPostedMessage = message;
        window.parent.postMessage(message, targetOrigin);
    }


    var HF_LoginAPI = window.HF_LoginAPI = function() {

        log("########################################");
        log("Init", window.location.href);

        var _version = '0.1';                   // The current version

        var identity = {};                      // identity
        var identityAccessStart;                // identityAccessStart notify
        var initData;                           // init data
        var imageBundle = {};                   // imageBundle (used for avatar upload)
        var $identityProviderDomain;            // used for every request
        var serverMagicValue;                   // serverMagicValue
        var waitForNotifyResponseId;            // id of "identity-access-window" request
        var secretSetResults = 0;               // 
        
        //  passwordServers
        var passwordServer1 = 'https://hcs-javascript.hookflash.me/password1';
        var passwordServer2 = 'https://hcs-javascript.hookflash.me/password2';
        
        /**
         * Gets the current version
         * 
         * @return string The version number
         */
        var getVersion = function() {
            return _version;
        };

        var init = function(bundle) {
            try {
                initData = bundle;
                $appid = initData.$appid;
                $identityProviderDomain = initData.$identityProvider;
                
                // reload scenario
                var url = window.location.href;
                if (url.indexOf("?reload=true") > 0) {
                    finishOAuthScenario(url);
                } else {
                    identityAccessWindowNotify(true, false);
                }
            } catch (err) {
                log("ERROR", "init", err.stack);
            }
        };
        
        // Global cross-domain message handler.
        window.onmessage = function(message) {
            if (!message.data) return;
            if (message.data === lastPostedMessage) return;

            log("window.onmessage", message.data);

            try {
                var data = JSON.parse(message.data);

                log("window.onmessage", "data", data);
                log("window.onmessage", "identity", identity);
                log("window.onmessage", "waitForNotifyResponseId", waitForNotifyResponseId);

                if (data.notify) {
                    if (data.notify.$method == "identity-access-start") {
                        // start login/sign up procedure
                        identityAccessStart = data.notify;
                        if (identityAccessStart.identity.reloginKey !== undefined) {
                            //relogin
                            startRelogin();
                        } else {
                            startLogin();
                        }
                    }
                } else
                if (data.result) {
                    if (data.result.$method == 'identity-access-window') {
                        if (
                            data.result.$id === waitForNotifyResponseId &&
                            identity.redirectURL
                        ) {
                            return redirectToURL(identity.redirectURL);
                        }
                    }
                } else
                if (data.request) {
                    if (data.request.$method === "identity-access-lockbox-update") {
                        identityAccessLockboxUpdate(data);
                    } else
                    if (data.request.$method === "identity-access-rolodex-credentials-get") {
                        identityAccessRolodexCredentialsGet(data);
                    }
                }
            } catch (err) {
                log("ERROR", "window.onmessage", err.stack);
            }
        };


        var permissionGrantComplete = function(id, permisions) {
            return postMessage(JSON.stringify({
                "notify" : {
                    "$domain" : $identityProviderDomain,
                    "$appid" : $appid,
                    "$id" : generateId(),
                    "$handler" : "lockbox",
                    "$method" : "lockbox-permission-grant-complete",

                    "grant" : {
                        "$id" : id,
                        "permissions" : {
                            "permission" : permisions
                        }
                    }
                }
            }), "*");
        };

        /**
         * Redirects parent page to URL.
         */
        var redirectToURL = function(url) {
            log("redirectToURL", url);
            localStorage.clientAuthenticationToken = identity.clientAuthenticationToken;
            localStorage.identityURI  = identity.uri;
            log("set localStorage", {
                clientAuthenticationToken: localStorage.clientAuthenticationToken,
                identityURI: localStorage.identityURI
            });
            window.top.location = url;
        };

        var identityAccessWindowNotify = function(ready, visibility) {
            log("identityAccessWindowNotify", ready, visibility);
            var id = generateId();
            var readyMessage = {
                "request" : {
                    "$domain" : $identityProviderDomain,
                    "$appid" : $appid,
                    "$id" : id,
                    "$handler" : "identity",
                    "$method" : "identity-access-window",

                    "browser" : {
                        "ready" : ready,
                        "visibility" : visibility
                    }
                }
            };
//            if (visibility && identity.type == 'facebook') {
                log("set waitForNotifyResponseId", id);
                waitForNotifyResponseId = id;
//            }
            return postMessage(JSON.stringify(readyMessage), "*");
        };

        /**
         * Decrypts lockbox keyhalf.
         * 
         * @return decrypted lockbox key half 
         */
        var decryptLockbox =  function(lockboxKeyHalf, passwordStretched, userId, userSalt) {
            var key = hmac(passwordStretched, userId);
            var iv = hash(userSalt);
            var dec = decrypt(lockboxKeyHalf, key, iv);
            return dec;
        };

        /**
         * Encrypts lockbox key half.
         * 
         * @return encrypted lockbox key half
         */
        var encryptLockbox = function(lockboxKeyHalf, passwordStretched, userId, userSalt) {
            var key = hmac(passwordStretched, userId);
            var iv = hash(userSalt);
            var enc = encrypt(lockboxKeyHalf, key, iv);
            return enc;
        };

        var startLogin = function() {
            log("startLogin");
            setType(identityAccessStart);
            if (
                identity.type === "email" ||
                identity.type === "phone"
            ) {
                startLoginLegacy();
            } else
            if (
                identity.type === "facebook" ||
                identity.type === "linkedin" ||
                identity.type === "twitter"
            ) {
                startLoginOauth();
            } else
            if (identity.type === "federated") {
                startLoginFederated();
            }
        };
        
        var startRelogin = function() {
            log("startRelogin");
            setType(identityAccessStart);
            //getSalts (and then call relogin)
            getIdentitySalts(relogin);
        };
        
        var relogin = function() {
            log("relogin");
            var reloginKeyDecrypted = decrypt(identityAccessStart.identity.reloginKey, identity.reloginEncryptionKey);
            var reloginKeyServerPart = reloginKeyDecrypted.split("--")[1];
            return login({
                "request": {
                    "$domain": $identityProviderDomain,
                    "$id": generateId(),
                    "$handler": "identity-provider",
                    "$method": "login",
                    "identity": {
                        "reloginKeyServerPart": reloginKeyServerPart
                    }
                }
            });
        };

        var setType = function(identityAccessStart) {
            log("setType", identityAccessStart);
            var id = identityAccessStart.identity.base || identityAccessStart.identity.uri;
            if (id.startsWith("identity:phone:")) {
                identity.type = "phone";
                identity.uri = "identity:phone:";
                identity.identifier = identityBase.substring(15, id.length);
            } else
            if (id.startsWith("identity:email:")) {
                identity.type = "email";
                identity.uri = "identity:email:";
                identity.identifier = id.substring(15, identityBase.length);
            } else
            if (id.startsWith("identity://" + $identityProviderDomain + "/linkedin.com")) {
                identity.type = "linkedin";
                identity.uri = "identity://" + $identityProviderDomain
                        + "/linkedin.com/";
            } else
            if (id.startsWith("identity://" + $identityProviderDomain)) {
                identity.type = "federated";
                identity.uri = "identity://" + $identityProviderDomain + "/";
                identity.identifier = id.split($identityProviderDomain + "/")[1];
            } else
            if (id.startsWith("identity://facebook.com")) {
                identity.type = "facebook";
                identity.uri = "identity://facebook/";
            } else
            if (id.startsWith("identity://twitter.com")) {
                identity.type = "twitter";
                identity.uri = "identity://twitter/";
            } else {
                log("ERROR", "Unknown identity type", id);
            }
        };

        var startLoginFederated = function() {
            log("startLoginFederated");
            // show login/sign up form
            // show federated div
            $("#" + initData.federatedId).css("display", "block");
            // add onclick listeners
            // sign up
            $("#" + initData.signup.click).click(function() {
                signUpOnClick();
            });
            // sign up -upload image handler
            $("#" + initData.signup.uploadClick).click(function() {
                uploadOnClick();
            });
            // login
            $("#" + initData.login.click).click(function() {
                loginOnClick();
            });
            identityAccessWindowNotify(true, true);
        };

        var signUpOnClick = function() {
            log("signUpOnClick");
            // read data from input fields
            identity.identifier = $("#" + initData.signup.id).val();
            identity.password = $("#" + initData.signup.password).val();
            identity.displayName = $("#" + initData.signup.displayName).val();

            getIdentitySalts(signUp);
        };

        var signUp = function() {
            log("signUp");

            // stretch password
            identity.passwordStretched = generatePasswordStretched(
                    identity.identifier, identity.password,
                    identity.serverPasswordSalt);
            // generate secretSalt
            identity.secretSalt = generateIdentitySecretSalt(identity.identifier,
                    identity.password, identity.passwordStretched,
                    identity.serverPasswordSalt);

            var requestData = {
                "request" : {
                    "$domain" : $identityProviderDomain,
                    "$id" : generateId(),
                    "$handler" : "identity-provider",
                    "$method" : "sign-up",

                    "identity" : {
                        "type" : identity.type,
                        "identifier" : identity.identifier,
                        "passwordHash" : identity.passwordStretched,
                        "secretSalt" : identity.secretSalt,
                        "serverPasswordSalt" : identity.serverPasswordSalt,
                        "uri" : identity.uri + identity.identifier
                    },
                    "displayName" : identity.displayName,
                    "avatars" : {
                        "avatar" : {
                            "name" : imageBundle.filename,
                            "url" : imageBundle.fileURL
                        }
                    }
                }
            };
            var requestDataString = JSON.stringify(requestData);
            log("ajax", "/api.php", requestData);
            $.ajax({
                url : "/api.php",
                type : "post",
                data : requestDataString,
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    if (signupSuccess(response)) {
                        getServerNonce(loginFederated);
                    } else {
                        log("ERROR", "SubmitSignup");
                    }
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    log("ERROR", "SubmitSignup-> The following error occured: "
                            + textStatus + errorThrown);
                }
            });
            
            // Validates response of Sign up action call.
            //
            // @param response from server
            // @param afterSignUp callback
            function signupSuccess(response, afterSignUp) {
                log("signupSuccess", response);
                try {
                    var result = JSON.parse(response);
                    // validate response
                    if (result.error) {
                        return false;
                    } else {
                        return true;
                    }
                } catch (e) {
                    return false;
                }
            }
        };

        /**
         * Upload avatar handler.
         */
        var uploadOnClick = function() {
            log("uploadOnClick");
            $.ajaxFileUpload({
                url : '/php/service/upload_avatar.php',
                secureuri : true,
                fileElementId : 'file',
                dataType : 'json',
                success : function(data, status) {
                    if (validateResponseUploadSuccess(data)) {
                        setImageBundle(data);
                    } else {
                        log("ERROR", 'fileupload');
                    }
                },
                error : function(data, status, e) {
                    log("ERROR", 'fileupload' + e);
                }
            });

            // validate response from avatar upload request.
            function validateResponseUploadSuccess(data) {
                try {
                    if (data.result.file.name && data.result.file.url) {
                        return true;
                    } else {
                        return false;
                    }
                } catch (e) {
                    return false;
                }
            }

            // set imageBundle data.
            function setImageBundle(data) {
                imageBundle.filename = data.result.file.name;
                imageBundle.fileURL = data.result.file.url;
                // TODO: implement image width and height
            }
        };
        
        var getIdentitySalts = function(callback) {
            log("getIdentitySalts");
            var data = {
                "request" : {
                    "$domain" : $identityProviderDomain,
                    "$id" : generateId(),
                    "$handler" : "identity-provider",
                    "$method" : "identity-salts-get",

                    "identity" : {
                        "type" : identity.type,
                        "identifier" : identity.identifier
                    }
                }
            };
            $.ajax({
                url : "/api.php",
                type : "post",
                data : JSON.stringify(data),
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    if (getIdentitySaltsSuccess(response)) {
                        callback();
                    } else {
                        log("ERROR", "getIdentitySalts");
                    }
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    log("ERROR", "GetIdentitySalts: The following error occured: " + textStatus);
                }
            });
            
            // getIdentitySalts on success callback.
            function getIdentitySaltsSuccess(response) {
                try {
                    // parse json
                    var result = JSON.parse(response).result;
                    if (result && result.identity
                            && result.identity.serverPasswordSalt) {
                        identity.serverPasswordSalt = result.identity.serverPasswordSalt;
                        if (result.identity.secretSalt) {
                            // set identitySecretSalt
                            identity.secretSalt = result.identity.secretSalt;
                        }
                        if (result.serverMagicValue){
                            serverMagicValue = result.serverMagicValue;
                        }
                        if (result.identity && result.identity.reloginEncryptionKey){
                            identity.reloginEncryptionKey = result.identity.reloginEncryptionKey;
                        }
                        return true;
                    } else {
                        return false;
                    }
                } catch (e) {
                    return false;
                }
            }
        };
        
        var getServerNonce = function(callback) {
            log("getServerNonce");
            var requestData = {
                "request" : {
                    "$domain" : $identityProviderDomain,
                    "$id" : generateId(),
                    "$handler" : "identity-provider",
                    "$method" : "server-nonce-get"
                }
            };
            var requestDataString = JSON.stringify(requestData);
            $.ajax({
                url : "/api.php",
                type : "post",
                data : requestDataString,
                success : function(response, textStatus, jqXHR) {
                    if (getServerNonceSuccess(response)){
                        callback();
                    } else {
                        log("ERROR", "getServerNonce");
                    }
                },
                error : function(jqXHR, textStatus, errorThrown) {
                    log("ERROR", "getServerNonce: The following error occured: "
                            + textStatus, errorThrown);
                }
            });
            
            // getServerNonceSuccess on success callback.
            function getServerNonceSuccess(response) {
                try {
                    var data = JSON.parse(response);
                    // set data to serverNonce global variable
                    identity.serverNonce = data.result.serverNonce;
                    return true;
                } catch (e) {
                    return false;
                }
            }
        };

        var loginOnClick = function() {
            log("loginOnClick");
            // read data from input fields
            identity.identifier = $("#" + initData.login.id).val();
            identity.password = $("#" + initData.login.password).val();

            getIdentitySalts(getIdentitySaltsCallbackFederated);
        };
        var getIdentitySaltsCallbackFederated = function(){
            log("getIdentitySaltsCallbackFederated");
            getServerNonce(loginFederated);
        }
        
        var login = function(loginData) {
            log("login", loginData);
            var loginDataString = JSON.stringify(loginData);
            $.ajax({
                url : "/api.php",
                type : "post",
                data : loginDataString,
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    log("ajax", "/api.php", "response", response);
                    try {
                        return afterLogin(JSON.parse(response));
                    } catch(err) {
                        log("ERROR", err.stack);
                    }
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    // log the error to the console
                    log("ERROR", "login - on error" + textStatus);
                }
            });
            
    //        // handle afterLogin
    //        function afterLogin(){
    //            log("DEBUG", 'login - afterLogin()');
    //            try {
    //                var responseJSON = JSON.parse(loginResponse);
    //                // pin validation scenario
    //                if (responseJSON.result 
    //                        && responseJSON.result.loginState == "PinValidationRequired"){
    //                    pinValidateStart();
    //                }
    //                if (responseJSON.result 
    //                        && responseJSON.result.loginState == "succeeded"){
    //                    
    //                    // login is valid
    //                    if ()
    //                    if (responseJSON.result.lockbox && responseJSON.result.lockbox.key){
    //                        // 
    //                    } else {
    //                        accessCompleteNotify(responseJSON.result);
    //                        
    //                    }
    //                }
    //            } catch (e) {
    //                log("ERROR", 'login - afterLogin()');
    //            }
    //        }
        };
        
        var loginFederated = function() {
            log("loginFederated");
            identity.passwordStretched = generatePasswordStretched(identity.identifier, 
                    identity.password, 
                    identity.serverPasswordSalt);
            identity.serverLoginProof = generateServerLoginProof(serverMagicValue, 
                    identity.passwordStretched, 
                    identity.identifier,
                    identity.serverPasswordSalt,
                    identity.secretSalt);
            identity.serverLoginFinalProof = generateServerLoginFinalProof(serverMagicValue, 
                    identity.serverLoginProof,     
                    identity.serverNonce);
            var loginData = {
                    "request": {
                        "$domain": $identityProviderDomain,
                        "$id": generateId(),
                        "$handler": "identity-provider",
                        "$method": "login",
                        
                        "proof": {
                                    "serverNonce": identity.serverNonce,
                                    "serverLoginProof": identity.serverLoginFinalProof
                                 },
                        "identity": {
                                        "type": identity.type,
                                        "identifier": identity.identifier,
                                    }
                    }
            };
            login(loginData);
        };

        var startLoginOauth = function(){
            log("startLoginOauth");
            identity.clientAuthenticationToken = generateClientAuthenticationToken(
                generateId(), 
                generateId(),
                generateId()
            );
            var requestDataString = JSON.stringify({
                "request": {
                    "$domain": $identityProviderDomain,
                    "$id": generateId(),
                    "$handler": "identity-provider",
                    "$method": "oauth-provider-authentication",
                    "clientAuthenticationToken": identity.clientAuthenticationToken,
                    "callbackURL": identityAccessStart.browser.outerFrameURL,
                    "identity": {
                        "type": identity.type
                    }
                }
            });
            log("ajax", "/api.php", requestDataString);
            $.ajax({
                url : "/api.php",
                type : "post",
                data : requestDataString,
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    // log a message to the console
                    log("DEBUG", "loginOAuth - on success");
                    // handle response
                    if (validateOauthProviderAuthentication(response)) {
                        log("identity", identity);
                        redirectParentOnIdentityAccessWindowResponse();
                    } else {
                        log("ERROR", "loginOAuth - validation error");
                    }
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    // log the error to the console
                    log("ERROR", "loginOAuth - on error" + textStatus);
                }
            });
            
            // loginOAuthStartScenario callback.
            function validateOauthProviderAuthentication(response) {
                try {
                    var responseJSON = JSON.parse(response);
                    var redirectURL = responseJSON.result.providerRedirectURL;
                    if (redirectURL){
                        identity.redirectURL = redirectURL;
                        return true;
                    }
                } catch (e) {
                    return false;
                }
                
            }
        };
        
        var redirectParentOnIdentityAccessWindowResponse = function() {
            log("redirectParentOnIdentityAccessWindowResponse");
            identityAccessWindowNotify(true, true);
        }
        
        var identityAccessCompleteNotify = function(data) {
            log("identityAccessCompleteNotify", data);
            var uri = generateIdentityURI(identity.type, identity.identifier);
            var lockboxkey = data.lockbox.key;
            
            // create reloginKey (only first time)
            var reloginKey;
            if (identityAccessStart.identity.reloginKey){
                reloginKey = identityAccessStart.identity.reloginKey;
            } else {
                reloginKey = encrypt(identity.passwordStretched + "--" + data.identity.reloginKeyServerPart, identity.reloginEncryptionKey);
            }

            var message = null;
            if (lockboxkey) {
                var iv = hash(identity.secretSalt);
                var key = decryptLockbox(lockboxkey, identity.passwordStretched, identity.identifier, iv);
                message = {
                    "notify": {
                        "$domain": $identityProviderDomain,
                        "$appid" : $appid,
                        "$id": generateId(),
                        "$handler": "identity",
                        "$method": "identity-access-complete",
                        
                        "identity": {
                            "accessToken": data.identity.accessToken,
                            "accessSecret": data.identity.accessSecret,
                            "accessSecretExpires": data.identity.accessSecretExpires,
                            
                            "uri": uri ,
                            "provider": $identityProviderDomain,
                            "reloginKey": reloginKey
                        },
                        "lockbox": {
                            "domain": data.$domain,
                            "key": key,
                            "reset": data.lockbox.reset
                        }
                    }
                };
            } else {
                message = {
                    "notify": {
                        "$domain": $identityProviderDomain,
                        "$appid" : $appid,
                        "$id": generateId(),
                        "$handler": "identity",
                        "$method": "identity-access-complete",
                        
                        "identity": {
                            "accessToken": data.identity.accessToken,
                            "accessSecret": data.identity.accessSecret,
                            "accessSecretExpires": data.identity.accessSecretExpires,
                            
                            "uri": uri,
                            "provider": $identityProviderDomain,
                            "reloginKey": reloginKey
                        }
                    }
                };
            }
            
            return postMessage(JSON.stringify(message), "*");
        };
                
        var finishOAuthScenario = function(url) {
            log("finishOAuthScenario", url);

            // remove domain
            var params = url.split("?").pop();
            params = params.split("&").pop();
            // facebook fix (remove #...)
            params = params.split("#")[0];
            params = decodeURIComponent(params);
            var paramsJSON = JSON.parse(params);

            log("get localStorage", {
                clientAuthenticationToken: localStorage.clientAuthenticationToken
            });
            var clientAuthenticationToken = localStorage.clientAuthenticationToken;
            identity.type = paramsJSON.result.identity.type;
            identity.identifier = paramsJSON.result.identity.identifier;

            return login({
                "request": {
                    "$domain": $identityProviderDomain,
                    "$appid" : $appid,
                    "$id": generateId(),
                    "$handler": "identity",
                    "$method": "login",                    
                    "proof" : {
                        "clientAuthenticationToken": clientAuthenticationToken,
                        "serverAuthenticationToken": paramsJSON.result.serverAuthenticationToken
                    },
                    "identity": {
                        "type": paramsJSON.result.identity.type,
                        "identifier": paramsJSON.result.identity.identifier
                    }
                }
            });
        };
        
        /**
         * Login api call.
         * 
         * @param loginData
         */
        var login = function(loginData) {
            log("login", loginData);
            var loginDataString = JSON.stringify(loginData);
            log("ajax", "/api.php", loginDataString);
            $.ajax({
                url : "/api.php",
                type : "post",
                data : loginDataString,
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    log("ajax", "/api.php", "response", response);
                    try {
                        return afterLogin(JSON.parse(response));
                    } catch(err) {
                        log("ERROR", err.stack);
                    }
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    // log the error to the console
                    log("ERROR", "login - on error" + textStatus);
                }
            });
        };

        // AfterLogin callback.
        var afterLogin = function(responseJSON) {
            log("afterLogin", responseJSON);
            try {
                if (!responseJSON.result) {
                    log("no 'result' property in response");
                    return;
                }
                if (responseJSON.result.error) {
                    log("login error!", responseJSON.result.error);
                    return;
                }
                // pin validation scenario
                if (responseJSON.result.loginState === "PinValidationRequired") {
                    pinValidateStart();
                } else
                if (responseJSON.result.loginState === "Succeeded") {
                    // login is valid
                    // OAuth
                    if (
                        identity.type == "facebook" ||
                        identity.type == "twitter" ||
                        identity.type == "linkedin"
                    ) {
                        if (!responseJSON.result.lockbox.key) {
                            // if first time seen identity
                            getHostingData(responseJSON, true);
                        } else {
                            // if seen this before
                            getHostingData(responseJSON, false);
                        }
                    } else {
                        // all other scenarios
                        identityAccessCompleteNotify(responseJSON.result);
                    }
                }
            } catch (err) {
                log("ERROR", err.stack);
            }
        };
        
        /**
         * Generates identity URI.
         * 
         * @param type
         * @param identifier
         */
        var generateIdentityURI = function(type, identifier) {
            var uri = null;
            if (type === 'facebook'){
                uri = "identity://facebook.com/" + identifier;
            } else if (type == 'federated'){
                uri = "identity://" + $identityProviderDomain + '/' + identifier;
            }
            uri.toLowerCase();
            return uri;
        };
        
        /**
         * Identity-access-lockbox-update request.
         * 
         * @param data
         */
        var identityAccessLockboxUpdate = function(data) {
            log("identityAccessLockboxUpdate", data);
            var key = data.request.lockbox.key;
            var type = identity.type;
            var keyEncrypted = encryptLockbox(key, identity.passwordStretched, identity.identifier, identity.secretSalt);
            
            var requestData = {
                    "request": {
                        "$domain": $identityProviderDomain,
                        "$id": data.request.$id,
                        "$handler": "identity-provider",
                        "$method": "lockbox-half-key-store",
                    
                        "nonce": data.request.clientNonce,
                        "identity": {
                            "accessToken": data.request.identity.accessToken,
                            "accessSecretProof": data.request.identity.accessSecretProof,
                            "accessSecretProofExpires": data.request.identity.accessSecretProofExpires,
                            
                            "type": identity.type,
                            "identifier": identity.identifier,
                            "uri": data.request.identity.uri
                        },      
                        "lockbox": {
                            "keyEncrypted": keyEncrypted
                        }
                    }
            };
            
            var requestDataString = JSON.stringify(requestData);
            $.ajax({
                url : "/api.php",
                type : "post",
                data : requestDataString,
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    if (validateIdentityAccessLockboxUpdateFedereated(response)) {
                        identityAccessLockboxUpdateResult(response);
                    } else {
                        log("ERROR", "SubmitSignup");
                    }
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    log("ERROR", "identityAccessLockboxUpdate-> The following error occured: "
                            + textStatus + errorThrown);
                }
            });
            
            function validateIdentityAccessLockboxUpdateFedereated(response){
                try {
                    var responseJSON = JSON.parse(response);
                    if (responseJSON.result.error !== undefined){
                        return true;
                    }
                    return true;
                } catch (e) {
                    return false;
                }
            }
            
        };
        
        /**
         * Identity-access-rolodex-credentials-get request.
         * 
         * @param data
         */
        var identityAccessRolodexCredentialsGet = function(data){
            log("identityAccessRolodexCredentialsGet", data);
            var requestDataString = JSON.stringify({
                "request": {
                    "$domain": $identityProviderDomain,
                    "$id": data.request.$id,
                    "$handler": data.request.$handler,
                    "$method": "identity-access-rolodex-credentials-get",
                    "clientNonce": data.request.nonce,
                    "identity": {
                        "accessToken": data.request.identity.accessToken,
                        "accessSecretProof": data.request.identity.accessSecretProof,
                        "accessSecretProofExpires": data.request.identity.accessSecretProofExpires,
                        "uri": data.request.identity.uri,
                        "provider": data.request.identity.provider
                    }
                }
            });
            $.ajax({
                url : "/api.php",
                type : "post",
                data : requestDataString,
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    if (validateIdentityAccessRolodexCredentialsGet(response)) {
                        identityAccessRolodexCredentialsGetResult(response);
                    } else {
                        log("ERROR", "SubmitSignup");
                    }
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    log("ERROR", "identityAccessRolodexCredentialsGet-> The following error occured: "
                            + textStatus + errorThrown);
                }
            });
            
            function validateIdentityAccessRolodexCredentialsGet(response) {
                try {
                    var responseJSON = JSON.parse(response);
                    if (responseJSON.result.error !== undefined){
                        return true;
                    }
                    return true;
                } catch (e) {
                    return false;
                }
            }
            
        };
        
        var identityAccessLockboxUpdateResult = function(response) {
            log("identityAccessLockboxUpdateResult", response);
            var responseJSON = JSON.parse(response);
            var message = {
                    "result": {
                        "$domain": responseJSON.result.$domain,
                        "$appid": $appid,
                        "$id": responseJSON.result.$id,
                        "$handler": "identity",
                        "$method": "identity-access-lockbox-update",
                        "$timestamp": Math.floor(Date.now()/1000)
                      }
                    };
            postMessage(JSON.stringify(message), "*");
        };
        
        var identityAccessRolodexCredentialsGetResult = function(response) {
            log("identityAccessRolodexCredentialsGetResult", response);
            var responseJSON = JSON.parse(response);
            if (responseJSON.result.error) {
                return postMessage(JSON.stringify({
                    "result": {
                        "$domain": responseJSON.result.$domain,
                        "$appid": $appid,
                        "$id": responseJSON.result.$id,
                        "$handler": "identity",
                        "$method": "identity-access-rolodex-credentials-get",
                        // TODO: Don't sent timestamp here. Forward the one from server response.
                        "$timestamp": Math.floor(Date.now()/1000),                    
                        "error": responseJSON.result.error
                    }
                }), "*");
            }
            return postMessage(JSON.stringify({
                "result": {
                    "$domain": responseJSON.result.$domain,
                    "$appid": $appid,
                    "$id": responseJSON.result.$id,
                    "$handler": "identity",
                    "$method": "identity-access-rolodex-credentials-get",
                    // TODO: Don't sent timestamp here. Forward the one from server response.
                    "$timestamp": Math.floor(Date.now()/1000),
                    "rolodex": {
                        "serverToken": responseJSON.result.rolodex.serverToken
                    }
                }
            }), "*");
        };
        
        var hostedIdentitySecretSet12 = function(responseJSON){
    //        var part1 = generateSecretPart(generateId(), responseJSON.identity.accessToken);
    //        var part2 = generateSecretPart(generateId(), responseJSON.identity.accessSecret);
    //        var parts12 = xorEncode(part1, part2);
    //        var parts21 = xorEncode(part2, part1);
    //alert(parts12 + " " + part21);
    //        hostedIdentitySecretSet(responseJSON, part1, passwordServer1);
    //        hostedIdentitySecretSet(responseJSON, part1, passwordServer2);
        };
        
        var getHostingData = function(responseJSON, setSecretScenario) {
            log("getHostingData", responseJSON, setSecretScenario);
            var purpose = setSecretScenario === true ? 
                    "hosted-identity-secret-part-set" : "hosted-identity-secet-part-get";
            
            var reqString = JSON.stringify({
                "request": {
                    "$domain": responseJSON.result.$domain,
                    "$id": generateId(),
                    "$handler": "identity",
                    "$method": "hosting-data-get",
                    
                    "purpose": purpose
                }
            });
            log("ajax", "/api.php", reqString);
            $.ajax({
                url : "/api.php",
                type : "post",
                data : reqString,
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    log("getHostingData - Result", response, responsoJSON);
                    // handle response
                    response.identity = responseJSON.result.identity;
                    if (setSecretScenario) {
                        var secretPart1 = generateSecretPart(generateId(), responseJSON.identity.accessToken);
                        var secretPart2 = generateSecretPart(generateId(), responseJSON.identity.accessSecret);
                        hostedIdentitySecretSet(response, secretPart1, passwordServer1);
                        hostedIdentitySecretSet(response, secretPart2, passwordServer2);
                    } else {
                        hostedIdentitySecretGet(response, passwordServer1);
                        hostedIdentitySecretGet(response, passwordServer2);
                    }
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    log("ERROR", "getHostingData", textStatus);
                }
            });
        };
        
        var hostedIdentitySecretSet = function(responseJSON, secretPart, server) {
            log("hostedIdentitySecretSet", responseJSON, secretPart, server);
            var nonce = responseJSON.result.hostingData.nonce;
            var hostingProof = responseJSON.result.hostingData.hostingProof;
            var hostingProofExpires = responseJSON.result.hostingData.hostingProofExpires;
            var clientNonce = generateClientNonce();
            var uri = generateIdentityURI();
            var accessSecretProof = generateAccessSecretProof(
                    uri,
                    clientNonce,
                    responseJSON.identity.accessSecretExpires,
                    responseJSON.identity.accessToken,
                    "hosted-identity-secret-part-set",
                    responseJSON.identity.accessSecret
                );
            var accessSecretProofExpires = responseJSON.identity.accessSecretExpires;

            var reqString = JSON.stringify({
                "request": {
                    "$domain": responseJSON.result.$domain,
                    "$id": generateId(),
                    "$handler": "identity",
                    "$method": "hosted-identity-secret-part-set",
                    
                    "nonce": nonce,
                    "hostingProof": hostingProof,
                    "hostingProofExpires": hostingProofExpires,
                    "clientNonce": clientNonce,
                    "identity": {
                        "accessToken": responseJSON.identity.accessToken,
                        "accessSecretProof": accessSecretProof,
                        "accessSecretProofExpires": accessSecretProofExpires,
                        
                        "uri": uri,
                        "secretSalt": identity.salt,
                        "secretPart": secretPart
                    }
                }
            });
            log("ajax", "/api.php", reqString);
            $.ajax({
                url : server,
                type : "post",
                data : reqString,
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    // handle response
                    afterSecretSet(response);
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    log("ERROR", "hostedIdentitySecretSet", textStatus);
                }
            });
        };
            
        var afterSecretSet = function(data) {
            log("afterSecretSet", data);
            var dataJSON = JSON.parse(data);
            if (dataJSON.result.error == undefined){
                secretSetResults++;
            }
            if (secretSetResults === 2) {
                identityAccessCompleteNotify();
            }
        };
        
        var hostedIdentitySecretGet = function(data) {
            log("hostedIdentitySecretGet", data);
            // hosted-identity-secret-get scenario
            var loginDataString = JSON.stringify(loginData);
            log("ajax", "/api.php", loginDataString);
            $.ajax({
                url : "/api.php",
                type : "post",
                data : loginDataString,
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    // log a message to the console
                    log("DEBUG", "hostedIdentitySecretGet - on success");
                    // handle response
                    afterSecretGet(response);
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    // log the error to the console
                    log("ERROR", "login - on error" + textStatus);
                }
            });
            var afterSecretGet = function(response){
                if (identity.secretPart == undefined){
                    identity.secretPart = responseJSON.identity.secretPart;
                } else {
                    identity.secretPart = xorEncode(identity.secretPart, responseJSON.identity.secretPart);
                    identityAccessCompleteNotify();
                }
            }
        };
        
        return {
            getVersion : getVersion,
            init : init
        };
    };

    ////////////// OTHER STUFF ///////////////////

    // startsWith method definition
    if (typeof String.prototype.startsWith != 'function') {
        String.prototype.startsWith = function(str) {
            return this.indexOf(str) == 0;
        };
    }

    // /////////////////////////////////////////////////////////
    // generate methods
    // /////////////////////////////////////////////////////////

    // Generates secret.
    //
    // @param p1
    // @param p2
    //
    // @return secret
    function generateSecretPart(p1, p2) {
        var secret;
        var sha1 = CryptoJS.algo.SHA1.create();
        // add entropy
        sha1.update(p1);
        sha1.update(p2);
        secret = sha1.finalize();
        log("generateSecretPart", p1, p2, secret);
        return secret.toString();
    }
    
    
    // Generates secretAccessSecretProof
    // 
    // @param uri 
    // @param clientNonce 
    // @param accessSecretExpires 
    // @param accessToken 
    // @param purpose 
    // @param accessSecret 
    // 
    // @return accessSecretProof
    function generateAccessSecretProof(
            uri,
            clientNonce,
            accessSecretExpires,
            accessToken,
            purpose,
            accessSecret) {
        var message = 'identity-access-validate:' + uri + ':' + clientNonce + ':' + accessSecretExpires + ':' + accessToken + ':' + purpose;
        return hmac(message, accessSecret);
    }

    String.prototype.toHex = function() {
        var hex = '', tmp;
        for(var i=0; i<this.length; i++) {
            tmp = this.charCodeAt(i).toString(16)
            if (tmp.length == 1) {
                tmp = '0' + tmp;
            }
            hex += tmp
        }
        return hex;
    }

    String.prototype.xor = function(other)
    {
        var xor = "";
        for (var i = 0; i < this.length && i < other.length; ++i) {
            xor += String.fromCharCode(this.charCodeAt(i) ^ other.charCodeAt(i));
        }
        return xor;
    }
    function xorEncode(txt1, txt2) {
        var ord = [];
        var buf = "";
        for (z = 1; z <= 255; z++) {
            ord[String.fromCharCode(z)] = z;
        }
        for (j = z = 0; z < txt1.length; z++) {
            buf += String.fromCharCode(ord[txt1.substr(z, 1)] ^ ord[txt2.substr(j, 1)]);
            j = (j < txt2.length) ? j + 1 : 0;
        }
        return buf;
    }

    // Generates passwordStretched.
    //
    // @param identity
    // @param password
    // @param serverPasswordSalt
    // @return passwordStreched
    function generatePasswordStretched(identifier, password, serverPasswordSalt) {
        var passwordStretched = "password-hash:" + identifier + password
                + serverPasswordSalt;
        // key stretching
        for ( var i = 0; i < 128; i++) {
            passwordStretched = hash(passwordStretched);
        }
        return passwordStretched;
    }

    // Generates IdentitySecretSalt.
    //
    // @param clientToken
    // @param serverToken
    // @param clientLoginSecretHash
    // @param serverSalt
    // @return IdentitySecretSalt
    function generateIdentitySecretSalt(clientToken, serverToken,
            clientLoginSecretHash, serverSalt) {
        var secretSalt;
        var sha1 = CryptoJS.algo.SHA1.create();
        // add entropy
        sha1.update(clientToken);
        sha1.update(serverToken);
        sha1.update(clientLoginSecretHash);
        sha1.update(serverSalt);
        secretSalt = sha1.finalize();

        return secretSalt.toString();
    }

    // Generates serverLoginProof.
    //
    // @param serverMagicValue
    // @param passwordStretched
    // @param identifier
    // @param serverPasswordSalt
    // @param identitySecretSalt
    // @return serverLoginProof
    function generateServerLoginProof(serverMagicValue, 
                                      passwordStretched, 
                                      identifier,
                                      serverPasswordSalt,
                                      identitySecretSalt) {
        var serverLoginProofInnerHmac = hmac('password-hash:' + identifier +':' + base64(serverPasswordSalt), 
                                              passwordStretched);
        var identitySaltHash = hash('salt:' + identifier + ':' + base64(identitySecretSalt));
        return hash(serverMagicValue + ':' + serverLoginProofInnerHmac + ':' + identitySaltHash);
    }

    // Generates serverLoginFinalProof.
    //
    // @param serverMagicValue
    // @param serverLoginProof
    // @param serverNonce
    // @return serverLoginFinalProof
    function generateServerLoginFinalProof(serverMagicValue, 
                                           serverLoginProof,     
                                           serverNonce) {
        return hmac('final:' + serverLoginProof + ':' + serverNonce, serverMagicValue);
    }

    //Generates ClientAuthenticationToken.
    //
    // @param random1
    // @param random2
    // @param random3
    // @return ClientAuthenticationToken
    function generateClientAuthenticationToken(random1, 
            random2,
            random3) {
        var clientAuthenticationToken;
        var sha1 = CryptoJS.algo.SHA1.create();
        // add entropy
        sha1.update(random1 + "d");
        sha1.update(random2 + "d");
        sha1.update(random3 + "d");
        clientAuthenticationToken = sha1.finalize();

        return clientAuthenticationToken.toString();
    }

    // ADAPTER METHODS

    // base64 encoder.
    //
    // @param input
    // @return base64 encoded
    function base64(input) {
        return Base64.encode(input);
    }

    // SHA1 hash method.
    //
    // @param input
    // @return hash
    function hash(input) {
        return CryptoJS.SHA1(input).toString();
    }

    // HmacSHA1.
    //
    // @param message
    // @param key
    // @return HmacSHA1
    function hmac(message, key) {
        return CryptoJS.HmacSHA1(message, key).toString();
    }

    // AES encrypt method
    //
    // @param message
    // @param key
    // @param iv
    // @return encrypted
    function encrypt(message, key, iv) {
        if (iv) {
            return CryptoJS.AES.encrypt(message, key, {
                iv : iv
            }).toString();
        } else {
            return CryptoJS.AES.encrypt(message, key).toString();
        }

    }

    //AES decrypt method
    //
    // @param message
    // @param key
    // @param iv
    // @return encrypted
    function decrypt(message, key, iv) {
        if (iv) {
            return CryptoJS.AES.decrypt(message, key, {
                iv : iv
            }).toString(CryptoJS.enc.Utf8);
        } else {
            return CryptoJS.AES.decrypt(message, key).toString(CryptoJS.enc.Utf8);
        }
    }

})(window);
