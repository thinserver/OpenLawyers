<?php

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
