<?php
//
// OpenLawyer's
// Software License: GNU Affero-GPL v3
//
// Installations- und Benutzungshinweise siehe Datei readme
//

// HINWEIS: DATEIBERECHTIGUNGEN
// Für Verzeichnis und Dateioperationen werden im Skript die Rechte 777 und 666 verwendet. 
// durch umask, die regelmäßig auf 22 steht, sollten die üblich zu verwendenden Rechte 755 und 644 vorliegen
// Für manuelle Backups und manuelle Updates ist immer darauf zu achten, dass der Webserver Besitzer ist, sonst gibt es
// bei diesen Rechten Probleme; andernfalls kann eine umask 0 diese überwinden

include('settings.php');

// isset bei $_POST ist immer gegeben, sobald <form> abgesandt wird und <input> einen namen hat, unabhängig davon, was oder ob etwas eingetragen wurde
// AUSNAHME: bei select o.st isset nur gegeben, wenn etwas ausgewählt wurde aus der Liste ! Bei Button ebenfalls

// ------------------------------------ Funktionen für Sicherheit & Session ----------------------------------------------------------------

include('gui.php');

// Entschärft Benutzereingaben - nur A..Z, 0..9 erlaubt, um Codeinjection zu verhindern

function MakeSafe($eingabe)
{
		$sReplace = '';
		return preg_replace("/[\"\'<>\\\{\}\$#]/", $sReplace, $eingabe);
}

// prüft, ob IP-Adresse der Notation entspricht

function CheckIP($ipadr)
{
		$state = 1;
		$count = 0;
		$told  = 0;
		$ipadr = $ipadr . ".";
		for ($t = 0; $t < strlen($ipadr); $t++) {
				if ($ipadr[$t] == ".") {
						$count++;
						$nums = substr($ipadr, $told, $t - $told);
						if ((strlen($nums) > 3) || (strlen($nums) == 0)) {
								$state = 0;
								break;
						}
						// regulärer Ausdruck - suche, ob etwas außer 0-9 vorhanden ist
						if (preg_match("/[^0-9]/", $nums)) {
								$state = 0;
								break;
						}
						$told = $t + 1;
				}
		}
		if ($count != 4) {
				$state = 0;
		}
		return $state;
}

// macht aus der IP eine genormte ...

function NormIP($sIpadr)
{
		$aZ      = array(
				'00',
				'0',
				''
		);
		$sIppart = '';
		$sNewIP  = '';
		for ($t = 0; $t < strlen($sIpadr); $t++) {
				if (($sIpadr[$t]) == '.') {
						$sIppart = $aZ[strlen($sIppart) - 1] . $sIppart . '.';
						$sNewIP  = $sNewIP . $sIppart;
						$sIppart = '';
				} else {
						$sIppart = $sIppart . $sIpadr[$t];
				}
		}
		$sNewIP = $sNewIP . $aZ[strlen($sIppart) - 1] . $sIppart;
		return $sNewIP;
}

include('ipblock.php');

function NormAZ($aAZ)
{
		if (is_array($aAZ)) {
		}
}

// Speichert die mit POST übergebenen Daten, um sie beim Neuaufbau der Seite wieder einzutragen

function POSTerhalten($aPostValues)
{
		if (!empty($aPostValues)) {
				unset($aPOSTCopy);
				foreach ($aPostValues as $sPOSTKey => $sPOSTValue) {
						// spezielle Notation für zu ersetzende GUI Variablen - in Großbuchstaben um Verwechslungen zu verhindern
						$sNewKey             = "_" . strtoupper($sPOSTKey) . "_";
						$aPOSTCopy[$sNewKey] = $sPOSTValue;
				}
				return $aPOSTCopy;
		} else
				return NULL;
}

// Zentrale Protokollfunktion für Aktenvorgänge
// gespeichert wird in ein der Akte(nvita) zugeordnetes Textdokument
// der Dateiname 'protokoll.txt' ist "hardverdrahtet". Die Dokumente im Aktenverzeichnis
// werden nach einem eigenen Namensschema gespeichert, ein Dokument protokoll.txt kann ein Benutzer
// nicht erzeugen - bei Änderung: in Funktion Aktenvita Unlöschbarkeit sicherstellen.

function Protokoll($hDatabase, $sProtokollvermerk)
{
		$sProtokollfilename = $_SESSION['aktenpath'] . "protokoll.txt";
		$sFirstRecord       = '';
		
		// Falls Datei noch nicht existiert - alte Installationen, bei Anlage der Akte, Eintrag in DB
		
		if (!file_exists($sProtokollfilename)) {
				secure_sqlite_query($hDatabase, "INSERT INTO aktenvita (azID,eintragsdatum,ersteller,dateiname,beschreibung) VALUES ('" . $_SESSION['akte'] . "','" . date("U") . "','System','protokoll.txt','Aktenprotokoll')");
				$sFirstRecord = date("d.m.Y") . ": System: Aktenprotokoll zu Akte " . $_SESSION['aktenzeichen'] . " angelegt \n";
		}
		
		// Dann öffnen bzw. physikalisch anlegen
		
		$hFile = fopen($sProtokollfilename, 'a+');
		
		if ($hFile) {
				$sNewLine = $sFirstRecord . date("d.m.Y") . ": " . $_SESSION['benutzer'] . ": " . $sProtokollvermerk . "\n";
				fputs($hFile, $sNewLine);
				fclose($hFile);
		} else {
				// Hinweis für Admin in Datenbanklog
				
				secure_sqlite_query($hDatabase, "INSERT INTO logfile (ipadresse,zeit,benutzer,ereignis) VALUES ('" . $_SESSION['ipadresse'] . "','" . date("U") . "','" . $_SESSION['benutzer'] . "','Akte " . $_SESSION['aktenzeichen'] . ": Protokolldatei öffnen/anlegen gescheitert')");
				
		}
		
}

include('sql.php');

// ------------------------------------ Hauptfunktionen des Programmes -----------------------------------------------

include('login.php');

// ---------------------------------------- SVG Kreation -------------------------------------------------------------

include('svg.php');

// ---------------------------------------- Administrator-Funktionen -------------------------------------------------


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

// Logfile anzeigen und Einträge löschen; Export erfolgt in LOG-Verzeichnis

