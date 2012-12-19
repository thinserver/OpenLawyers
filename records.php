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
