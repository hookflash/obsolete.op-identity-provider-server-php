
Dump Commands
=============

	mysqldump -u root -p --no-data --databases provider_db > provider_db.schema.sql


Import Commands
===============

	cat provider_db.schema.sql | mysql -u root -p --no-data
