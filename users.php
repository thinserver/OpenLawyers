<?php

// Benutzer bearbeiten

function Benutzer()
{
		global $sDatabase;
		$hDatabase           = secure_sqlite_open($sDatabase);
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		// will jemand Benutzer hinzuf ?
		
		if (isset($_POST['hinzufuegen'])) {
				$sUsername = $_POST['name'];
				$sPw       = $_POST['passwort'];
				$sPw2      = $_POST['passwort2'];
				if (($sUsername != "") && ($sPw != "") && ($sPw2 != "")) {
						if ($sPw == $sPw2) {
								if (strlen($sPw) > 5) {
										$aEntry = secure_sqlite_array_query($hDatabase, "SELECT username FROM users WHERE username='" . $sUsername . "'");
										if (sizeof($aEntry) == 0) {
												secure_sqlite_query($hDatabase, "INSERT INTO users (username,passwort) VALUES ('" . $sUsername . "','" . MD5($sPw) . "')");
										} else {
												$aParam['_error_']   = "Benutzer existiert bereits !";
												$aParam['_display_'] = 'block';
										}
								} else {
										$aParam['_error_']   = "Passwort muss mindestens aus 6 Zeichen bestehen !";
										$aParam['_display_'] = 'block';
								}
						} else {
								$aParam['_error_']   = "Passwörter sind nicht identisch !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Bitte Nutzernamen und Passwort eingeben !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// will jemand Benutzer löschen ?
		
		if (isset($_POST['loeschen'])) {
				if (isset($_POST['user'])) {
						$iUser = (int) $_POST['user'];
						if ($iUser != 1) {
								$aQuery = secure_sqlite_array_query($hDatabase, "SELECT akten.azID FROM akten LEFT JOIN wiedervorlagen ON akten.azID=wiedervorlagen.azID WHERE ((akten.bearbeiterID='" . $iUser . "') OR (wiedervorlagen.bearbeiterID='" . $iUser . "')) LIMIT 1");
								
								if (empty($aQuery)) {
										secure_sqlite_query($hDatabase, "DELETE FROM users WHERE id='" . $iUser . "'");
								} else {
										$aParam['_error_']   = "Benutzer ist einer Akte oder aktiven Wiedervorlagen zugeordnet !";
										$aParam['_display_'] = 'block';
								}
						} else {
								$aParam['_error_']   = "Administrator kann nicht gelöscht werden !";
								$aParam['_display_'] = 'block';
						}
				}
		}
		
		//   will jemand Passwort für einen Nutzer ändern ?              
		
		if (isset($_POST['aendern'])) {
				if (isset($_POST['user'])) {
						$sPw  = $_POST['passwortneu'];
						$sPw2 = $_POST['passwortneu2'];
						
						if ($sPw == $sPw2) {
								if (strlen($sPw) > 5) {
										secure_sqlite_query($hDatabase, "UPDATE users SET passwort='" . MD5($sPw) . "' WHERE id='" . (int) $_POST['user'] . "'");
								} else {
										$aParam['_error_']   = "Passwort muss mindestens aus 6 Zeichen bestehen !";
										$aParam['_display_'] = 'block';
								}
						} else {
								$aParam['_error_']   = "Passwörter sind nicht identisch !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Bitte wählen Sie einen Nutzer aus der Liste aus !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// liefert ein Array - jeder Array Eintrag ist wieder ein Array/Hashtable mit den Zeileneinträgen
		
		$aLogs = secure_sqlite_array_query($hDatabase, "SELECT id,username FROM users ORDER BY username");
		secure_sqlite_close($hDatabase);
		
		if (!sizeof($aLogs) == 0) {
				// gibt es haupt  Eintr?
				for ($t = 0; $t < sizeof($aLogs); $t++) {
						$aNr[$t]      = $aLogs[$t]['id'];
						$aEintrag[$t] = $aLogs[$t]['username'];
				}
				$aParam['_id_']       = $aNr;
				$aParam['_username_'] = $aEintrag;
		}
		
		ShowGui('user.html', $aParam);
}
