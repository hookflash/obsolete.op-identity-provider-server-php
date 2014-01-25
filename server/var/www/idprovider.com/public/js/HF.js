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

        log("##### INIT #####", window.location.href);

        var _version = '0.1';                   // The current version

        var identity = {};                      // identity
        var identityAccessStart;                // identityAccessStart notify
        var initData;                           // init data
        var imageBundle = {};                   // imageBundle (used for avatar upload)
        var $appid = null;
        var $identityProviderDomain;            // used for every request
        var serverMagicValue;                   // serverMagicValue
        var waitForNotifyResponseId;            // id of "identity-access-window" request
        var secretSetResults = 0;               //
        var secretGetResults = 0;               //
        var loginResponseJSON = null; 

        //  passwordServers
        var passwordServer1 = null;
        var passwordServer2 = null;

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
                $identityProviderDomain = initData.$identityProvider;

                passwordServer1 = initData.passwordServer1;
                passwordServer2 = initData.passwordServer2;

                log("INFO", "init bundle", initData);

                // Buffer logging calls until we have an `$appid` available.
                window.__LOGGER.setChannel(false);

                // reload scenario
                if (initData.identityServiceAuthenticationURL) {

                    log("##### Finish oAuth #####", window.location.href);

                    finishOAuthScenario(initData.identityServiceAuthenticationURL);
                } else {

                    log("##### Signal Init #####", window.location.href);

                    identityAccessWindowNotify(true, false);
                }
            } catch (err) {
                if (!$appid) {
                    window.__LOGGER.setChannel("identity-provider-js-all");
                }
                log("ERROR", "init", err.message, err.stack);
            }
        };
        
        // Global cross-domain message handler.
        window.onmessage = function(message) {
            if (!message.data) return;
            if (message.data === lastPostedMessage) return;

            try {
                var data = JSON.parse(message.data);

                log("window.onmessage", "data", data);

                if (data.notify) {

                    $appid = data.notify.$appid;
                    window.__LOGGER.setChannel("identity-provider-js-" + $appid);

                    if (data.notify.$method == "identity-access-start") {
                        // start login/sign up procedure
                        identityAccessStart = data.notify;

                        log("window.onmessage", "identityAccessStart", identityAccessStart);

                        if (identityAccessStart.identity.reloginKey !== undefined) {
                            //relogin
                            startRelogin();
                        } else {
                            startLogin();
                        }
                    }
                } else
                if (data.result) {

                    $appid = data.result.$appid;
                    window.__LOGGER.setChannel("identity-provider-js-" + $appid);

                    if (data.result.$method == 'identity-access-window') {

                        log("window.onmessage", "identity", identity);
                        log("window.onmessage", "waitForNotifyResponseId", waitForNotifyResponseId);

                        if (
                            data.result.$id === waitForNotifyResponseId &&
                            identity.redirectURL
                        ) {
                            return redirectToURL(identity.redirectURL);
                        }
                    }
                } else
                if (data.request) {

                    $appid = data.request.$appid;
                    window.__LOGGER.setChannel("identity-provider-js-" + $appid);

                    if (data.request.$method === "identity-access-lockbox-update") {
                        identityAccessLockboxUpdate(data);
                    } else
                    if (data.request.$method === "identity-access-rolodex-credentials-get") {
                        identityAccessRolodexCredentialsGet(data);
                    }
                }
            } catch (err) {
                if (!$appid) {
                    window.__LOGGER.setChannel("identity-provider-js-all");                    
                }
                log("window.onmessage", "message.data", message.data);
                log("ERROR", "window.onmessage", err.message, err.stack);
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
            localStorage.identityAccessStart = JSON.stringify(identityAccessStart);
            localStorage.$appid = $appid;
            log("set localStorage", {
                clientAuthenticationToken: localStorage.clientAuthenticationToken,
                identityAccessStart: localStorage.identityAccessStart,
                $appid: $appid
            });
            window.top.location = url;
        };

        var identityAccessWindowNotify = function(ready, visibility) {
            log("identityAccessWindowNotify", ready, visibility);
            var id = generateId();
            var readyMessage = {
                "request" : {
                    "$domain" : $identityProviderDomain,
                    // TODO: Document the fact that the `$appid` is set to `""` here because it is not yet known.
                    "$appid" : "",
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
            try {
                log("startLogin");
                setType(identityAccessStart);
                if (
                    identity.type === "email" ||
                    identity.type === "phone"
                ) {
                    throw new Error("Login for identity type '" + identity.type + "' not yet supported!");
                } else
                if (
                    identity.type === "facebook" ||
                    identity.type === "linkedin" ||
                    identity.type === "twitter"
                ) {
                    startLoginOauth();
                } else
                if (initData.ignoreBase) {
                    startLoginChoose();
                } else
                if (identity.type === "federated") {
                    startLoginFederated();
                } else {
                    throw new Error("Don't know how to proceed!");
                }
            } catch(err) {
                log("ERROR", "startLogin", err.message, err.stack);
            }
        };

        var showView = function (name) {
            $("#op-spinner").addClass("op-hidden");
            $('DIV[id^="op-"][id$="-view"]').addClass("op-hidden");
            if (!Array.isArray(name)) {
                name = [ name ];
            }
            if (name.indexOf("federated-login") !== -1 && initData.ignoreBase) {
                name.push("social-facebook");
            }
            name.forEach(function(name) {
                if (name === "loading") {
                    $("#op-spinner").removeClass("op-hidden");
                } else {
                    $('DIV[id^="op-"][id$="-view"]#op-' + name + '-view').removeClass("op-hidden");
                }
            });
        }


        function startLoginChoose() {
            log("startLoginChoose");

            log("startLoginChoose", "identity", identity);
            log("startLoginChoose", "identityAccessStart", identityAccessStart);

            if (
                identity.type === "facebook" &&
                identityAccessStart.identity.uri &&
                /^identity:\/\/facebook\.com\/.+$/.test(identityAccessStart.identity.uri)
            ) {
                log("startLoginChoose", "found full '" + identity.type + "' identity. Logging in right away.");

                identity.uri = identityAccessStart.identity.uri;

                showView("loading");
                startLoginOauth();

            } else {
                startLoginFederated();

                $("#op-social-facebook-button").click(function() {
                    log("startLoginChoose clicked social-facebook button");
                    identity.type = "facebook";
                    identity.uri = "identity://facebook/";
                    identity.identifier = "";
                    showView("loading");
                    startLoginOauth();
                });
            }
        }


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
            if (identityAccessStart.identity.type) {
                identity.type = identityAccessStart.identity.type;
                identity.uri = "identity://" + identity.type + "/";
                identity.identifier = identityAccessStart.identity.identifier || "";
                return;
            }
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
                log("WARN", "Unknown identity type", id);
            }
        };

        var startLoginFederated = function() {
            log("startLoginFederated");

            function showLoginError(message) {
                showView("federated-login");
                var elm = $("#op-federated-login-view DIV.op-error");
                elm.html(message);
                elm.removeClass("op-hidden");                
                setTimeout(function() {
                    elm.addClass("op-hidden");
                }, 5000);
                window.scrollTo(0, 0);
            }

            function showSignupError(message) {
                showView("federated-signup");
                var elm = $("#op-federated-signup-view DIV.op-error");
                elm.html(message);
                elm.removeClass("op-hidden");
                setTimeout(function() {
                    elm.addClass("op-hidden");
                }, 5000);
                window.scrollTo(0, 0);
            }


            showView("federated-login");

            $("#op-federated-signup-button").click(function() {
                log("user signup clicked");

                // read data from input fields
                identity.identifier = $("#" + initData.signup.id).val().toLowerCase();
                identity.password = $("#" + initData.signup.password).val();
                identity.displayName = $("#" + initData.signup.displayName).val();

                if (!identity.displayName) {
                    showSignupError("Please enter a Display Name!");
                    return;
                }
                if (!identity.identifier) {
                    showSignupError("Please enter a Username!");
                    return;
                }
                if (!identity.password) {
                    showSignupError("Please enter a Password!");
                    return;
                }

                showView("loading");

                getIdentitySalts(function() {

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
                            "$appid": $appid!==undefined ? $appid : '',
                            "$handler" : "identity-provider",
                            "$method" : "sign-up",
                            "identity" : {
                                "type" : identity.type,
                                "identifier" : identity.identifier,
                                "passwordHash" : identity.passwordStretched,
                                "secretSalt" : identity.secretSalt,
                                "serverPasswordSalt" : identity.serverPasswordSalt,
                                "uri" : identity.uri + identity.identifier,
                                "displayName" : identity.displayName,
                                "avatars" : {
                                    "avatar" : {
                                        "name" : imageBundle.filename,
                                        "url" : imageBundle.fileURL
                                    }
                                }
                            }
                        }
                    };

                    log("ajax", "/api.php", requestData);
                    $.ajax({
                        url : "/api.php",
                        type : "post",
                        data : JSON.stringify(requestData),
                        // callback handler that will be called on success
                        success : function(response, textStatus, jqXHR) {
                            log("ajax", "/api.php", "response", response);
                            try {
                                var result = JSON.parse(response).result;
                                if (result.error) {
                                    if (result.error.reason.$id == "403") {
                                        showSignupError("Username already exists!");
                                    } else {
                                        showSignupError(result.error.reason.message);
                                    }
                                } else {
                                    getServerNonce(loginFederated);
                                }
                            } catch(err) {
                                log("ERROR", "signup", err.message, err.stack);
                                showSignupError("Server response not valid. Please try again in a moment.");
                            }
                        },
                        // callback handler that will be called on error
                        error : function(jqXHR, textStatus, errorThrown) {
                            log("ERROR", "signup", "ajax", textStatus, errorThrown);
                            showSignupError("Error while contacting server. Please try again in a moment.");
                        }
                    });
                });
            });
            // signup upload avatar image handler
            $("#op-federated-signup-upload-button").click(function() {
                log("user uploading avatar");
                // validate response from avatar upload request.
                function validateResponseUploadSuccess(data) {
                    try {
                        return !!(data.result.file.name && data.result.file.url);
                    } catch (err) {
                        return false;
                    }
                }
                log("ajax", "/php/service/upload_avatar.php", "request");
                $.ajaxFileUpload({
                    url : '/php/service/upload_avatar.php',
                    secureuri : true,
                    fileElementId : 'file',
                    dataType : 'json',
                    success : function(data, status) {
                        log("ajax", "/php/service/upload_avatar.php", "response", status, data);
                        if (
                            data &&
                            data.result &&
                            data.result.file &&
                            data.result.file.name &&
                            data.result.file.url
                        ) {
                            imageBundle.filename = data.result.file.name;
                            imageBundle.fileURL = data.result.file.url;
                            // TODO: implement image width and height.
                        } else {
                            log("ERROR", 'fileupload', 'response not valid', status, data);
                            // TODO: Display message to user.
                        }
                    },
                    error: function(data, status, err) {
                        log("ERROR", 'fileupload', status, data, err);
                        // TODO: Display message to user.
                    }
                });
            });
            // login
            $("#op-federated-login-button").click(function() {
                log("user login clicked");
                // read data from input fields
                identity.identifier = $("#" + initData.login.id).val().toLowerCase();
                identity.password = $("#" + initData.login.password).val();
                log("identity.identifier", identity.identifier);
                log("identity.password.length", identity.password.length);

                if (!identity.identifier) {
                    showLoginError("Please enter a Username!");
                    return;
                }
                if (!identity.password) {
                    showLoginError("Please enter a Password!");
                    return;
                }

                showView("loading");

                getIdentitySalts(function() {
                    getServerNonce(function() {
                        loginFederated(function(err) {
                            log("loginFederated", "callback", err);
                            if (err) {
                                if (err.code === 403) {
                                    showLoginError("Incorrect login!");
                                } else {
                                    showLoginError(err.message);
                                }
                                return;
                            }
                        });
                    });
                });
            });
            identityAccessWindowNotify(true, true);

            $(document).ready(function() {
                if (/dev=true/.test(window.location.search)) {
                    var button = null;
                    button = $('<button>Display Login Feedback</button>');
                    button.click(function() {
                        showLoginError("login error");
                    });
                    $("DIV.op-view-label").append(button);
                    button = $('<button>Display Signup Feedback</button>');
                    button.click(function() {
                        showSignupError("signup error");
                    });
                    $("DIV.op-view-label").append(button);
                }
            });
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
        
        var loginFederated = function(loginResponseCallback) {
            log("loginFederated", loginResponseCallback);
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
            login({
                "request": {
                    "$domain": $identityProviderDomain,
                    "$appid": $appid!==undefined ? $appid : '',
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
            }, loginResponseCallback);
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
                    "$appid": $appid!==undefined ? $appid : '',
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
                    log("DEBUG", "validateOauthProviderAuthentication - response", response);
                    var responseJSON = JSON.parse(response);
                    log("DEBUG", "validateOauthProviderAuthentication - responseJSON", responseJSON);
                    var redirectURL = responseJSON.result.providerRedirectURL;
                    log("DEBUG", "validateOauthProviderAuthentication - redirectURL", redirectURL);
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
            log("identityAccessCompleteNotify", "identity", identity);
            var uri = generateIdentityURI(identity.type, identity.identifier);
            log("identityAccessCompleteNotify", "uri", uri);
            var lockboxkey = data.lockbox.key;
            
            // create reloginKey (only first time)
            var reloginKey = "";
            log("identityAccessCompleteNotify", "identityAccessStart", identityAccessStart);
            if (identityAccessStart.identity.reloginKey) {
                reloginKey = identityAccessStart.identity.reloginKey || "";
            } else
            if (identity.passwordStretched && identity.reloginEncryptionKey && data.identity.reloginKeyServerPart) {
                reloginKey = encrypt(identity.passwordStretched + "--" + data.identity.reloginKeyServerPart, identity.reloginEncryptionKey);
            }

            log("identityAccessCompleteNotify", "reloginKey", reloginKey);
            log("identityAccessCompleteNotify", "lockboxkey", lockboxkey);

            try {
                var message = null;
                if (lockboxkey) {
                    var iv = hash(identity.secretSalt);
                    log("identityAccessCompleteNotify", "iv", iv);
                    var key = decryptLockbox(lockboxkey, identity.passwordStretched, identity.identifier, iv);
                    log("identityAccessCompleteNotify", "key", key);
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

                log("identityAccessCompleteNotify", "message", message);

                return postMessage(JSON.stringify(message), "*");
            } catch(err) {
                log("ERROR", err.message, err.stack);
            }
        };
                
        var finishOAuthScenario = function(url) {
            try {
                log("finishOAuthScenario", url);
                // remove domain
                var params = url.split("?").pop();
                params = params.split("&").pop();
//                log("finishOAuthScenario", "params 1", params);
                // facebook fix (remove #...)
                params = params.split("#")[0];
//                log("finishOAuthScenario", "params 2", params);
                params = decodeURIComponent(params);

                // HACK fix for:
                // `["finishOAuthScenario","params 3 - to be JSON parsed","{\"reason\":{\"error\":\"Sign+in+failed+due+to+missing+parameter+values\"}}{\"result\":{\"loginState\":\"OAuthAuthenticationSucceeded\",\"identity\":{\"type\":\"facebook\",\"identifier\":\"100084075\"},\"serverAuthenticationToken\":\"3994743e949e5b7f2fcd4c0782135192568e5d6\"}}"]`
                var doubleJsonIndex = params.indexOf("}{");
                if (doubleJsonIndex > 0) {
                    params = params.substring(doubleJsonIndex + 1);
                }

                log("finishOAuthScenario", "params 3 - to be JSON parsed", params);
                var paramsJSON = JSON.parse(params);

                log("finishOAuthScenario", "paramsJSON", paramsJSON);

                log("get localStorage", {
                    clientAuthenticationToken: localStorage.clientAuthenticationToken,
                    identityAccessStart: localStorage.identityAccessStart,
                    $appid: localStorage.$appid
                });
                $appid = localStorage.$appid;
                if (!$appid) {
                    window.__LOGGER.setChannel("identity-provider-js-all");
                    log("finishOAuthScenario", "$appid", $appid);
                } else {
                    window.__LOGGER.setChannel("identity-provider-js-" + $appid);
                }

                var clientAuthenticationToken = localStorage.clientAuthenticationToken;
                identityAccessStart = JSON.parse(localStorage.identityAccessStart);
                setType(identityAccessStart);
                identity.type = paramsJSON.result.identity.type;
                identity.identifier = paramsJSON.result.identity.identifier;

                log("finishOAuthScenario", "identity", identity);

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
            } catch(err) {
                if (!$appid) {
                    window.__LOGGER.setChannel("identity-provider-js-all");
                }
                log("ERROR", "finishOAuthScenario", err.message, err.stack);
            }
        };

        var login = function(loginData, loginResponseCallback) {
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
                        loginResponseJSON = JSON.parse(response);
                        log("login", "loginResponseJSON", loginResponseJSON);
                        if (!loginResponseJSON.result) {
                            throw new Error("No 'result' property in response");
                        }
                        if (loginResponseJSON.result.error) {
                            var err = new Error(loginResponseJSON.result.error.reason.message);
                            err.code = parseInt(loginResponseJSON.result.error.reason.$id);
                            if (loginResponseCallback) {
                                loginResponseCallback(err);
                            }
                            throw err;
                        }
                        // pin validation scenario
                        if (loginResponseJSON.result.loginState === "PinValidationRequired") {
                            pinValidateStart();
                        } else
                        if (loginResponseJSON.result.loginState === "Succeeded") {
                            // login is valid
                            // OAuth
                            if (
                                identity.type == "facebook" ||
                                identity.type == "twitter" ||
                                identity.type == "linkedin"
                            ) {
                                if (!loginResponseJSON.result.lockbox.key) {
                                    // if first time seen identity
                                    getHostingData(loginResponseJSON, true);
                                } else {
                                    // if seen this before
                                    getHostingData(loginResponseJSON, false);
                                }
                            } else {
                                // all other scenarios
                                identityAccessCompleteNotify(loginResponseJSON.result);
                            }
                        }
                    } catch(err) {
                        log("ERROR", err.message, err.stack);
                    }
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    // log the error to the console
                    log("ERROR", "login - on error" + textStatus);
                }
            });
        };

        /**
         * Generates identity URI.
         * 
         * @param type
         * @param identifier
         */
        var generateIdentityURI = function(type, identifier) {
            log("generateIdentityURI", type, identifier);
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
                    
                        "nonce": data.request.nonce,
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

        var getHostingData = function(responseJSON, setSecretScenario) {
            log("getHostingData", responseJSON, setSecretScenario);
            var reqString = JSON.stringify({
                "request": {
                    "$domain": responseJSON.result.$domain,
                    "$id": generateId(),
                    "$handler": "identity",
                    "$method": "hosting-data-get",
                    "purpose": (setSecretScenario === true) ? 
                                   "hosted-identity-secret-part-set" :
                                   "hosted-identity-secret-part-get"
                }
            });
            log("ajax", "/api.php", reqString);
            $.ajax({
                url : "/api.php",
                type : "post",
                data : reqString,
                // callback handler that will be called on success
                success: function(response, textStatus, jqXHR) {
                    try {
                        log("ajax", "/api.php", "response", response);
                        response = JSON.parse(response);
                        response.identity = responseJSON.result.identity;
                        log("ajax", "/api.php", "success", "setSecretScenario", setSecretScenario);
                        if (setSecretScenario) {
                            log("ajax", "/api.php", "success", "response", response);
                            var secretPart1 = generateSecretPart(generateId(), response.identity.accessToken);
                            log("ajax", "/api.php", "success", "secretPart1", secretPart1);
                            var secretPart2 = generateSecretPart(generateId(), response.identity.accessSecret);
                            log("ajax", "/api.php", "success", "secretPart2", secretPart2);
                            hostedIdentitySecretSet(response, secretPart1, passwordServer1);
                            hostedIdentitySecretSet(response, secretPart2, passwordServer2);
                        } else {
                            hostedIdentitySecretGet(response, passwordServer1);
                            hostedIdentitySecretGet(response, passwordServer2);
                        }
                    } catch(err) {
                        log("ERROR", err.message, err.stack);
                    }
                },
                // callback handler that will be called on error
                error: function(jqXHR, textStatus, errorThrown) {
                    log("ERROR", "getHostingData", textStatus);
                }
            });
        };

        var hostedIdentitySecretSet = function(responseJSON, secretPart, server) {
            log("hostedIdentitySecretSet", responseJSON, secretPart, server);
            var nonce = responseJSON.result.hostingData.nonce;
            log("hostedIdentitySecretSet", "nonce", nonce);
            var hostingProof = responseJSON.result.hostingData.hostingProof;
            log("hostedIdentitySecretSet", "hostingProof", hostingProof);
            var hostingProofExpires = responseJSON.result.hostingData.hostingProofExpires;
            var clientNonce = generateId();
            log("hostedIdentitySecretSet", "identity", identity);
            var uri = generateIdentityURI(identity.type, identity.identifier);
            log("hostedIdentitySecretSet", "uri", uri);
            var accessSecretProof = generateAccessSecretProof(
                uri,
                clientNonce,
                responseJSON.identity.accessSecretExpires,
                responseJSON.identity.accessToken,
                "hosted-identity-secret-part-set",
                responseJSON.identity.accessSecret
            );
            log("hostedIdentitySecretSet", "accessSecretProof", accessSecretProof);
            var accessSecretProofExpires = responseJSON.identity.accessSecretExpires;
            log("hostedIdentitySecretSet", "accessSecretProofExpires", accessSecretProofExpires);
            // generate secretSalt
            var identitySecretSalt = generateSecretSaltForArgs([
                identity.identifier,
                nonce,
                clientNonce,
                generateId()
            ]);
            log("hostedIdentitySecretSet", "identitySecretSalt", identitySecretSalt);
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
                        "secretSalt": identitySecretSalt,
                        "secretPart": secretPart
                    }
                }
            });
            log("ajax", server, reqString);
            $.ajax({
                url : server,
                type : "post",
                data : reqString,
                dataType: "json",
                contentType: "application/json",
                // callback handler that will be called on success
                success: function(response, textStatus, jqXHR) {
                    log("ajax", server, "response", response);
                    return afterSecretSet(response, secretPart);
                },
                // callback handler that will be called on error
                error: function(jqXHR, textStatus, errorThrown) {
                    log("ERROR", "hostedIdentitySecretSet", textStatus, errorThrown, {
                        readyState: jqXHR.readyState,
                        status: jqXHR.status,
                        statusText: jqXHR.statusText,
                        responseText: jqXHR.responseText
                    });
                }
            });
        };

        var afterSecretSet = function(dataJSON, secretPart) {
            log("afterSecretSet", dataJSON, secretPart);
            try {
                log("afterSecretSet", identity);
                if (identity.secretPartSet === undefined) {
                    log("afterSecretSet", "if branch", secretSetResults);
                    identity.secretPartSet = secretPart;
                } else {
                    log("afterSecretSet", "else branch", secretSetResults);
                    identity.passwordStretched = xorEncode(identity.secretPartSet, secretPart);
                }
                log("afterSecretSet", secretSetResults, identity);
                
                if (!dataJSON.result.error) {
                    secretSetResults++;
                }
                if (secretSetResults === 2) {
                    log("afterSecreySet", "Will enter identityAccessCompleteNotify with loginResponseJSON:", loginResponseJSON);
                    identityAccessCompleteNotify(loginResponseJSON.result);
                }
            } catch(err) {
                log("ERROR", err.message, err.stack);
            }
        };

        var hostedIdentitySecretGet = function(data, server) {
            log("hostedIdentitySecretSet", data, server);
            var nonce = data.result.hostingData.nonce;
            log("hostedIdentitySecretSet", "nonce", nonce);
            var hostingProof = data.result.hostingData.hostingProof;
            log("hostedIdentitySecretSet", "hostingProof", hostingProof);
            var hostingProofExpires = data.result.hostingData.hostingProofExpires;
            var clientNonce = generateId();
            log("hostedIdentitySecretSet", "identity", identity);
            var uri = generateIdentityURI(identity.type, identity.identifier);
            log("hostedIdentitySecretSet", "uri", uri);
            var accessSecretProof = generateAccessSecretProof(
                uri,
                clientNonce,
                data.identity.accessSecretExpires,
                data.identity.accessToken,
                "hosted-identity-secret-part-get",
                data.identity.accessSecret
            );
            log("hostedIdentitySecretSet", "accessSecretProof", accessSecretProof);
            var accessSecretProofExpires = data.identity.accessSecretExpires;
            log("hostedIdentitySecretSet", "accessSecretProofExpires", accessSecretProofExpires);
            // hosted-identity-secret-get scenario
            var reqString = JSON.stringify({
                "request": {
                    "$domain": data.result.$domain,
                    "$id": generateId(),
                    "$handler": "identity",
                    "$method": "hosted-identity-secret-part-get",
                    "nonce": nonce,
                    "hostingProof": hostingProof,
                    "hostingProofExpires": hostingProofExpires,
                    "clientNonce": clientNonce,
                    "identity": {
                        "accessToken": data.identity.accessToken,
                        "accessSecretProof": accessSecretProof,
                        "accessSecretProofExpires": accessSecretProofExpires,                        
                        "uri": uri
                    }
                }
            });
            log("ajax", server, reqString);
            $.ajax({
                url : server,
                type : "post",
                data : reqString,
                dataType: "json",
                contentType: "application/json",
                // callback handler that will be called on success
                success : function(response, textStatus, jqXHR) {
                    log("ajax", "/api.php", "response", response);
                    // handle response
                    afterSecretGet(response);
                },
                // callback handler that will be called on error
                error : function(jqXHR, textStatus, errorThrown) {
                    log("ERROR", "hostedIdentitySecretGet", textStatus, errorThrown, {
                        readyState: jqXHR.readyState,
                        status: jqXHR.status,
                        statusText: jqXHR.statusText,
                        responseText: jqXHR.responseText
                    });
                }
            });
            var afterSecretGet = function(response) {
                try {
                    log("afterSecretGet", response);
                    log("afterSecretGet", "identity", identity);
                    log("afterSecretGet", "loginResponseJSON", loginResponseJSON);
                    if (response.result.error) {
                        log("ERROR", response.result.error);
                        return;
                    }
                    if (!identity.secretPart) {
                        identity.secretPart = response.result.identity.secretPart;
                    } else {
                        identity.passwordStretched = xorEncode(identity.secretPart, response.result.identity.secretPart);
                        delete identity.secretPart;
                        identity.secretSalt = response.result.identity.secretSalt;
                    }
                    secretGetResults++;
                    if (secretGetResults === 2) {
                        log("afterSecretGet", "identity after", identity);
                        identityAccessCompleteNotify(loginResponseJSON.result);
                    }
                } catch(err) {
                    log("ERROR", err.message, err.stack);
                }
            }
        };
        
        return {
            getVersion : getVersion,
            init : init,
            showView: showView
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
        log("generateSecretPart", p1, p2);
        var sha1 = CryptoJS.algo.SHA1.create();
        // add entropy
        sha1.update(p1);
        sha1.update(p2);
        var secret = sha1.finalize();
        log("generateSecretPart", "secret", secret);
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
        // @see http://en.wikipedia.org/wiki/Key_stretching
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
    function generateIdentitySecretSalt(clientToken, serverToken, clientLoginSecretHash, serverSalt) {
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

    function generateSecretSaltForArgs(args) {
        log("generateSecretSaltForArgs", args);
        var sha1 = CryptoJS.algo.SHA1.create();
        args.forEach(function(arg) {
            sha1.update(arg);
        });
        return sha1.finalize().toString();
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
