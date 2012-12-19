<?php

// Funktion bearbeitet login

function Login($aLogindaten)
{
		global $sDatabase;
		global $sUsergui;
		global $sAdmingui;
		
		if ((!isset($aLogindaten['benutzername'])) || (!isset($aLogindaten['passwort']))) {
				$aParam['_display_'] = 'block';
				$aParam['_error_']   = 'Name und Passwort<br>eingeben !';
				ShowGui('login.html', $aParam);
		}
		if (($aLogindaten['benutzername'] == '') || ($aLogindaten['passwort'] == '')) {
				$aParam['_display_'] = 'block';
				$aParam['_error_']   = 'Name und Passwort<br>eingeben !';
				ShowGui('login.html', $aParam);
		}
		
		CheckIPBlock($aLogindaten['benutzername']);
		
		$sIPadr    = getenv('REMOTE_ADDR');
		$hDatabase = secure_sqlite_open($sDatabase);
		$aErgebnis = secure_sqlite_array_query($hDatabase, "SELECT * FROM users WHERE username='" . $aLogindaten['benutzername'] . "' AND passwort='" . MD5($aLogindaten['passwort']) . "'");
		if (sizeof($aErgebnis) == 0) {
				secure_sqlite_query($hDatabase, "INSERT INTO logfile (ipadresse,zeit,benutzer,ereignis) VALUES ('" . ip2long($sIPadr) . "','" . date("U") . "','" . $aLogindaten['benutzername'] . "','Login fehlgeschlagen')");
				secure_sqlite_close($hDatabase);
				$aParam['_display_'] = 'block';
				$aParam['_error_']   = 'Zugriff verweigert !';
				ShowGui('login.html', $aParam);
		}
		secure_sqlite_query($hDatabase, "INSERT INTO logfile (ipadresse,zeit,benutzer,ereignis) VALUES ('" . ip2long($sIPadr) . "','" . date("U") . "','" . $aLogindaten['benutzername'] . "','Eingeloggt')");
		
		if ($aLogindaten['benutzername'] == "Administrator") {
				$_SESSION['ipadresse'] = ip2long(getenv('REMOTE_ADDR'));
				$_SESSION['benutzer']  = 'Administrator';
				$_SESSION['time']      = date('U');
				$_SESSION['panel']     = 'adminpanel.html';
				$_SESSION['guipath']   = $sAdmingui;
				secure_sqlite_close($hDatabase);
				ShowGui('adminpanel.html', null);
		}
		
		$_SESSION['ipadresse'] = ip2long(getenv('REMOTE_ADDR'));
		$_SESSION['benutzer']  = $aLogindaten['benutzername'];
		$_SESSION['time']      = date('U');
		$_SESSION['panel']     = 'userpanel.html';
		$_SESSION['userID']    = $aErgebnis[0]['id'];
		$_SESSION['guipath']   = $sUsergui;
		secure_sqlite_close($hDatabase);
		ShowGui('userpanel.html', null);
		die;
}

// Abmelden ...

function Logout()
{
		global $sDatabase;
		global $aStatfiles;
		
		// Mit Statistik wurden diverse XML Dateien angelegt. Aus Sicherheitsgr�nden werden diese gel�scht.
		// Die L�schung kann nicht unmittelbar im Statistikmodul erfolgen, da bei Ende der Funktion noch nicht sichergestellt ist,
		// dass die erzeugten Dateien an den Client �bertragen wurden ...
		
		if ($_SESSION['benutzer'] == "Administrator") {
				for ($t = 0; $t < sizeof($aStatfiles); $t++) {
						@unlink($aStatfiles[$t]);
				}
		}
		
		$hDatabase = secure_sqlite_open($sDatabase);
		secure_sqlite_query($hDatabase, "INSERT INTO logfile (ipadresse,zeit,benutzer,ereignis) VALUES ('" . $_SESSION['ipadresse'] . "','" . date("U") . "','" . $_SESSION['benutzer'] . "','Ausgeloggt')");
		secure_sqlite_close($hDatabase);
		unset($_SESSION);
		session_destroy();
		$aParam['_display_'] = 'none';
		$aParam['_error_']   = '';
		ShowGui('login.html', $aParam);
}