function LogFile()
{
		global $sDatabase;
		global $sLogpath;
		$hDatabase           = secure_sqlite_open($sDatabase);
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		// will jemand einen Eintrag löschen ?
		
		if (isset($_POST['delete'])) {
				if ($_POST['zeile'] != '') {
						secure_sqlite_query($hDatabase, "DELETE FROM logfile WHERE nr='" . (int) $_POST['zeile'] . "'");
				} else {
						$aParam['_error_']   = "Wählen Sie<br>einen Eintrag aus !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// alle löschen ?
		
		if (isset($_POST['delall'])) {
				secure_sqlite_query($hDatabase, "DELETE FROM logfile");
		}
		
		// Liste exportieren ?
		
		if (isset($_POST['export'])) {
				$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM logfile");
				if (!empty($aQuery)) {
						$hLogfile = @fopen($sLogpath . date("dMYHis") . ".log", "w+");
						if ($hLogfile) {
								fputs($hLogfile, "Datum, Ip-Adresse, Benutzer, Ereignis\r\n");
								for ($t = 0; $t < sizeof($aQuery); $t++) {
										$sEntry = date("d.m.Y H:i:s", $aQuery[$t]['zeit']) . "," . long2ip($aQuery[$t]['ipadresse']) . "," . $aQuery[$t]['benutzer'] . "," . $aQuery[$t]['ereignis'] . "\r\n";
										fputs($hLogfile, $sEntry);
								}
								fclose($hLogfile);
						} else {
								$aParam['_error_']   = "Log-Datei konnte nicht angelegt werden !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Einträge vorhanden !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// liefert ein Array - jeder Array Eintrag ist wieder ein Array/Hashtable mit den Zeileneinträgen
		
		$aLogs = secure_sqlite_array_query($hDatabase, "SELECT * FROM logfile");
		secure_sqlite_close($hDatabase);
		
		if (!sizeof($aLogs) == 0) {
				// gibt es haupt  Eintr?
				for ($t = 0; $t < sizeof($aLogs); $t++) {
						$aNr[$t]      = $aLogs[$t]['nr'];
						$aEintrag[$t] = $aLogs[$t]['benutzer'];
						$aDate[$t]    = date("d.m.Y H:i:s", $aLogs[$t]['zeit']);
						$aIP[$t]      = long2ip($aLogs[$t]['ipadresse']);
						$aEvent[$t]   = $aLogs[$t]['ereignis'];
				}
				$aParam['_nr_']    = $aNr;
				$aParam['_user_']  = $aEintrag;
				$aParam['_date_']  = $aDate;
				$aParam['_ip_']    = $aIP;
				$aParam['_event_'] = $aEvent;
		} else {
				$aParam['_nr_']    = '';
				$aParam['_user_']  = '';
				$aParam['_date_']  = '';
				$aParam['_ip_']    = '';
				$aParam['_event_'] = '';
		}
		
		ShowGui('logfile.html', $aParam);
}

// Sicherheit - nur konkret definierte IP-Adressen dürfen Zugriff haben

function Sicherheit()
{
		global $sDatabase;
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		$hDatabase = secure_sqlite_open($sDatabase);
		// will jemand Eintrlen ?
		if (isset($_POST['loeschen'])) {
				if (isset($_POST['eintraege'])) {
						foreach ($_POST['eintraege'] as $iSelected) {
								// im HTML Code wird als "name" für <select> ein Array angegeben. Unter diesem Namen findet man bei $_POST das Array(!)
								if ((int) $iSelected > 1) {
										// 127.0.0.1 darf nicht gelöscht werden
										secure_sqlite_query($hDatabase, "DELETE FROM security WHERE nr='" . (int) $iSelected . "'");
								} else {
										$aParam['_error_']   = "127.0.0.1 darf nicht gelöscht werden !";
										$aParam['_display_'] = 'block';
								}
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		// will jemand IPAdresse hinzufügen ?
		if (isset($_POST['hinzufuegen'])) {
				$sIpadr = $_POST['ipadresse'];
				
				if ($sIpadr != "") {
						if (CheckIP($sIpadr)) {
								secure_sqlite_query($hDatabase, "INSERT INTO security (ipadresse) VALUES ('" . ip2long($sIpadr) . "')");
						} else {
								$aParam['_error_']   = "IP-Adresse entspricht nicht der Notation !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Bitte Adresse angeben !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// liefert ein Array - jeder Array Eintrag ist wieder ein Array/Hashtable mit den Zeileneinträgen
		$aLogs = secure_sqlite_array_query($hDatabase, "SELECT * FROM security");
		secure_sqlite_close($hDatabase);
		
		if (!sizeof($aLogs) == 0) {
				// gibt es haupt  Eintr?
				for ($t = 0; $t < sizeof($aLogs); $t++) {
						$aNr[$t]      = $aLogs[$t]['nr'];
						$aEintrag[$t] = NormIP(long2ip($aLogs[$t]['ipadresse']));
				}
				$aParam['_nr_']    = $aNr;
				$aParam['_ipadr_'] = $aEintrag;
				if (sizeof($aNr) > 30) {
						$aParam['_max_'] = 30;
				} else {
						$aParam['_max_'] = sizeof($aNr);
				}
		} else {
				$aParam['_nr_']      = null;
				$aParam['_eintrag_'] = 'Keine Einträge vorhanden !';
				$aParam['_max_']     = 1;
		}
		
		ShowGui('ip.html', $aParam);
}

include('users.php');

include('templates.php');

include('records.php');

include('lawtopics.php');

include('statistics.php');

include('linkdb.php');

include('followups.php');

include('collaborators.php');

// ------------------------------------ User-Funktionen --------------------------------------------------------------

include('record_functions.php');

// Wiedervorlagen anzeigen - aktenunabhängig

function Wiedervorlagen()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_error_']      = '';
		$aParam['_selected_']   = '';
		$aParam['_display_']    = 'none';
		$aParam['_nr_']         = '';
		$aParam['_az_']         = '';
		$aParam['_krubrum_']    = '';
		$aParam['_datum_']      = '';
		$aParam['_bearbeiter_'] = '';
		$aParam['_grund_']      = '';
		$aParam['_typ_']        = '';
		$aParam['_wvdatum_']    = date('d.m.Y');
		$aParam['_wvuser_']     = $_SESSION['benutzer'];
		$aParam['_wvtyp_']      = '';
		$aParam['_wvtypid_']    = '';
		$aParam['_whichWV_']    = 'Sämtliche Wiedervorlagenarten';
		
		$iTermin = date('U');
		
		if (isset($_POST['oeffnen'])) {
				if (isset($_POST['zeile']) && ($_POST['zeile'] != '')) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT azID FROM wiedervorlagen WHERE nr='" . (int) $_POST['zeile'] . "'");
						if (sizeof($aQuery) != 0) {
								secure_sqlite_close($hDatabase);
								unset($_POST);
								$_POST['oeffnen2'] = 1;
								$_POST['zeile']    = $aQuery[0]['azID'];
								OpenAkte();
						} else {
								$aParam['_error_']   = "Akte nicht gefunden !";
								$aParam['_display_'] = "block";
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = "block";
				}
		}
		
		
		if (isset($_POST['aktualisiere'])) {
				$iTermin = mktime(23, 59, 59, (int) $_POST['monat'], (int) $_POST['tag'], (int) $_POST['jahr']);
				if ($iTermin < date('U')) {
						$aParam['_error_']   = 'Termin muss in der Zukunft liegen !';
						$aParam['_display_'] = 'block';
						$iTermin             = date('U');
				}
				$aParam['_wvdatum_'] = date('d.m.Y', $iTermin);
				$iUser               = (int) $_POST['bearbeiter'];
				$iWvTypID            = (int) $_POST['wvtyp'];
				if (($iUser != 0) && ($iWvTypID != 0)) {
						$aQuery              = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, users.username, aktenzeichen.aznr, aktenzeichen.azjahr, wvtypen.typ, wvtypen.id, wiedervorlagen.zeitunddatum, wiedervorlagen.information, wiedervorlagen.nr FROM akten, users, aktenzeichen, wvtypen, wiedervorlagen WHERE wiedervorlagen.status=0 AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID=aktenzeichen.id AND users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.bearbeiterID='" . $iUser . "' AND wiedervorlagen.zeitunddatum<" . $iTermin . " AND wvtypen.id=" . $iWvTypID . " AND akten.azID=aktenzeichen.id ORDER BY wiedervorlagen.zeitunddatum");
						$aActuser            = secure_sqlite_array_query($hDatabase, "SELECT username FROM users WHERE id='" . $iUser . "'");
						$aActWV              = secure_sqlite_array_query($hDatabase, "SELECT typ FROM wvtypen WHERE id='" . $iWvTypID . "'");
						$aParam['_wvuser_']  = $aActuser[0]['username'];
						$aParam['_whichWV_'] = $aActWV[0]['typ'];
				} elseif ($iUser != 0) {
						$aQuery             = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, users.username, aktenzeichen.aznr, aktenzeichen.azjahr, wvtypen.typ, wiedervorlagen.zeitunddatum, wiedervorlagen.information, wiedervorlagen.nr FROM akten, users, aktenzeichen, wvtypen, wiedervorlagen WHERE wiedervorlagen.status=0 AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID=aktenzeichen.id AND users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.bearbeiterID='" . $iUser . "' AND wiedervorlagen.zeitunddatum<" . $iTermin . " AND akten.azID=aktenzeichen.id ORDER BY wiedervorlagen.zeitunddatum");
						$aActuser           = secure_sqlite_array_query($hDatabase, "SELECT username FROM users WHERE id='" . $iUser . "'");
						$aParam['_wvuser_'] = $aActuser[0]['username'];
				} elseif ($iWvTypID != 0) {
						$aQuery              = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, users.username, aktenzeichen.aznr, aktenzeichen.azjahr, wvtypen.typ, wvtypen.id, wiedervorlagen.zeitunddatum, wiedervorlagen.information, wiedervorlagen.nr FROM akten, users, aktenzeichen, wvtypen, wiedervorlagen WHERE wiedervorlagen.status=0 AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID=aktenzeichen.id AND users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.zeitunddatum<" . $iTermin . " AND wvtypen.id=" . $iWvTypID . " AND akten.azID=aktenzeichen.id ORDER BY wiedervorlagen.zeitunddatum");
						$aActWV              = secure_sqlite_array_query($hDatabase, "SELECT typ FROM wvtypen WHERE id='" . $iWvTypID . "'");
						$aParam['_wvuser_']  = "sämtliche Nutzer";
						$aParam['_whichWV_'] = $aActWV[0]['typ'];
				} else {
						$aQuery             = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, users.username, aktenzeichen.aznr, aktenzeichen.azjahr, wvtypen.typ, wiedervorlagen.zeitunddatum, wiedervorlagen.information, wiedervorlagen.nr FROM akten, users, aktenzeichen, wvtypen, wiedervorlagen WHERE wiedervorlagen.status=0 AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID=aktenzeichen.id AND users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.zeitunddatum<" . $iTermin . " AND akten.azID=aktenzeichen.id ORDER BY wiedervorlagen.zeitunddatum");
						$aParam['_wvuser_'] = "sämtliche Nutzer";
				}
		} else {
				$aQuery = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, users.username, aktenzeichen.aznr, aktenzeichen.azjahr, wvtypen.typ, wiedervorlagen.zeitunddatum, wiedervorlagen.information, wiedervorlagen.nr FROM akten, users, aktenzeichen, wvtypen, wiedervorlagen WHERE wiedervorlagen.status=0 AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID=aktenzeichen.id AND users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.zeitunddatum<" . $iTermin . " AND users.username='" . $_SESSION['benutzer'] . "' AND akten.azID=aktenzeichen.id ORDER BY wiedervorlagen.zeitunddatum");
		}
		
		$aQuery2 = secure_sqlite_array_query($hDatabase, "SELECT id, username FROM users WHERE username!='Administrator' ORDER BY username");
		$aQuery3 = secure_sqlite_array_query($hDatabase, "SELECT * FROM wvtypen ORDER BY typ");
		
		secure_sqlite_close($hDatabase);
		
		$aUser[0]     = 'Alle';
		$aID[0]       = '0';
		$aSelected[0] = '';
		
		for ($t = 0; $t < sizeof($aQuery2); $t++) {
				$aUser[$t + 1] = $aQuery2[$t]['username'];
				$aID[$t + 1]   = $aQuery2[$t]['id'];
				if ($_SESSION['benutzer'] == $aQuery2[$t]['username']) {
						$aSelected[$t + 1] = 'selected';
				} else {
						$aSelected[$t + 1] = '';
				}
		}
		
		$aParam['_user_']     = $aUser;
		$aParam['_id_']       = $aID;
		$aParam['_selected_'] = $aSelected;
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aNr[$t]          = $aQuery[$t]['wiedervorlagen.nr'];
						$aAz[$t]          = $aQuery[$t]['aktenzeichen.aznr'] . "-" . $aQuery[$t]['aktenzeichen.azjahr'];
						$aBearbeiter[$t]  = $aQuery[$t]['users.username'];
						$aGrund[$t]       = $aQuery[$t]['wiedervorlagen.information'];
						$aTyp[$t]         = $aQuery[$t]['wvtypen.typ'];
						$aDatum[$t]       = date("d.m.Y", $aQuery[$t]['wiedervorlagen.zeitunddatum']);
						$aKrubrum[$t]     = $aQuery[$t]['akten.kurzruburm'];
						$aWVDateCount[$t] = $t;
				}
				$aParam['_nr_']          = $aNr;
				$aParam['_az_']          = $aAz;
				$aParam['_datum_']       = $aDatum;
				$aParam['_bearbeiter_']  = $aBearbeiter;
				$aParam['_grund_']       = $aGrund;
				$aParam['_typ_']         = $aTyp;
				$aParam['_krubrum_']     = $aKrubrum;
				$aParam['_wvDatecount_'] = $aWVDateCount;
		}
		
		$aWvTyp[0]   = 'Alle Typen';
		$aWvTypID[0] = '0';
		
		if (sizeof($aQuery3) != 0) {
				for ($t = 0; $t < sizeof($aQuery3); $t++) {
						$aWvTyp[$t + 1]   = $aQuery3[$t]['typ'];
						$aWvTypID[$t + 1] = $aQuery3[$t]['id'];
				}
				$aParam['_wvtyp_']   = $aWvTyp;
				$aParam['_wvtypid_'] = $aWvTypID;
		}
		
		ShowGui('wvansicht.html', $aParam);
}

// Wiedervorlagen eintragen

function AktenWV()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		
		if (isset($_POST['add'])) {
				$iWVtermin = mktime(0, 0, 0, (int) $_POST['monat'], (int) $_POST['tag'], (int) $_POST['jahr']);
				if ($iWVtermin <= date('U')) {
						$aParam['_error_']   = 'Termin muss in der Zukunft liegen !';
						$aParam['_display_'] = 'block';
				} else {
						if ($_POST['wegen'] == '') {
								$aParam['_error_']   = 'Bitte geben Sie einen WV-Grund an !';
								$aParam['_display_'] = 'block';
						} else {
								secure_sqlite_query($hDatabase, "INSERT INTO wiedervorlagen (azID,zeitunddatum,terminID,bearbeiterID,bearbeiterDone,information,status) VALUES ('" . $_SESSION['akte'] . "','" . $iWVtermin . "','" . $_POST['wvtyp'] . "','" . $_POST['bearbeiter'] . "','','" . $_POST['wegen'] . "','0')");
								Protokoll($hDatabase, "Wiedervorlage für den " . date("d.m.Y", $iWVtermin) . " wegen '" . $_POST['wegen'] . "' eingetragen");
								$aGetDate = getdate($iWVtermin);
								if ($aGetDate['weekday'] == 'Sunday' || $aGetDate['weekday'] == 'Saturday') {
										$aParam['_error_']   = '<b>ACHTUNG</b> - Termin liegt auf Wochenende!';
										$aParam['_display_'] = 'block';
								}
						}
				}
		}
		
		if (isset($_POST['done'])) {
				if ((int) ($_POST['zeile']) != 0) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT users.username AS username, wiedervorlagen.zeitunddatum AS termin, wiedervorlagen.information AS grund FROM users,wiedervorlagen WHERE users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.nr='" . (int) $_POST['zeile'] . "'");
						if ($aQuery[0]['username'] == $_SESSION['benutzer']) {
								secure_sqlite_query($hDatabase, "UPDATE wiedervorlagen SET status=1, bearbeiterID=NULL, bearbeiterDone='" . $_SESSION['benutzer'] . "' WHERE nr='" . (int) $_POST['zeile'] . "'");
								Protokoll($hDatabase, "Wiedervorlage für den " . date("d.m.Y", $aQuery[0]['termin']) . " wegen '" . $aQuery[0]['grund'] . "' als erledigt markiert.");
						} else {
								$aParam['_error_']   = "Nur der zuständige Bearbeiter darf<br>Wiedervorlagen als erledigt markieren !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = "block";
				}
		}
		
		$aParam['_nr_']         = '';
		$aParam['_datum_']      = '';
		$aParam['_bearbeiter_'] = '';
		$aParam['_grund_']      = '';
		$aParam['_typ_']        = '';
		
		$aParam['_nr1_']         = '';
		$aParam['_datum1_']      = '';
		$aParam['_bearbeiter1_'] = '';
		$aParam['_grund1_']      = '';
		$aParam['_typ1_']        = '';
		// SELECT akten.azID FROM akten LEFT JOIN aktenvita ON akten.azID=aktenvita.azID LEFT JOIN wiedervorlagen ON akten.azID=wiedervorlage
		
		// kleiner Workaround - irgendwann im Laufe der Entwicklungszeit ist aus "bearbeiterDone" "BearbeiterDone" bei der Tabellenerzeugung geworden.
		// SQLite arbeitet selbst bei den SQL Befehlen nicht case sensitiv, allerdings wird in den Abfragearrays case sensitiv wie bei Anlage
		// zurückgegeben. Daher, für Datenbanken, die BearbeiterDone oder bearbeiterDone enthalten durch AS eine allgemein gültige Zuweisung
		
		$aQuery           = secure_sqlite_array_query($hDatabase, "SELECT *, wiedervorlagen.bearbeiterDone AS bearbeiterDone FROM wiedervorlagen LEFT JOIN wvtypen ON wvtypen.id=wiedervorlagen.terminID LEFT JOIN akten ON akten.azID=wiedervorlagen.azID LEFT JOIN users ON users.id=wiedervorlagen.bearbeiterID WHERE wiedervorlagen.azID=" . $_SESSION['akte'] . " ORDER BY wiedervorlagen.zeitunddatum");
		$aQueryBearbeiter = secure_sqlite_array_query($hDatabase, "SELECT username FROM users, akten WHERE akten.bearbeiterID=users.id AND akten.azID=" . $_SESSION['akte'] . "");
		
		// $aQuery=secure_sqlite_array_query($hDatabase,"SELECT wiedervorlagen.bearbeiterDone, users.username, wvtypen.typ, wiedervorlagen.status, wiedervorlagen.zeitunddatum, wiedervorlagen.nr, wiedervorlagen.information, wiedervorlagen.azID FROM users, aktenzeichen, wvtypen, wiedervorlagen WHERE aktenzeichen.id=wiedervorlagen.azID AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID='".$_SESSION['akte']."' AND (users.id=wiedervorlagen.bearbeiterID OR bearbeiterDone!='') ORDER BY wiedervorlagen.zeitunddatum");
		$aQuery2 = secure_sqlite_array_query($hDatabase, "SELECT id, username FROM users WHERE username!='Administrator' ORDER BY username");
		$aQuery3 = secure_sqlite_array_query($hDatabase, "SELECT * FROM wvtypen");
		
		secure_sqlite_close($hDatabase);
		
		// Wiedervorlagenarten
		
		if (sizeof($aQuery3) != 0) {
				for ($t = 0; $t < sizeof($aQuery3); $t++) {
						$aId[$t]  = $aQuery3[$t]['id'];
						$aTyp[$t] = $aQuery3[$t]['typ'];
				}
				$aParam['_wvid_']  = $aId;
				$aParam['_wvtyp_'] = $aTyp;
		} else {
				$aParam['_wvid_']  = 0;
				$aParam['_wvtyp_'] = 'WV';
		}
		
		// Mögliche Bearbeiter
		
		if (sizeof($aQuery2) != 0) {
				for ($t = 0; $t < sizeof($aQuery2); $t++) {
						$aUser[$t] = $aQuery2[$t]['username'];
						$aID[$t]   = $aQuery2[$t]['id'];
						if ($aQueryBearbeiter[0]['username'] == $aQuery2[$t]['username']) {
								$aSelected[$t] = 'selected';
						} else {
								$aSelected[$t] = '';
						}
				}
				$aParam['_user_']     = $aUser;
				$aParam['_id_']       = $aID;
				$aParam['_selected_'] = $aSelected;
		}
		
		if (sizeof($aQuery) != 0) {
				$z            = 0;
				$z1           = 0;
				$aWVnr        = '';
				$aDatum       = '';
				$aBearbeiter  = '';
				$aGrund       = '';
				$aTyp         = '';
				$aWVnr1       = '';
				$aDatum1      = '';
				$aBearbeiter1 = '';
				$aGrund1      = '';
				$aTyp1        = '';
				
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						if ($aQuery[$t]['wiedervorlagen.status'] == 0) {
								$aWVnr[$z]       = $aQuery[$t]['wiedervorlagen.nr'];
								$aBearbeiter[$z] = $aQuery[$t]['users.username'];
								$aGrund[$z]      = $aQuery[$t]['wiedervorlagen.information'];
								$aTyp[$z]        = $aQuery[$t]['wvtypen.typ'];
								$aDatum[$z]      = date("d.m.Y", $aQuery[$t]['wiedervorlagen.zeitunddatum']);
								$z++;
						} else {
								$aWVnr1[$z1]       = $aQuery[$t]['wiedervorlagen.nr'];
								$aBearbeiter1[$z1] = $aQuery[$t]['bearbeiterDone'];
								$aGrund1[$z1]      = $aQuery[$t]['wiedervorlagen.information'];
								$aTyp1[$z1]        = $aQuery[$t]['wvtypen.typ'];
								$aDatum1[$z1]      = date("d.m.Y", $aQuery[$t]['wiedervorlagen.zeitunddatum']);
								$z1++;
						}
				}
				
				$aParam['_nr_']         = $aWVnr;
				$aParam['_datum_']      = $aDatum;
				$aParam['_bearbeiter_'] = $aBearbeiter;
				$aParam['_grund_']      = $aGrund;
				$aParam['_typ_']        = $aTyp;
				
				$aParam['_nr1_']         = $aWVnr1;
				$aParam['_datum1_']      = $aDatum1;
				$aParam['_bearbeiter1_'] = $aBearbeiter1;
				$aParam['_grund1_']      = $aGrund1;
				$aParam['_typ1_']        = $aTyp1;
		}
		
		ShowGui('wvadd.html', $aParam);
}

// Formatvorlagen

function Formatvorlagen()
{
		global $sDatabase;
		global $sFvpath;
		
		$hDatabase           = secure_sqlite_open($sDatabase);
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		if (isset($_POST['zeile']) && ($_POST['zeile'] != '')) {
				$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM formatvorlagen WHERE nr=" . (int) $_POST['zeile'] . "");
				if (sizeof($aQuery) != 0) {
						preg_match("/\..*$/", $aQuery[0]['filename'], $aExt);
						$sFile = $sFvpath . $aQuery[0]['filename'];
						$sName = $aQuery[0]['name'] . $aExt[0];
						if (file_exists($sFile)) {
								secure_sqlite_close($hDatabase);
								header("Content-Description: File Transfer");
								header("Content-type: application/" . $aExt[0]);
								header("Content-Disposition: attachment; filename=\"" . $sName . "\"");
								readfile($sFile);
								die;
						} else {
								$aParam['_error_']   = "Datei nicht gefunden !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Formatvorlage<br>nicht gefunden !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM formatvorlagen ORDER BY name");
		secure_sqlite_close($hDatabase);
		
		if (sizeof($aQuery) > 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aNr[$t]   = $aQuery[$t]['nr'];
						$aName[$t] = $aQuery[$t]['name'];
				}
				$aParam['_nr_']          = $aNr;
				$aParam['_bezeichnung_'] = $aName;
		} else {
				$aParam['_nr_']          = '';
				$aParam['_bezeichnung_'] = "Keine Vorlagen gespeichert !";
		}
		ShowGui('fvwahl.html', $aParam);
}

// Link aus Linkliste öffnen

function Linkliste()
{
		global $sDatabase;
		global $sFvpath;
		
		$hDatabase               = secure_sqlite_open($sDatabase);
		$aParam['_url_']         = '';
		$aParam['_bezeichnung_'] = 'Keine Links gespeichert !';
		$aParam['_nr_']          = '';
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM linkliste ORDER BY bezeichnung");
		secure_sqlite_close($hDatabase);
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aUrl[$t]  = base64_decode($aQuery[$t]['ahref']);
						$aName[$t] = $aQuery[$t]['bezeichnung'];
						$aNr[$t]   = $aQuery[$t]['nr'];
				}
				$aParam['_url_']         = $aUrl;
				$aParam['_bezeichnung_'] = $aName;
				$aParam['_nr_']          = $aNr;
		}
		
		ShowGui('linkwahl.html', $aParam);
}

// Posteingang

function Posteingang()
{
		global $sDatabase;
		$aErrorCodes = array(
				'Upload erfolgreich',
				'Die Datei ist zu groß',
				'Die Datei ist zu groß',
				'Datei konnte nur zum Teil übertragen werden !',
				'Keine Datei angegeben !',
				'Datei konnte nicht gespeichert werden !'
		);
		
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_datum_']    = '';
		$aParam['_absender_'] = '';
		$aParam['_inhalt_']   = '';
		$aParam['_error_']    = '';
		$aParam['_display_']  = 'none';
		$aParam['_typ_']      = '';
		$aParam['_status_']   = '';
		$aParam['_nr_']       = '';
		
		if (isset($_POST['open'])) {
				if ((int) $_POST['zeile'] != 0) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT dateiname,inhalt FROM posteingang WHERE nr=" . (int) $_POST['zeile'] . "");
						if (sizeof($aQuery) != 0) {
								$sFile = $_SESSION['aktenpath'] . '/IN/' . $aQuery[0]['dateiname'];
								if (file_exists($sFile)) {
										secure_sqlite_close($hDatabase);
										preg_match("/\..*$/", $aQuery[0]['dateiname'], $aExt);
										$sName = $aQuery[0]['inhalt'] . $aExt[0];
										
										header("Content-Description: File Transfer");
										header("Content-Type: application/octetstream");
										header("Content-Disposition: attachment; filename=\"" . $sName . "\"");
										header("Content-Transfer-Encoding: binary");
										header("Expires: +1m");
										header("Pragma: private");
										header("Cache-Control: private");
										
										readfile($sFile);
										die;
								} else {
										$aParam['_error_']   = "Dokument existiert nicht !";
										$aParam['_display_'] = 'block';
								}
						} else {
								$aParam['_error_']   = "Dokument existiert nicht !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Kein Dokument ausgewählt !";
						$aParam['_display_'] = 'block';
				}
		}
		
		
		if (isset($_POST['add'])) {
				if (($_POST['absender'] != '') && ($_POST['inhalt']) != '') {
						if ($_FILES['dokument']['error'] != 4) {
								// wurde Dokument zum Hinzuf ausgew ?
								if ($_FILES['dokument']['error'] == 0) {
										// File ordnungsgem hochgeladen ?
										$sName = $_POST['inhalt'];
										if (preg_match("/\..*$/", $_FILES['dokument']['name'], $aExt)) {
												$sNewFilename = date("dMYHis") . $aExt[0];
										} else {
												$sNewFilename = date("dMYHis") . '.unknown';
										}
										
										if (!file_exists($_SESSION['aktenpath'] . '/IN/')) {
												if (!mkdir($_SESSION['aktenpath'] . '/IN/', 0777)) {
														$aParam['_error_']   = 'Eingangsverzeichnis konnte nicht angelegt werden !<br>Bitte Rechte prüfen !';
														$aParam['_display_'] = 'block';
												}
										}
										
										if (file_exists($_SESSION['aktenpath'] . '/IN/')) {
												if (@move_uploaded_file($_FILES['dokument']['tmp_name'], $_SESSION['aktenpath'] . '/IN/' . $sNewFilename)) {
														secure_sqlite_query($hDatabase, "INSERT INTO posteingang (azID,datum,typ,dateiname,absender,inhalt) VALUES ('" . $_SESSION['akte'] . "','" . date("U") . "','" . $_POST['typ'] . "','" . $sNewFilename . "','" . $_POST['absender'] . "','" . $_POST['inhalt'] . "')");
														Protokoll($hDatabase, "Posteingang von Absender '" . $_POST['absender'] . "' wegen '" . $_POST['inhalt'] . "' registriert");
														$aParam['_error_']   = $aErrorCodes[0];
														$aParam['_display_'] = 'block';
												} else {
														$aParam['_error_']   = $aErrorCodes[5];
														$aParam['_display_'] = 'block';
												}
										}
								}
						} else {
								// ohne Dokument
								
								secure_sqlite_query($hDatabase, "INSERT INTO posteingang (azID,datum,typ,dateiname,absender,inhalt) VALUES ('" . $_SESSION['akte'] . "','" . date("U") . "','" . $_POST['typ'] . "',NULL,'" . $_POST['absender'] . "','" . $_POST['inhalt'] . "')");
								Protokoll($hDatabase, "Posteingang von Absender '" . $_POST['absender'] . "' wegen '" . $_POST['inhalt'] . "' registriert");
								
								
						}
				} else {
						$aParam['_error_']   = "Bitte Absender und Inhalt des Schreibens angeben !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM posteingang WHERE azID='" . $_SESSION['akte'] . "'");
		
		secure_sqlite_close($hDatabase);
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aDatum[$t]  = date("d.m.Y", $aQuery[$t]['datum']);
						$aAbs[$t]    = $aQuery[$t]['absender'];
						$aInhalt[$t] = $aQuery[$t]['inhalt'];
						$aTyp[$t]    = $aQuery[$t]['typ'];
						if ($aQuery[$t]['dateiname'] != null) {
								$aStatus[$t] = '<img src="./skin/disk.gif">';
								$aNr[$t]     = $aQuery[$t]['nr'];
						} else {
								$aStatus[$t] = '&#160;';
								$aNr[$t]     = '';
						}
				}
				$aParam['_datum_']    = $aDatum;
				$aParam['_absender_'] = $aAbs;
				$aParam['_inhalt_']   = $aInhalt;
				$aParam['_typ_']      = $aTyp;
				$aParam['_status_']   = $aStatus;
				$aParam['_nr_']       = $aNr;
		}
		
		ShowGui('posteingang.html', $aParam);
}

// Postausgang

function Postausgang()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam['_datum_']      = '';
		$aParam['_empfaenger_'] = '';
		$aParam['_inhalt_']     = '';
		$aParam['_typ_']        = '';
		$aParam['_absender_']   = '';
		$aParam['_nr_']         = '';
		$aParam['_status_']     = '';
		
		$aParam['_error_']       = '';
		$aParam['_aktenvita_']   = 'Kein Eintrag vorhanden';
		$aParam['_aktenvitaID_'] = '';
		
		$aParam['_Eintrag_']  = 'disabled';
		$aParam['_checked_']  = '';
		$aParam['_checked2_'] = 'checked';
		
		$aParam['_user_']      = '';
		$aParam['_selected2_'] = '';
		$aParam['_display_']   = 'none';
		
		if (isset($_POST['add'])) {
				if ($_POST['empfaenger'] != '') {
						if ((int) $_POST['woher'] == 1) {
								// Bezeichnung wird aus Aktenvita gewählt !
								if (($_POST['inhalt']) != '') {
										$aInhaltak = secure_sqlite_array_query($hDatabase, "SELECT beschreibung FROM aktenvita WHERE nr=" . (int) $_POST['inhalt'] . "");
										secure_sqlite_query($hDatabase, "INSERT INTO postausgang (azID,datum,empfaenger,inhalt,user,typ,aktenvitaID) VALUES ('" . $_SESSION['akte'] . "','" . date('U') . "','" . $_POST['empfaenger'] . "','" . $aInhaltak[0]['beschreibung'] . "','" . $_POST['bearbeiter'] . "','" . $_POST['typ'] . "','" . $_POST['inhalt'] . "')");
										Protokoll($hDatabase, "Postausgang an Empfänger '" . $_POST['empfaenger'] . "' wegen '" . $aInhaltak[0]['beschreibung'] . "' registriert");
										
								} else {
										$aParam['_error_']   = "Bitte geben Sie einen Inhalt des Schreibens an !";
										$aParam['_display_'] = 'block';
								}
						}
						
						else {
								if ((int) $_POST['woher'] == 2) {
										// Bezeichnung selbst eingegeben
										if ($_POST['inhalt2'] != '') {
												secure_sqlite_query($hDatabase, "INSERT INTO postausgang (azID,datum,empfaenger,inhalt,user,typ,aktenvitaID) VALUES ('" . $_SESSION['akte'] . "','" . date('U') . "','" . $_POST['empfaenger'] . "','" . $_POST['inhalt2'] . "','" . $_POST['bearbeiter'] . "','" . $_POST['typ'] . "',NULL)");
												Protokoll($hDatabase, "Postausgang an Empfänger '" . $_POST['empfaenger'] . "' wegen '" . $_POST['inhalt2'] . "' registriert");
										} else {
												$aParam['_error_']   = "Bitte Inhalt des Schreibens angeben !";
												$aParam['_display_'] = 'block';
										}
								} else {
										$aParam['_error_']   = "Undefinierte Eingabe !";
										$aParam['_display_'] = 'block';
								}
						}
				} else {
						$aParam['_error_']   = "Bitte Empfänger des Schreibens angeben !";
						$aParam['_display_'] = 'block';
				}
		}
		if (isset($_POST['oeffnen'])) {
				if ((int) $_POST['zeile'] != 0) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM aktenvita WHERE nr=" . (int) $_POST['zeile'] . "");
						if (sizeof($aQuery) != 0) {
								$sFile = $_SESSION['aktenpath'] . $aQuery[0]['dateiname'];
								if (file_exists($sFile)) {
										secure_sqlite_close($hDatabase);
										preg_match("/\..*$/", $aQuery[0]['dateiname'], $aExt);
										$sName = $aQuery[0]['beschreibung'] . $aExt[0];
										
										header("Content-Description: File Transfer");
										header("Content-Type: application/octetstream");
										header("Content-Disposition: attachment; filename=\"" . $sName . "\"");
										header("Content-Transfer-Encoding: binary");
										header("Expires: +1m");
										header("Pragma: private");
										header("Cache-Control: private");
										
										readfile($sFile);
										die;
								} else {
										$aParam['_error_']   = "Dokument existiert nicht !";
										$aParam['_display_'] = 'block';
								}
						} else {
								$aParam['_error_']   = "Dokument wurde aus Aktenvita gelöscht !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Kein Dokument ausgewählt !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT beschreibung,nr FROM aktenvita WHERE azID='" . $_SESSION['akte'] . "' AND dateiname!='' ORDER BY nr DESC");
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aDokumente[$t]   = $aQuery[$t]['beschreibung'];
						$aAktenvitaID[$t] = $aQuery[$t]['nr'];
				}
				$aParam['_aktenvita_']   = $aDokumente;
				$aParam['_aktenvitaID_'] = $aAktenvitaID;
				$aParam['_Eintrag_']     = '';
				$aParam['_checked_']     = 'checked';
				$aParam['_checked2_']    = '';
		}
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT username FROM users WHERE username!='Administrator'");
		
		if (sizeof($aQuery) != 0) {
				unset($aSelected);
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aBearbeiter[$t] = $aQuery[$t]['username'];
						if ($aBearbeiter[$t] == $_SESSION['benutzer']) {
								$aSelected[$t] = 'selected';
						} else {
								$aSelected[$t] = '';
						}
				}
				$aParam['_selected2_'] = $aSelected;
				$aParam['_user_']      = $aBearbeiter;
		}
		
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM postausgang WHERE azID='" . $_SESSION['akte'] . "'");
		
		secure_sqlite_close($hDatabase);
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aDatum[$t]    = date("d.m.Y", $aQuery[$t]['datum']);
						$aAbs[$t]      = $aQuery[$t]['empfaenger'];
						$aInhalt[$t]   = $aQuery[$t]['inhalt'];
						$aTyp[$t]      = $aQuery[$t]['typ'];
						$aAbsender[$t] = $aQuery[$t]['user'];
						if ($aQuery[$t]['aktenvitaID'] != null) {
								$aNrAktenvita[$t] = $aQuery[$t]['aktenvitaID'];
								$aStatus[$t]      = '<img src="./skin/disk.gif">';
						} else {
								$aNrAktenvita[$t] = '';
								$aStatus[$t]      = '&#160;';
						}
				}
				$aParam['_datum_']      = $aDatum;
				$aParam['_empfaenger_'] = $aAbs;
				$aParam['_inhalt_']     = $aInhalt;
				$aParam['_typ_']        = $aTyp;
				$aParam['_absender_']   = $aAbsender;
				$aParam['_nr_']         = $aNrAktenvita;
				$aParam['_status_']     = $aStatus;
		}
		
		ShowGui('postausgang.html', $aParam);
}

