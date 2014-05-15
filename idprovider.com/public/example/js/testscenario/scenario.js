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

//Set global data 
function setData() {
    logIt(scenario + " -started");
    var scenario = document.getElementById('scenario').value;
    var mode = document.getElementById('mode').value;

    var result = $("#result").val();
    notifyBundle = JSON.parse(result);

    refreshDataMonitor();

    logIt(scenario + " -data parsed");

    // continue
    openOuter(mode);
}

//Open iframe or new window.
//@param mode - "popup" or "iframe"
function openOuter(mode) {
    // send data for outer frame
    if (typeof (Storage) !== "undefined") {
        localStorage.notifyBundle = JSON.stringify(notifyBundle);
    } else {
        // No localStorage support.
        logIt('FATAL ERROR - No localStorage support.');
    }
    if (mode == 'iframe') {
        // open new window
        alert("Opening popup.\n Finish page = " + postLoginRedirectURL);
        window.open(outerURL);
    }else if(mode == 'popup'){
        // open outer frame as iframe
        alert("Opening new window.\n Finish page = " + postLoginRedirectURL);
        window.open(outerURL);
        var outerIframe = document.createElement('iframe');
        outerIframe.src = "https://" + outerURL;

        //append to outerIframe div (hidden by default)
        var iframeParent = document.getElementsById('outerIframe');
        iframeParent.appendChild(outerIframe);
    }
}
