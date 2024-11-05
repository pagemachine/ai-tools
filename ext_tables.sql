CREATE TABLE tx_aitools_domain_model_prompt (
	prompt text,
	description varchar(255),
	type varchar(255) DEFAULT '' NOT NULL,
	default tinyint(1) DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_aitools_domain_model_server (
	title varchar(255),
	type varchar(255),

	apikey varchar(255),

	endpoint varchar(255),
	formality varchar(255),

	username varchar(255),
	password varchar(255),
	imageUrl varchar(255),
	translationUrl varchar(255),
);
