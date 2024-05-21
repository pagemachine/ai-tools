CREATE TABLE tx_aitools_domain_model_prompt (
	prompt text,
	description varchar(255),
	type varchar(255) DEFAULT '' NOT NULL,
	default tinyint(1) DEFAULT '0' NOT NULL,
);
