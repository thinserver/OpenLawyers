<?php

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