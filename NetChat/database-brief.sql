CREATE TABLE IF NOT EXISTS hormones_netchat_channels (
	name VARCHAR(200) PRIMARY KEY,
	visible BIT(1) NOT NULL,
	invite BIT(1) NOT NULL, -- if true, one must be invited by former subs in the channel to subscribe
	passphrase VARCHAR(100) DEFAULT NULL,
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
