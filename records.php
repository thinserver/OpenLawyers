<?php

// Aktenzeichen (Start) ändern

function AZfestlegen()
{
		global $sDatabase;
		$hDatabase           = secure_sqlite_open($sDatabase);
		$aParam['_error_']   = "";
		$aParam['_display_'] = "none";
		
		// neues Aktenzeichen festlegen ?
		
		if (isset($_POST['azfestlegen'])) {
				if (preg_match("/[^0-9]/", $_POST['azstartnr'])) {
						$aParam['_error_']   = "Aktenzeichen darf nur aus Ziffern bestehen !";
						$aParam['_display_'] = 'block';
				} else {
						if (preg_match("/[^0-9]/", $_POST['azjahr'])) {
								$aParam['_error_']   = "Aktenzeichen darf nur aus Ziffern bestehen !";
								$aParam['_display_'] = 'block';
						} else {
								if ($_POST['azstartnr'] != '' && $_POST['azjahr'] != '') {
										// fragt zunächst ab, ob das gewählte START-Aktenzeichen schon existiert oder ob es 
										// vor bestehenden Aktenzeichen liegt - wegen des Fortlaufens unzulässig
										
										$aGet = secure_sqlite_array_query($hDatabase, "SELECT aznr,azjahr FROM aktenzeichen WHERE aznr>=" . $_POST['azstartnr'] . " AND azjahr=" . $_POST['azjahr']);
										if (sizeof($aGet) != 0) {
												$aParam['_error_']   = "Neues (Start-)Aktenzeichen muss bestehenden Aktenzeichen folgen !";
												$aParam['_display_'] = 'block';
										} else {
												secure_sqlite_query($hDatabase, "UPDATE freiesAZ SET aznr=" . $_POST['azstartnr'] . ", azjahr=" . $_POST['azjahr']);
										}
								} else {
										$aParam['_error_']   = "Sie müssen Startnummer und Jahr angeben !";
										$aParam['_display_'] = 'block';
								}
						}
				}
		}
		
		// neue Rechnungsnummer festlegen ?
		
		if (isset($_POST['rnrfestlegen'])) {
				if (preg_match("/[^0-9]/", $_POST['rnrstartnr'])) {
						$aParam['_error_']   = "Rechnungsnummer darf nur aus Ziffern bestehen !";
						$aParam['_display_'] = 'block';
				} else {
						if (preg_match("/[^0-9]/", $_POST['rnrjahr'])) {
								$aParam['_error_']   = "Rechnungsnummer darf nur aus Ziffern bestehen !";
								$aParam['_display_'] = 'block';
						} else {
								if ($_POST['rnrstartnr'] != '' && $_POST['rnrjahr'] != '') {
										// fragt zunächst ab, ob das gewählte START-Rechnungsnummer schon existiert oder ob es 
										// vor bestehenden Rechnungsnummern liegt - wegen des Fortlaufens unzulässig
										
										$aGet = secure_sqlite_array_query($hDatabase, "SELECT nr,jahr FROM rechnungsnummer WHERE nr>=" . $_POST['rnrstartnr'] . " AND jahr=" . $_POST['rnrjahr']);
										if (sizeof($aGet) != 0) {
												$aParam['_error_']   = "Neue (Start-)Rechnungsnummer muss bestehenden Rechnungsnummern folgen !";
												$aParam['_display_'] = 'block';
										} else {
												secure_sqlite_query($hDatabase, "UPDATE freieRNR SET nr=" . $_POST['rnrstartnr'] . ", jahr=" . $_POST['rnrjahr']);
										}
								} else {
										$aParam['_error_']   = "Sie müssen Startnummer und Jahr angeben !";
										$aParam['_display_'] = 'block';
								}
						}
				}
		}
		
		$aAzs    = secure_sqlite_array_query($hDatabase, "SELECT * FROM aktenzeichen");
		$aRnrn   = secure_sqlite_array_query($hDatabase, "SELECT * FROM rechnungsnummer");
		$aAktaz  = secure_sqlite_array_query($hDatabase, "SELECT * FROM freiesAZ");
		$aAktrnr = secure_sqlite_array_query($hDatabase, "SELECT * FROM freieRNR");
		secure_sqlite_close($hDatabase);
		
		if (sizeof($aAzs) != 0) {
				// gibt es überhaupt Einträge ?
				for ($t = 0; $t < sizeof($aAzs); $t++) {
						$aAz[$t] = $aAzs[$t]['aznr'] . '-' . $aAzs[$t]['azjahr'];
				}
				$aParam['_alleaz_'] = $aAz;
				if (sizeof($aAz) > 20) {
						$aParam['_max_'] = 20;
				} else {
						$aParam['_max_'] = sizeof($aAz);
				}
		} else {
				$aParam['_alleaz_'] = "Keine Akte";
				$aParam['_max_']    = 1;
		}
		
		if (sizeof($aRnrn) != 0) {
				// gibt es haupt  Eintr?
				for ($t = 0; $t < sizeof($aRnrn); $t++) {
						$aRnr[$t] = $aRnrn[$t]['jahr'] . '-' . $aRnrn[$t]['nr'];
				}
				$aParam['_allernr_'] = $aRnr;
				if (sizeof($aRnr) > 20) {
						$aParam['_max2_'] = 20;
				} else {
						$aParam['_max2_'] = sizeof($aRnr);
				}
		} else {
				$aParam['_max2_']    = 1;
				$aParam['_allernr_'] = "Keine Rechnungsnummer";
		}
		
		$aParam['_az_']  = $aAktaz[0]['aznr'] . '-' . $aAktaz[0]['azjahr'];
		$aParam['_rnr_'] = $aAktrnr[0]['jahr'] . '-' . $aAktrnr[0]['nr'];
		ShowGui('azrnr.html', $aParam);
}

// Akte oeffnen

