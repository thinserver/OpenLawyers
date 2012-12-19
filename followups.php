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

// Wiedervorlagen anzeigen - aktenunabhängig

function Wiedervorlagen()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_error_']      = '';
		$aParam['_selected_']   = '';
		$aParam['_display_']    = 'none';
		$aParam['_nr_']         = '';
		$aParam['_az_']         = '';
		$aParam['_krubrum_']    = '';
		$aParam['_datum_']      = '';
		$aParam['_bearbeiter_'] = '';
		$aParam['_grund_']      = '';
		$aParam['_typ_']        = '';
		$aParam['_wvdatum_']    = date('d.m.Y');
		$aParam['_wvuser_']     = $_SESSION['benutzer'];
		$aParam['_wvtyp_']      = '';
		$aParam['_wvtypid_']    = '';
		$aParam['_whichWV_']    = 'Sämtliche Wiedervorlagenarten';
		
		$iTermin = date('U');
		
		if (isset($_POST['oeffnen'])) {
				if (isset($_POST['zeile']) && ($_POST['zeile'] != '')) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT azID FROM wiedervorlagen WHERE nr='" . (int) $_POST['zeile'] . "'");
						if (sizeof($aQuery) != 0) {
								secure_sqlite_close($hDatabase);
								unset($_POST);
								$_POST['oeffnen2'] = 1;
								$_POST['zeile']    = $aQuery[0]['azID'];
								OpenAkte();
						} else {
								$aParam['_error_']   = "Akte nicht gefunden !";
								$aParam['_display_'] = "block";
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = "block";
				}
		}
		
		
		if (isset($_POST['aktualisiere'])) {
				$iTermin = mktime(23, 59, 59, (int) $_POST['monat'], (int) $_POST['tag'], (int) $_POST['jahr']);
				if ($iTermin < date('U')) {
						$aParam['_error_']   = 'Termin muss in der Zukunft liegen !';
						$aParam['_display_'] = 'block';
						$iTermin             = date('U');
				}
				$aParam['_wvdatum_'] = date('d.m.Y', $iTermin);
				$iUser               = (int) $_POST['bearbeiter'];
				$iWvTypID            = (int) $_POST['wvtyp'];
				if (($iUser != 0) && ($iWvTypID != 0)) {
						$aQuery              = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, users.username, aktenzeichen.aznr, aktenzeichen.azjahr, wvtypen.typ, wvtypen.id, wiedervorlagen.zeitunddatum, wiedervorlagen.information, wiedervorlagen.nr FROM akten, users, aktenzeichen, wvtypen, wiedervorlagen WHERE wiedervorlagen.status=0 AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID=aktenzeichen.id AND users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.bearbeiterID='" . $iUser . "' AND wiedervorlagen.zeitunddatum<" . $iTermin . " AND wvtypen.id=" . $iWvTypID . " AND akten.azID=aktenzeichen.id ORDER BY wiedervorlagen.zeitunddatum");
						$aActuser            = secure_sqlite_array_query($hDatabase, "SELECT username FROM users WHERE id='" . $iUser . "'");
						$aActWV              = secure_sqlite_array_query($hDatabase, "SELECT typ FROM wvtypen WHERE id='" . $iWvTypID . "'");
						$aParam['_wvuser_']  = $aActuser[0]['username'];
						$aParam['_whichWV_'] = $aActWV[0]['typ'];
				} elseif ($iUser != 0) {
						$aQuery             = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, users.username, aktenzeichen.aznr, aktenzeichen.azjahr, wvtypen.typ, wiedervorlagen.zeitunddatum, wiedervorlagen.information, wiedervorlagen.nr FROM akten, users, aktenzeichen, wvtypen, wiedervorlagen WHERE wiedervorlagen.status=0 AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID=aktenzeichen.id AND users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.bearbeiterID='" . $iUser . "' AND wiedervorlagen.zeitunddatum<" . $iTermin . " AND akten.azID=aktenzeichen.id ORDER BY wiedervorlagen.zeitunddatum");
						$aActuser           = secure_sqlite_array_query($hDatabase, "SELECT username FROM users WHERE id='" . $iUser . "'");
						$aParam['_wvuser_'] = $aActuser[0]['username'];
				} elseif ($iWvTypID != 0) {
						$aQuery              = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, users.username, aktenzeichen.aznr, aktenzeichen.azjahr, wvtypen.typ, wvtypen.id, wiedervorlagen.zeitunddatum, wiedervorlagen.information, wiedervorlagen.nr FROM akten, users, aktenzeichen, wvtypen, wiedervorlagen WHERE wiedervorlagen.status=0 AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID=aktenzeichen.id AND users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.zeitunddatum<" . $iTermin . " AND wvtypen.id=" . $iWvTypID . " AND akten.azID=aktenzeichen.id ORDER BY wiedervorlagen.zeitunddatum");
						$aActWV              = secure_sqlite_array_query($hDatabase, "SELECT typ FROM wvtypen WHERE id='" . $iWvTypID . "'");
						$aParam['_wvuser_']  = "sämtliche Nutzer";
						$aParam['_whichWV_'] = $aActWV[0]['typ'];
				} else {
						$aQuery             = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, users.username, aktenzeichen.aznr, aktenzeichen.azjahr, wvtypen.typ, wiedervorlagen.zeitunddatum, wiedervorlagen.information, wiedervorlagen.nr FROM akten, users, aktenzeichen, wvtypen, wiedervorlagen WHERE wiedervorlagen.status=0 AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID=aktenzeichen.id AND users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.zeitunddatum<" . $iTermin . " AND akten.azID=aktenzeichen.id ORDER BY wiedervorlagen.zeitunddatum");
						$aParam['_wvuser_'] = "sämtliche Nutzer";
				}
		} else {
				$aQuery = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, users.username, aktenzeichen.aznr, aktenzeichen.azjahr, wvtypen.typ, wiedervorlagen.zeitunddatum, wiedervorlagen.information, wiedervorlagen.nr FROM akten, users, aktenzeichen, wvtypen, wiedervorlagen WHERE wiedervorlagen.status=0 AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID=aktenzeichen.id AND users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.zeitunddatum<" . $iTermin . " AND users.username='" . $_SESSION['benutzer'] . "' AND akten.azID=aktenzeichen.id ORDER BY wiedervorlagen.zeitunddatum");
		}
		
		$aQuery2 = secure_sqlite_array_query($hDatabase, "SELECT id, username FROM users WHERE username!='Administrator' ORDER BY username");
		$aQuery3 = secure_sqlite_array_query($hDatabase, "SELECT * FROM wvtypen ORDER BY typ");
		
		secure_sqlite_close($hDatabase);
		
		$aUser[0]     = 'Alle';
		$aID[0]       = '0';
		$aSelected[0] = '';
		
		for ($t = 0; $t < sizeof($aQuery2); $t++) {
				$aUser[$t + 1] = $aQuery2[$t]['username'];
				$aID[$t + 1]   = $aQuery2[$t]['id'];
				if ($_SESSION['benutzer'] == $aQuery2[$t]['username']) {
						$aSelected[$t + 1] = 'selected';
				} else {
						$aSelected[$t + 1] = '';
				}
		}
		
		$aParam['_user_']     = $aUser;
		$aParam['_id_']       = $aID;
		$aParam['_selected_'] = $aSelected;
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aNr[$t]          = $aQuery[$t]['wiedervorlagen.nr'];
						$aAz[$t]          = $aQuery[$t]['aktenzeichen.aznr'] . "-" . $aQuery[$t]['aktenzeichen.azjahr'];
						$aBearbeiter[$t]  = $aQuery[$t]['users.username'];
						$aGrund[$t]       = $aQuery[$t]['wiedervorlagen.information'];
						$aTyp[$t]         = $aQuery[$t]['wvtypen.typ'];
						$aDatum[$t]       = date("d.m.Y", $aQuery[$t]['wiedervorlagen.zeitunddatum']);
						$aKrubrum[$t]     = $aQuery[$t]['akten.kurzruburm'];
						$aWVDateCount[$t] = $t;
				}
				$aParam['_nr_']          = $aNr;
				$aParam['_az_']          = $aAz;
				$aParam['_datum_']       = $aDatum;
				$aParam['_bearbeiter_']  = $aBearbeiter;
				$aParam['_grund_']       = $aGrund;
				$aParam['_typ_']         = $aTyp;
				$aParam['_krubrum_']     = $aKrubrum;
				$aParam['_wvDatecount_'] = $aWVDateCount;
		}
		
		$aWvTyp[0]   = 'Alle Typen';
		$aWvTypID[0] = '0';
		
		if (sizeof($aQuery3) != 0) {
				for ($t = 0; $t < sizeof($aQuery3); $t++) {
						$aWvTyp[$t + 1]   = $aQuery3[$t]['typ'];
						$aWvTypID[$t + 1] = $aQuery3[$t]['id'];
				}
				$aParam['_wvtyp_']   = $aWvTyp;
				$aParam['_wvtypid_'] = $aWvTypID;
		}
		
		ShowGui('wvansicht.html', $aParam);
}

