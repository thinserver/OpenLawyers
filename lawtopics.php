<?php

// Rechtgebiete bearbeiten

function RGbearbeiten()
{
		global $sDatabase;
		$hDatabase = OpenDB($sDatabase);
		
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		// will jemand Einträge löschen ?
		
		if (isset($_POST['loeschen'])) {
				if (isset($_POST['eintraege'])) {
						$aQuery = SQLArrayQuery($hDatabase, "SELECT COUNT(*) FROM rechtsgebiete");
						if ($aQuery[0]['COUNT(*)'] > sizeof($_POST['eintraege'])) {
								foreach ($_POST['eintraege'] as $iSelected) {
										$aQuery = SQLArrayQuery($hDatabase, "SELECT azID FROM akten WHERE rechtsgebietID='" . (int) $iSelected . "' LIMIT 1");
										if (empty($aQuery)) {
												SQLQuery($hDatabase, "DELETE FROM rechtsgebiete WHERE id='" . (int) $iSelected . "'");
										} else {
												$aParam['_error_']   = "Rechtsgebiet ist einer Akte zugeordnet !";
												$aParam['_display_'] = 'block';
										}
								}
						} else {
								$aParam['_error_']   = "Es dürfen nicht alle Gebiete gelöscht werden !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Wählen Sie zu löschende Rechtsgebiete aus !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// will jemand Rechtsgebiet hinzufügen ?
		
		if (isset($_POST['hinzufuegen'])) {
				$sGebiet = $_POST['gebiet'];
				if ($sGebiet != "") {
						SQLQuery($hDatabase, "INSERT INTO rechtsgebiete (bezeichnung) VALUES ('" . $sGebiet . "')");
				} else {
						$aParam['_error_']   = "Bitte geben Sie ein Rechtsgebiet an !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aLogs = SQLArrayQuery($hDatabase, "SELECT * FROM rechtsgebiete ORDER BY bezeichnung");
		CloseDB($hDatabase);
		
		if (!sizeof($aLogs) == 0) {
				// gibt es haupt  Eintr?
				for ($t = 0; $t < sizeof($aLogs); $t++) {
						$aNr[$t]      = $aLogs[$t]['id'];
						$aEintrag[$t] = $aLogs[$t]['bezeichnung'];
				}
				$aParam['_id_']     = $aNr;
				$aParam['_gebiet_'] = $aEintrag;
				if (sizeof($aNr) > 30) {
						$aParam['_max_'] = 30;
				} else {
						$aParam['_max_'] = sizeof($aNr);
				}
		} else {
				$aParam['_id_']     = null;
				$aParam['_gebiet_'] = 'Keine Einträge vorhanden !';
				$aParam['_max_']    = 1;
		}
		
		ShowGui('rgbearbeiten.html', $aParam);
}
