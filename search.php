<?php

// Dokumentensuche

function DokSuche()
{
		global $sDatabase;
		global $sAktenpath;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_error_']       = '';
		$aParam['_display_']     = 'none';
		$aParam['_nr_']          = '';
		$aParam['_az_']          = '';
		$aParam['_anlagedatum_'] = '';
		$aParam['_bezeichnung_'] = '';
		$aParam['_krubrum_']     = '';
		
		// Dokument soll gesucht werden ...
		
		if (isset($_POST['suche'])) {
				if ($_POST['bezeichnung'] != '') {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT akten.kurzruburm, aktenvita.ersteller, aktenvita.nr,aktenzeichen.aznr,aktenzeichen.azjahr,aktenvita.eintragsdatum,aktenvita.beschreibung FROM akten,aktenvita,aktenzeichen WHERE aktenvita.beschreibung LIKE '%" . $_POST['bezeichnung'] . "%' AND aktenvita.azID=akten.azID AND aktenvita.azID=aktenzeichen.id ORDER BY aktenvita.eintragsdatum DESC");
						if (!empty($aQuery)) {
								for ($t = 0; $t < sizeof($aQuery); $t++) {
										$aNr[$t]           = $aQuery[$t]['aktenvita.nr'];
										$aAz[$t]           = $aQuery[$t]['aktenzeichen.aznr'] . "-" . $aQuery[$t]['aktenzeichen.azjahr'];
										$aBeschreibung[$t] = $aQuery[$t]['aktenvita.beschreibung'];
										$aDatum[$t]        = date("d.m.Y", $aQuery[$t]['aktenvita.eintragsdatum']);
										$aKrubrum[$t]      = $aQuery[$t]['akten.kurzruburm'];
										$aErsteller[$t]    = $aQuery[$t]['aktenvita.ersteller'];
								}
								$aParam['_nr_']          = $aNr;
								$aParam['_az_']          = $aAz;
								$aParam['_anlagedatum_'] = $aDatum;
								$aParam['_bezeichnung_'] = $aBeschreibung;
								$aParam['_krubrum_']     = $aKrubrum;
								$aParam['_ersteller_']   = $aErsteller;
						} else {
								$aParam['_error_']   = "Kein Dokument gefunden !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Kein Suchkriterium angegeben !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// gefundenes Dokument öffnen ?
		
		if (isset($_POST['oeffnen'])) {
				if ((int) $_POST['zeile'] != 0) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT aktenzeichen.aznr,aktenzeichen.azjahr,aktenvita.* FROM aktenvita,aktenzeichen WHERE aktenvita.nr=" . (int) $_POST['zeile'] . " AND aktenzeichen.id=aktenvita.azID");
						
						if (!empty($aQuery)) {
								$sFile = $sAktenpath . $aQuery[0]['aktenzeichen.azjahr'] . '/' . $aQuery[0]['aktenzeichen.aznr'] . '/' . $aQuery[0]['aktenvita.dateiname'];
								if (file_exists($sFile)) {
										secure_sqlite_close($hDatabase);
										preg_match("/\..*$/", $aQuery[0]['aktenvita.dateiname'], $aExt);
										$sName = $aQuery[0]['aktenvita.beschreibung'] . $aExt[0];
										
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
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// Akte zu gefundenem Dokument öffnen ?
		
		if (isset($_POST['akteoeffnen'])) {
				if ((int) $_POST['zeile'] != 0) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT azID FROM aktenvita WHERE aktenvita.nr=" . (int) $_POST['zeile'] . "");
						$iAzID  = $aQuery[0]['azID'];
						if ($iAzID != 0) {
								$aQuery = secure_sqlite_array_query($hDatabase, "SELECT akten.status, akten.kurzruburm,akten.wegen,aktenzeichen.aznr,aktenzeichen.azjahr FROM akten,aktenzeichen WHERE akten.azID=" . $iAzID . " AND aktenzeichen.id=" . $iAzID . "");
								secure_sqlite_close($hDatabase);
								
								$_SESSION['akte']      = $iAzID;
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
								$aParam['_error_']   = "Akte existiert nicht !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		secure_sqlite_close($hDatabase);
		
		ShowGui('doksuche.html', $aParam);
}