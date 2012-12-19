<?php

// Posteingang

function Posteingang()
{
		global $sDatabase;
		$aErrorCodes = array(
				'Upload erfolgreich',
				'Die Datei ist zu groß',
				'Die Datei ist zu groß',
				'Datei konnte nur zum Teil übertragen werden !',
				'Keine Datei angegeben !',
				'Datei konnte nicht gespeichert werden !'
		);
		
		$hDatabase = OpenDB($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_datum_']    = '';
		$aParam['_absender_'] = '';
		$aParam['_inhalt_']   = '';
		$aParam['_error_']    = '';
		$aParam['_display_']  = 'none';
		$aParam['_typ_']      = '';
		$aParam['_status_']   = '';
		$aParam['_nr_']       = '';
		
		if (isset($_POST['open'])) {
				if ((int) $_POST['zeile'] != 0) {
						$aQuery = SQLArrayQuery($hDatabase, "SELECT dateiname,inhalt FROM posteingang WHERE nr=" . (int) $_POST['zeile'] . "");
						if (sizeof($aQuery) != 0) {
								$sFile = $_SESSION['aktenpath'] . '/IN/' . $aQuery[0]['dateiname'];
								if (file_exists($sFile)) {
										CloseDB($hDatabase);
										preg_match("/\..*$/", $aQuery[0]['dateiname'], $aExt);
										$sName = $aQuery[0]['inhalt'] . $aExt[0];
										
										header("Content-Description: File Transfer");
										header("Content-Type: application/octetstream");
										header("Content-Disposition: attachment; filename=\"" . $sName . "\"");
										header("Content-Transfer-Encoding: binary");
										header("Expires: +1m");
										header("Pragma: private");
										header("Cache-Control: private");
										
										readfile($sFile);
										die;
								} else {
										$aParam['_error_']   = "Dokument existiert nicht !";
										$aParam['_display_'] = 'block';
								}
						} else {
								$aParam['_error_']   = "Dokument existiert nicht !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Kein Dokument ausgewählt !";
						$aParam['_display_'] = 'block';
				}
		}
		
		
		if (isset($_POST['add'])) {
				if (($_POST['absender'] != '') && ($_POST['inhalt']) != '') {
						if ($_FILES['dokument']['error'] != 4) {
								// wurde Dokument zum Hinzuf ausgew ?
								if ($_FILES['dokument']['error'] == 0) {
										// File ordnungsgem hochgeladen ?
										$sName = $_POST['inhalt'];
										if (preg_match("/\..*$/", $_FILES['dokument']['name'], $aExt)) {
												$sNewFilename = date("dMYHis") . $aExt[0];
										} else {
												$sNewFilename = date("dMYHis") . '.unknown';
										}
										
										if (!file_exists($_SESSION['aktenpath'] . '/IN/')) {
												if (!mkdir($_SESSION['aktenpath'] . '/IN/', 0777)) {
														$aParam['_error_']   = 'Eingangsverzeichnis konnte nicht angelegt werden !<br>Bitte Rechte prüfen !';
														$aParam['_display_'] = 'block';
												}
										}
										
										if (file_exists($_SESSION['aktenpath'] . '/IN/')) {
												if (@move_uploaded_file($_FILES['dokument']['tmp_name'], $_SESSION['aktenpath'] . '/IN/' . $sNewFilename)) {
														SQLQuery($hDatabase, "INSERT INTO posteingang (azID,datum,typ,dateiname,absender,inhalt) VALUES ('" . $_SESSION['akte'] . "','" . date("U") . "','" . $_POST['typ'] . "','" . $sNewFilename . "','" . $_POST['absender'] . "','" . $_POST['inhalt'] . "')");
														Protokoll($hDatabase, "Posteingang von Absender '" . $_POST['absender'] . "' wegen '" . $_POST['inhalt'] . "' registriert");
														$aParam['_error_']   = $aErrorCodes[0];
														$aParam['_display_'] = 'block';
												} else {
														$aParam['_error_']   = $aErrorCodes[5];
														$aParam['_display_'] = 'block';
												}
										}
								}
						} else {
								// ohne Dokument
								
								SQLQuery($hDatabase, "INSERT INTO posteingang (azID,datum,typ,dateiname,absender,inhalt) VALUES ('" . $_SESSION['akte'] . "','" . date("U") . "','" . $_POST['typ'] . "',NULL,'" . $_POST['absender'] . "','" . $_POST['inhalt'] . "')");
								Protokoll($hDatabase, "Posteingang von Absender '" . $_POST['absender'] . "' wegen '" . $_POST['inhalt'] . "' registriert");
								
								
						}
				} else {
						$aParam['_error_']   = "Bitte Absender und Inhalt des Schreibens angeben !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aQuery = SQLArrayQuery($hDatabase, "SELECT * FROM posteingang WHERE azID='" . $_SESSION['akte'] . "'");
		
		CloseDB($hDatabase);
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aDatum[$t]  = date("d.m.Y", $aQuery[$t]['datum']);
						$aAbs[$t]    = $aQuery[$t]['absender'];
						$aInhalt[$t] = $aQuery[$t]['inhalt'];
						$aTyp[$t]    = $aQuery[$t]['typ'];
						if ($aQuery[$t]['dateiname'] != null) {
								$aStatus[$t] = '<img src="./skin/disk.gif">';
								$aNr[$t]     = $aQuery[$t]['nr'];
						} else {
								$aStatus[$t] = '&#160;';
								$aNr[$t]     = '';
						}
				}
				$aParam['_datum_']    = $aDatum;
				$aParam['_absender_'] = $aAbs;
				$aParam['_inhalt_']   = $aInhalt;
				$aParam['_typ_']      = $aTyp;
				$aParam['_status_']   = $aStatus;
				$aParam['_nr_']       = $aNr;
		}
		
		ShowGui('posteingang.html', $aParam);
}

// Postausgang

function Postausgang()
{
		global $sDatabase;
		$hDatabase = OpenDB($sDatabase);
		
		$aParam['_datum_']      = '';
		$aParam['_empfaenger_'] = '';
		$aParam['_inhalt_']     = '';
		$aParam['_typ_']        = '';
		$aParam['_absender_']   = '';
		$aParam['_nr_']         = '';
		$aParam['_status_']     = '';
		
		$aParam['_error_']       = '';
		$aParam['_aktenvita_']   = 'Kein Eintrag vorhanden';
		$aParam['_aktenvitaID_'] = '';
		
		$aParam['_Eintrag_']  = 'disabled';
		$aParam['_checked_']  = '';
		$aParam['_checked2_'] = 'checked';
		
		$aParam['_user_']      = '';
		$aParam['_selected2_'] = '';
		$aParam['_display_']   = 'none';
		
		if (isset($_POST['add'])) {
				if ($_POST['empfaenger'] != '') {
						if ((int) $_POST['woher'] == 1) {
								// Bezeichnung wird aus Aktenvita gewählt !
								if (($_POST['inhalt']) != '') {
										$aInhaltak = SQLArrayQuery($hDatabase, "SELECT beschreibung FROM aktenvita WHERE nr=" . (int) $_POST['inhalt'] . "");
										SQLQuery($hDatabase, "INSERT INTO postausgang (azID,datum,empfaenger,inhalt,user,typ,aktenvitaID) VALUES ('" . $_SESSION['akte'] . "','" . date('U') . "','" . $_POST['empfaenger'] . "','" . $aInhaltak[0]['beschreibung'] . "','" . $_POST['bearbeiter'] . "','" . $_POST['typ'] . "','" . $_POST['inhalt'] . "')");
										Protokoll($hDatabase, "Postausgang an Empfänger '" . $_POST['empfaenger'] . "' wegen '" . $aInhaltak[0]['beschreibung'] . "' registriert");
										
								} else {
										$aParam['_error_']   = "Bitte geben Sie einen Inhalt des Schreibens an !";
										$aParam['_display_'] = 'block';
								}
						}
						
						else {
								if ((int) $_POST['woher'] == 2) {
										// Bezeichnung selbst eingegeben
										if ($_POST['inhalt2'] != '') {
												SQLQuery($hDatabase, "INSERT INTO postausgang (azID,datum,empfaenger,inhalt,user,typ,aktenvitaID) VALUES ('" . $_SESSION['akte'] . "','" . date('U') . "','" . $_POST['empfaenger'] . "','" . $_POST['inhalt2'] . "','" . $_POST['bearbeiter'] . "','" . $_POST['typ'] . "',NULL)");
												Protokoll($hDatabase, "Postausgang an Empfänger '" . $_POST['empfaenger'] . "' wegen '" . $_POST['inhalt2'] . "' registriert");
										} else {
												$aParam['_error_']   = "Bitte Inhalt des Schreibens angeben !";
												$aParam['_display_'] = 'block';
										}
								} else {
										$aParam['_error_']   = "Undefinierte Eingabe !";
										$aParam['_display_'] = 'block';
								}
						}
				} else {
						$aParam['_error_']   = "Bitte Empfänger des Schreibens angeben !";
						$aParam['_display_'] = 'block';
				}
		}
		if (isset($_POST['oeffnen'])) {
				if ((int) $_POST['zeile'] != 0) {
						$aQuery = SQLArrayQuery($hDatabase, "SELECT * FROM aktenvita WHERE nr=" . (int) $_POST['zeile'] . "");
						if (sizeof($aQuery) != 0) {
								$sFile = $_SESSION['aktenpath'] . $aQuery[0]['dateiname'];
								if (file_exists($sFile)) {
										CloseDB($hDatabase);
										preg_match("/\..*$/", $aQuery[0]['dateiname'], $aExt);
										$sName = $aQuery[0]['beschreibung'] . $aExt[0];
										
										header("Content-Description: File Transfer");
										header("Content-Type: application/octetstream");
										header("Content-Disposition: attachment; filename=\"" . $sName . "\"");
										header("Content-Transfer-Encoding: binary");
										header("Expires: +1m");
										header("Pragma: private");
										header("Cache-Control: private");
										
										readfile($sFile);
										die;
								} else {
										$aParam['_error_']   = "Dokument existiert nicht !";
										$aParam['_display_'] = 'block';
								}
						} else {
								$aParam['_error_']   = "Dokument wurde aus Aktenvita gelöscht !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Kein Dokument ausgewählt !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aQuery = SQLArrayQuery($hDatabase, "SELECT beschreibung,nr FROM aktenvita WHERE azID='" . $_SESSION['akte'] . "' AND dateiname!='' ORDER BY nr DESC");
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aDokumente[$t]   = $aQuery[$t]['beschreibung'];
						$aAktenvitaID[$t] = $aQuery[$t]['nr'];
				}
				$aParam['_aktenvita_']   = $aDokumente;
				$aParam['_aktenvitaID_'] = $aAktenvitaID;
				$aParam['_Eintrag_']     = '';
				$aParam['_checked_']     = 'checked';
				$aParam['_checked2_']    = '';
		}
		
		$aQuery = SQLArrayQuery($hDatabase, "SELECT username FROM users WHERE username!='Administrator'");
		
		if (sizeof($aQuery) != 0) {
				unset($aSelected);
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aBearbeiter[$t] = $aQuery[$t]['username'];
						if ($aBearbeiter[$t] == $_SESSION['benutzer']) {
								$aSelected[$t] = 'selected';
						} else {
								$aSelected[$t] = '';
						}
				}
				$aParam['_selected2_'] = $aSelected;
				$aParam['_user_']      = $aBearbeiter;
		}
		
		
		$aQuery = SQLArrayQuery($hDatabase, "SELECT * FROM postausgang WHERE azID='" . $_SESSION['akte'] . "'");
		
		CloseDB($hDatabase);
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aDatum[$t]    = date("d.m.Y", $aQuery[$t]['datum']);
						$aAbs[$t]      = $aQuery[$t]['empfaenger'];
						$aInhalt[$t]   = $aQuery[$t]['inhalt'];
						$aTyp[$t]      = $aQuery[$t]['typ'];
						$aAbsender[$t] = $aQuery[$t]['user'];
						if ($aQuery[$t]['aktenvitaID'] != null) {
								$aNrAktenvita[$t] = $aQuery[$t]['aktenvitaID'];
								$aStatus[$t]      = '<img src="./skin/disk.gif">';
						} else {
								$aNrAktenvita[$t] = '';
								$aStatus[$t]      = '&#160;';
						}
				}
				$aParam['_datum_']      = $aDatum;
				$aParam['_empfaenger_'] = $aAbs;
				$aParam['_inhalt_']     = $aInhalt;
				$aParam['_typ_']        = $aTyp;
				$aParam['_absender_']   = $aAbsender;
				$aParam['_nr_']         = $aNrAktenvita;
				$aParam['_status_']     = $aStatus;
		}
		
		ShowGui('postausgang.html', $aParam);
}

