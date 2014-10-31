-- femto blog system
-- 
-- database install script for PostgreSQL
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
-- If creating databases is disabled for you, disable the following line
-- by prepending -- 
CREATE DATABASE Femto ENCODING 'UTF8';
-- Attention: Working with PostgreSQL, you need to reconnect to the new
-- created database in order to take the following commands take place in
-- the desired database.


-- TYPES

CREATE TYPE femtotypepostvisibility AS ENUM ('posted','hidden','draft');
CREATE TYPE femtotypecomvisibility AS ENUM ('visible','hidden','closed');
CREATE TYPE femtotypeboolean AS ENUM ('true','false');


-- TABLES

CREATE TABLE FemtoAuthor (
	authorid serial PRIMARY KEY NOT NULL,
	username varchar(16) UNIQUE NOT NULL,
	passHash varchar(255) NOT NULL,
	longname varchar(32) NOT NULL,
	accesslevel int NOT NULL,
	blocked femtotypeboolean NOT NULL DEFAULT 'false',
	created timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	lastLogin timestamp(0) DEFAULT '2000-01-01 00:00:00'
);

CREATE TABLE FemtoPost (
	postid serial PRIMARY KEY NOT NULL,
	visibility femtotypepostvisibility NOT NULL DEFAULT 'draft',
	created timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	title text NOT NULL,
	urltitle varchar(100) NOT NULL,
	content text NOT NULL,
	authorid integer NOT NULL,
	lang char(2) NOT NULL,
	langreference integer DEFAULT NULL,
	modified timestamp(0) NOT NULL,
	comvisibility femtotypecomvisibility NOT NULL DEFAULT 'visible',
	comcount integer NOT NULL DEFAULT '0',
	CONSTRAINT authoridfk FOREIGN KEY (authorid)
		REFERENCES femtoauthor (authorid)
		ON UPDATE CASCADE ON DELETE RESTRICT,
	CONSTRAINT langreffk FOREIGN KEY (langreference)
		REFERENCES femtopost (postid)
		ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE FemtoComment (
	commentid serial PRIMARY KEY NOT NULL,
	postid integer NOT NULL,
	created timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	longname varchar(32) NOT NULL,
	email varchar(64) NOT NULL,
	url text DEFAULT NULL,
	content text NOT NULL,
	lang char(2) NOT NULL,
	approved femtotypeboolean NOT NULL DEFAULT 'false',
	ip varchar(15) DEFAULT 'x.x.x.x',
	CONSTRAINT postidfk FOREIGN KEY (postid)
		REFERENCES femtopost (postid)
		ON UPDATE CASCADE ON DELETE CASCADE
);


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
		to_char(FemtoComment.created, 'YYYY&#8209;MM&#8209;DD HH24:MI:SS') AS created,
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
			THEN to_char(created, 'HH24:MI')
			ELSE to_char(created, 'YYYY-MM-DD HH24:MI')
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
		to_char(FemtoPost.created, 'YYYY-MM-DD') AS created,
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
		to_char(created, 'MM/YYYY') AS monthyear
	FROM
		FemtoPost
	WHERE
		visibility = 'posted';


-- Triggers and their functions

CREATE FUNCTION updatecomcount() RETURNS trigger AS
$BODY$
DECLARE
	pid integer;
BEGIN
	IF TG_OP = 'INSERT' THEN
		pid := NEW.postid;
	ELSIF TG_OP = 'DELETE' THEN
		pid := OLD.postid;
	END IF;
	UPDATE femtopost
	SET comcount = (
		SELECT COUNT(commentid)
		FROM femtocomment
		WHERE postid = pid)
	WHERE postid = pid;
	RETURN null;
END
$BODY$
LANGUAGE plpgsql VOLATILE NOT LEAKPROOF;

CREATE TRIGGER femtotadelcom
AFTER DELETE
ON femtocomment
FOR EACH ROW
EXECUTE PROCEDURE updatecomcount();

CREATE TRIGGER femtotainscom
AFTER INSERT
ON femtocomment
FOR EACH ROW
EXECUTE PROCEDURE updatecomcount();


-- User Privileges

-- CREATE USER femto WITH PASSWORD 'your_database_password';
-- CREATE USER femto_adm WITH PASSWORD 'another_database_password';
-- 
-- GRANT SELECT
-- ON FemtoPost, FemtoComment, FemtoAuthor, FemtoVPost, FemtoVComment, FemtoVPostoverview, FemtoVPostmetadata, FemtoVMailComment, FemtoVAdmComment, FemtoVAdmLogin
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
-- ON FemtoPost, FemtoAuthor
-- TO femto_adm;
-- 
-- GRANT EXECUTE
-- ON FUNCTION updatecomcount()
-- TO femto, femto_adm;
-- 
-- GRANT UPDATE (comcount)
-- ON FemtoPost
-- TO femto;
-- 
-- GRANT SELECT, UPDATE
-- ON SEQUENCE femtocomment_commentid_seq
-- TO femto, femto_adm;
-- 
-- GRANT SELECT, UPDATE
-- ON SEQUENCE femtopost_postid_seq, femtoauthor_authorid_seq
-- TO femto_adm;
