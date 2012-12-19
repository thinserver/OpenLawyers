<?php

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
				SQLQuery($hDatabase, "INSERT INTO aktenvita (azID,eintragsdatum,ersteller,dateiname,beschreibung) VALUES ('" . $_SESSION['akte'] . "','" . date("U") . "','System','protokoll.txt','Aktenprotokoll')");
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
				
				SQLQuery($hDatabase, "INSERT INTO logfile (ipadresse,zeit,benutzer,ereignis) VALUES ('" . $_SESSION['ipadresse'] . "','" . date("U") . "','" . $_SESSION['benutzer'] . "','Akte " . $_SESSION['aktenzeichen'] . ": Protokolldatei öffnen/anlegen gescheitert')");
				
		}
		
}