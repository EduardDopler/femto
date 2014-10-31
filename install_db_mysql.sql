-- femto blog system
-- 
-- database install script for MySQL
-- version 0.1
-- by Eduard Dopler
-- contact@eduard-dopler.de
-- Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
--
-- 
-- NOTE the user privileges part at the bottom. Insert your password there
-- and uncomment it.


-- DATABASE
--
-- If creating databases is disabled for you, disable the following lines
-- by prepending -- 
CREATE DATABASE  IF NOT EXISTS femto /*!40100 DEFAULT CHARACTER SET utf8 */;
USE femto;
/*!40101 SET NAMES utf8 */;


-- TABLES

CREATE TABLE FemtoAuthor (
	authorid SERIAL PRIMARY KEY NOT NULL,
	username VARCHAR(16) UNIQUE NOT NULL,
	passHash VARCHAR(255) NOT NULL,
	longname VARCHAR(32) NOT NULL,
	accesslevel INTEGER NOT NULL,
	blocked ENUM('true','false') NOT NULL DEFAULT 'false',
	created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	lastlogin TIMESTAMP DEFAULT '2000-01-01 00:00:00'
) ENGINE=INNODB;

CREATE TABLE FemtoPost (
	postid SERIAL PRIMARY KEY NOT NULL,
	visibility ENUM('posted','hidden','draft') NOT NULL DEFAULT 'draft',
	created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	title TEXT NOT NULL,
	urltitle VARCHAR(100) NOT NULL,
	content LONGTEXT NOT NULL,
	authorid BIGINT UNSIGNED NOT NULL,
	lang CHAR(2) NOT NULL,
	langreference BIGINT UNSIGNED DEFAULT NULL,
	modified TIMESTAMP NOT NULL,
	comvisibility ENUM('visible','hidden','closed') NOT NULL DEFAULT 'visible',
	comcount INTEGER NOT NULL DEFAULT '0',
	KEY authorid (authorid),
	KEY langreference (langreference),
	CONSTRAINT authoridfk FOREIGN KEY (authorid)
		REFERENCES FemtoAuthor (authorid)
		ON UPDATE CASCADE ON DELETE RESTRICT,
	CONSTRAINT langreffk FOREIGN KEY (langreference)
		REFERENCES FemtoPost (postid)
		ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=INNODB;

CREATE TABLE FemtoComment (
	commentid SERIAL PRIMARY KEY NOT NULL,
	postid BIGINT UNSIGNED NOT NULL,
	created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	longname VARCHAR(32) NOT NULL,
	email VARCHAR(64) NOT NULL,
	url TINYTEXT DEFAULT NULL,
	content TEXT NOT NULL,
	lang CHAR(2) NOT NULL,
	approved ENUM('true','false') DEFAULT 'false',
	ip VARCHAR(15) DEFAULT 'x.x.x.x',
	KEY postid (postid),
	CONSTRAINT postidfk FOREIGN KEY (postid)
		REFERENCES FemtoPost (postid)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=INNODB;


-- Add first administrator
INSERT INTO FemtoAuthor
	(username, passhash, longname, accesslevel)
	VALUES
	('admin', '$2y$10$sW5aULwen11VS/18rU1HYetTfyLBbHp/0W9iFI8Lq7VvVblISuvEO', 'Administrator', 1);


-- VIEWS

CREATE VIEW FemtoVAdmLogin AS
	SELECT
		authorid,
		username,
		passhash,
		longname,
		accesslevel,
		lastlogin
	FROM
		FemtoAuthor
	WHERE
		blocked = 'false';

CREATE VIEW FemtoVAdmComment AS
	SELECT 
		FemtoComment.commentid,
		FemtoComment.postid,
		REPLACE(FemtoComment.created, '-', '&#8209;') AS created,
		FemtoComment.longname,
		FemtoComment.email,
		FemtoComment.url,
		FemtoComment.content,
		FemtoComment.lang,
		FemtoComment.approved,
		FemtoComment.ip,
		FemtoPost.title,
		FemtoPost.authorid
	FROM
		FemtoComment
		JOIN FemtoPost ON FemtoComment.postid = FemtoPost.postid;

CREATE VIEW FemtoVComment AS
	SELECT 
		commentid,
		postid,
        CASE
            WHEN CAST(NOW() AS DATE) = CAST(created AS DATE)
            THEN DATE_FORMAT(created, '%H:%i')
            ELSE created
        END AS created,
		longname,
		url,
		content,
		lang,
		approved
	FROM
		FemtoComment;

CREATE VIEW FemtoVMailComment AS
	SELECT 
		FemtoComment.commentid,
		FemtoComment.postid,
		FemtoComment.created,
		FemtoComment.longname,
		FemtoComment.email,
		FemtoComment.url,
		FemtoComment.content,
		FemtoComment.ip,
		FemtoPost.title
	FROM
		FemtoComment
		JOIN FemtoPost ON FemtoComment.postid = FemtoPost.postid;

CREATE VIEW FemtoVPost AS
	SELECT 
        FemtoPost.postid,
        DATE_FORMAT(FemtoPost.created, '%Y-%m-%d') AS created,
        FemtoPost.title,
        FemtoPost.urltitle,
        FemtoPost.content,
        FemtoPost.lang,
        FemtoPost.langreference,
        FemtoPost.comvisibility,
        FemtoPost.comcount,
        FemtoAuthor.longname AS author
    FROM
        FemtoPost
        JOIN FemtoAuthor ON FemtoPost.authorid = FemtoAuthor.authorid
    WHERE
        FemtoPost.visibility = 'posted';

CREATE VIEW FemtoVPostmetadata AS
	SELECT 
		FemtoPost.postid,
		FemtoPost.created,
		FemtoPost.title,
		FemtoPost.urltitle,
		FemtoPost.content,
		FemtoPost.lang,
		FemtoPost.modified,
		FemtoAuthor.longname AS author
	FROM
		FemtoPost
		JOIN FemtoAuthor ON FemtoPost.authorid = FemtoAuthor.authorid
	WHERE
		FemtoPost.visibility = 'posted';

CREATE VIEW FemtoVPostoverview AS
	SELECT 
		postid,
		title,
		urltitle,
		lang,
		DATE_FORMAT(created, '%m/%Y') AS monthyear
	FROM
		FemtoPost
	WHERE
		visibility = 'posted';


-- Triggers and its functions

DELIMITER $$
CREATE TRIGGER FemtoTADelCom
AFTER DELETE ON FemtoComment
FOR EACH ROW
BEGIN
	UPDATE FemtoPost
	SET comcount = (
		SELECT COUNT(commentid)
		FROM FemtoComment
		WHERE postid = OLD.postid)
	WHERE postid = OLD.postid;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER FemtoTAInsCom
AFTER INSERT ON FemtoComment
FOR EACH ROW
BEGIN
	UPDATE FemtoPost
	SET comcount = (
		SELECT COUNT(commentid)
		FROM FemtoComment
		WHERE postid = NEW.postid)
	WHERE postid = NEW.postid;
END$$
DELIMITER ;


-- User Privileges

-- CREATE USER femto IDENTIFIED BY 'your_database_password';
-- CREATE USER femto_adm IDENTIFIED BY 'another_database_password';
-- 
-- GRANT SELECT
-- ON *
-- TO femto, femto_adm;
-- 
-- GRANT UPDATE, INSERT
-- ON FemtoComment
-- TO femto;
-- 
-- GRANT UPDATE, INSERT, DELETE
-- ON FemtoComment
-- TO femto, femto_adm;
-- 
-- GRANT UPDATE, INSERT, DELETE
-- ON FemtoPost
-- TO femto_adm;
-- 
-- GRANT UPDATE, INSERT, DELETE
-- ON FemtoAuthor
-- TO femto_adm;
-- 
-- GRANT UPDATE (comcount)
-- ON FemtoPost
-- TO femto;