// Stammdaten

function Stammdaten()
{
		global $sDatabase;
		
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		if (isset($_POST['aendern'])) {
				if (($_POST['rubrum'] != '') && ($_POST['wegen'] != '')) {
						secure_sqlite_query($hDatabase, "UPDATE akten SET kurzruburm='" . $_POST['rubrum'] . "', wegen='" . $_POST['wegen'] . "', sonstiges='" . $_POST['sonst'] . "', bearbeiterID='" . $_POST['bearbeiter'] . "', rechtsgebietID='" . $_POST['rgebiet'] . "' WHERE azID='" . $_SESSION['akte'] . "'");
						Protokoll($hDatabase, "Stammdaten geändert.");
						secure_sqlite_close($hDatabase);
						unset($_POST);
						$_POST['oeffnen2'] = 1;
						$_POST['zeile']    = $_SESSION['akte'];
						unset($_SESSION['akte']);
						unset($_SESSION['aktenpath']);
						unset($_SESSION['aktenzeichen']);
						unset($_SESSION['kurzrubrum']);
						OpenAkte();
				} else {
						$aParam['_error_']   = "Bitte legen Sie Kurzrubrum und Wegen fest !";
						$aParam['_display_'] = 'block';
				}
		}
		
		if (isset($_POST['ablegen'])) {
				$aQuery = secure_sqlite_array_query($hDatabase, "SELECT nr FROM wiedervorlagen WHERE azID='" . $_SESSION['akte'] . "' AND status=0");
				if (sizeof($aQuery) == 0) {
						secure_sqlite_query($hDatabase, "UPDATE akten SET status='1' WHERE azID='" . $_SESSION['akte'] . "'");
						Protokoll($hDatabase, "Akte abgelegt.");
						secure_sqlite_close($hDatabase);
						CloseAkte();
				} else {
						$aParam['_error_']   = "Es sind für diese Akte noch Wiedervorlagen eingetragen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT bearbeiterID,rechtsgebietID,kurzruburm,wegen,sonstiges FROM akten WHERE azID='" . $_SESSION['akte'] . "'");
		
		$aParam['_krubrum_'] = $aQuery[0]['kurzruburm'];
		$aParam['_wegen_']   = $aQuery[0]['wegen'];
		$aParam['_sonst_']   = $aQuery[0]['sonstiges'];
		$iBearbeiterID       = $aQuery[0]['bearbeiterID'];
		$iRgID               = $aQuery[0]['rechtsgebietID'];
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT betrag FROM rechnungsnummer WHERE azID='" . $_SESSION['akte'] . "'");
		if (!empty($aQuery)) {
				$fGesamt = 0;
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$fGesamt = $fGesamt + floatval($aQuery[$t]['betrag']);
				}
				$aParam['_rvg_'] = number_format($fGesamt, 2, ".", ".");
		} else {
				$aParam['_rvg_'] = "0.00";
		}
		
		$aQuery  = secure_sqlite_array_query($hDatabase, "SELECT * FROM rechtsgebiete");
		$aQuery2 = secure_sqlite_array_query($hDatabase, "SELECT * FROM users WHERE username!='Administrator'");
		secure_sqlite_close($hDatabase);
		
		for ($t = 0; $t < sizeof($aQuery); $t++) {
				$aRnr[$t]  = $aQuery[$t]['id'];
				$aName[$t] = $aQuery[$t]['bezeichnung'];
				if ($iRgID == $aQuery[$t]['id']) {
						$aSelected[$t] = 'selected';
				} else {
						$aSelected[$t] = '';
				}
		}
		$aParam['_rnr_']     = $aRnr;
		$aParam['_rgebiet_'] = $aName;
		$aParam['_rgaktiv_'] = $aSelected;
		
		unset($aSelected);
		unset($aName);
		
		for ($t = 0; $t < sizeof($aQuery2); $t++) {
				$aSnr[$t]  = $aQuery2[$t]['id'];
				$aName[$t] = $aQuery2[$t]['username'];
				if ($iBearbeiterID == $aQuery2[$t]['id']) {
						$aSelected[$t] = 'selected';
				} else {
						$aSelected[$t] = '';
				}
		}
		$aParam['_snr_']    = $aSnr;
		$aParam['_user_']   = $aName;
		$aParam['_uaktiv_'] = $aSelected;
		
		ShowGui('stammdaten.html', $aParam);
}

