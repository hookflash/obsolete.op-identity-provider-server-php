
install:
	if [ ! -d "dependencies" ]; then mkdir dependencies; fi
	# If installed in dev context we link the client library.
	if [ -d "../github.com+openpeer+op-identity-provider-client" ]; then \
		rm -Rf dependencies/op-identity-provider-client; \
		ln -s ../../github.com+openpeer+op-identity-provider-client dependencies/op-identity-provider-client; \
	fi

.PHONY: install
