<?php

// ehemals init.php - Erzeugen einer Datenbank bei erstmaligem Aufruf oder bei Pool
// "UpdateSpeedFix" - Anlage von Indizes - integriert

function InitDB()
{
		// Bei Erweiterung um Poolfähigkeit müssen diese überschrieben werden
		
		global $sGuipath;
		global $sDatabasepath;
		global $sDatabase;
		global $sFvpath;
		global $sAktenpath;
		global $sLogpath;
		global $sTmp;
		
		$aVerzeichnisse    = array(
				$sDatabasepath,
				$sFvpath,
				$sAktenpath,
				$sLogpath,
				$sTmp
		);
		$aAktionsMeldungen = array(
				"Verzeichnis " . $sDatabasepath . " für Datenbank anlegen",
				"Verzeichnis " . $sFvpath . " für Formatvorlagen anlegen",
				"Verzeichnis " . $sAktenpath . " für Akten anlegen",
				"Verzeichnis " . $sLogpath . " für Logfile anlegen",
				"Verzeichnis " . $sTmp . " für temporäre Dateien anlegen"
		);
		$aStatusMeldung    = array(
				"Fehlgeschlagen",
				"Erfolgreich",
				"Achtung: Existiert bereits !"
		);
		
		$aParam['_status_']  = '';
		$aParam['_aktion_']  = '';
		$aParam['_error_']   = '';
		$aParam['_error2_']  = 'Erfolgreich durchgef&uuml;hrt. Sie k&ouml;nnen sich nun als Administrator <a href="index.php" style="color:red;">einloggen</a> und die Datenbank konfigurieren.';
		$aParam['_display_'] = 'none';
		
		$bErrStatus = false;
		
		// ----------------------------- Datenbankstruktur. Nicht Ändern ! ---------------------------------------------------
		
		$aTables = array(
				"CREATE TABLE users(id INTEGER PRIMARY KEY, username VARCHAR(20) NOT NULL, passwort VARCHAR(32) NOT NULL)",
				"CREATE TABLE security(nr INTEGER PRIMARY KEY,ipadresse INT NOT NULL)",
				"CREATE TABLE logfile(nr INTEGER PRIMARY KEY, ipadresse INT NOT NULL, zeit TIMESTAMP, benutzer VARCHAR(20), ereignis VARCHAR(255))",
				
				"CREATE TABLE aktenzeichen(id INTEGER PRIMARY KEY, aznr INT, azjahr INT)",
				"CREATE TABLE rechnungsnummer(id INTEGER PRIMARY KEY, nr INT, jahr INT, azID INT, betrag FLOAT)",
				"CREATE TABLE freiesAZ(aznr INT, azjahr INT)",
				"CREATE TABLE freieRNR(nr INT, jahr INT)",
				
				"CREATE TABLE linkliste(nr INTEGER PRIMARY KEY, bezeichnung VARCHAR(50), ahref VARCHAR(255))",
				
				"CREATE TABLE akten(azID INTEGER, anlagedatum DATETIME, kurzruburm VARCHAR(50), wegen VARCHAR(50), sonstiges VARCHAR(50), rechtsgebietID INT, bearbeiterID INT, status CHAR(1))",
				"CREATE TABLE aktenvita(nr INTEGER PRIMARY KEY, azID INT, eintragsdatum DATETIME, ersteller VARCHAR(20), dateiname VARCHAR(255), beschreibung VARCHAR(30))",
				"CREATE TABLE formatvorlagen(nr INTEGER PRIMARY KEY, name VARCHAR(30), filename VARCHAR(255))",
				
				"CREATE TABLE kosten(nr INTEGER PRIMARY KEY, azID INT, datum DATETIME, grund VARCHAR(50), betrag FLOAT)",
				
				"CREATE TABLE postausgang(nr INTEGER PRIMARY KEY, azID INT, datum DATETIME, typ VARCHAR(20), user VARCHAR(20), empfaenger VARCHAR(30), inhalt VARCHAR(30), aktenvitaID INT)",
				"CREATE TABLE posteingang(nr INTEGER PRIMARY KEY, azID INT, datum DATETIME, absender VARCHAR(30), inhalt VARCHAR(30), typ VARCHAR(20), dateiname VARCHAR(255))",
				
				"CREATE TABLE adressen(id INTEGER PRIMARY KEY, firma VARCHAR(50), name VARCHAR(50), vorname VARCHAR(50), strasse1 VARCHAR(50), strasse2 VARCHAR(50), plz INT, ort VARCHAR(50), telefon1 VARCHAR(20), telefon2 VARCHAR(20), fax VARCHAR(20), email VARCHAR(50))",
				"CREATE TABLE beteiligte(id INTEGER PRIMARY KEY, azID INT, beteiligtenartID INT, adressenID INT, ansprechpartner VARCHAR(50), telefon VARCHAR(20), aktenzeichen VARCHAR(20))",
				"CREATE TABLE beteiligtenart(id INTEGER PRIMARY KEY, arten VARCHAR(20))",
				
				"CREATE TABLE rechtsgebiete(id INTEGER PRIMARY KEY, bezeichnung VARCHAR(30))",
				"CREATE TABLE wiedervorlagen(nr INTEGER PRIMARY KEY,azID INT, zeitunddatum DATETIME, terminID INT, bearbeiterID INT, bearbeiterDone VARCHAR(20), information VARCHAR(100), status CHAR(1))",
				"CREATE TABLE wvtypen(id INTEGER PRIMARY KEY, typ VARCHAR(50))"
		);
		
		// -------------------------------------- Indizes erzeugen -------------------------------------------
		
		$aDBIndex = array(
				"CREATE INDEX IDX_aktenzeichen_01 ON aktenzeichen (id)",
				
				"CREATE INDEX IDX_akten_01 ON akten (azID)",
				"CREATE INDEX IDX_akten_02 ON akten (kurzruburm)",
				"CREATE INDEX IDX_akten_03 ON akten (wegen)",
				
				"CREATE INDEX IDX_wiedervorlagen_01 ON wiedervorlagen (nr)",
				"CREATE INDEX IDX_wiedervorlagen_02 ON wiedervorlagen (status)",
				"CREATE INDEX IDX_wiedervorlagen_03 ON wiedervorlagen (azID)",
				"CREATE INDEX IDX_wiedervorlagen_04 ON wiedervorlagen (zeitunddatum)",
				"CREATE INDEX IDX_wiedervorlagen_05 ON wiedervorlagen (terminID)",
				"CREATE INDEX IDX_wiedervorlagen_06 ON wiedervorlagen (bearbeiterID)",
				
				"CREATE INDEX IDX_users_01 ON users (id)",
				
				"CREATE INDEX IDX_rechnungsnummer_01 ON rechnungsnummer (id)",
				"CREATE INDEX IDX_rechnungsnummer_02 ON rechnungsnummer (azID)",
				
				"CREATE INDEX IDX_aktenvita_01 ON aktenvita (nr)",
				"CREATE INDEX IDX_aktenvita_02 ON aktenvita (azID)",
				"CREATE INDEX IDX_aktenvita_03 ON aktenvita (beschreibung)",
				
				"CREATE INDEX IDX_kosten_01 ON kosten (nr)",
				"CREATE INDEX IDX_kosten_02 ON kosten (azID)",
				
				"CREATE INDEX IDX_posteingang_01 ON posteingang (nr)",
				"CREATE INDEX IDX_posteingang_02 ON posteingang (azID)",
				"CREATE INDEX IDX_posteingang_03 ON posteingang (datum)",
				"CREATE INDEX IDX_posteingang_04 ON posteingang (absender)",
				
				"CREATE INDEX IDX_postausgang_01 ON postausgang (nr)",
				"CREATE INDEX IDX_postausgang_02 ON postausgang (azID)",
				"CREATE INDEX IDX_postausgang_03 ON postausgang (datum)",
				"CREATE INDEX IDX_postausgang_04 ON postausgang (empfaenger)",
				
				"CREATE INDEX IDX_adressen_01 ON adressen (id)",
				"CREATE INDEX IDX_adressen_02 ON adressen (firma)",
				"CREATE INDEX IDX_adressen_03 ON adressen (name)",
				"CREATE INDEX IDX_adressen_04 ON adressen (vorname)",
				
				"CREATE INDEX IDX_beteiligte_01 ON beteiligte (id)",
				"CREATE INDEX IDX_beteiligte_02 ON beteiligte (azID)",
				"CREATE INDEX IDX_beteiligte_03 ON beteiligte (adressenID)",
				
				"CREATE INDEX IDX_wvtypen_01 ON wvtypen (id)"
		);
		
		// ------------------------------- Basisdaten / Nur mit Bedacht verändern ! -------------------------------------------
		
		$aDBBasis = array(
				"INSERT INTO users (username,passwort) VALUES ('Administrator','" . MD5('sysop') . "')",
				"INSERT INTO security (ipadresse) VALUES (" . ip2long('127.0.0.1') . ")",
				"INSERT INTO freiesAZ (aznr,azjahr) VALUES (1," . date('y') . ")",
				"INSERT INTO freieRNR (nr,jahr) VALUES (1," . date('Y') . ")",
				
				// Wiedervorlagentypen - mindestens ein Typ muss eingetragen sein 
				
				"INSERT INTO wvtypen (typ) VALUES ('Wiedervorlage')",
				"INSERT INTO wvtypen (typ) VALUES ('Schriftsatzfrist')",
				"INSERT INTO wvtypen (typ) VALUES ('Einspruchsfrist')",
				"INSERT INTO wvtypen (typ) VALUES ('Berufungsfrist')",
				"INSERT INTO wvtypen (typ) VALUES ('Revisionsfrist')",
				"INSERT INTO wvtypen (typ) VALUES ('Rechtsmittelfrist')",
				"INSERT INTO wvtypen (typ) VALUES ('Gerichtstermin')",
				
				// Beteiligtenarten - diese dürfen NICHT geändert werden !
				
				"INSERT INTO beteiligtenart (arten) VALUES ('Mandant')",
				"INSERT INTO beteiligtenart (arten) VALUES ('Gegner')",
				"INSERT INTO beteiligtenart (arten) VALUES ('Gegner RA')",
				"INSERT INTO beteiligtenart (arten) VALUES ('Rechtsschutz')",
				"INSERT INTO beteiligtenart (arten) VALUES ('Streithelfer')",
				"INSERT INTO beteiligtenart (arten) VALUES ('Bevollmächtigter')",
				"INSERT INTO beteiligtenart (arten) VALUES ('Gericht I. Instanz')",
				"INSERT INTO beteiligtenart (arten) VALUES ('Gericht II. Instanz')",
				"INSERT INTO beteiligtenart (arten) VALUES ('Gericht III. Instanz')",
				
				// Rechtsgebiete - mindestens ein Rechtsgebiet muss eingetragen sein 
				
				"INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Zivilrecht')",
				"INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Strafrecht')",
				"INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Verwaltungsrecht')",
				"INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Gesellschaftsrecht')",
				"INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Verkehrsrecht')",
				"INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Arbeitsrecht')",
				"INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Sozialrecht')",
				"INSERT INTO rechtsgebiete (bezeichnung) VALUES ('Gewerblicher Rechtsschutz')"
		);
		
		// ------------------------------- Initialisierungsroutinen -----------------------------------------------------------
		
		// Notwendige Verzeichnisse anlegen
		
		for ($t = 0; $t < sizeof($aVerzeichnisse); $t++) {
				$aStatus[$t]   = $aStatusMeldung[2];
				$aAktionen[$t] = $aAktionsMeldungen[$t];
				if (!file_exists($aVerzeichnisse[$t])) {
						$bError = @mkdir($aVerzeichnisse[$t], 0777);
						if ($bError == false) {
								$bErrStatus = true;
						}
						$aStatus[$t] = $aStatusMeldung[$bError];
				} else {
						$bErrStatus = true;
				}
		}
		
		// Datenbank anlegen
		
		$aAktionen[] = "Datenbankdatei " . $sDatabase . " erzeugen";
		
		$hDatabase = secure_sqlite_open($sDatabase, $sError);
		
		// Tabellen erzeugen & Standardwerte eintragen
		
		if ($hDatabase != false) {
				$aStatus[] = "Erfolgreich";
				
				$aAktionen[] = "Erzeuge Tabellen";
				
				$sMessage = "Erfolgreich";
				
				unset($aErrorMess);
				for ($t = 0; $t < sizeof($aTables); $t++) {
						secure_sqlite_query($hDatabase, $aTables[$t], $sError);
						if ($sError != null) {
								$sMessage     = 'Fehlgeschlagen';
								$aErrorMess[] = "Index " . $t . " - " . $sError;
								$bErrStatus   = true;
						}
				}
				
				$aStatus[] = $sMessage;
				if (sizeof($aErrorMess) != 0) {
						for ($t = 0; $t < sizeof($aErrorMess); $t++) {
								$aAktionen[] = '&nbsp;';
								$aStatus[]   = $aErrorMess[$t];
						}
				}
				
				
				$aAktionen[] = "Erzeuge Indizes";
				
				$sMessage = "Erfolgreich";
				
				unset($aErrorMess);
				for ($t = 0; $t < sizeof($aDBIndex); $t++) {
						secure_sqlite_query($hDatabase, $aDBIndex[$t], $sError);
						if ($sError != null) {
								$sMessage     = 'Fehlgeschlagen';
								$aErrorMess[] = "Index " . $t . " - " . $sError;
								$bErrStatus   = true;
						}
				}
				
				$aStatus[] = $sMessage;
				if (sizeof($aErrorMess) != 0) {
						for ($t = 0; $t < sizeof($aErrorMess); $t++) {
								$aAktionen[] = '&nbsp;';
								$aStatus[]   = $aErrorMess[$t];
						}
				}
				
				
				$aAktionen[] = "Eintragen von Basisdaten";
				
				$sMessage = "Erfolgreich";
				
				unset($aErrorMess);
				for ($t = 0; $t < sizeof($aDBBasis); $t++) {
						secure_sqlite_query($hDatabase, $aDBBasis[$t], $sError);
						if ($sError != null) {
								$sMessage     = 'Fehlgeschlagen';
								$aErrorMess[] = "Index " . $t . " - " . $sError;
								$bErrStatus   = true;
						}
				}
				
				$aStatus[] = $sMessage;
				if (sizeof($aErrorMess) != 0) {
						for ($t = 0; $t < sizeof($aErrorMess); $t++) {
								$aAktionen[] = '&nbsp;';
								$aStatus[]   = $aErrorMess[$t];
						}
				}
				
				
				secure_sqlite_close($hDatabase);
		} else {
				$aStatus[]  = $sError;
				$bErrStatus = true;
		}
		
		$aParam['_status_'] = $aStatus;
		$aParam['_aktion_'] = $aAktionen;
		
		if ($bErrStatus == true) {
				$aParam['_error2_']  = 'Es sind Fehler aufgetreten ! Beseitigen Sie die Ursachen und führen Sie die Initialisierung erneut durch.';
				$aParam['_error_']   = 'Fehlgeschlagen !';
				$aParam['_display_'] = 'block';
		}
		
		ShowGui('admin/initdb.html', $aParam);
}
