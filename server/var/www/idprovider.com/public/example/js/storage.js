
function saveInStorage(data) {
	// localStorage support!
	if (typeof (Storage) !== "undefined") {
		for (var key in data) {
			localStorage.key = key.value();
		}
	} else {
		// No localStorage support.
	}
}

function readDataFromStorage() {
	// localStorage support!
	if (typeof (Storage) !== "undefined") {
		clientToken = localStorage.clientToken;
		serverToken = localStorage.serverToken;
		browserVisibility = localStorage.browserVisibility;
		postLoginRedirectURL = localStorage.postLoginRedirectURL;
		clientLoginSecret = localStorage.clientLoginSecret;
		identityReloginAccessKey = localStorage.identityReloginAccessKey;
	} else {
		// No localStorage support.
	}
}
