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
CREATE TRIGGER organ_flag_limit BEFORE INSERT ON hormones_oragns FOR EACH ROW
BEGIN
	IF NEW.flag < 0 OR NEW.flag > 63
	THEN
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
	json TEXT
);
-- type: the hormone type, in the format "namespace.hormoneName", e.g. "hormones.moderation.Mute"

CREATE TABLE IF NOT EXISTS hormones_tissues (
	tissueId CHAR(32) PRIMARY KEY,
	organId TINYINT NOT NULL,
	lastOnline TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	usedSlots SMALLINT UNSIGNED,
	maxSlots SMALLINT UNSIGNED,
	ip VARCHAR(68),
	port SMALLINT UNSIGNED,
	hormonesVersion SMALLINT,
	displayName VARCHAR(100),
	processId SMALLINT UNSIGNED,
	FOREIGN KEY (organId) REFERENCES hormones_oragns(organId) ON UPDATE CASCADE ON DELETE RESTRICT
);
-- tissueId = server unique ID
