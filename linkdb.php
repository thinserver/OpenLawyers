<?php

// Link-Datenbank

function Linklist()
{
		global $sDatabase;
		$hDatabase           = secure_sqlite_open($sDatabase);
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		// will jemand Einträge löschen ?
		
		if (isset($_POST['loeschen'])) {
				if (isset($_POST['eintraege'])) {
						foreach ($_POST['eintraege'] as $iSelected) {
								secure_sqlite_query($hDatabase, "DELETE FROM linkliste WHERE nr='" . (int) $iSelected . "'");
						}
				} else {
						$aParam['_error_']   = "Wählen Sie einen<br>Eintrag aus !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// will jemand Link hinzufügen ?
		
		if (isset($_POST['hinzufuegen'])) {
				$sBezeichnung = $_POST['bezeichnung'];
				$sLink        = $_POST['link'];
				if ($sBezeichnung != "" && $sLink != "") {
						// Protokoll entfernen
						if (preg_match('/.*:\/\/*/i', $sLink, $aMatches) == 1) {
								if (($aMatches[0] != 'http://') && ($aMatches[0] != 'https://')) {
										$sLink = "http://" . preg_replace('/.*:\/\/*/i', '', $sLink);
								}
						} else {
								$sLink = "http://" . $sLink;
						}
						
						secure_sqlite_query($hDatabase, "INSERT INTO linkliste (bezeichnung,ahref) VALUES ('" . $sBezeichnung . "','" . base64_encode($sLink) . "')");
				} else {
						$aParam['_error_']   = "Geben Sie eine URL und eine Bezeichnung an !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aLogs = secure_sqlite_array_query($hDatabase, "SELECT * FROM linkliste ORDER BY bezeichnung");
		secure_sqlite_close($hDatabase);
		
		if (sizeof($aLogs) != 0) {
				// gibt es überhaupt Einträge ?
				for ($t = 0; $t < sizeof($aLogs); $t++) {
						$aNr[$t]      = $aLogs[$t]['nr'];
						$aEintrag[$t] = $aLogs[$t]['bezeichnung'];
				}
				$aParam['_nr_']   = $aNr;
				$aParam['_link_'] = $aEintrag;
				if (sizeof($aNr) > 30) {
						$aParam['_max_'] = 30;
				} else {
						$aParam['_max_'] = sizeof($aNr);
				}
		} else {
				$aParam['_nr_']   = null;
				$aParam['_link_'] = 'Keine Einträge vorhanden !';
				$aParam['_max_']  = 0;
		}
		
		ShowGui('linkliste.html', $aParam);
}