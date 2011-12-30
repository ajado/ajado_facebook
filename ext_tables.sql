#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_ajadofacebook_id tinytext,
	tx_ajadofacebook_link tinytext,
	tx_ajadofacebook_gender tinytext,
	tx_ajadofacebook_email tinytext,
    tx_ajadofacebook_locale varchar(5) DEFAULT '' NOT NULL,
    tx_ajadofacebook_updated_time varchar(25) DEFAULT '' NOT NULL
);