function OpenAkte()
{
		global $sDatabase;
		global $sAktenpath;
		
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_error_']       = '';
		$aParam['_display_']     = 'none';
		$aParam['_azid_']        = '';
		$aParam['_az_']          = '';
		$aParam['_anlagedatum_'] = '';
		$aParam['_bearbeiter_']  = '';
		$aParam['_krubrum_']     = '';
		$aParam['_status_']      = '';
		
		// jemand hat AZ eingeben
		
		if (isset($_POST['oeffnen1'])) {
				if (($_POST['aznr'] != '') && ($_POST['azjahr'] != '')) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT id,aznr,azjahr FROM aktenzeichen WHERE aznr=" . (int) $_POST['aznr'] . " AND azjahr=" . (int) $_POST['azjahr'] . "");
						if (sizeof($aQuery) != 0) {
								$_SESSION['akte'] = $aQuery[0]['id'];
								$aQuery           = secure_sqlite_array_query($hDatabase, "SELECT kurzruburm,wegen,status FROM akten WHERE azID=" . $aQuery[0]['id'] . "");
								secure_sqlite_close($hDatabase);
								$_SESSION['aktenpath'] = $sAktenpath . (int) $_POST['azjahr'] . '/' . (int) $_POST['aznr'] . '/';
								
								$aParam['_az_']           = (int) $_POST['aznr'] . "-" . (int) $_POST['azjahr'];
								$_SESSION['aktenzeichen'] = $aParam['_az_'];
								$aParam['_krubrum_']      = $aQuery[0]['kurzruburm'];
								$_SESSION['kurzrubrum']   = $aParam['_krubrum_'];
								$aParam['_wegen_']        = $aQuery[0]['wegen'];
								unset($_POST);
								if ($aQuery[0]['status'] == 0) {
										$_SESSION['aktenstatus'] = 0;
										ShowGui('akteoffen.html', $aParam);
								} else {
										$_SESSION['aktenstatus'] = 1;
										ShowGui('akteabgelegt.html', $aParam);
								}
						} else {
								$aParam['_error_']   = "Keine Akte gefunden !";
								$aParam['_display_'] = 'block';
						}
						secure_sqlite_close($hDatabase);
				} else {
						$aParam['_error_']   = "Ungültiges Aktenzeichen !";
						$aParam['_display_'] = "block";
				}
		}
		// jemand will Akte suchen
		
		if (isset($_POST['suchen'])) {
				if (($_POST['krubrum'] != '') || ($_POST['firma'] != '') || ($_POST['name'] != '') || ($_POST['wegen'] != '')) {
						unset($strFind);
						unset($aPost);
						$count = 0;
						
						if ($_POST['krubrum'] != '') {
								$aPost[$count] = "akten.kurzruburm LIKE '%" . $_POST['krubrum'] . "%'";
								$count++;
						}
						if ($_POST['name'] != '') {
								$aPost[$count] = "(adressen.name LIKE '%" . $_POST['name'] . "%' OR adressen.vorname LIKE '%" . $_POST['name'] . "%')";
								$count++;
						}
						if ($_POST['firma'] != '') {
								$aPost[$count] = "adressen.firma LIKE '%" . $_POST['firma'] . "%'";
								$count++;
						}
						if ($_POST['wegen'] != '') {
								$aPost[$count] = "akten.wegen LIKE '%" . $_POST['wegen'] . "%'";
						}
						$strFind = join(' AND ', $aPost);
						
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT DISTINCT users.username, akten.kurzruburm, akten.status, akten.anlagedatum, akten.azID, aktenzeichen.aznr, aktenzeichen.azjahr FROM akten, aktenzeichen, users LEFT JOIN beteiligte ON akten.azID=beteiligte.azID LEFT JOIN adressen ON beteiligte.adressenID=adressen.id WHERE akten.azID=aktenzeichen.id AND users.id=akten.bearbeiterID AND " . $strFind . " ORDER BY akten.anlagedatum DESC");
						
						if (!empty($aQuery)) {
								for ($t = 0; $t < sizeof($aQuery); $t++) {
										$aID[$t]         = $aQuery[$t]['akten.azID'];
										$aAz[$t]         = $aQuery[$t]['aktenzeichen.aznr'] . "-" . $aQuery[$t]['aktenzeichen.azjahr'];
										$aDatum[$t]      = date("d.m.Y", $aQuery[$t]['akten.anlagedatum']);
										$aBearbeiter[$t] = $aQuery[$t]['users.username'];
										$aRubrum[$t]     = $aQuery[$t]['akten.kurzruburm'];
										if ($aQuery[$t]['akten.status'] == 0) {
												$aStat[$t] = 'Aktiv';
										} else {
												$aStat[$t] = 'Abgelegt';
										}
								}
								
								$aParam['_azid_']        = $aID;
								$aParam['_az_']          = $aAz;
								$aParam['_anlagedatum_'] = $aDatum;
								$aParam['_bearbeiter_']  = $aBearbeiter;
								$aParam['_krubrum_']     = $aRubrum;
								$aParam['_status_']      = $aStat;
						} else {
								$aParam['_error_']   = "Keine Akte gefunden !";
								$aParam['_display_'] = "block";
						}
						secure_sqlite_close($hDatabase);
				} else {
						$aParam['_error_']   = "Keine Suchkriterien angegeben !";
						$aParam['_display_'] = "block";
				}
		}
		
		// Aus Liste wurde etwas gewählt
		
		if (isset($_POST['oeffnen2'])) {
				if ($_POST['zeile'] != '') {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT akten.status, akten.kurzruburm,akten.wegen,aktenzeichen.aznr,aktenzeichen.azjahr FROM akten,aktenzeichen WHERE akten.azID=" . $_POST['zeile'] . " AND aktenzeichen.id=" . $_POST['zeile'] . "");
						secure_sqlite_close($hDatabase);
						
						$_SESSION['akte']      = $_POST['zeile'];
						$_SESSION['aktenpath'] = $sAktenpath . $aQuery[0]['aktenzeichen.azjahr'] . '/' . $aQuery[0]['aktenzeichen.aznr'] . '/';
						
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
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = "block";
				}
		}
		
		ShowGui('openakte.html', $aParam);
}

// Akte anlegen