// Kosten und Rechnungsnummer

function Kosten()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_nr_']        = '';
		$aParam['_rnr_']       = '';
		$aParam['_rnrbetrag_'] = '0.00';
		$aParam['_id_']        = '';
		$aParam['_grund_']     = '';
		$aParam['_betrag_']    = '';
		$aParam['_gesamt_']    = '0.00';
		$aParam['_error_']     = '';
		$aParam['_display_']   = 'none';
		
		if (isset($_POST['zuweisen'])) {
				$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM freieRNR");
				secure_sqlite_query($hDatabase, "UPDATE freieRNR SET nr='" . ((int) $aQuery[0]['nr'] + 1) . "'");
				secure_sqlite_query($hDatabase, "INSERT INTO rechnungsnummer(nr,jahr,azID,betrag) VALUES ('" . $aQuery[0]['nr'] . "','" . $aQuery[0]['jahr'] . "','" . $_SESSION['akte'] . "','" . floatval($_POST['rnrbetrag']) . "')");
				Protokoll($hDatabase, "Rechnungsnummer " . $aQuery[0]['jahr'] . " - " . $aQuery[0]['nr'] . " zugewiesen.");
		}
		
		if (isset($_POST['pkhzuweisen'])) {
				secure_sqlite_query($hDatabase, "INSERT INTO rechnungsnummer(nr,jahr,azID,betrag) VALUES ('0','0','" . $_SESSION['akte'] . "','" . floatval($_POST['pkhbetrag']) . "')");
				Protokoll($hDatabase, "BerH / PKH zugewiesen.");
		}
		
		
		if (isset($_POST['aendern'])) {
				if ($_POST['zeile'] != '') {
						if ($_POST['zeile'][0] == "b") {
								$iID = (int) substr($_POST['zeile'], 1);
								secure_sqlite_query($hDatabase, "UPDATE rechnungsnummer SET betrag='" . floatval($_POST[$_POST['zeile']]) . "' WHERE id='" . $iID . "'");
						} else {
								$aParam['_error_']   = "Keine Auswahl getroffen !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		if (isset($_POST['zufuegen'])) {
				if (($_POST['grund'] != '') && ($_POST['betrag'] != '')) {
						secure_sqlite_query($hDatabase, "INSERT INTO kosten(azID,datum,grund,betrag) VALUES ('" . $_SESSION['akte'] . "','" . date('U') . "','" . $_POST['grund'] . "','" . floatval($_POST['betrag']) . "')");
						Protokoll($hDatabase, "Kosten in Höhe von " . $_POST['betrag'] . " (EUR) für '" . $_POST['grund'] . "' erfasst.");
				} else {
						$aParam['_error_']   = "Bitte Betrag und Grund angeben !";
						$aParam['_display_'] = 'block';
				}
		}
		if (isset($_POST['del'])) {
				if ($_POST['zeile'] != '') {
						if ($_POST['zeile'][0] != "b") {
								secure_sqlite_query($hDatabase, "DELETE FROM kosten WHERE nr='" . (int) $_POST['zeile'] . "'");
						} else {
								$aParam['_error_']   = "Keine Auswahl getroffen !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aQuery  = secure_sqlite_array_query($hDatabase, "SELECT * FROM freieRNR");
		$aQuery2 = secure_sqlite_array_query($hDatabase, "SELECT * FROM rechnungsnummer WHERE azID='" . $_SESSION['akte'] . "'");
		$aQuery3 = secure_sqlite_array_query($hDatabase, "SELECT * FROM kosten WHERE azID='" . $_SESSION['akte'] . "'");
		secure_sqlite_close($hDatabase);
		
		$aParam['_nextrnr_'] = $aQuery[0]['jahr'] . " - " . $aQuery[0]['nr'];
		
		if (sizeof($aQuery2) != 0) {
				for ($t = 0; $t < sizeof($aQuery2); $t++) {
						if (($aQuery2[$t]['jahr'] == 0) && ($aQuery2[$t]['nr'] == 0)) {
								$aRnr[$t] = "Beratungshilfe/PKH";
						} else {
								$aRnr[$t] = $aQuery2[$t]['jahr'] . " - " . $aQuery2[$t]['nr'];
						}
						$aRnrBetrag[$t] = number_format($aQuery2[$t]['betrag'], 2, ".", "");
						$aRnrId[$t]     = $aQuery2[$t]['id'];
				}
				$aParam['_rnr_']       = $aRnr;
				$aParam['_rnrbetrag_'] = $aRnrBetrag;
				$aParam['_id_']        = $aRnrId;
		}
		
		$fGesamt = 0;
		
		if (sizeof($aQuery3) != 0) {
				for ($t = 0; $t < sizeof($aQuery3); $t++) {
						$aNr[$t]     = $aQuery3[$t]['nr'];
						$aGrund[$t]  = $aQuery3[$t]['grund'];
						$aBetrag[$t] = number_format($aQuery3[$t]['betrag'], 2, ".", ".");
						$fGesamt     = $fGesamt + $aQuery3[$t]['betrag'];
				}
				$aParam['_nr_']     = $aNr;
				$aParam['_grund_']  = $aGrund;
				$aParam['_betrag_'] = $aBetrag;
				$aParam['_gesamt_'] = number_format($fGesamt, 2, ".", ".");
		}
		
		ShowGui('kosten.html', $aParam);
}

// Beteiligte 

function Beteiligte()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_name_']         = '';
		$aParam['_adresse_']      = '';
		$aParam['_betstatus_']    = '';
		$aParam['_betadresse_']   = 'Nicht eingetragen';
		$aParam['_bettyp_']       = 'Keine Typen';
		$aParam['_betid_']        = 0;
		$aParam['_adrid_']        = 0;
		$aParam['_beteiligteid_'] = 0;
		
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		if (isset($_POST['del'])) {
				if ($_POST['zeile'] != '') {
						if ($_POST['zeile'][0] == "b") {
								$iID = (int) substr($_POST['zeile'], 1);
								secure_sqlite_query($hDatabase, "DELETE FROM beteiligte WHERE id='" . $iID . "'");
						}
						
						else {
								$aParam['_error_']   = "Bitte Beteiligten auswählen !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Bitte Beteiligten auswählen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		if (isset($_POST['add'])) {
				if (isset($_POST['betart']) && ($_POST['zeile'] != '')) {
						if ($_POST['zeile'][0] != "b") {
								// Kleine Interessenkollisionsprüfung
								// Im Kern wird geschaut, ob der hinzugefügte Beteiligte schon in anderer Beteiligungsart
								// mit einer anderen Akte verknüpft ist.
								
								$aKollision = secure_sqlite_array_query($hDatabase, "SELECT aktenzeichen.aznr AS aznr, aktenzeichen.azjahr AS azjahr, adressen.firma AS firma, adressen.name AS name FROM beteiligte, adressen, aktenzeichen WHERE beteiligte.adressenID=" . $_POST['zeile'] . " AND beteiligte.beteiligtenartID!=" . $_POST['betart'] . " AND beteiligte.azID!=" . $_SESSION['akte'] . " AND aktenzeichen.id=beteiligte.azID AND adressen.id=beteiligte.adressenID");
								if (sizeof($aKollision) != 0) {
										$aParam['_display_'] = 'block';
										$aParam['_error_']   = 'ACHTUNG !<br>Mögliche Interessenkollision !<br>Details im Aktenprotokoll vermerkt.';
										
										$sDetailInfo = "Mögliche Interessenkollision !\nBeteiligter -" . ($aKollision[0]['firma'] != "" ? " Firma: " . $aKollision[0]['firma'] : "") . ($aKollision[0]['name'] != "" ? " Name: " . $aKollision[0]['name'] : "") . " - ist mit folgenden Akten verknüpft:\n";
										for ($t = 0; $t < sizeof($aKollision); $t++) {
												$sDetailInfo = $sDetailInfo . "Aktenzeichen " . $aKollision[$t]['aznr'] . "-" . $aKollision[$t]['azjahr'] . "\n";
										}
										
										Protokoll($hDatabase, $sDetailInfo);
								}
								
								secure_sqlite_query($hDatabase, "INSERT INTO beteiligte(azID,beteiligtenartID,adressenID,ansprechpartner,telefon,aktenzeichen) VALUES('" . $_SESSION['akte'] . "','" . $_POST['betart'] . "','" . $_POST['zeile'] . "','" . $_POST['ansprechpanam'] . "','" . $_POST['ansprechpatel'] . "','" . $_POST['ansprechpazei'] . "')");
						}
						
						else {
								$aParam['_error_']   = "Bitte Adresse aus Suchergebnissen auswählen !";
								$aParam['_display_'] = 'block';
						}
				}
				
				else {
						$aParam['_error_']   = "Keine Adresse gewählt !";
						$aParam['_display_'] = 'block';
				}
		}
		
		if (isset($_POST['find'])) {
				if (($_POST['firma'] != '') || ($_POST['name'] != '') || ($_POST['vorname'])) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM adressen WHERE firma LIKE '%" . $_POST['firma'] . "%' AND name LIKE '%" . $_POST['name'] . "%' AND vorname LIKE '%" . $_POST['vorname'] . "%'");
						if (sizeof($aQuery) != 0) {
								for ($t = 0; $t < sizeof($aQuery); $t++) {
										$aAdrid[$t] = $aQuery[$t]['id'];
										
										$aName[$t] = (($aQuery[$t]['firma'] != "") ? $aQuery[$t]['firma'] . "<br/>" : "") . (($aQuery[$t]['vorname'] != "") ? $aQuery[$t]['vorname'] . " " : "") . $aQuery[$t]['name'];
										
										$aAdresse[$t] = (($aQuery[$t]['strasse1'] != "") ? $aQuery[$t]['strasse1'] . "<br/>" : "") . (($aQuery[$t]['strasse2'] != "") ? $aQuery[$t]['strasse2'] . "<br/>" : "") . (($aQuery[$t]['plz'] != "") ? $aQuery[$t]['plz'] : "") . " " . (($aQuery[$t]['ort'] != "") ? $aQuery[$t]['ort'] . "<br/><br/>" : "") . (($aQuery[$t]['telefon1'] != "") ? "Tel " . $aQuery[$t]['telefon1'] . "<br/>" : "") . (($aQuery[$t]['telefon2'] != "") ? "Tel " . $aQuery[$t]['telefon2'] . "<br/>" : "") . (($aQuery[$t]['fax'] != "") ? "Fax " . $aQuery[$t]['fax'] . "<br/>" : "") . (($aQuery[$t]['email'] != "") ? "<a href='mailto:" . $aQuery[$t]['email'] . "'>" . $aQuery[$t]['email'] . "</a>" : "");
								}
								$aParam['_name_']    = $aName;
								$aParam['_adresse_'] = $aAdresse;
								$aParam['_adrid_']   = $aAdrid;
						} else {
								$aParam['_error_']   = "Keine Adresse gefunden!";
								$aParam['_display_'] = 'block';
								
						}
				} else {
						$aParam['_error_']   = "Bitte Suchkriterien angeben !";
						$aParam['_display_'] = 'block';
				}
		}
		
		
		$aQuery  = secure_sqlite_array_query($hDatabase, "SELECT * FROM beteiligtenart");
		$aQuery2 = secure_sqlite_array_query($hDatabase, "SELECT adressen.*,beteiligtenart.arten,beteiligte.* FROM adressen,beteiligte,beteiligtenart WHERE adressen.id=beteiligte.adressenID AND beteiligte.azID='" . $_SESSION['akte'] . "' AND beteiligte.beteiligtenartID=beteiligtenart.id ORDER BY beteiligtenartID");
		secure_sqlite_close($hDatabase);
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aBetid[$t] = $aQuery[$t]['id'];
						$aArt[$t]   = $aQuery[$t]['arten'];
				}
				$aParam['_betid_']  = $aBetid;
				$aParam['_bettyp_'] = $aArt;
		}
		
		if (sizeof($aQuery2) != 0) {
				for ($t = 0; $t < sizeof($aQuery2); $t++) {
						$aId[$t]     = $aQuery2[$t]['beteiligte.id'];
						$aStatus[$t] = $aQuery2[$t]['beteiligtenart.arten'];
						
						//  $aBetadresse[$t]=$aQuery2[$t]['adressen.firma']."<br>".$aQuery2[$t]['adressen.vorname']." ".$aQuery2[$t]['adressen.name']."<br>".$aQuery2[$t]['adressen.strasse1']."<br>".$aQuery2[$t]['adressen.strasse2']."<br>".$aQuery2[$t]['adressen.plz']." ".$aQuery2[$t]['adressen.ort']."<br>Tel. ".$aQuery2[$t]['adressen.telefon1']."<br>Tel. ".$aQuery2[$t]['adressen.telefon2']."<br>Fax ".$aQuery2[$t]['adressen.fax']."<br>".$aQuery2[$t]['adressen.email'];
						//  $aBetadresse[$t]=$aBetadresse[$t]."Ansprechpartner<br>".$aQuery2[$t]['beteiligte.ansprechpartner']."<br>Tel. ".$aQuery2[$t]['beteiligte.telefon']."<br>AZ ".$aQuery2[$t]['beteiligte.aktenzeichen']."<br>";
						
						$aBetadresse[$t] = (($aQuery2[$t]['adressen.firma'] != "") ? $aQuery2[$t]['adressen.firma'] . "<br/>" : "") . (($aQuery2[$t]['adressen.vorname'] != "") ? $aQuery2[$t]['adressen.vorname'] . " " : "") . (($aQuery2[$t]['adressen.name'] != "") ? $aQuery2[$t]['adressen.name'] . "<br/>" : "<br/>") . (($aQuery2[$t]['adressen.strasse1'] != "") ? $aQuery2[$t]['adressen.strasse1'] . "<br/>" : "") . (($aQuery2[$t]['adressen.strasse2'] != "") ? $aQuery2[$t]['adressen.strasse2'] . "<br/>" : "") . (($aQuery2[$t]['adressen.plz'] != "") ? $aQuery2[$t]['adressen.plz'] : "") . " " . (($aQuery2[$t]['adressen.ort'] != "") ? $aQuery2[$t]['adressen.ort'] . "<br/>" : "<br/>") . (($aQuery2[$t]['adressen.telefon1'] != "") ? "Tel " . $aQuery2[$t]['adressen.telefon1'] . "<br/>" : "") . (($aQuery2[$t]['adressen.telefon2'] != "") ? "Tel " . $aQuery2[$t]['adressen.telefon2'] . "<br/>" : "") . (($aQuery2[$t]['adressen.fax'] != "") ? "Fax " . $aQuery2[$t]['adressen.fax'] . "<br/>" : "") . (($aQuery2[$t]['adressen.email'] != "") ? "<a href='mailto:" . $aQuery2[$t]['adressen.email'] . "'>" . $aQuery2[$t]['adressen.email'] . "</a><br/>" : "");
						$sTmpBetAdresse  = (($aQuery2[$t]['beteiligte.ansprechpartner'] != "") ? $aQuery2[$t]['beteiligte.ansprechpartner'] . "<br/>" : "") . (($aQuery2[$t]['beteiligte.telefon'] != "") ? "Kontakt " . $aQuery2[$t]['beteiligte.telefon'] . "<br/>" : "") . (($aQuery2[$t]['beteiligte.aktenzeichen'] != "") ? "Zeichen " . $aQuery2[$t]['beteiligte.aktenzeichen'] : "");
						if ($sTmpBetAdresse != "") {
								$aBetadresse[$t] = $aBetadresse[$t] . "<br/><b>Ansprechpartner</b><br/>" . $sTmpBetAdresse;
						}
				}
				
				$aParam['_beteiligteid_'] = $aId;
				$aParam['_betstatus_']    = $aStatus;
				$aParam['_betadresse_']   = $aBetadresse;
		}
		
		ShowGui('beteiligte.html', $aParam);
}


// Dokumentensuche

function DokSuche()
{
		global $sDatabase;
		global $sAktenpath;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_error_']       = '';
		$aParam['_display_']     = 'none';
		$aParam['_nr_']          = '';
		$aParam['_az_']          = '';
		$aParam['_anlagedatum_'] = '';
		$aParam['_bezeichnung_'] = '';
		$aParam['_krubrum_']     = '';
		
		// Dokument soll gesucht werden ...
		
		if (isset($_POST['suche'])) {
				if ($_POST['bezeichnung'] != '') {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, aktenvita.ersteller, aktenvita.nr,aktenzeichen.aznr,aktenzeichen.azjahr,aktenvita.eintragsdatum,aktenvita.beschreibung FROM akten,aktenvita,aktenzeichen WHERE aktenvita.beschreibung LIKE '%" . $_POST['bezeichnung'] . "%' AND aktenvita.azID=akten.azID AND aktenvita.azID=aktenzeichen.id ORDER BY aktenvita.eintragsdatum DESC");
						if (!empty($aQuery)) {
								for ($t = 0; $t < sizeof($aQuery); $t++) {
										$aNr[$t]           = $aQuery[$t]['aktenvita.nr'];
										$aAz[$t]           = $aQuery[$t]['aktenzeichen.aznr'] . "-" . $aQuery[$t]['aktenzeichen.azjahr'];
										$aBeschreibung[$t] = $aQuery[$t]['aktenvita.beschreibung'];
										$aDatum[$t]        = date("d.m.Y", $aQuery[$t]['aktenvita.eintragsdatum']);
										$aKrubrum[$t]      = $aQuery[$t]['akten.kurzruburm'];
										$aErsteller[$t]    = $aQuery[$t]['aktenvita.ersteller'];
								}
								$aParam['_nr_']          = $aNr;
								$aParam['_az_']          = $aAz;
								$aParam['_anlagedatum_'] = $aDatum;
								$aParam['_bezeichnung_'] = $aBeschreibung;
								$aParam['_krubrum_']     = $aKrubrum;
								$aParam['_ersteller_']   = $aErsteller;
						} else {
								$aParam['_error_']   = "Kein Dokument gefunden !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Kein Suchkriterium angegeben !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// gefundenes Dokument öffnen ?
		
		if (isset($_POST['oeffnen'])) {
				if ((int) $_POST['zeile'] != 0) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT aktenzeichen.aznr,aktenzeichen.azjahr,aktenvita.* FROM aktenvita,aktenzeichen WHERE aktenvita.nr=" . (int) $_POST['zeile'] . " AND aktenzeichen.id=aktenvita.azID");
						
						if (!empty($aQuery)) {
								$sFile = $sAktenpath . $aQuery[0]['aktenzeichen.azjahr'] . '/' . $aQuery[0]['aktenzeichen.aznr'] . '/' . $aQuery[0]['aktenvita.dateiname'];
								if (file_exists($sFile)) {
										secure_sqlite_close($hDatabase);
										preg_match("/\..*$/", $aQuery[0]['aktenvita.dateiname'], $aExt);
										$sName = $aQuery[0]['aktenvita.beschreibung'] . $aExt[0];
										
										header("Content-Description: File Transfer");
										header("Content-Type: application/octetstream");
										header("Content-Disposition: attachment; filename=\"" . $sName . "\"");
										header("Content-Transfer-Encoding: binary");
										header("Expires: +1m");
										header("Pragma: private");
										header("Cache-Control: private");
										readfile($sFile);
										die;
								} else {
										$aParam['_error_']   = "Dokument existiert nicht !";
										$aParam['_display_'] = 'block';
								}
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// Akte zu gefundenem Dokument öffnen ?
		
		if (isset($_POST['akteoeffnen'])) {
				if ((int) $_POST['zeile'] != 0) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT azID FROM aktenvita WHERE aktenvita.nr=" . (int) $_POST['zeile'] . "");
						$iAzID  = $aQuery[0]['azID'];
						if ($iAzID != 0) {
								$aQuery = secure_sqlite_array_query($hDatabase, "SELECT akten.status, akten.kurzruburm,akten.wegen,aktenzeichen.aznr,aktenzeichen.azjahr FROM akten,aktenzeichen WHERE akten.azID=" . $iAzID . " AND aktenzeichen.id=" . $iAzID . "");
								secure_sqlite_close($hDatabase);
								
								$_SESSION['akte']      = $iAzID;
								$_SESSION['aktenpath'] = $sAktenpath . $aQuery[0]['aktenzeichen.azjahr'] . '/' . $aQuery[0]['aktenzeichen.aznr'] . '/';
								
								$aParam['_az_']           = $aQuery[0]['aktenzeichen.aznr'] . "-" . $aQuery[0]['aktenzeichen.azjahr'];
								$_SESSION['aktenzeichen'] = $aParam['_az_'];
								$aParam['_krubrum_']      = $aQuery[0]['akten.kurzruburm'];
								$_SESSION['kurzrubrum']   = $aParam['_krubrum_'];
								$aParam['_wegen_']        = $aQuery[0]['akten.wegen'];
								unset($_POST);
								if ($aQuery[0]['akten.status'] == 0) {
										$_SESSION['aktenstatus'] = 0;
										ShowGui('akteoffen.html', $aParam);
								} else {
										$_SESSION['aktenstatus'] = 1;
										ShowGui('akteabgelegt.html', $aParam);
								}
						} else {
								$aParam['_error_']   = "Akte existiert nicht !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		secure_sqlite_close($hDatabase);
		
		ShowGui('doksuche.html', $aParam);
}

// Postbuch - Anzeige der Ein- und Ausgänge aktenübergreifend

function Postbuch()
{
		// lässt sich mit Boardmitteln nach vielen Tests ohne spezielle Arrayfunktionen wohl nicht realisieren
		
		function add_array(&$aF, $aS)
		{
				$iEndOf = sizeof($aF);
				for ($t = 0; $t < sizeof($aS); $t++) {
						$aF[$iEndOf + $t] = $aS[$t];
				}
		}
		
		// wie vor
		
		function cmp_array($aE1, $aE2)
		{
				if ($aE1['datum'] == $aE2['datum']) {
						return 0;
				} else {
						return (($aE1['datum'] > $aE2['datum']) ? -1 : 1);
				}
		}
		
		
		// main
		
		global $sDatabase;
		
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_nr_']               = '';
		$aParam['_richtung_']         = '';
		$aParam['_datum_']            = '';
		$aParam['_inhalt_']           = '';
		$aParam['_form_']             = '';
		$aParam['_az_']               = '';
		$aParam['_anzeigekriterien_'] = '';
		$aParam['_kontakt_']          = '';
		
		$aParam['_error_']   = '&nbsp;';
		$aParam['_display_'] = 'none';
		
		// Eingangsfall
		
		$iPostbuchteil = 1;
		$iTerminAnfang = mktime(0, 0, 0);
		$iTerminEnd    = mktime(23, 59, 59);
		$sQueryString  = " AND p.datum>" . $iTerminAnfang . " AND p.datum<" . $iTerminEnd;
		$sQueryString2 = $sQueryString;
		
		$sSuchanzeige = 'Gesamtes Postbuch für den ' . date("d.m.Y", $iTerminAnfang);
		
		// selektierte Akte soll geöffnet werden, wegen Einmaligkeit ID ist Struktur x_azID, x fortlaufend
		
		if (isset($_POST['oeffnen'])) {
				if ($_POST['zeile'] != '') {
						$iAzID  = (int) (substr(strrchr($_POST['zeile'], '_'), 1));
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT akten.status, akten.kurzruburm,akten.wegen,aktenzeichen.aznr,aktenzeichen.azjahr FROM akten,aktenzeichen WHERE akten.azID=" . $iAzID . " AND aktenzeichen.id=" . $iAzID);
						
						if (sizeof($aQuery) != 0) {
								unset($aParam);
								secure_sqlite_close($hDatabase);
								$_SESSION['akte']         = $iAzID;
								$_SESSION['aktenpath']    = $sAktenpath . $aQuery[0]['aktenzeichen.azjahr'] . '/' . $aQuery[0]['aktenzeichen.aznr'] . '/';
								$aParam['_az_']           = $aQuery[0]['aktenzeichen.aznr'] . "-" . $aQuery[0]['aktenzeichen.azjahr'];
								$_SESSION['aktenzeichen'] = $aParam['_az_'];
								$aParam['_krubrum_']      = $aQuery[0]['akten.kurzruburm'];
								$_SESSION['kurzrubrum']   = $aParam['_krubrum_'];
								$aParam['_wegen_']        = $aQuery[0]['akten.wegen'];
								unset($_POST);
								
								if ($aQuery[0]['akten.status'] == 0) {
										$_SESSION['aktenstatus'] = 0;
										ShowGui('akteoffen.html', $aParam);
								} else {
										$_SESSION['aktenstatus'] = 1;
										ShowGui('akteabgelegt.html', $aParam);
								}
						} else {
								$aParam['_error_']   = "Akte existiert nicht !";
								$aParam['_display_'] = 'block';
								
								// Nach Fehlermeldung soll möglichst altes Suchergebnis wieder angezeigt werden - alle Button sind teil desselben FORMs
								$_POST['aktualisiere'] = 'Aktualisieren';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
						
						// Nach Fehlermeldung soll möglichst altes Suchergebnis wieder angezeigt werden
						$_POST['aktualisiere'] = 'Aktualisieren';
				}
		}
		
		// Postbucheinträge nach Vorgaben darstellen
		
		if (isset($_POST['aktualisiere'])) {
				$iTerminAnfang = mktime(0, 0, 0, (int) $_POST['monat'], (int) $_POST['tag'], (int) $_POST['jahr']);
				
				if ($iTerminAnfang > date('U')) {
						$aParam['_error_'] = 'Termin kann nicht in der Zukunft liegen !';
						
						$aParam['_display_'] = 'block';
						
						// aktuellen Tag
						$iTerminAnfang = mktime(0, 0, 0);
						$iTerminEnd    = mktime(23, 59, 59);
				} else {
						$iTerminEnd = mktime(23, 59, 59, (int) $_POST['monat'], (int) $_POST['tag'], (int) $_POST['jahr']);
				}
				
				// konkreter Tag
				
				if ((int) $_POST['zeitraum'] == 0) {
						// taggenaue Suche - in der Datenbank wird Eintrag sekundengenau gespeichert
						$sQueryString = " AND p.datum>" . $iTerminAnfang . " AND p.datum<" . $iTerminEnd;
						$sSuchanzeige = " für den " . date("d.m.Y", $iTerminAnfang);
				} else {
						$sQueryString = " AND p.datum>" . $iTerminAnfang;
						$sSuchanzeige = " seit dem " . date("d.m.Y", $iTerminAnfang);
				}
				
				// Alternativ oder zusätzliche Einschränkung durch Adressatensuche
				
				$sQueryString2 = $sQueryString;
				
				if ($_POST['adressat'] != '') {
						// UND - (Exklusives) ODER-Suche - bei ODER ist Datum irrelevant
						if ((int) $_POST['suchen'] == 0) {
								$sQueryString = " AND ";
								$sSuchanzeige = " nach Empfänger/Absender \"" . $_POST['adressat'] . "\"";
						} else {
								$sQueryString = $sQueryString . " AND ";
								$sSuchanzeige = $sSuchanzeige . " nach Empfänger/Absender \"" . $_POST['adressat'] . "\"";
						}
						$sQueryString2 = $sQueryString . "p.absender LIKE '%" . $_POST['adressat'] . "%'";
						$sQueryString  = $sQueryString . "p.empfaenger LIKE '%" . $_POST['adressat'] . "%'";
				}
				
				$iPostbuchteil = (int) $_POST['postbuchteil'];
				switch ($iPostbuchteil) {
						case 1:
								$sSuchanzeige = "Gesamtes Postbuch " . $sSuchanzeige;
								break;
						case 2:
								$sSuchanzeige = "Postausgänge " . $sSuchanzeige;
								break;
						case 3:
								$sSuchanzeige = "Posteingänge " . $sSuchanzeige;
								break;
				}
		}
		
		
		
		// Normaler Ablauf ...  
		
		$aQuery  = array();
		$aQuery2 = array();
		
		// der Postausgang soll nur abgefragt werden, wenn NICHT nach dem Posteingang (Nr. 3) gesucht wird
		
		if ($iPostbuchteil != 3) {
				$sPraeQuery = "SELECT 'Ausgang' AS richtung, p.inhalt AS inhalt, p.datum AS datum, p.typ AS form, p.empfaenger AS kontakt, a.kurzruburm AS krubrum, az.aznr AS nr, az.azjahr AS jahr, az.id AS id FROM postausgang p, aktenzeichen az, akten a WHERE p.azID=az.id AND a.azID=az.id";
				$aQuery     = secure_sqlite_array_query($hDatabase, $sPraeQuery . $sQueryString);
		}
		
		// der Posteingang soll nur abgefragt werden, wenn NICHT nach dem Postausgang (Nr. 2) gesucht wird
		
		if ($iPostbuchteil != 2) {
				$sPraeQuery = "SELECT 'Eingang' AS richtung, p.inhalt AS inhalt, p.datum AS datum, p.typ AS form, p.absender AS kontakt, a.kurzruburm AS krubrum, az.aznr AS nr, az.azjahr AS jahr, az.id AS id FROM posteingang p, aktenzeichen az, akten a WHERE p.azID=az.id AND a.azID=az.id";
				$aQuery2    = secure_sqlite_array_query($hDatabase, $sPraeQuery . $sQueryString2);
		}
		
		secure_sqlite_close($hDatabase);
		
		// die späte Rache für zwei getrennte, unterschiedliche Tabellen Posteingang, Postausgang
		// mühsam array zusammenfügen und sortieren nach Datum der Einträge - was sekundengenau läuft ;-)
		
		add_array($aQuery, $aQuery2);
		
		usort($aQuery, 'cmp_array');
		
		if (!empty($aQuery)) {
				$t = 0;
				
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aNr[$t]       = $t . "_" . $aQuery[$t]['id'];
						$aRichtung[$t] = $aQuery[$t]['richtung'];
						$aAz[$t]       = $aQuery[$t]['nr'] . "-" . $aQuery[$t]['jahr'];
						$aInhalt[$t]   = $aQuery[$t]['inhalt'];
						$aDatum[$t]    = date("d.m.Y", $aQuery[$t]['datum']);
						$aKrubrum[$t]  = $aQuery[$t]['krubrum'];
						$aForm[$t]     = $aQuery[$t]['form'];
						$aKontakt[$t]  = $aQuery[$t]['kontakt'];
				}
				
				$aParam['_nr_']       = $aNr;
				$aParam['_az_']       = $aAz;
				$aParam['_datum_']    = $aDatum;
				$aParam['_inhalt_']   = $aInhalt;
				$aParam['_krubrum_']  = $aKrubrum;
				$aParam['_form_']     = $aForm;
				$aParam['_richtung_'] = $aRichtung;
				$aParam['_kontakt_']  = $aKontakt;
				$aParam['_krubrum_']  = $aKrubrum;
		}
		
		
		$aParam['_anzeigekriterien_'] = $sSuchanzeige;
		
		ShowGui('postbuch.html', $aParam);
}


// Kurzvermerk

function Kurzvermerk()
{
		global $sDatabase;
		
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_error_']   = '&nbsp;';
		$aParam['_display_'] = 'none';
		
		// Vermerk anlegen 
		
		if (isset($_POST['eintragen'])) {
				if (($_POST['wegen'] != '') && ($_POST['inhalt'] != '')) {
						$sFilename = date("dMYHis") . ".txt";
						$hFile     = fopen($_SESSION['aktenpath'] . $sFilename, 'w+');
						if ($hFile) {
								fputs($hFile, "Vermerk vom " . date("d.m.Y") . "\n");
								fputs($hFile, "zur Akte " . $_SESSION['aktenzeichen'] . "\n");
								fputs($hFile, "in Sachen " . $_SESSION['kurzrubrum'] . "\n");
								fputs($hFile, "von Bearbeiter " . $_SESSION['benutzer'] . "\n\n");
								fputs($hFile, "Betreff: " . $_POST['wegen'] . "\n\n");
								
								$iBytes = fputs($hFile, $_POST['inhalt']);
								fclose($hFile);
								if ($iBytes == strlen($_POST['inhalt'])) {
										secure_sqlite_query($hDatabase, "INSERT INTO aktenvita (azID,eintragsdatum,ersteller,dateiname,beschreibung) VALUES ('" . $_SESSION['akte'] . "','" . date("U") . "','" . $_SESSION['benutzer'] . "','" . $sFilename . "','" . $_POST['wegen'] . "')");
										Protokoll($hDatabase, "Kurzvermerk zu '" . $_POST['wegen'] . "' erstellt.");
										unset($aParam);
										$aParam['_error_']   = 'Vermerk erstellt.';
										$aParam['_display_'] = 'block';
								} else {
										@unlink($_SESSION['aktenpath'] . $sFilename);
										
										$aParam['_error_']   = 'Fehler beim Speichern !';
										$aParam['_display_'] = 'block';
								}
						} else {
								$aParam['_error_']   = 'Vermerk konnte nicht angelegt werden !';
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = 'Bitte Betreff und Inhalt eintragen !';
						$aParam['_display_'] = 'block';
				}
		}
		
		secure_sqlite_close($hDatabase);
		ShowGui('notiz.html', $aParam);
}

// Termine

function Termine()
{
		ShowGui('const.html', null);
}

// Akte schlie

function CloseAkte()
{
		unset($_SESSION['akte']);
		unset($_SESSION['aktenpath']);
		unset($_SESSION['aktenstatus']);
		unset($_SESSION['aktenzeichen']);
		unset($_SESSION['kurzrubrum']);
		ShowGui('closeakte.html', null);
}

// ------------------------------------ Steuerzentrale funktionenaufruf ------------------------------------------

function Adminfuncs()
{
		global $aAdminfunc;
		global $iWhichfunction;
		if (($iWhichfunction < 0) || ($iWhichfunction > (sizeof($aAdminfunc) - 1))) {
				NoFunction();
		}
		// Undefinierter Funktionenaufruf
		call_user_func($aAdminfunc[$iWhichfunction]);
}

function Userfuncs()
{
		global $aUserfunc;
		global $iWhichfunction;
		if (($iWhichfunction < 0) || ($iWhichfunction > (sizeof($aUserfunc) - 1))) {
				NoFunction();
		}
		// Undefinierter Funktionenaufruf
		call_user_func($aUserfunc[$iWhichfunction]);
}

function NoFunction()
{
		Error('Undefinierter Zustand !');
		die;
}


// Vorprüfungen bei erstmaligem Aufruf

function CheckIntegrity()
{
		global $sGuipath;
		global $sDatabase;
		
		// PHP5 
		
		$sVersion = phpversion();
		if ((int) $sVersion[0] < 5) {
				Error("Fehler: OpenLawyer\'s benötigt mindestens PHP5 !");
				die;
		}
		
		// SQlite2
		
		if (phpversion('sqlite') == '') {
				Error("Fehler: PHP-Bibliothek für SQLite fehlt !");
				die;
		}
		
		// Ohne Oberfläche läuft nichts
		
		if (!file_exists($sGuipath)) {
				Error("Fehler: Oberflächendateien (GUI) nicht verfügbar.");
				die;
		}
		
		// Existiert überhaupt eine Datenbank ? Wenn nicht, wohl erster Start
		
		if (!file_exists($sDatabase)) {
				InitDB();
		}
		
		// Datenbank existiert - geht Zugriff ?
		
		$hTestHandle = secure_sqlite_open($sDatabase, $sError);
		if ($hTestHandle == false) {
				Error("Fehler bei Datenbankzugriff: " . $sError);
				die;
		}
		secure_sqlite_close($hTestHandle);
		
		IPSperre();
}


// ------------------------------------ Hier beginnt die main() -------------------------------------------------------

// error_reporting(E_ALL);
// ini_set("display_errors", TRUE);

// für SQlite - benötigt TMP Verzeichnis mit Zugriffsrechten

putenv("TMP=" . $sTmp);

session_start();

// kommt jemand ohne Session und ohne Post ? Dann erstmaliger Aufruf, Login !

if (empty($_SESSION) && empty($_POST)) {
		CheckIntegrity();
		$aParam['_display_'] = 'none';
		$aParam['_error_']   = '';
		ShowGui('login.html', $aParam);
}

// POST-Codes werden vorher von diversen Sonderzeichen befreit ...

if (!empty($_POST)) {
		$aPost = $_POST;
		unset($_POST);
		for ($t = 0; $t < sizeof($aPost); $t++) {
				$sKey   = key($aPost);
				$sValue = current($aPost);
				if (is_string($sValue)) {
						$sValue = rtrim($sValue);
						$sValue = ltrim($sValue);
						$sValue = MakeSafe($sValue);
				}
				
				$aPost[$sKey] = $sValue;
				next($aPost);
		}
		$_POST = $aPost;
}

// kommt jemand mit einer Session ? Wenn ohne, aber mit POST, kann es nur Loginanfrage sein

if (!isset($_SESSION["benutzer"])) {
		Login($_POST);
}

// eine Session ist gesetzt ...

// stimmt die Session-IP mit der aktuellen überein ? 

if (ip2long(getenv('REMOTE_ADDR')) != $_SESSION['ipadresse']) {
		unset($_SESSION);
		session_destroy();
		$aParam['_display_'] = 'none';
		$aParam['_error_']   = '';
		ShowGui('login.html', $aParam);
}

// Session abgelaufen (mehr als 24h her) ?

if ((date('U') - $_SESSION['time']) > 86400) {
		unset($_SESSION);
		session_destroy();
		$aParam['_display_'] = 'none';
		$aParam['_error_']   = '';
		ShowGui('login.html', $aParam);
}

// okay, alles in Ordnung .. $_POST-Codes = konkrete Funktion, $_GET-Codes Funktionsaufruf / GUI-Aufruf

if (empty($_GET) && empty($_POST)) {
		ShowGui($_SESSION['panel'], null);
}

// $_GET übersandt - dient nur zum Aufruf von Funktionen aus Menuspalte und für GUIs

if (!empty($_GET)) {
		if (isset($_GET['function'])) {
				$iWhichfunction = (int) $_GET['function'];
				if ($_SESSION['benutzer'] == 'Administrator') {
						Adminfuncs();
				} else {
						Userfuncs();
				}
		}
		
		if (isset($_GET['gui'])) {
				if ($_GET['gui'] == "status.html") {
						$aParam['_benutzer_'] = $_SESSION['benutzer'];
						ShowGui(MakeSafe($_GET['gui']), $aParam);
				} else {
						ShowGui(MakeSafe($_GET['gui']), null);
				}
		}
		
		header("HTTP/1.1 404 Not Found");
		ShowGui('404.html', null);
		die;
}

// okay, es kam POST Informationen -> jede GUI übermittelt Functionscode zur Auswertung ...

if (isset($_POST['function'])) {
		$iWhichfunction = (int) $_POST['function'];
} else {
		$iWhichfunction = 0;
}

if ($_SESSION['benutzer'] == 'Administrator') {
		Adminfuncs();
} else {
		Userfuncs();
}

die;
?>
