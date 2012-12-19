<?php

// prüft, ob fehlerhafter Login des Nutzer 3 mal erfolgt ist, falls z.B. von verschiedenen IPs

function CheckIPBlock($nutzername)
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$logins = secure_sqlite_array_query($hDatabase, "SELECT nr,benutzer,zeit,ereignis FROM logfile WHERE benutzer='" . $nutzername . "' AND ereignis='Login fehlgeschlagen' ORDER BY nr DESC");
		if (sizeof($logins) > 2) {
				// letzter Login auf IP
				$zeitabstand = date("U") - $logins[0]['zeit'];
				// 15 Minuten her ?
				if (($zeitabstand / 60) < 15) {
						$zeitabstand = $logins[0]['zeit'] - $logins[2]['zeit'];
						// liegen die 3 Logins in einem Zeitfenster von 5 Minuten ?
						if (($zeitabstand / 60) < 5) {
								secure_sqlite_query($hDatabase, "INSERT INTO logfile (ipadresse,zeit,benutzer,ereignis) VALUES ('" . ip2long($ipadr) . "','" . date("U") . "','" . $nutzername . "','Nutzer-Sperre')");
								secure_sqlite_close($hDatabase);
								$aParam['_display_'] = 'block';
								$aParam['_error_']   = 'Zugriff wegen fehlgeschlagener<br>Loginversuche verweigert !';
								ShowGui('login.html', $aParam);
						}
				}
		}
		secure_sqlite_close($hDatabase);
}

// prüft vor Zugriff, ob zulässige IP oder IPSperre (3 mal fehlgeschlagener Loginversuch von einer IP-Adresse)

function IPSperre()
{
		global $sDatabase;
		// Darf von der IP-Adresse zugegriffen werden ?
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$ipadr  = getenv('REMOTE_ADDR');
		$logins = secure_sqlite_array_query($hDatabase, "SELECT ipadresse FROM security WHERE ipadresse='" . ip2long($ipadr) . "'");
		
		// ist die gewählte IP des Nutzer freigeschaltet ?
		
		if (sizeof($logins) == 0) {
				secure_sqlite_query($hDatabase, "INSERT INTO logfile (ipadresse,zeit,benutzer,ereignis) VALUES ('" . ip2long($ipadr) . "','" . date("U") . "','Unbekannt','Unzulässige IP')");
				secure_sqlite_close($hDatabase);
				header("HTTP/1.1 404 Not Found");
				ShowGui('404.html', null);
				die;
		}
		
		$logins = secure_sqlite_array_query($hDatabase, "SELECT nr,ipadresse,zeit,ereignis FROM logfile WHERE ipadresse='" . ip2long($ipadr) . "' AND ereignis='Login fehlgeschlagen' ORDER BY nr DESC");
		
		// auf der selben IP mindestens 3 fehlgeschlagene Logins ..
		
		if (sizeof($logins) > 2) {
				// letzter Login auf IP
				$zeitabstand = date("U") - $logins[0]['zeit'];
				// weniger als 15 Minuten her ?
				if (($zeitabstand / 60) < 15) {
						$zeitabstand = $logins[0]['zeit'] - $logins[2]['zeit'];
						
						// liegen die 3 Logins in einem Zeitfenster von 5 Minuten ?
						
						if (($zeitabstand / 60) < 5) {
								secure_sqlite_query($hDatabase, "INSERT INTO logfile (ipadresse,zeit,benutzer,ereignis) VALUES ('" . ip2long($ipadr) . "','" . date("U") . "','Unbekannt','IP-Sperre')");
								secure_sqlite_close($hDatabase);
								$aParam['_display_'] = 'block';
								$aParam['_error_']   = 'Zugriff wegen fehlgeschlagener<br>Loginversuche verweigert !';
								ShowGui('login.html', $aParam);
						}
				}
		}
		secure_sqlite_close($hDatabase);
}
