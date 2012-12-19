<?php

// Stammdaten

function Stammdaten()
{
		global $sDatabase;
		
		$hDatabase = OpenDB($sDatabase);
		
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		if (isset($_POST['aendern'])) {
				if (($_POST['rubrum'] != '') && ($_POST['wegen'] != '')) {
						SQLQuery($hDatabase, "UPDATE akten SET kurzruburm='" . $_POST['rubrum'] . "', wegen='" . $_POST['wegen'] . "', sonstiges='" . $_POST['sonst'] . "', bearbeiterID='" . $_POST['bearbeiter'] . "', rechtsgebietID='" . $_POST['rgebiet'] . "' WHERE azID='" . $_SESSION['akte'] . "'");
						Protokoll($hDatabase, "Stammdaten geändert.");
						CloseDB($hDatabase);
						unset($_POST);
						$_POST['oeffnen2'] = 1;
						$_POST['zeile']    = $_SESSION['akte'];
						unset($_SESSION['akte']);
						unset($_SESSION['aktenpath']);
						unset($_SESSION['aktenzeichen']);
						unset($_SESSION['kurzrubrum']);
						OpenAkte();
				} else {
						$aParam['_error_']   = "Bitte legen Sie Kurzrubrum und Wegen fest !";
						$aParam['_display_'] = 'block';
				}
		}
		
		if (isset($_POST['ablegen'])) {
				$aQuery = SQLArrayQuery($hDatabase, "SELECT nr FROM wiedervorlagen WHERE azID='" . $_SESSION['akte'] . "' AND status=0");
				if (sizeof($aQuery) == 0) {
						SQLQuery($hDatabase, "UPDATE akten SET status='1' WHERE azID='" . $_SESSION['akte'] . "'");
						Protokoll($hDatabase, "Akte abgelegt.");
						CloseDB($hDatabase);
						CloseAkte();
				} else {
						$aParam['_error_']   = "Es sind für diese Akte noch Wiedervorlagen eingetragen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aQuery = SQLArrayQuery($hDatabase, "SELECT bearbeiterID,rechtsgebietID,kurzruburm,wegen,sonstiges FROM akten WHERE azID='" . $_SESSION['akte'] . "'");
		
		$aParam['_krubrum_'] = $aQuery[0]['kurzruburm'];
		$aParam['_wegen_']   = $aQuery[0]['wegen'];
		$aParam['_sonst_']   = $aQuery[0]['sonstiges'];
		$iBearbeiterID       = $aQuery[0]['bearbeiterID'];
		$iRgID               = $aQuery[0]['rechtsgebietID'];
		
		$aQuery = SQLArrayQuery($hDatabase, "SELECT betrag FROM rechnungsnummer WHERE azID='" . $_SESSION['akte'] . "'");
		if (!empty($aQuery)) {
				$fGesamt = 0;
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$fGesamt = $fGesamt + floatval($aQuery[$t]['betrag']);
				}
				$aParam['_rvg_'] = number_format($fGesamt, 2, ".", ".");
		} else {
				$aParam['_rvg_'] = "0.00";
		}
		
		$aQuery  = SQLArrayQuery($hDatabase, "SELECT * FROM rechtsgebiete");
		$aQuery2 = SQLArrayQuery($hDatabase, "SELECT * FROM users WHERE username!='Administrator'");
		CloseDB($hDatabase);
		
		for ($t = 0; $t < sizeof($aQuery); $t++) {
				$aRnr[$t]  = $aQuery[$t]['id'];
				$aName[$t] = $aQuery[$t]['bezeichnung'];
				if ($iRgID == $aQuery[$t]['id']) {
						$aSelected[$t] = 'selected';
				} else {
						$aSelected[$t] = '';
				}
		}
		$aParam['_rnr_']     = $aRnr;
		$aParam['_rgebiet_'] = $aName;
		$aParam['_rgaktiv_'] = $aSelected;
		
		unset($aSelected);
		unset($aName);
		
		for ($t = 0; $t < sizeof($aQuery2); $t++) {
				$aSnr[$t]  = $aQuery2[$t]['id'];
				$aName[$t] = $aQuery2[$t]['username'];
				if ($iBearbeiterID == $aQuery2[$t]['id']) {
						$aSelected[$t] = 'selected';
				} else {
						$aSelected[$t] = '';
				}
		}
		$aParam['_snr_']    = $aSnr;
		$aParam['_user_']   = $aName;
		$aParam['_uaktiv_'] = $aSelected;
		
		ShowGui('stammdaten.html', $aParam);
}