
install:
	if [ ! -d "dependencies" ]; then mkdir dependencies; fi
	# If installed in dev context we link the client library.
	if [ -d "../github.com+openpeer+op-identity-provider-client" ]; then \
		rm -Rf dependencies/op-identity-provider-client; \
		ln -s ../../github.com+openpeer+op-identity-provider-client dependencies/op-identity-provider-client; \
	fi
	# NOTE: We keep everything composer installes in the repository now.
	#touch debug.log; chmod 777 debug.log
	#php composer.phar install

.PHONY: install
