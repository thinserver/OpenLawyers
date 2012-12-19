CREATE TABLE users(id INTEGER PRIMARY KEY AUTO_INCREMENT, username VARCHAR(20) NOT NULL, passwort VARCHAR(32) NOT NULL);
CREATE TABLE security(nr INTEGER PRIMARY KEY AUTO_INCREMENT,ipadresse INT NOT NULL);
CREATE TABLE logfile(nr INTEGER PRIMARY KEY AUTO_INCREMENT, ipadresse INT NOT NULL, zeit TIMESTAMP, benutzer VARCHAR(20), ereignis VARCHAR(255));

CREATE TABLE aktenzeichen(id INTEGER PRIMARY KEY AUTO_INCREMENT, aznr INT, azjahr INT);
CREATE TABLE rechnungsnummer(id INTEGER PRIMARY KEY AUTO_INCREMENT, nr INT, jahr INT, azID INT, betrag FLOAT);
CREATE TABLE freiesAZ(aznr INT PRIMARY KEY AUTO_INCREMENT, azjahr INT);
CREATE TABLE freieRNR(nr INT PRIMARY KEY AUTO_INCREMENT, jahr INT);

CREATE TABLE linkliste(nr INTEGER PRIMARY KEY AUTO_INCREMENT, bezeichnung VARCHAR(50), ahref VARCHAR(255));

CREATE TABLE akten(azID INTEGER, anlagedatum DATETIME, kurzruburm VARCHAR(50), wegen VARCHAR(50), sonstiges VARCHAR(50), rechtsgebietID INT, bearbeiterID INT, status CHAR(1));
CREATE TABLE aktenvita(nr INTEGER PRIMARY KEY AUTO_INCREMENT, azID INT, eintragsdatum DATETIME, ersteller VARCHAR(20), dateiname VARCHAR(255), beschreibung VARCHAR(30));
CREATE TABLE formatvorlagen(nr INTEGER PRIMARY KEY AUTO_INCREMENT, name VARCHAR(30), filename VARCHAR(255));

CREATE TABLE kosten(nr INTEGER PRIMARY KEY AUTO_INCREMENT, azID INT, datum DATETIME, grund VARCHAR(50), betrag FLOAT);

CREATE TABLE postausgang(nr INTEGER PRIMARY KEY AUTO_INCREMENT, azID INT, datum DATETIME, typ VARCHAR(20), user VARCHAR(20), empfaenger VARCHAR(30), inhalt VARCHAR(30), aktenvitaID INT);
CREATE TABLE posteingang(nr INTEGER PRIMARY KEY AUTO_INCREMENT, azID INT, datum DATETIME, absender VARCHAR(30), inhalt VARCHAR(30), typ VARCHAR(20), dateiname VARCHAR(255));

CREATE TABLE adressen(id INTEGER PRIMARY KEY AUTO_INCREMENT, firma VARCHAR(50), name VARCHAR(50), vorname VARCHAR(50), strasse1 VARCHAR(50), strasse2 VARCHAR(50), plz INT, ort VARCHAR(50), telefon1 VARCHAR(20), telefon2 VARCHAR(20), fax VARCHAR(20), email VARCHAR(50));
CREATE TABLE beteiligte(id INTEGER PRIMARY KEY AUTO_INCREMENT, azID INT, beteiligtenartID INT, adressenID INT, ansprechpartner VARCHAR(50), telefon VARCHAR(20), aktenzeichen VARCHAR(20));
CREATE TABLE beteiligtenart(id INTEGER PRIMARY KEY AUTO_INCREMENT, arten VARCHAR(20));

CREATE TABLE rechtsgebiete(id INTEGER PRIMARY KEY AUTO_INCREMENT, bezeichnung VARCHAR(30));
CREATE TABLE wiedervorlagen(nr INTEGER PRIMARY KEY AUTO_INCREMENT, azID INT, zeitunddatum DATETIME, terminID INT, bearbeiterID INT, bearbeiterDone VARCHAR(20), information VARCHAR(100), status CHAR(1));
CREATE TABLE wvtypen(id INTEGER PRIMARY KEY AUTO_INCREMENT, typ VARCHAR(50));


INSERT INTO wvtypen (typ) VALUES ('Wiedervorlage');
INSERT INTO wvtypen (typ) VALUES ('Schriftsatzfrist');
INSERT INTO wvtypen (typ) VALUES ('Einspruchsfrist');
INSERT INTO wvtypen (typ) VALUES ('Berufungsfrist');
INSERT INTO wvtypen (typ) VALUES ('Revisionsfrist');
INSERT INTO wvtypen (typ) VALUES ('Rechtsmittelfrist');
INSERT INTO wvtypen (typ) VALUES ('Gerichtstermin');

INSERT INTO beteiligtenart (arten) VALUES ('Mandant');
INSERT INTO beteiligtenart (arten) VALUES ('Gegner');
INSERT INTO beteiligtenart (arten) VALUES ('Gegner RA');
INSERT INTO beteiligtenart (arten) VALUES ('Rechtsschutz');
INSERT INTO beteiligtenart (arten) VALUES ('Streithelfer');
INSERT INTO beteiligtenart (arten) VALUES ('Bevollmächtigter');
INSERT INTO beteiligtenart (arten) VALUES ('Gericht I. Instanz');
INSERT INTO beteiligtenart (arten) VALUES ('Gericht II. Instanz');
INSERT INTO beteiligtenart (arten) VALUES ('Gericht III. Instanz');

INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Zivilrecht');
INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Strafrecht');
INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Verwaltungsrecht');
INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Gesellschaftsrecht');
INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Verkehrsrecht');
INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Arbeitsrecht');
INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Sozialrecht');
INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Gewerblicher Rechtsschutz');