// Wiedervorlagen eintragen

function AktenWV()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		
		if (isset($_POST['add'])) {
				$iWVtermin = mktime(0, 0, 0, (int) $_POST['monat'], (int) $_POST['tag'], (int) $_POST['jahr']);
				if ($iWVtermin <= date('U')) {
						$aParam['_error_']   = 'Termin muss in der Zukunft liegen !';
						$aParam['_display_'] = 'block';
				} else {
						if ($_POST['wegen'] == '') {
								$aParam['_error_']   = 'Bitte geben Sie einen WV-Grund an !';
								$aParam['_display_'] = 'block';
						} else {
								secure_sqlite_query($hDatabase, "INSERT INTO wiedervorlagen (azID,zeitunddatum,terminID,bearbeiterID,bearbeiterDone,information,status) VALUES ('" . $_SESSION['akte'] . "','" . $iWVtermin . "','" . $_POST['wvtyp'] . "','" . $_POST['bearbeiter'] . "','','" . $_POST['wegen'] . "','0')");
								Protokoll($hDatabase, "Wiedervorlage für den " . date("d.m.Y", $iWVtermin) . " wegen '" . $_POST['wegen'] . "' eingetragen");
								$aGetDate = getdate($iWVtermin);
								if ($aGetDate['weekday'] == 'Sunday' || $aGetDate['weekday'] == 'Saturday') {
										$aParam['_error_']   = '<b>ACHTUNG</b> - Termin liegt auf Wochenende!';
										$aParam['_display_'] = 'block';
								}
						}
				}
		}
		
		if (isset($_POST['done'])) {
				if ((int) ($_POST['zeile']) != 0) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT users.username AS username, wiedervorlagen.zeitunddatum AS termin, wiedervorlagen.information AS grund FROM users,wiedervorlagen WHERE users.id=wiedervorlagen.bearbeiterID AND wiedervorlagen.nr='" . (int) $_POST['zeile'] . "'");
						if ($aQuery[0]['username'] == $_SESSION['benutzer']) {
								secure_sqlite_query($hDatabase, "UPDATE wiedervorlagen SET status=1, bearbeiterID=NULL, bearbeiterDone='" . $_SESSION['benutzer'] . "' WHERE nr='" . (int) $_POST['zeile'] . "'");
								Protokoll($hDatabase, "Wiedervorlage für den " . date("d.m.Y", $aQuery[0]['termin']) . " wegen '" . $aQuery[0]['grund'] . "' als erledigt markiert.");
						} else {
								$aParam['_error_']   = "Nur der zuständige Bearbeiter darf<br>Wiedervorlagen als erledigt markieren !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = "block";
				}
		}
		
		$aParam['_nr_']         = '';
		$aParam['_datum_']      = '';
		$aParam['_bearbeiter_'] = '';
		$aParam['_grund_']      = '';
		$aParam['_typ_']        = '';
		
		$aParam['_nr1_']         = '';
		$aParam['_datum1_']      = '';
		$aParam['_bearbeiter1_'] = '';
		$aParam['_grund1_']      = '';
		$aParam['_typ1_']        = '';
		// SELECT akten.azID FROM akten LEFT JOIN aktenvita ON akten.azID=aktenvita.azID LEFT JOIN wiedervorlagen ON akten.azID=wiedervorlage
		
		// kleiner Workaround - irgendwann im Laufe der Entwicklungszeit ist aus "bearbeiterDone" "BearbeiterDone" bei der Tabellenerzeugung geworden.
		// SQLite arbeitet selbst bei den SQL Befehlen nicht case sensitiv, allerdings wird in den Abfragearrays case sensitiv wie bei Anlage
		// zurückgegeben. Daher, für Datenbanken, die BearbeiterDone oder bearbeiterDone enthalten durch AS eine allgemein gültige Zuweisung
		
		$aQuery           = secure_sqlite_array_query($hDatabase, "SELECT *, wiedervorlagen.bearbeiterDone AS bearbeiterDone FROM wiedervorlagen LEFT JOIN wvtypen ON wvtypen.id=wiedervorlagen.terminID LEFT JOIN akten ON akten.azID=wiedervorlagen.azID LEFT JOIN users ON users.id=wiedervorlagen.bearbeiterID WHERE wiedervorlagen.azID=" . $_SESSION['akte'] . " ORDER BY wiedervorlagen.zeitunddatum");
		$aQueryBearbeiter = secure_sqlite_array_query($hDatabase, "SELECT username FROM users, akten WHERE akten.bearbeiterID=users.id AND akten.azID=" . $_SESSION['akte'] . "");
		
		// $aQuery=secure_sqlite_array_query($hDatabase,"SELECT wiedervorlagen.bearbeiterDone, users.username, wvtypen.typ, wiedervorlagen.status, wiedervorlagen.zeitunddatum, wiedervorlagen.nr, wiedervorlagen.information, wiedervorlagen.azID FROM users, aktenzeichen, wvtypen, wiedervorlagen WHERE aktenzeichen.id=wiedervorlagen.azID AND wiedervorlagen.terminID=wvtypen.id AND wiedervorlagen.azID='".$_SESSION['akte']."' AND (users.id=wiedervorlagen.bearbeiterID OR bearbeiterDone!='') ORDER BY wiedervorlagen.zeitunddatum");
		$aQuery2 = secure_sqlite_array_query($hDatabase, "SELECT id, username FROM users WHERE username!='Administrator' ORDER BY username");
		$aQuery3 = secure_sqlite_array_query($hDatabase, "SELECT * FROM wvtypen");
		
		secure_sqlite_close($hDatabase);
		
		// Wiedervorlagenarten
		
		if (sizeof($aQuery3) != 0) {
				for ($t = 0; $t < sizeof($aQuery3); $t++) {
						$aId[$t]  = $aQuery3[$t]['id'];
						$aTyp[$t] = $aQuery3[$t]['typ'];
				}
				$aParam['_wvid_']  = $aId;
				$aParam['_wvtyp_'] = $aTyp;
		} else {
				$aParam['_wvid_']  = 0;
				$aParam['_wvtyp_'] = 'WV';
		}
		
		// Mögliche Bearbeiter
		
		if (sizeof($aQuery2) != 0) {
				for ($t = 0; $t < sizeof($aQuery2); $t++) {
						$aUser[$t] = $aQuery2[$t]['username'];
						$aID[$t]   = $aQuery2[$t]['id'];
						if ($aQueryBearbeiter[0]['username'] == $aQuery2[$t]['username']) {
								$aSelected[$t] = 'selected';
						} else {
								$aSelected[$t] = '';
						}
				}
				$aParam['_user_']     = $aUser;
				$aParam['_id_']       = $aID;
				$aParam['_selected_'] = $aSelected;
		}
		
		if (sizeof($aQuery) != 0) {
				$z            = 0;
				$z1           = 0;
				$aWVnr        = '';
				$aDatum       = '';
				$aBearbeiter  = '';
				$aGrund       = '';
				$aTyp         = '';
				$aWVnr1       = '';
				$aDatum1      = '';
				$aBearbeiter1 = '';
				$aGrund1      = '';
				$aTyp1        = '';
				
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						if ($aQuery[$t]['wiedervorlagen.status'] == 0) {
								$aWVnr[$z]       = $aQuery[$t]['wiedervorlagen.nr'];
								$aBearbeiter[$z] = $aQuery[$t]['users.username'];
								$aGrund[$z]      = $aQuery[$t]['wiedervorlagen.information'];
								$aTyp[$z]        = $aQuery[$t]['wvtypen.typ'];
								$aDatum[$z]      = date("d.m.Y", $aQuery[$t]['wiedervorlagen.zeitunddatum']);
								$z++;
						} else {
								$aWVnr1[$z1]       = $aQuery[$t]['wiedervorlagen.nr'];
								$aBearbeiter1[$z1] = $aQuery[$t]['bearbeiterDone'];
								$aGrund1[$z1]      = $aQuery[$t]['wiedervorlagen.information'];
								$aTyp1[$z1]        = $aQuery[$t]['wvtypen.typ'];
								$aDatum1[$z1]      = date("d.m.Y", $aQuery[$t]['wiedervorlagen.zeitunddatum']);
								$z1++;
						}
				}
				
				$aParam['_nr_']         = $aWVnr;
				$aParam['_datum_']      = $aDatum;
				$aParam['_bearbeiter_'] = $aBearbeiter;
				$aParam['_grund_']      = $aGrund;
				$aParam['_typ_']        = $aTyp;
				
				$aParam['_nr1_']         = $aWVnr1;
				$aParam['_datum1_']      = $aDatum1;
				$aParam['_bearbeiter1_'] = $aBearbeiter1;
				$aParam['_grund1_']      = $aGrund1;
				$aParam['_typ1_']        = $aTyp1;
		}
		
		ShowGui('wvadd.html', $aParam);
}