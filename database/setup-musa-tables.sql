SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `musaMusicSongsolos`;
DROP TABLE IF EXISTS `musaGenderTypes`;
DROP TABLE IF EXISTS `musaSongsolos`;
DROP TABLE IF EXISTS `musaMusic`;
DROP TABLE IF EXISTS `musaSongParts`;
DROP TABLE IF EXISTS `musaCountries`;
DROP TABLE IF EXISTS `musaPersons`;
DROP TABLE IF EXISTS `musaMusicHolidays`;
DROP TABLE IF EXISTS `musaMusicCategories`;
DROP TABLE IF EXISTS `musaStorages`;
DROP TABLE IF EXISTS `musaMusicArrangers`;
DROP TABLE IF EXISTS `musaMusicLanguages`;
DROP TABLE IF EXISTS `musaHolidays`;
DROP TABLE IF EXISTS `musaCategories`;
DROP TABLE IF EXISTS `musaMusicThemes`;
DROP TABLE IF EXISTS `musaThemes`;
DROP TABLE IF EXISTS `musaMusicAuthors`;
DROP TABLE IF EXISTS `musaInstruments`;
DROP TABLE IF EXISTS `musaMusicInstruments`;
DROP TABLE IF EXISTS `musaLanguages`;
DROP TABLE IF EXISTS `musaMusicComposers`;

DROP TABLE IF EXISTS `musaTokens`;
DROP TABLE IF EXISTS `musaUsers`;
DROP TABLE IF EXISTS `musaOrgs`;
DROP TABLE IF EXISTS `musaRoleTypes`;
DROP TABLE IF EXISTS `musaUserStatus`;
    
CREATE TABLE IF NOT EXISTS `musaRoleTypes`
(
    `role_code`   varchar(45) NOT NULL ,
    `role_name`   varchar(45) NOT NULL ,
    `permissions` text NOT NULL ,
PRIMARY KEY (`role_code`)
);
INSERT musaRoleTypes (role_code, role_name, permissions) VALUES ('ROOT','Root','root');
INSERT musaRoleTypes (role_code, role_name, permissions) VALUES ('SUPER','Superadmin','super');
INSERT musaRoleTypes (role_code, role_name, permissions) VALUES ('ADMIN','Administratör','admin');
INSERT musaRoleTypes (role_code, role_name, permissions) VALUES ('EDITOR','Editor','editor');
INSERT musaRoleTypes (role_code, role_name, permissions) VALUES ('USER','Användare','user');


CREATE TABLE IF NOT EXISTS `musaUserStatus`
(
 `status_code` varchar(45) NOT NULL ,
 `status_name` varchar(45) NOT NULL ,
PRIMARY KEY (`status_code`)
);
INSERT musaUserStatus (status_code, status_name) VALUES ('INVITED','Inbjuden');
INSERT musaUserStatus (status_code, status_name) VALUES ('NORMAL','Normal');
INSERT musaUserStatus (status_code, status_name) VALUES ('DISABLED','Avstängd');
INSERT musaUserStatus (status_code, status_name) VALUES ('DELETED','Raderad');

CREATE TABLE IF NOT EXISTS `musaOrgs`
(
    `org_id` int(11) NOT NULL AUTO_INCREMENT,
    `org_name` varchar(200) NOT NULL ,
    `org_info` text  DEFAULT NULL ,
    `org_created` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`org_id`)
);
INSERT musaOrgs (org_id, org_name) VALUES (1,'Administration');
INSERT musaOrgs (org_id, org_name) VALUES (2,'Testkyrkan');