function CreateAkte()
{
		global $sDatabase;
		global $sAktenpath;
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_error_']   = "";
		$aParam['_display_'] = 'none';
		
		if (isset($_POST['anlegen'])) {
				// Jetzt soll Akte angelegt werden -> machen wirs ! 
				if (($_POST['rubrum'] != '') && ($_POST['wegen'] != '')) {
						$hDatabase = secure_sqlite_open($sDatabase);
						$aQuery    = secure_sqlite_array_query($hDatabase, "SELECT aznr,azjahr FROM freiesAZ");
						$iNextNr   = (int) $aQuery[0]['aznr'] + 1;
						secure_sqlite_query($hDatabase, "UPDATE freiesAZ SET aznr=" . $iNextNr . "");
						$iAznr   = (int) $aQuery[0]['aznr'];
						$iAzjahr = (int) $aQuery[0]['azjahr'];
						secure_sqlite_query($hDatabase, "INSERT INTO aktenzeichen (aznr,azjahr) VALUES (" . (int) $aQuery[0]['aznr'] . "," . (int) $aQuery[0]['azjahr'] . ")");
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT id FROM aktenzeichen WHERE aznr=" . (int) $aQuery[0]['aznr'] . " AND azjahr=" . (int) $aQuery[0]['azjahr'] . "");
						secure_sqlite_query($hDatabase, "INSERT INTO akten (azID,anlagedatum,kurzruburm,wegen,sonstiges,rechtsgebietID,bearbeiterID,status) VALUES (" . $aQuery[0]['id'] . "," . date('U') . ",'" . $_POST['rubrum'] . "','" . $_POST['wegen'] . "','" . $_POST['sonst'] . "','" . $_POST['rgebiet'] . "','" . $_POST['bearbeiter'] . "','0')");
						
						
						$sPath = $iAzjahr . '/' . $iAznr;
						if (!file_exists($sAktenpath . $iAzjahr)) {
								if (!@mkdir($sAktenpath . $iAzjahr, 0777)) {
										Error('Verzeichnis konnte nicht angelegt werden, bitte Rechte prüfen !');
										die;
								}
						}
						if (!file_exists($sAktenpath . $sPath)) {
								if (!@mkdir($sAktenpath . $sPath, 0777)) {
										Error('Verzeichnis konnte nicht angelegt werden, bitte Rechte prüfen !');
										die;
								}
						}
						$_SESSION['aktenpath'] = $sAktenpath . $sPath . '/';
						$_SESSION['akte']      = $aQuery[0]['id'];
						
						$aParam['_az_']           = $iAznr . "-" . $iAzjahr;
						$_SESSION['aktenzeichen'] = $aParam['_az_'];
						$aParam['_krubrum_']      = $_POST['rubrum'];
						$_SESSION['kurzrubrum']   = $aParam['_krubrum_'];
						$aParam['_wegen_']        = $_POST['wegen'];
						
						// Aktenanlage wird protokolliert
						
						$sProtokollrecord = "Akte " . $_SESSION['aktenzeichen'] . " angelegt. \n" . "Kurzrubrum '" . $_SESSION['kurzrubrum'] . "'\n" . "wegen '" . $aParam['_wegen_'] . "'\n" . "Sonstiges '" . $_POST['sonst'] . "'";
						
						Protokoll($hDatabase, $sProtokollrecord);
						
						secure_sqlite_close($hDatabase);
						ShowGui('akteoffen.html', $aParam);
				} else {
						$aParam['_error_']   = "Bitte füllen Sie Kurzrubrum und Wegen aus !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$hDatabase        = secure_sqlite_open($sDatabase);
		$aQuery           = secure_sqlite_array_query($hDatabase, "SELECT aznr,azjahr FROM freiesAZ");
		$aParam['_az_']   = $aQuery[0]['aznr'] . "-" . $aQuery[0]['azjahr'];
		$aParam['_date_'] = date('d. M Y');
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM rechtsgebiete ORDER BY bezeichnung");
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aNr[$t]   = $aQuery[$t]['id'];
						$aWhat[$t] = $aQuery[$t]['bezeichnung'];
				}
				$aParam['_rnr_']     = $aNr;
				$aParam['_rgebiet_'] = $aWhat;
		} else {
				$aParam['_rnr_']     = null;
				$aParam['_rgebiet_'] = "Keine Gebiete";
		}
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT id,username FROM users WHERE username!='Administrator' ORDER BY username");
		secure_sqlite_close($hDatabase);
		
		unset($aNr);
		unset($aWhat);
		unset($aSelected);
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aNr[$t]   = $aQuery[$t]['id'];
						$aWhat[$t] = $aQuery[$t]['username'];
						if ($_SESSION['benutzer'] == $aQuery[$t]['username']) {
								$aSelected[$t] = 'selected';
						} else {
								$aSelected[$t] = '';
						}
				}
				$aParam['_snr_']      = $aNr;
				$aParam['_user_']     = $aWhat;
				$aParam['_selected_'] = $aSelected;
		} else {
				$aParam['_snr_']  = null;
				$aParam['_user_'] = "Keine Bearbeiter";
		}
		
		ShowGui('akteanlegen.html', $aParam);
}

// Handaktenbogen ...

