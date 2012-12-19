<?php

// Kurzvermerk

function Kurzvermerk()
{
		global $sDatabase;
		
		$hDatabase = OpenDB($sDatabase);
		
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
										SQLQuery($hDatabase, "INSERT INTO aktenvita (azID,eintragsdatum,ersteller,dateiname,beschreibung) VALUES ('" . $_SESSION['akte'] . "','" . date("U") . "','" . $_SESSION['benutzer'] . "','" . $sFilename . "','" . $_POST['wegen'] . "')");
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
		
		CloseDB($hDatabase);
		ShowGui('notiz.html', $aParam);
}