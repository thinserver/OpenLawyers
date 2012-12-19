<?php

// Formatvorlagen bearbeiten

function FvBearbeiten()
{
		global $sDatabase;
		global $sFvpath;
		$hDatabase           = OpenDB($sDatabase);
		$aErrorCodes         = array(
				'Upload erfolgreich',
				'Die Datei ist zu groß',
				'Die Datei ist zu groß',
				'Datei konnte nur zum Teil übertragen werden !',
				'Keine Datei angegeben !',
				'Datei konnte nicht gespeichert werden !'
		);
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		// will jemand Eintrag len ?
		
		if (isset($_POST['vorlagen'])) {
				foreach ($_POST['vorlagen'] as $iSelected) {
						$aFiles = SQLArrayQuery($hDatabase, "SELECT filename FROM formatvorlagen WHERE nr='" . (int) $iSelected . "'");
						if (sizeof($aFiles) != 0) {
								if (file_exists($sFvpath . $aFiles[0]['filename'])) {
										if (@unlink($sFvpath . $aFiles[0]['filename'])) {
												SQLQuery($hDatabase, "DELETE FROM formatvorlagen WHERE nr='" . (int) $iSelected . "'");
										} else {
												$aParam['_error_']   = 'Vorlage konnte nicht gelöscht werden !';
												$aParam['_display_'] = 'block';
										}
								} else {
										SQLQuery($hDatabase, "DELETE FROM formatvorlagen WHERE nr='" . (int) $iSelected . "'");
								}
						}
				}
		}
		
		// will jemand Formatvorlage hinzufügen ?
		
		if (isset($_POST['hinzufuegen'])) {
				// bei Fileupload wird ein Array $_FILES erzeugt, dass im KEY des Namens des <input>-Tags für die Datei Infos speichert
				if (($_POST['bezeichnung'] != "") && ($_FILES['vorlage']['error'] == 0)) {
						$sName = $_POST['bezeichnung'];
						if (preg_match("/\..*$/", $_FILES['vorlage']['name'], $aExt)) {
								$sNewFilename = date("dMYHis") . $aExt[0];
						} else {
								$sNewFilename = date("dMYHis") . '.unknown';
						}
						if (@move_uploaded_file($_FILES['vorlage']['tmp_name'], $sFvpath . $sNewFilename)) {
								SQLQuery($hDatabase, "INSERT INTO formatvorlagen (name,filename) VALUES ('" . $sName . "','" . $sNewFilename . "')");
								$aParam['_error_']   = $aErrorCodes[0];
								$aParam['_display_'] = 'block';
						} else {
								$aParam['_error_']   = $aErrorCodes[5];
								$aParam['_display_'] = 'block';
						}
				} else {
						if ($_POST['bezeichnung'] != "") {
								$aParam['_error_']   = $aErrorCodes[$_FILES['vorlage']['error']];
								$aParam['_display_'] = 'block';
						}
				}
		}
		
		$aLogs = SQLArrayQuery($hDatabase, "SELECT nr,name FROM formatvorlagen ORDER BY name");
		CloseDB($hDatabase);
		
		if (!sizeof($aLogs) == 0) {
				// gibt es überhaupt Einträge ?
				for ($t = 0; $t < sizeof($aLogs); $t++) {
						$aNr[$t]      = $aLogs[$t]['nr'];
						$aEintrag[$t] = $aLogs[$t]['name'];
				}
				$aParam['_nr_']   = $aNr;
				$aParam['_name_'] = $aEintrag;
				if (sizeof($aNr) > 20) {
						$aParam['_max_'] = 20;
				} else {
						$aParam['_max_'] = sizeof($aNr);
				}
		} else {
				$aParam['_nr_']   = null;
				$aParam['_name_'] = 'Keine Einträge vorhanden !';
				$aParam['_max_']  = 1;
		}
		ShowGui('fvbearbeiten.html', $aParam);
}