// Postbuch - Anzeige der Ein- und Ausgänge aktenübergreifend

function Postbuch()
{
		// lässt sich mit Boardmitteln nach vielen Tests ohne spezielle Arrayfunktionen wohl nicht realisieren
		
		function add_array(&$aF, $aS)
		{
				$iEndOf = sizeof($aF);
				for ($t = 0; $t < sizeof($aS); $t++) {
						$aF[$iEndOf + $t] = $aS[$t];
				}
		}
		
		// wie vor
		
		function cmp_array($aE1, $aE2)
		{
				if ($aE1['datum'] == $aE2['datum']) {
						return 0;
				} else {
						return (($aE1['datum'] > $aE2['datum']) ? -1 : 1);
				}
		}
		
		
		// main
		
		global $sDatabase;
		
		$hDatabase = OpenDB($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_nr_']               = '';
		$aParam['_richtung_']         = '';
		$aParam['_datum_']            = '';
		$aParam['_inhalt_']           = '';
		$aParam['_form_']             = '';
		$aParam['_az_']               = '';
		$aParam['_anzeigekriterien_'] = '';
		$aParam['_kontakt_']          = '';
		
		$aParam['_error_']   = '&nbsp;';
		$aParam['_display_'] = 'none';
		
		// Eingangsfall
		
		$iPostbuchteil = 1;
		$iTerminAnfang = mktime(0, 0, 0);
		$iTerminEnd    = mktime(23, 59, 59);
		$sQueryString  = " AND p.datum>" . $iTerminAnfang . " AND p.datum<" . $iTerminEnd;
		$sQueryString2 = $sQueryString;
		
		$sSuchanzeige = 'Gesamtes Postbuch für den ' . date("d.m.Y", $iTerminAnfang);
		
		// selektierte Akte soll geöffnet werden, wegen Einmaligkeit ID ist Struktur x_azID, x fortlaufend
		
		if (isset($_POST['oeffnen'])) {
				if ($_POST['zeile'] != '') {
						$iAzID  = (int) (substr(strrchr($_POST['zeile'], '_'), 1));
						$aQuery = SQLArrayQuery($hDatabase, "SELECT akten.status, akten.kurzruburm,akten.wegen,aktenzeichen.aznr,aktenzeichen.azjahr FROM akten,aktenzeichen WHERE akten.azID=" . $iAzID . " AND aktenzeichen.id=" . $iAzID);
						
						if (sizeof($aQuery) != 0) {
								unset($aParam);
								CloseDB($hDatabase);
								$_SESSION['akte']         = $iAzID;
								$_SESSION['aktenpath']    = $sAktenpath . $aQuery[0]['aktenzeichen.azjahr'] . '/' . $aQuery[0]['aktenzeichen.aznr'] . '/';
								$aParam['_az_']           = $aQuery[0]['aktenzeichen.aznr'] . "-" . $aQuery[0]['aktenzeichen.azjahr'];
								$_SESSION['aktenzeichen'] = $aParam['_az_'];
								$aParam['_krubrum_']      = $aQuery[0]['akten.kurzruburm'];
								$_SESSION['kurzrubrum']   = $aParam['_krubrum_'];
								$aParam['_wegen_']        = $aQuery[0]['akten.wegen'];
								unset($_POST);
								
								if ($aQuery[0]['akten.status'] == 0) {
										$_SESSION['aktenstatus'] = 0;
										ShowGui('akteoffen.html', $aParam);
								} else {
										$_SESSION['aktenstatus'] = 1;
										ShowGui('akteabgelegt.html', $aParam);
								}
						} else {
								$aParam['_error_']   = "Akte existiert nicht !";
								$aParam['_display_'] = 'block';
								
								// Nach Fehlermeldung soll möglichst altes Suchergebnis wieder angezeigt werden - alle Button sind teil desselben FORMs
								$_POST['aktualisiere'] = 'Aktualisieren';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
						
						// Nach Fehlermeldung soll möglichst altes Suchergebnis wieder angezeigt werden
						$_POST['aktualisiere'] = 'Aktualisieren';
				}
		}
		
		// Postbucheinträge nach Vorgaben darstellen
		
		if (isset($_POST['aktualisiere'])) {
				$iTerminAnfang = mktime(0, 0, 0, (int) $_POST['monat'], (int) $_POST['tag'], (int) $_POST['jahr']);
				
				if ($iTerminAnfang > date('U')) {
						$aParam['_error_'] = 'Termin kann nicht in der Zukunft liegen !';
						
						$aParam['_display_'] = 'block';
						
						// aktuellen Tag
						$iTerminAnfang = mktime(0, 0, 0);
						$iTerminEnd    = mktime(23, 59, 59);
				} else {
						$iTerminEnd = mktime(23, 59, 59, (int) $_POST['monat'], (int) $_POST['tag'], (int) $_POST['jahr']);
				}
				
				// konkreter Tag
				
				if ((int) $_POST['zeitraum'] == 0) {
						// taggenaue Suche - in der Datenbank wird Eintrag sekundengenau gespeichert
						$sQueryString = " AND p.datum>" . $iTerminAnfang . " AND p.datum<" . $iTerminEnd;
						$sSuchanzeige = " für den " . date("d.m.Y", $iTerminAnfang);
				} else {
						$sQueryString = " AND p.datum>" . $iTerminAnfang;
						$sSuchanzeige = " seit dem " . date("d.m.Y", $iTerminAnfang);
				}
				
				// Alternativ oder zusätzliche Einschränkung durch Adressatensuche
				
				$sQueryString2 = $sQueryString;
				
				if ($_POST['adressat'] != '') {
						// UND - (Exklusives) ODER-Suche - bei ODER ist Datum irrelevant
						if ((int) $_POST['suchen'] == 0) {
								$sQueryString = " AND ";
								$sSuchanzeige = " nach Empfänger/Absender \"" . $_POST['adressat'] . "\"";
						} else {
								$sQueryString = $sQueryString . " AND ";
								$sSuchanzeige = $sSuchanzeige . " nach Empfänger/Absender \"" . $_POST['adressat'] . "\"";
						}
						$sQueryString2 = $sQueryString . "p.absender LIKE '%" . $_POST['adressat'] . "%'";
						$sQueryString  = $sQueryString . "p.empfaenger LIKE '%" . $_POST['adressat'] . "%'";
				}
				
				$iPostbuchteil = (int) $_POST['postbuchteil'];
				switch ($iPostbuchteil) {
						case 1:
								$sSuchanzeige = "Gesamtes Postbuch " . $sSuchanzeige;
								break;
						case 2:
								$sSuchanzeige = "Postausgänge " . $sSuchanzeige;
								break;
						case 3:
								$sSuchanzeige = "Posteingänge " . $sSuchanzeige;
								break;
				}
		}
		
		
		
		// Normaler Ablauf ...  
		
		$aQuery  = array();
		$aQuery2 = array();
		
		// der Postausgang soll nur abgefragt werden, wenn NICHT nach dem Posteingang (Nr. 3) gesucht wird
		
		if ($iPostbuchteil != 3) {
				$sPraeQuery = "SELECT 'Ausgang' AS richtung, p.inhalt AS inhalt, p.datum AS datum, p.typ AS form, p.empfaenger AS kontakt, a.kurzruburm AS krubrum, az.aznr AS nr, az.azjahr AS jahr, az.id AS id FROM postausgang p, aktenzeichen az, akten a WHERE p.azID=az.id AND a.azID=az.id";
				$aQuery     = SQLArrayQuery($hDatabase, $sPraeQuery . $sQueryString);
		}
		
		// der Posteingang soll nur abgefragt werden, wenn NICHT nach dem Postausgang (Nr. 2) gesucht wird
		
		if ($iPostbuchteil != 2) {
				$sPraeQuery = "SELECT 'Eingang' AS richtung, p.inhalt AS inhalt, p.datum AS datum, p.typ AS form, p.absender AS kontakt, a.kurzruburm AS krubrum, az.aznr AS nr, az.azjahr AS jahr, az.id AS id FROM posteingang p, aktenzeichen az, akten a WHERE p.azID=az.id AND a.azID=az.id";
				$aQuery2    = SQLArrayQuery($hDatabase, $sPraeQuery . $sQueryString2);
		}
		
		CloseDB($hDatabase);
		
		// die späte Rache für zwei getrennte, unterschiedliche Tabellen Posteingang, Postausgang
		// mühsam array zusammenfügen und sortieren nach Datum der Einträge - was sekundengenau läuft ;-)
		
		add_array($aQuery, $aQuery2);
		
		usort($aQuery, 'cmp_array');
		
		if (!empty($aQuery)) {
				$t = 0;
				
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aNr[$t]       = $t . "_" . $aQuery[$t]['id'];
						$aRichtung[$t] = $aQuery[$t]['richtung'];
						$aAz[$t]       = $aQuery[$t]['nr'] . "-" . $aQuery[$t]['jahr'];
						$aInhalt[$t]   = $aQuery[$t]['inhalt'];
						$aDatum[$t]    = date("d.m.Y", $aQuery[$t]['datum']);
						$aKrubrum[$t]  = $aQuery[$t]['krubrum'];
						$aForm[$t]     = $aQuery[$t]['form'];
						$aKontakt[$t]  = $aQuery[$t]['kontakt'];
				}
				
				$aParam['_nr_']       = $aNr;
				$aParam['_az_']       = $aAz;
				$aParam['_datum_']    = $aDatum;
				$aParam['_inhalt_']   = $aInhalt;
				$aParam['_krubrum_']  = $aKrubrum;
				$aParam['_form_']     = $aForm;
				$aParam['_richtung_'] = $aRichtung;
				$aParam['_kontakt_']  = $aKontakt;
				$aParam['_krubrum_']  = $aKrubrum;
		}
		
		
		$aParam['_anzeigekriterien_'] = $sSuchanzeige;
		
		ShowGui('postbuch.html', $aParam);
}