function AktenBogen()
{
		// nur für diesen speziellen Fall um die vielen Alternativen zu vereinheitlichen
		
		function NormAdress($aS)
		{
				$sB = (($aS['adressen.firma'] != "") ? $aS['adressen.firma'] . "<br/>" : "") . (($aS['adressen.vorname'] != "") ? $aS['adressen.vorname'] . " " : "") . (($aS['adressen.name'] != "") ? $aS['adressen.name'] . "<br/>" : "<br/>") . (($aS['adressen.strasse1'] != "") ? $aS['adressen.strasse1'] . "<br/>" : "") . (($aS['adressen.strasse2'] != "") ? $aS['adressen.strasse2'] . "<br/>" : "") . (($aS['adressen.plz'] != "") ? $aS['adressen.plz'] : "") . " " . (($aS['adressen.ort'] != "") ? $aS['adressen.ort'] . "<br/>" : "<br/>") . (($aS['adressen.telefon1'] != "") ? "<br/>Tel " . $aS['adressen.telefon1'] . "<br/>" : "<br/>") . (($aS['adressen.telefon2'] != "") ? "Tel " . $aS['adressen.telefon2'] . "<br/>" : "") . (($aS['adressen.fax'] != "") ? "Fax " . $aS['adressen.fax'] . "<br/>" : "") . (($aS['adressen.email'] != "") ? "<a href='mailto:" . $aS['adressen.email'] . "'>" . $aS['adressen.email'] . "</a><br/>" : "");
				
				// Aus den Kombinationen von mehreren Einträgen auf einer Zeile - Vorname, Name z.B. - können sich
				// überflüssige Linebreaks ergeben; die schnellste Lösung für alle Möglichkeiten ist, diese mit regulären Ausdrücken
				// kurzerhand zu beseitigen ..
				
				$sB = preg_replace('/(<br\/>\s*){2,}/', '<br/>', $sB);
				
				$sT = (($aS['beteiligte.ansprechpartner'] != "") ? $aS['beteiligte.ansprechpartner'] . "<br/>" : "") . (($aS['beteiligte.telefon'] != "") ? "Kontakt " . $aS['beteiligte.telefon'] . "<br/>" : "") . (($aS['beteiligte.aktenzeichen'] != "") ? "Zeichen " . $aS['beteiligte.aktenzeichen'] : "");
				
				if ($sT != "") {
						$sB = $sB . "<br/><b>Ansprechpartner</b><br/>" . $sT;
				}
				
				return $sB;
		}
		
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam['_az_']         = '';
		$aParam['_krubrum_']    = '';
		$aParam['_datum_']      = '';
		$aParam['_wegen_']      = '';
		$aParam['_sonst_']      = '';
		$aParam['_bearbeiter_'] = '';
		$aParam['_rgebiet_']    = '';
		$aParam['_mandant_']    = 'Nicht eingetragen';
		$aParam['_gegner_']     = 'Nicht eingetragen';
		$aParam['_gegnerra_']   = 'Nicht eingetragen';
		$aParam['_beteiligte_'] = 'Nicht eingetragen';
		$aParam['_betstatus_']  = '';
		
		
		$aQuery  = secure_sqlite_array_query($hDatabase, "SELECT * FROM akten, aktenzeichen, users, rechtsgebiete WHERE akten.azID='" . $_SESSION['akte'] . "' AND akten.azID=aktenzeichen.id AND akten.rechtsgebietID=rechtsgebiete.id AND akten.bearbeiterID=users.id");
		$aQuery2 = secure_sqlite_array_query($hDatabase, "SELECT adressen.*,beteiligtenart.arten,beteiligte.* FROM adressen,beteiligte,beteiligtenart WHERE adressen.id=beteiligte.adressenID AND beteiligte.azID='" . $_SESSION['akte'] . "' AND beteiligte.beteiligtenartID=beteiligtenart.id ORDER BY beteiligtenartID");
		
		secure_sqlite_close($hDatabase);
		
		if (!empty($aQuery)) {
				$aParam['_az_']         = $aQuery[0]['aktenzeichen.aznr'] . "-" . $aQuery[0]['aktenzeichen.azjahr'];
				$aParam['_datum_']      = date("d. M Y", $aQuery[0]['akten.anlagedatum']);
				$aParam['_krubrum_']    = $aQuery[0]['akten.kurzruburm'];
				$aParam['_wegen_']      = $aQuery[0]['akten.wegen'];
				$aParam['_sonst_']      = $aQuery[0]['akten.sonstiges'];
				$aParam['_bearbeiter_'] = $aQuery[0]['users.username'];
				$aParam['_rgebiet_']    = $aQuery[0]['rechtsgebiete.bezeichnung'];
		}
		
		if (!empty($aQuery2)) {
				$z  = 0;
				$z1 = 0;
				$z2 = 0;
				$z3 = 0;
				
				$aBetadresse[0] = 'Nicht eingetragen';
				$aStatus[0]     = '';
				$aMandant[0]    = 'Nicht eingetragen';
				$aGegner[0]     = 'Nicht eingetragen';
				$aGegnerra[0]   = 'Nicht eingetragen';
				
				for ($t = 0; $t < sizeof($aQuery2); $t++) {
						switch ($aQuery2[$t]['beteiligtenart.arten']) {
								case "Mandant":
										$aMandant[$z1] = NormAdress($aQuery2[$t]);
										$z1++;
										break;
								case "Gegner":
										$aGegner[$z2] = NormAdress($aQuery2[$t]);
										$z2++;
										break;
								case "Gegner RA":
										$aGegnerra[$z3] = NormAdress($aQuery2[$t]);
										$z3++;
										break;
								default:
										$aBetadresse[$z] = NormAdress($aQuery2[$t]);
										$aStatus[$z]     = $aQuery2[$t]['beteiligtenart.arten'];
										$z++;
										break;
						}
				}
				
				$aParam['_beteiligte_'] = $aBetadresse;
				$aParam['_betstatus_']  = $aStatus;
				$aParam['_mandant_']    = $aMandant;
				$aParam['_gegner_']     = $aGegner;
				$aParam['_gegnerra_']   = $aGegnerra;
		}
		
		
		ShowGui('aktenbogen.html', $aParam);
}

// Adressen eingeben & ändern & suchen 

