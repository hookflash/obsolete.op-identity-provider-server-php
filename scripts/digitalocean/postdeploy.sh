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


echo "Installing cURL ..."
sudo apt-get -y install curl libcurl3 libcurl3-dev php5-curl

echo "Linking service into apache document root ..."
rm -f /var/www/html/$PIO_SERVICE_ID || true
ln -s $PIO_SERVICE_PATH/live/install /var/www/html/$PIO_SERVICE_ID
chown -Rf www-data:www-data $PIO_SERVICE_PATH/live/install

echo "Configuring apache ..."
a2enmod rewrite
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


echo "Provision MySQL Database ..."
node --eval '
const FS = require("fs");
const EXEC = require("child_process").exec;

function parseConfig(callback) {
	var mysqlConfig = FS.readFileSync("/etc/mysql/debian.cnf", "utf8");
	var config = {};
	var activeSection = null;
	mysqlConfig.split("\n").forEach(function(line) {
		var m = line.match(/^\[([^\]]+)\]$/);
		if (m) {
			activeSection = m[1];
			if (!config[activeSection]) config[activeSection] = {};
		} else {
			var m = line.match(/^(\S+)\s*=\s*(.+)$/);
			if (m) {
				config[activeSection][m[1]] = m[2];
			}
		}
	});
	return callback(null, config);
}

function provisionTable(config, callback) {

	function createDatabase(callback) {
		var command = "cat '$PIO_SERVICE_PATH'/sync/source/idprovider.com/schema/provider_db.schema.sql | mysql -u" + config.client.user + " -p" + config.client.password;
		console.log("Running command: " + command);
		return EXEC(command, function(err, stdout, stderr) {
			if (err) return callback(err);
			return callback(null);
		});
	}

	function checkIfExists(callback) {
		var command = "mysql -u" + config.client.user + " -p" + config.client.password + " -D provider_db -e \"SHOW TABLES\"";
		console.log("Running command: " + command);
		return EXEC(command, function(err, stdout, stderr) {
			if (err) {
				if (/Unknown database/.test(err.message)) {
					return callback(null, false);
				}
				return callback(err);
			}
			return callback(null, true);
		});
	}

	return checkIfExists(function(err, exists) {
		if (err) return callback(err);
		if (exists) return callback(null);
		return createDatabase(callback);
	});
}

function main(callback) {
	return parseConfig(function(err, config) {
		if (err) return callback(err);
		if (!FS.existsSync("/opt/data/config")) {
			FS.mkdirSync("/opt/data/config");
		}
		FS.writeFileSync("/opt/data/config/mysql.json", JSON.stringify(config.client));
		return provisionTable(config, callback);
	});
}

main(function(err) {
	if (err) {
		console.error(err.stack);
		process.exit(1);
	}
	process.exit(0);
});
'
