CREATE TABLE IF NOT EXISTS hormones_metadata (
	name VARCHAR(20) PRIMARY KEY,
	val VARCHAR(20)
);
INSERT INTO hormones_metadata (name, val) VALUES ('version', $version);

CREATE TABLE IF NOT EXISTS hormones_organs (
	organId TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
	name VARCHAR(64) UNIQUE
);
DELIMITER $$
CREATE TRIGGER organs_organId_limit BEFORE INSERT ON hormones_organs FOR EACH ROW
BEGIN
	IF (NEW.organId < 0 OR NEW.organId > 63) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'organ flag';
	END IF;
END
$$
DELIMITER ;

CREATE TABLE IF NOT EXISTS hormones_blood (
	hormoneId BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
	type VARCHAR(64) NOT NULL,
	receptors BIT(64) DEFAULT x'FFFFFFFFFFFFFFFF',
	creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	expiry TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	json TEXT
);
-- type: the hormone type, in the format "namespace.hormoneName", e.g. "hormones.moderation.Mute"

CREATE TABLE IF NOT EXISTS hormones_tissues (
	tissueId CHAR(32) PRIMARY KEY,
	organId TINYINT UNSIGNED NOT NULL,
	lastOnline TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	usedSlots SMALLINT UNSIGNED,
	maxSlots SMALLINT UNSIGNED,
	ip VARCHAR(68),
	port SMALLINT UNSIGNED,
	hormonesVersion SMALLINT,
	displayName VARCHAR(100),
	processId SMALLINT UNSIGNED,
	FOREIGN KEY (organId) REFERENCES hormones_organs(organId) ON UPDATE CASCADE ON DELETE RESTRICT
);
-- tissueId = server unique ID

CREATE TABLE IF NOT EXISTS hormones_mod_banlist (
	name VARCHAR(20) PRIMARY KEY,
	start TIMESTAMP NOT NULL,
	stop TIMESTAMP DEFAULT NULL,
	message VARCHAR(512) DEFAULT '',
	organs BIT(64) DEFAULT x'FFFFFFFFFFFFFFFF',
	doer VARCHAR(20)
);

CREATE TABLE IF NOT EXISTS hormones_netchat_channels (
	name VARCHAR(200) PRIMARY KEY,
	visible BIT(1) NOT NULL,
	invite BIT(1) NOT NULL, -- if true, one must be invited by former subs in the channel to subscribe
	passphrase VARCHAR(50) DEFAULT NULL,
	permission VARCHAR(200) DEFAULT NULL,
	defaultPerm TINYINT SIGNED NOT NULL DEFAULT 0 -- may be -1 (QUIET) on some channels
);
DELIMITER $$
CREATE TRIGGER netchat_channel_name BEFORE INSERT ON hormones_netchat_channels FOR EACH ROW
BEGIN
	IF NOT (NEW.name REGEXP '^[^\x07\x2C\s]{1,200}$') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'channel name';
	END IF;
END
$$

CREATE TABLE IF NOT EXISTS hormones_netchat_subs (
	channel VARCHAR(200),
	user VARCHAR(20),
	lastJoin TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	permLevel TINYINT SIGNED NOT NULL DEFAULT 0, -- ChanPermLevel::SUBSCRIBER
	subLevel TINYINT SIGNED NOT NULL DEFAULT 3, -- ChanSubLevel::IGNORING
	PRIMARY KEY (channel, user),
	FOREIGN KEY (channel) REFERENCES hormones_netchat_channels(name) ON DELETE CASCADE ON UPDATE CASCADE
);
-- When a user parts a channel, the row isn't immediately deleted. The subLevel is changed to SubLevel::IGNORING.
-- The row is only deleted if UNIX_TIMESTAMP() - UNIX_TIMESTAMP(lastJoin) > 3600 AND permLevel = channels.defaultPerm AND subLevel = 3, and
-- this deletion does not notify a third party.
-- TODO execute this deletion.
