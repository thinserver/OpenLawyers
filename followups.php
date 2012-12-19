<?php

// Arten der Wiedervorlage festlegen !

function WVTypen()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		// will jemand Einträge löschen ?
		
		if (isset($_POST['loeschen'])) {
				if (isset($_POST['eintraege'])) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT COUNT(*) FROM wvtypen");
						if ($aQuery[0]['COUNT(*)'] > sizeof($_POST['eintraege'])) {
								foreach ($_POST['eintraege'] as $iSelected) {
										$aQuery = secure_sqlite_array_query($hDatabase, "SELECT azID FROM wiedervorlagen WHERE terminID='" . (int) $iSelected . "' LIMIT 1");
										if (empty($aQuery)) {
												secure_sqlite_query($hDatabase, "DELETE FROM wvtypen WHERE id='" . (int) $iSelected . "'");
										} else {
												$aParam['_error_']   = "Wiedervorlagentyp ist einer Akte zugeordnet !";
												$aParam['_display_'] = 'block';
										}
								}
						} else {
								$aParam['_error_']   = "Es dürfen nicht alle Wiedervorlagentypen gelöscht werden !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Wählen Sie zu löschende Wiedervorlagentypen aus !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// will jemand WV-Typ hinzufügen ?
		
		if (isset($_POST['hinzufuegen'])) {
				$sGebiet = $_POST['wvname'];
				if ($sGebiet != "") {
						secure_sqlite_query($hDatabase, "INSERT INTO wvtypen (typ) VALUES ('" . $sGebiet . "')");
				} else {
						$aParam['_error_']   = "Bitte geben Sie eine Bezeichnung an !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aLogs = secure_sqlite_array_query($hDatabase, "SELECT * FROM wvtypen ORDER BY typ");
		secure_sqlite_close($hDatabase);
		
		if (!sizeof($aLogs) == 0) {
				// gibt es überhaupt Einträge ?
				for ($t = 0; $t < sizeof($aLogs); $t++) {
						$aNr[$t]      = $aLogs[$t]['id'];
						$aEintrag[$t] = $aLogs[$t]['typ'];
				}
				$aParam['_id_']    = $aNr;
				$aParam['_wvtyp_'] = $aEintrag;
				if (sizeof($aNr) > 30) {
						$aParam['_max_'] = 30;
				} else {
						$aParam['_max_'] = sizeof($aNr);
				}
		} else {
				$aParam['_id_']    = null;
				$aParam['_wvtyp_'] = 'Keine Einträge vorhanden !';
				$aParam['_max_']   = 1;
		}
		
		ShowGui('wvtypen.html', $aParam);
}