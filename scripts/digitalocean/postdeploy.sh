#!/bin/bash -e

#######################################################
# Minimal deployed PIO service structure
#######################################################
CONFIGURED_DIR=$(date +%s%N)

if [ ! -d "configured/$CONFIGURED_DIR" ]; then
	mkdir -p configured/$CONFIGURED_DIR
fi
cp -Rf sync/scripts configured/$CONFIGURED_DIR/scripts
cp -Rf sync/source configured/$CONFIGURED_DIR/source
cp -Rf sync/source configured/$CONFIGURED_DIR/install
cp sync/.pio.json configured/$CONFIGURED_DIR

rm -f live || true
ln -s configured/$CONFIGURED_DIR live

sudo chmod -Rf ug+x $PIO_SCRIPTS_PATH
#######################################################


echo "Linking service into apache document root ..."
rm -f /var/www/html/$PIO_SERVICE_ID || true
ln -s $PIO_SERVICE_PATH/live/install /var/www/html/$PIO_SERVICE_ID
chown -Rf www-data:www-data $PIO_SERVICE_PATH/live/install

echo "Configuring apache ..."
rm -f /etc/apache2/sites-enabled/000-default.conf || true
cp -f $PIO_SCRIPTS_PATH/apache.conf /etc/apache2/sites-enabled/$PIO_SERVICE_ID.conf

echo "Restarting apache after syntax check..."
apachectl -t -D DUMP_VHOSTS
echo "Apache processes before:"
ps axuww | grep apache
apachectl graceful
sleep 1
echo "Apache processes after:"
ps axuww | grep apache