function Adressen()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		$aParam['_kontakt_'] = '';
		$aParam['_name_']    = '';
		$aParam['_nr_']      = '';
		$aParam['_fds_']     = '';
		
		if (!empty($_POST)) {
				$aAdresse['firma']    = $_POST['firma'];
				$aAdresse['name']     = $_POST['name'];
				$aAdresse['vorname']  = $_POST['vorname'];
				$aAdresse['strasse1'] = $_POST['str1'];
				$aAdresse['strasse2'] = $_POST['str2'];
				$aAdresse['plz']      = $_POST['plz'];
				$aAdresse['ort']      = $_POST['ort'];
				$aAdresse['telefon1'] = $_POST['tel1'];
				$aAdresse['telefon2'] = $_POST['tel2'];
				$aAdresse['fax']      = $_POST['fax'];
				$aAdresse['email']    = $_POST['email'];
		}
		
		// Adresse hinzufügen ?
		
		if (isset($_POST['addadress'])) {
				if (($aAdresse['firma'] != '') || ($aAdresse['name'] != '')) {
						// Adressen auf Doppeleingabe prüfen und ggf. warnen !
						
						$bDoppelflag = 0;
						$aDoppelt    = secure_sqlite_array_query($hDatabase, "SELECT * FROM adressen WHERE (firma='" . $aAdresse['firma'] . "' AND firma!='')  OR (name='" . $aAdresse['name'] . "' AND name!='')");
						
						if (sizeof($aDoppelt) != 0) {
								$bDoppelflag = 1;
								
								$aParam['_display_'] = 'block';
								$aParam['_error_']   = 'ACHTUNG !<br><br>Ein ähnlicher Eintrag<br>existiert bereits !';
						}
						
						
						$sKeys   = "(";
						$sValues = "('";
						for ($t = 0; $t < 10; $t++) {
								$sKeys   = $sKeys . key($aAdresse) . ",";
								$sValues = $sValues . current($aAdresse) . "','";
								next($aAdresse);
						}
						$sKeys   = $sKeys . key($aAdresse) . ")";
						$sValues = $sValues . current($aAdresse) . "')";
						
						secure_sqlite_query($hDatabase, "INSERT INTO adressen " . $sKeys . " VALUES " . $sValues . "");
						
						// Anzeige aller vergleichbaren Einträge
						
						if ($bDoppelflag == 1) {
								unset($aDoppelt);
								$aDoppelt = secure_sqlite_array_query($hDatabase, "SELECT * FROM adressen WHERE (firma='" . $aAdresse['firma'] . "' AND firma!='')  OR (name='" . $aAdresse['name'] . "' AND name!='')");
								for ($t = 0; $t < sizeof($aDoppelt); $t++) {
										$aAdrid[$t] = $aDoppelt[$t]['id'];
										$aFirma[$t] = (($aDoppelt[$t]['firma'] != "") ? $aDoppelt[$t]['firma'] : "&nbsp;");
										$aName[$t]  = $aDoppelt[$t]['vorname'] . "&nbsp;" . $aDoppelt[$t]['name'];
										
										$aAdr[$t]     = (($aDoppelt[$t]['strasse1'] != "") ? $aDoppelt[$t]['strasse1'] . "<br/>" : "") . (($aDoppelt[$t]['strasse2'] != "") ? $aDoppelt[$t]['strasse2'] . "<br/>" : "") . $aDoppelt[$t]['plz'] . "&nbsp;" . $aDoppelt[$t]['ort'];
										$aKontakt[$t] = (($aDoppelt[$t]['telefon1'] != "") ? "Tel " . $aDoppelt[$t]['telefon1'] . "<br/>" : "") . (($aDoppelt[$t]['telefon2'] != "") ? "Tel " . $aDoppelt[$t]['telefon2'] . "<br/>" : "") . (($aDoppelt[$t]['fax'] != "") ? "Fax " . $aDoppelt[$t]['fax'] . "<br/>" : "") . (($aDoppelt[$t]['email'] != "") ? "<a href='mailto:" . $aDoppelt[$t]['email'] . "'>" . $aDoppelt[$t]['email'] . "</a>" : "");
										
										if ($aKontakt[$t] == "") {
												$aKontakt[$t] = "&nbsp;";
										}
								}
								
								$aParam['_firma_']   = $aFirma;
								$aParam['_name_']    = $aName;
								$aParam['_adresse_'] = $aAdr;
								$aParam['_kontakt_'] = $aKontakt;
								$aParam['_nr_']      = $aAdrid;
						}
						
						unset($_POST);
						
						if ($aParam['_error_'] == '') {
								$aParam['_error_'] = "Adresse hinzugefügt !";
						}
						$aParam['_display_'] = 'block';
				} else {
						$aParam['_error_']   = "Geben Sie Firma<br>oder Name an !";
						$aParam['_display_'] = 'block';
				}
		}
		// Adresse suchen ?
		
		if (isset($_POST['find'])) {
				// die beliebige Notation von Telefonnummern - das Problem tritt vernachlässigbar auch bei den anderen Einträgen auf - macht es erforderlich,
				// die Telefonnummern bei der SQL Anfrage als einschränkendes Merkmal zunächst auszublenden und ggf. im Nachgang clientseitig zu normalisieren.
				// Alternativ kann auch die DB um einen normalisierten, d.h. von Sonderzeichen befreiten Eintrag erweitert werden,
				// Dies ist jedoch aus DB-ökonomischen Gründen und dem damit notwendigen Eingriff in das System nur zweitrangige Wahl
				
				$sVars     = '';
				$bLast     = false;
				$bTelsuche = false;
				
				foreach ($aAdresse as $sKey => $sValue) {
						if (!empty($sValue)) {
								if (($sKey == "telefon1") || ($sKey == "telefon2") || ($sKey == "fax")) {
										// ursprüngliche unbereinigte Lösung: $sVars=$sVars."(telefon1 LIKE '%".$sValue."%' OR telefon2 LIKE '%".$sValue."%')"; 
										// nur vormerken für spätere Bereinigung, nicht jedoch in Suche einbeziehen
										// $aAdresse für späteren Vergleich bereits normalisieren
										
										$aAdresse[$sKey] = preg_replace("/[^0-9]/", "", $sValue);
										
										$bTelsuche = true;
								} else {
										if ($bLast) {
												$sVars = $sVars . " AND ";
										}
										$sVars = $sVars . $sKey . " LIKE '%" . $sValue . "%'";
										$bLast = true;
								}
						}
				}
				
				// Das "WHERE" wird dem Suchstring vorangestellt; wird nur nach einer Tel. gesucht, gibt es keinen Suchstring, ist auch das WHERE
				// für die spätere Abfrage unnötig
				
				if (!empty($sVars)) {
						$sVars = "WHERE " . $sVars;
				}
				
				// Für den Fall, dass nur nach Telefonnummern gesucht werden soll, gibt es keinen Suchstring
				
				if ((empty($sVars) && ($bTelsuche)) || (!empty($sVars))) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM adressen " . $sVars);
						if (!empty($aQuery)) {
								$z = 0;
								
								for ($t = 0; $t < sizeof($aQuery); $t++) {
										// Falls Telsuche gewünscht, nicht passende Einträge aussortieren ...
										if ($bTelsuche) {
												unset($aFindTel);
												$aFindTel[0] = $aAdresse['telefon1'];
												$aFindTel[1] = $aAdresse['telefon2'];
												$aFindTel[2] = $aAdresse['fax'];
												
												$sFoundTel = preg_replace("/[^0-9]/", "", $aQuery[$t]['telefon1']) . ";" . preg_replace("/[^0-9]/", "", $aQuery[$t]['telefon2']) . ";" . preg_replace("/[^0-9]/", "", $aQuery[$t]['fax']);
												
												// strpos liefert auch 0 (Fundstelle Index 0, also am Anfang) zurück, was bei != oder == nicht von False abgrenzbar ist
												// daher !== und ===
												
												// jetzt wird's bunt - wenn der User mehrere Einträge in die suchmaske eingibt, gibt es eine UND Suche
												// um die Verschachtelung überschaubar zu lassen wird mit der Kurznotation gearbeitet
												// Wird Tel1 und Fax angegeben, ist der Eintrag zu Tel2 leer, strpos könnte undefinierbaren Mist
												// zurückliefern. Die Verkettung erfolgt, dass bei leeren Einträgen der Teil der Bedingung auf TRUE
												// gesetzt wird und der Ausdruck nicht ausgewertet wird. Sind alle leer, kommt es gar nicht dazu, weil
												// dann Status Telefonsuche auf Null ist.
												
												// Dass Fax und Telefonnummern gleichwertig/austauschbar sind, ist zu vernachlässigen; es ist undenkbar
												// dass die Faxnummer eines Eintrages gleichzeitig die Telefonnummer eines anderen Eintrages ist
												
												if ((($aFindTel[0] != "") ? (strpos($sFoundTel, $aFindTel[0]) !== false) : true) && (($aFindTel[1] != "") ? (strpos($sFoundTel, $aFindTel[1]) !== false) : true) && (($aFindTel[2] != "") ? (strpos($sFoundTel, $aFindTel[2]) !== false) : true)) {
														// if ((strpos($sFoundTel, $aFindTel[0])!==FALSE) || (strpos($sFoundTel, $aFindTel[1])!== FALSE))                                      
														
														$aAdrid[$z] = $aQuery[$t]['id'];
														$aFirma[$z] = (($aQuery[$t]['firma'] != "") ? $aQuery[$t]['firma'] : "&nbsp;");
														$aName[$z]  = $aQuery[$t]['vorname'] . "&nbsp;" . $aQuery[$t]['name'];
														
														$aAdr[$z]     = (($aQuery[$t]['strasse1'] != "") ? $aQuery[$t]['strasse1'] . "<br/>" : "") . (($aQuery[$t]['strasse2'] != "") ? $aQuery[$t]['strasse2'] . "<br/>" : "") . $aQuery[$t]['plz'] . "&nbsp;" . $aQuery[$t]['ort'];
														$aKontakt[$z] = (($aQuery[$t]['telefon1'] != "") ? "Tel " . $aQuery[$t]['telefon1'] . "<br/>" : "") . (($aQuery[$t]['telefon2'] != "") ? "Tel " . $aQuery[$t]['telefon2'] . "<br/>" : "") . (($aQuery[$t]['fax'] != "") ? "Fax " . $aQuery[$t]['fax'] . "<br/>" : "") . (($aQuery[$t]['email'] != "") ? "<a href='mailto:" . $aQuery[$t]['email'] . "'>" . $aQuery[$t]['email'] . "</a>" : "");
														
														if ($aKontakt[$z] == "") {
																$aKontakt[$z] = "&nbsp;";
														}
														
														$aFullDataSet[$z] = "<dl id='fd" . $aQuery[$t]['id'] . "'>" . "<dt>firma</dt><dd>" . $aQuery[$t]['firma'] . "</dd>" . "<dt>name</dt><dd>" . $aQuery[$t]['name'] . "</dd>" . "<dt>vorname</dt><dd>" . $aQuery[$t]['vorname'] . "</dd>" . "<dt>str1</dt><dd>" . $aQuery[$t]['strasse1'] . "</dd>" . "<dt>str2</dt><dd>" . $aQuery[$t]['strasse2'] . "</dd>" . "<dt>plz</dt><dd>" . $aQuery[$t]['plz'] . "</dd>" . "<dt>ort</dt><dd>" . $aQuery[$t]['ort'] . "</dd>" . "<dt>tel1</dt><dd>" . $aQuery[$t]['telefon1'] . "</dd>" . "<dt>tel2</dt><dd>" . $aQuery[$t]['telefon2'] . "</dd>" . "<dt>fax</dt><dd>" . $aQuery[$t]['fax'] . "</dd>" . "<dt>email</dt><dd>" . $aQuery[$t]['email'] . "</dd>" . "</dl>";
														
														$z++;
												}
										}
										
										else {
												// ungefilterter/unformierter Datensatz zur Übertragung in die Eingabemaske, sofern entsprechende Zeilen
												// in der Tabelle angeklickt wird
												
												$aFullDataSet[$t] = "<dl id='fd" . $aQuery[$t]['id'] . "'>" . "<dt>firma</dt><dd>" . $aQuery[$t]['firma'] . "</dd>" . "<dt>name</dt><dd>" . $aQuery[$t]['name'] . "</dd>" . "<dt>vorname</dt><dd>" . $aQuery[$t]['vorname'] . "</dd>" . "<dt>str1</dt><dd>" . $aQuery[$t]['strasse1'] . "</dd>" . "<dt>str2</dt><dd>" . $aQuery[$t]['strasse2'] . "</dd>" . "<dt>plz</dt><dd>" . $aQuery[$t]['plz'] . "</dd>" . "<dt>ort</dt><dd>" . $aQuery[$t]['ort'] . "</dd>" . "<dt>tel1</dt><dd>" . $aQuery[$t]['telefon1'] . "</dd>" . "<dt>tel2</dt><dd>" . $aQuery[$t]['telefon2'] . "</dd>" . "<dt>fax</dt><dd>" . $aQuery[$t]['fax'] . "</dd>" . "<dt>email</dt><dd>" . $aQuery[$t]['email'] . "</dd>" . "</dl>";
												
												
												
												$aAdrid[$t] = $aQuery[$t]['id'];
												$aFirma[$t] = (($aQuery[$t]['firma'] != "") ? $aQuery[$t]['firma'] : "&nbsp;");
												$aName[$t]  = $aQuery[$t]['vorname'] . "&nbsp;" . $aQuery[$t]['name'];
												
												$aAdr[$t]     = (($aQuery[$t]['strasse1'] != "") ? $aQuery[$t]['strasse1'] . "<br/>" : "") . (($aQuery[$t]['strasse2'] != "") ? $aQuery[$t]['strasse2'] . "<br/>" : "") . $aQuery[$t]['plz'] . "&nbsp;" . $aQuery[$t]['ort'];
												$aKontakt[$t] = (($aQuery[$t]['telefon1'] != "") ? "Tel " . $aQuery[$t]['telefon1'] . "<br/>" : "") . (($aQuery[$t]['telefon2'] != "") ? "Tel " . $aQuery[$t]['telefon2'] . "<br/>" : "") . (($aQuery[$t]['fax'] != "") ? "Fax " . $aQuery[$t]['fax'] . "<br/>" : "") . (($aQuery[$t]['email'] != "") ? "<a href='mailto:" . $aQuery[$t]['email'] . "'>" . $aQuery[$t]['email'] . "</a>" : "");
												
												if ($aKontakt[$t] == "") {
														$aKontakt[$t] = "&nbsp;";
												}
										}
								}
								
								$aParam['_firma_']   = $aFirma;
								$aParam['_name_']    = $aName;
								$aParam['_adresse_'] = $aAdr;
								$aParam['_kontakt_'] = $aKontakt;
								$aParam['_nr_']      = $aAdrid;
								$aParam['_fds_']     = $aFullDataSet;
						} else {
								$aParam['_error_']   = "Keinen Eintrag gefunden !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Suchkriterien angegeben !";
						$aParam['_display_'] = 'block';
				}
				
				// bei der Telefonsuche keine passenden Einträge gefunden ...
				
				if ($bTelsuche && $z == 0) {
						$aParam['_error_']   = "Keinen Eintrag gefunden !";
						$aParam['_display_'] = 'block';
				}
		}
		// Adresse löschen ?
		
		if (isset($_POST['deladress'])) {
				if ((int) $_POST['zeile'] != 0) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM beteiligte WHERE adressenID='" . (int) $_POST['zeile'] . "'");
						if (empty($aQuery)) {
								secure_sqlite_query($hDatabase, "DELETE FROM adressen WHERE id='" . (int) $_POST['zeile'] . "'");
								$aParam['_error_']   = "Eintrag gelöscht !";
								$aParam['_display_'] = 'block';
						} else {
								$aParam['_error_']   = "Gewählte Adresse ist mit einer Akte verknüpft !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// Adresse aktualisieren
		// Im Gegensatz zu früher wird nun der bestehende Datensatz in die Eingabemaske bei Fokus
		// übertragen. Damit erledigt sich die besondere Behandlung von Leerfeldern. So können Teile
		// des Eintrages durch schlichtes Löschen der Formzeile gelöscht werden; das früher
		// nötige KeyWord LEN ist unnötig  
		
		if (isset($_POST['aktadress'])) {
				if ((int) $_POST['zeile'] != 0) {
						if (($aAdresse['firma'] == '') && ($aAdresse['name'] == '')) {
								$aParam['_error_']   = 'Firma und Name löschen.<br/>Sehr lustig.';
								$aParam['_display_'] = 'block';
						}
						
						else {
								$sVars = '';
								foreach ($aAdresse as $sKey => $sValue) {
										if ($sVars != '') {
												$sVars = $sVars . ", ";
										}
										$sVars = $sVars . $sKey . "='" . $sValue . "'";
								}
								
								secure_sqlite_query($hDatabase, "UPDATE adressen SET " . $sVars . " WHERE id='" . (int) $_POST['zeile'] . "'");
								$aParam['_error_']   = "Eintrag aktualisiert !";
								$aParam['_display_'] = 'block';
						}
						
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		secure_sqlite_close($hDatabase);
		ShowGui('adresseingabe.html', $aParam);
}

// Aktenvita

function AktenVita()
{
		global $sDatabase;
		
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aErrorCodes             = array(
				'Upload erfolgreich',
				'Die Datei ist zu groß',
				'Die Datei ist zu groß',
				'Datei konnte nur zum Teil übertragen werden !',
				'Keine Datei angegeben !',
				'Datei konnte nicht gespeichert werden !'
		);
		$aParam['_error_']       = '';
		$aParam['_display_']     = 'none';
		$aParam['_nr_']          = '';
		$aParam['_datum_']       = '';
		$aParam['_bearbeiter_']  = '';
		$aParam['_bezeichnung_'] = '';
		
		$aParam['_user_']     = '';
		$aParam['_selected_'] = '';
		
		
		// will jemand Eintrag löschen ?
		
		if (isset($_POST['loeschen'])) {
				if ($_POST['zeile'] != '') {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM aktenvita WHERE nr=" . (int) $_POST['zeile'] . "");
						if (sizeof($aQuery) != 0) {
								if ($aQuery[0]['dateiname'] != 'protokoll.txt') {
										if (file_exists($_SESSION['aktenpath'] . $aQuery[0]['dateiname'])) {
												if (@unlink($_SESSION['aktenpath'] . $aQuery[0]['dateiname'])) {
														secure_sqlite_query($hDatabase, "DELETE FROM aktenvita WHERE nr=" . (int) $_POST['zeile'] . "");
														secure_sqlite_query($hDatabase, "UPDATE Postausgang SET aktenvitaID=NULL WHERE aktenvitaID=" . (int) $_POST['zeile'] . "");
														Protokoll($hDatabase, "Dokument '" . $aQuery[0]['beschreibung'] . "' aus Aktenvita gelöscht.");
												} else {
														$aParam['_error_']   = 'Dokument konnte nicht gelöscht werden !';
														$aParam['_display_'] = 'block';
												}
										} else {
												secure_sqlite_query($hDatabase, "DELETE FROM aktenvita WHERE nr=" . (int) $_POST['zeile'] . "");
												Protokoll($hDatabase, "Dokument '" . $aQuery[0]['beschreibung'] . "' aus Aktenvita gelöscht.");
										}
								} else {
										$aParam['_error_']   = 'Systemprotokoll nicht löschbar.';
										$aParam['_display_'] = 'block';
								}
						} else {
								$aParam['_error_']   = 'Dokument existiert nicht !';
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = "block";
				}
		}
		
		// Eintrag soll geöffnet werden
		
		if (isset($_POST['oeffnen'])) {
				if ($_POST['zeile'] != '') {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM aktenvita WHERE nr=" . (int) $_POST['zeile'] . "");
						if (sizeof($aQuery) != 0) {
								$sFile = $_SESSION['aktenpath'] . $aQuery[0]['dateiname'];
								if (file_exists($sFile)) {
										secure_sqlite_close($hDatabase);
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
										$aParam['_error_']   = "Dokument " . $sFile . " existiert nicht !";
										$aParam['_display_'] = 'block';
								}
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = "block";
				}
		}
		
		
		// will jemand Dokument hinzufügen ?
		
		if (isset($_POST['hinzufuegen'])) {
				// bei Fileupload wird ein Array $_FILES erzeugt, das im KEY des Namens des <input>-Tags für die Datei Infos speichert
				if (($_POST['bezeichnung'] != "") && ($_FILES['dokument']['error'] == 0)) {
						$sName = $_POST['bezeichnung'];
						if (preg_match("/\..*$/", $_FILES['dokument']['name'], $aExt)) {
								$sNewFilename = date("dMYHis") . $aExt[0];
						} else {
								$sNewFilename = date("dMYHis") . '.unknown';
						}
						
						if (@move_uploaded_file($_FILES['dokument']['tmp_name'], $_SESSION['aktenpath'] . $sNewFilename)) {
								secure_sqlite_query($hDatabase, "INSERT INTO aktenvita (azID,eintragsdatum,ersteller,dateiname,beschreibung) VALUES ('" . $_SESSION['akte'] . "','" . date("U") . "','" . $_POST['bearbeiter'] . "','" . $sNewFilename . "','" . $_POST['bezeichnung'] . "')");
								Protokoll($hDatabase, "Dokument '" . $_POST['bezeichnung'] . "' in Aktenvita eingetragen.");
								$aParam['_error_']   = $aErrorCodes[0];
								$aParam['_display_'] = 'block';
						} else {
								$aParam['_error_']   = $aErrorCodes[5];
								$aParam['_display_'] = 'block';
						}
				} else {
						if ($_POST['bezeichnung'] != "") {
								$aParam['_error_']   = $aErrorCodes[$_FILES['dokument']['error']];
								$aParam['_display_'] = 'block';
						} else {
								$aParam['_error_']   = "Keine Bezeichnung gewählt !";
								$aParam['_display_'] = "block";
						}
				}
		}
		
		$aLogs  = secure_sqlite_array_query($hDatabase, "SELECT nr,eintragsdatum,dateiname,beschreibung,ersteller FROM aktenvita WHERE aktenvita.azID=" . $_SESSION['akte']);
		$aUsers = secure_sqlite_array_query($hDatabase, "SELECT id,username FROM users WHERE username!='Administrator'");
		
		$aAktenbearbeiter = secure_sqlite_array_query($hDatabase, "SELECT users.username FROM users,akten WHERE akten.bearbeiterID=users.id AND akten.azID=" . $_SESSION['akte'] . "");
		
		secure_sqlite_close($hDatabase);
		
		if (!sizeof($aUsers) == 0) {
				for ($t = 0; $t < sizeof($aUsers); $t++) {
						$aUsersname[$t] = $aUsers[$t]['username'];
						if ($aUsers[$t]['username'] == $aAktenbearbeiter[0]['users.username']) {
								$aSelected[$t] = 'selected';
						} else {
								$aSelected[$t] = '';
						}
				}
				$aParam['_user_']     = $aUsersname;
				$aParam['_selected_'] = $aSelected;
		}
		
		if (!sizeof($aLogs) == 0) {
				// gibt es überhaupt Einträge
				
				for ($t = 0; $t < sizeof($aLogs); $t++) {
						$aNr[$t]          = $aLogs[$t]['nr'];
						$aDatum[$t]       = date("d.m.Y", $aLogs[$t]['eintragsdatum']);
						$aBearbeiter[$t]  = $aLogs[$t]['ersteller'];
						$aBezeichnung[$t] = $aLogs[$t]['beschreibung'];
				}
				$aParam['_nr_']          = $aNr;
				$aParam['_datum_']       = $aDatum;
				$aParam['_bearbeiter_']  = $aBearbeiter;
				$aParam['_bezeichnung_'] = $aBezeichnung;
		}
		ShowGui('aktenvita.html', $aParam);
}

// Archivierte Akte aktivieren

function ActAkte()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aQuery = secure_sqlite_array_query($hDatabase, "SELECT status FROM akten WHERE azID='" . $_SESSION['akte'] . "'");
		if (sizeof($aQuery) != 0) {
				if ($aQuery[0]['status'] == "1") {
						secure_sqlite_query($hDatabase, "UPDATE akten SET status='0' WHERE azID='" . $_SESSION['akte'] . "'");
						Protokoll($hDatabase, "Akte reaktiviert.");
						
						secure_sqlite_close($hDatabase);
						unset($_POST);
						$_POST['oeffnen2'] = 1;
						$_POST['zeile']    = $_SESSION['akte'];
						OpenAkte();
				}
		}
		ShowGui('null.html', null);
}

// Akte schliessen

function CloseAkte()
{
		unset($_SESSION['akte']);
		unset($_SESSION['aktenpath']);
		unset($_SESSION['aktenstatus']);
		unset($_SESSION['aktenzeichen']);
		unset($_SESSION['kurzrubrum']);
		ShowGui('closeakte.html', null);
}