DROP TABLE IF EXISTS `musaUsers`;
CREATE TABLE IF NOT EXISTS `musaUsers` (
    `user_id` int(11) NOT NULL AUTO_INCREMENT,
    `org_id`     int(11) NOT NULL ,
    `name`       varchar(100) NOT NULL ,
    `title`      varchar(100) DEFAULT NULL ,
    `email`      varchar(100) DEFAULT NULL ,
    `phone`      varchar(100) DEFAULT NULL ,
    `role`       varchar(100) DEFAULT NULL ,
    `show`       bool NOT NULL DEFAULT true,
    `email_verified` varchar(45) DEFAULT NULL,
    `password` varchar(100) DEFAULT NULL,
    `status_code` varchar(45) DEFAULT NULL,
    `role_code` varchar(45) DEFAULT NULL,
    `last_login` datetime DEFAULT NULL,
    `user_created` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `email_UNIQUE` (`email`),
    KEY `status_idx` (`status_code`),
    KEY `role_code_idx` (`role_code`),
    KEY `org_id_idx` (`org_id`),
    CONSTRAINT `org_id` FOREIGN KEY (`org_id`) REFERENCES `musaOrgs` (`org_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
    CONSTRAINT `role_code` FOREIGN KEY (`role_code`) REFERENCES `musaRoleTypes` (`role_code`) ON DELETE NO ACTION ON UPDATE CASCADE,
    CONSTRAINT `status_code` FOREIGN KEY (`status_code`) REFERENCES `musaUserStatus` (`status_code`) ON DELETE NO ACTION ON UPDATE CASCADE
  );
INSERT musaUsers (org_id, name, email, password, role_code) VALUES (1,'Thomas','thomas@tclarsson.se','$2y$10$EwlLs6xsjQLwQIFTlTOak.oknzEB/1Ja0VvYgoExDVTcskOHHm1mu','ROOT');
INSERT musaUsers (org_id, name, email, password, role_code) VALUES (1,'Erik','erblom@gmail.com','$2y$10$EwlLs6xsjQLwQIFTlTOak.oknzEB/1Ja0VvYgoExDVTcskOHHm1mu','ROOT');
INSERT musaUsers (org_id, name, email, password, role_code) VALUES (2,'Adminson','test1@tclarsson.se','$2y$10$EwlLs6xsjQLwQIFTlTOak.oknzEB/1Ja0VvYgoExDVTcskOHHm1mu','ADMIN');
INSERT musaUsers (org_id, name, email, password, role_code) VALUES (2,'Editson','test2@tclarsson.se','$2y$10$EwlLs6xsjQLwQIFTlTOak.oknzEB/1Ja0VvYgoExDVTcskOHHm1mu','EDITOR');
INSERT musaUsers (org_id, name, email, password, role_code) VALUES (2,'Testson','test3@tclarsson.se','$2y$10$EwlLs6xsjQLwQIFTlTOak.oknzEB/1Ja0VvYgoExDVTcskOHHm1mu','USER');

CREATE TABLE IF NOT EXISTS `musaTokens` (
    `token_id` int(11) NOT NULL AUTO_INCREMENT,
    `token` varchar(100) NOT NULL,
    `user_id` int(11) NOT NULL,
    `expiry_date` datetime NOT NULL,
    `token_created` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`token_id`),
    KEY `FK_21` (`user_id`),
    CONSTRAINT `FK_19` FOREIGN KEY (`user_id`) REFERENCES `musaUsers` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
    );
  

CREATE TABLE IF NOT EXISTS `musaMusic`
(
 `music_id`      integer NOT NULL AUTO_INCREMENT,
 `org_id`        integer NOT NULL ,
 `storage_id`    integer NULL ,
 `choir_parts`   integer NULL ,
 `solo_parts`    integer NULL ,
 `titel`         varchar(100) NOT NULL ,
 `subtitle`      varchar(100) NULL ,
 `yearOfComp`    year NULL ,
 `movements`     integer NULL ,
 `notes`         text NULL ,
 `serial_number` varchar(45) NULL ,
 `publisher`     varchar(200) NULL ,
 `identifier`    varchar(200) NULL ,

PRIMARY KEY (`music_id`),
KEY `FK_296` (`solo_parts`),
CONSTRAINT `FK_294` FOREIGN KEY `FK_296` (`solo_parts`) REFERENCES `musaSongParts` (`song_parts_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
KEY `FK_299` (`choir_parts`),
CONSTRAINT `FK_297` FOREIGN KEY `FK_299` (`choir_parts`) REFERENCES `musaSongParts` (`song_parts_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
KEY `FK_466` (`org_id`),
CONSTRAINT `FK_464` FOREIGN KEY `FK_466` (`org_id`) REFERENCES `musaOrgs` (`org_id`),
KEY `FK_470` (`storage_id`),
CONSTRAINT `FK_468` FOREIGN KEY `FK_470` (`storage_id`) REFERENCES `musaStorages` (`storage_id`)
);


CREATE TABLE IF NOT EXISTS `musaCategories`
(
 `category_id`   integer NOT NULL AUTO_INCREMENT,
 `category_name` varchar(100) NOT NULL ,

PRIMARY KEY (`category_id`)
);
INSERT musaCategories (category_name) VALUES ('Kvintett');




CREATE TABLE IF NOT EXISTS `musaCountries`
(
 `country_id` integer NOT NULL AUTO_INCREMENT,
 `country_name`    varchar(100) NOT NULL ,

PRIMARY KEY (`country_id`)
);
INSERT musaCountries (country_name) VALUES ('Sverige');
INSERT musaCountries (country_name) VALUES ('Norge');
INSERT musaCountries (country_name) VALUES ('Österrike');



CREATE TABLE IF NOT EXISTS `musaGenderTypes`
(
 `gender_id` varchar(45) NOT NULL ,
 `gender_name`    varchar(45) NOT NULL ,

PRIMARY KEY (`gender_id`)
);
INSERT musaGenderTypes (gender_id,gender_name) VALUES ('UNKNOWN','Okänd');
INSERT musaGenderTypes (gender_id,gender_name) VALUES ('FEMALE','Kvinna');
INSERT musaGenderTypes (gender_id,gender_name) VALUES ('MALE','Man');


CREATE TABLE IF NOT EXISTS `musaHolidays`
(
 `holiday_id` integer NOT NULL AUTO_INCREMENT,
 `holiday_name`    varchar(100) NOT NULL ,

PRIMARY KEY (`holiday_id`)
);
INSERT musaHolidays (holiday_name) VALUES ('Jul');
INSERT musaHolidays (holiday_name) VALUES ('Påsk');


CREATE TABLE IF NOT EXISTS `musaInstruments`
(
 `instrument_id` integer NOT NULL AUTO_INCREMENT,
 `instrument_name`    varchar(100) NOT NULL ,

PRIMARY KEY (`instrument_id`)
);
INSERT musaInstruments (instrument_name) VALUES ('Bastuba');
INSERT musaInstruments (instrument_name) VALUES ('Basfiol');


CREATE TABLE IF NOT EXISTS `musaLanguages`
(
 `language_id` integer NOT NULL AUTO_INCREMENT,
 `language_name`    varchar(100) NOT NULL ,

PRIMARY KEY (`language_id`)
);
INSERT musaLanguages (language_name) VALUES ('Svenska');
INSERT musaLanguages (language_name) VALUES ('Engelska');
INSERT musaLanguages (language_name) VALUES ('Tyska');
INSERT musaLanguages (language_name) VALUES ('Franska');


CREATE TABLE IF NOT EXISTS `musaMusicSongsolos`
(
 `songsolo_id` integer NOT NULL ,
 `music_id`   integer NOT NULL ,

KEY `FK_482` (`songsolo_id`),
CONSTRAINT `FK_480` FOREIGN KEY `FK_482` (`songsolo_id`) REFERENCES `musaSongsolos` (`songsolo_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
KEY `FK_485` (`music_id`),
CONSTRAINT `FK_483` FOREIGN KEY `FK_485` (`music_id`) REFERENCES `musaMusic` (`music_id`) ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS `musaMusicArrangers`
(
 `music_id`  integer NOT NULL ,
 `person_id` integer NOT NULL ,

KEY `FK_391` (`music_id`),
CONSTRAINT `FK_389` FOREIGN KEY `FK_391` (`music_id`) REFERENCES `musaMusic` (`music_id`) ON DELETE CASCADE ON UPDATE CASCADE,
KEY `FK_394` (`person_id`),
CONSTRAINT `FK_392` FOREIGN KEY `FK_394` (`person_id`) REFERENCES `musaPersons` (`person_id`) ON DELETE NO ACTION ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS `musaMusicAuthors`
(
 `music_id`  integer NOT NULL ,
 `person_id` integer NOT NULL ,

KEY `FK_385` (`music_id`),
CONSTRAINT `FK_383` FOREIGN KEY `FK_385` (`music_id`) REFERENCES `musaMusic` (`music_id`) ON DELETE CASCADE ON UPDATE CASCADE,
KEY `FK_388` (`person_id`),
CONSTRAINT `FK_386` FOREIGN KEY `FK_388` (`person_id`) REFERENCES `musaPersons` (`person_id`) ON DELETE NO ACTION ON UPDATE CASCADE
);



CREATE TABLE IF NOT EXISTS `musaMusicCategories`
(
 `music_id`    integer NOT NULL ,
 `category_id` integer NOT NULL ,

KEY `FK_457` (`music_id`),
CONSTRAINT `FK_455` FOREIGN KEY `FK_457` (`music_id`) REFERENCES `musaMusic` (`music_id`) ON DELETE CASCADE ON UPDATE CASCADE,
KEY `FK_460` (`category_id`),
CONSTRAINT `FK_458` FOREIGN KEY `FK_460` (`category_id`) REFERENCES `musaCategories` (`category_id`) ON DELETE NO ACTION ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS `musaMusicComposers`
(
 `music_id`  integer NOT NULL ,
 `person_id` integer NOT NULL ,

KEY `FK_377` (`music_id`),
CONSTRAINT `FK_375` FOREIGN KEY `FK_377` (`music_id`) REFERENCES `musaMusic` (`music_id`) ON DELETE CASCADE ON UPDATE CASCADE,
KEY `FK_380` (`person_id`),
CONSTRAINT `FK_378` FOREIGN KEY `FK_380` (`person_id`) REFERENCES `musaPersons` (`person_id`) ON DELETE NO ACTION ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS `musaMusicHolidays`
(
 `holiday_id` integer NOT NULL ,
 `music_id`   integer NOT NULL ,

KEY `FK_344` (`holiday_id`),
CONSTRAINT `FK_342` FOREIGN KEY `FK_344` (`holiday_id`) REFERENCES `musaHolidays` (`holiday_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
KEY `FK_347` (`music_id`),
CONSTRAINT `FK_345` FOREIGN KEY `FK_347` (`music_id`) REFERENCES `musaMusic` (`music_id`) ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS `musaMusicInstruments`
(
 `music_id`      integer NOT NULL ,
 `instrument_id` integer NOT NULL ,

KEY `FK_312` (`music_id`),
CONSTRAINT `FK_310` FOREIGN KEY `FK_312` (`music_id`) REFERENCES `musaMusic` (`music_id`) ON DELETE CASCADE ON UPDATE CASCADE,
KEY `FK_315` (`instrument_id`),
CONSTRAINT `FK_313` FOREIGN KEY `FK_315` (`instrument_id`) REFERENCES `musaInstruments` (`instrument_id`) ON DELETE NO ACTION ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS `musaMusicLanguages`
(
 `language_id` integer NOT NULL ,
 `music_id`    integer NOT NULL ,

KEY `FK_356` (`language_id`),
CONSTRAINT `FK_354` FOREIGN KEY `FK_356` (`language_id`) REFERENCES `musaLanguages` (`language_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
KEY `FK_359` (`music_id`),
CONSTRAINT `FK_357` FOREIGN KEY `FK_359` (`music_id`) REFERENCES `musaMusic` (`music_id`) ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS `musaMusicThemes`
(
 `theme_id` integer NOT NULL ,
 `music_id` integer NOT NULL ,

KEY `FK_333` (`theme_id`),
CONSTRAINT `FK_331` FOREIGN KEY `FK_333` (`theme_id`) REFERENCES `musaThemes` (`theme_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
KEY `FK_336` (`music_id`),
CONSTRAINT `FK_334` FOREIGN KEY `FK_336` (`music_id`) REFERENCES `musaMusic` (`music_id`) ON DELETE CASCADE ON UPDATE CASCADE
);



CREATE TABLE IF NOT EXISTS `musaPersons`
(
 `person_id`      integer NOT NULL AUTO_INCREMENT,
 `gender_id` varchar(45) NULL ,
 `country_id`     integer NULL ,
 `family_name`    varchar(100) NOT NULL ,
 `first_name`     varchar(100) NULL ,
 `date_born`      year NULL ,
 `date_dead`      year NULL ,

PRIMARY KEY (`person_id`),
KEY `FK_281` (`country_id`),
CONSTRAINT `FK_279` FOREIGN KEY `FK_281` (`country_id`) REFERENCES `musaCountries` (`country_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
KEY `FK_448` (`gender_id`),
CONSTRAINT `FK_446` FOREIGN KEY `FK_448` (`gender_id`) REFERENCES `musaGenderTypes` (`gender_id`) ON DELETE NO ACTION ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS `musaSongParts`
(
 `song_parts_id` integer NOT NULL AUTO_INCREMENT,
 `song_parts_name`    varchar(200) NOT NULL ,

PRIMARY KEY (`song_parts_id`)
);
INSERT musaSongParts (song_parts_name) VALUES ('BTAS');
INSERT musaSongParts (song_parts_name) VALUES ('BBTTAASS');


CREATE TABLE IF NOT EXISTS `musaSongsolos`
(
 `songsolo_id` integer NOT NULL AUTO_INCREMENT,
 `voice_name`      varchar(45) NOT NULL ,

PRIMARY KEY (`songsolo_id`)
);
INSERT musaSongsolos (voice_name) VALUES ('Sopran');


CREATE TABLE IF NOT EXISTS `musaStorages`
(
 `storage_id`    integer NOT NULL AUTO_INCREMENT,
 `org_id`        integer NOT NULL ,
 `storage_name` varchar(100) NOT NULL ,

PRIMARY KEY (`storage_id`),
KEY `FK_416` (`org_id`),
CONSTRAINT `FK_414` FOREIGN KEY `FK_416` (`org_id`) REFERENCES `musaOrgs` (`org_id`) ON DELETE NO ACTION ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS `musaThemes`
(
 `theme_id` integer NOT NULL AUTO_INCREMENT,
 `theme_name`    varchar(100) NOT NULL ,

PRIMARY KEY (`theme_id`)
);
INSERT musaThemes (theme_name) VALUES ('Jul');
INSERT musaThemes (theme_name) VALUES ('Påsk');

SET FOREIGN_KEY_CHECKS = 1;          
