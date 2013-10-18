
Dump Commands
=============

	mysqldump -u root -p --no-data --databases hf_password1_db > hf_password1_db.schema.sql
	mysqldump -u root -p --no-data --databases hf_password2_db > hf_password2_db.schema.sql
	mysqldump -u root -p --no-data --databases hf_service_db > hf_service_db.schema.sql
	mysqldump -u root -p --no-data --databases provider_db > provider_db.schema.sql


Import Commands
===============

	cat hf_password1_db.schema.sql | mysql -u root -p --no-data
	cat hf_password2_db.schema.sql | mysql -u root -p --no-data
	cat hf_service_db.schema.sql | mysql -u root -p --no-data
	cat provider_db.schema.sql | mysql -u root -p --no-data
