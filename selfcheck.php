<?php

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
/*		if (phpversion('sqlite') == '') {
				Error("Fehler: PHP-Bibliothek für SQLite fehlt !");
				die;
		}
*/		
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
