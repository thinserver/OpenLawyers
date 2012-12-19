<?php

// Kosten und Rechnungsnummer

function Kosten()
{
		global $sDatabase;
		$hDatabase = OpenDB($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_nr_']        = '';
		$aParam['_rnr_']       = '';
		$aParam['_rnrbetrag_'] = '0.00';
		$aParam['_id_']        = '';
		$aParam['_grund_']     = '';
		$aParam['_betrag_']    = '';
		$aParam['_gesamt_']    = '0.00';
		$aParam['_error_']     = '';
		$aParam['_display_']   = 'none';
		
		if (isset($_POST['zuweisen'])) {
				$aQuery = SQLArrayQuery($hDatabase, "SELECT * FROM freieRNR");
				SQLQuery($hDatabase, "UPDATE freieRNR SET nr='" . ((int) $aQuery[0]['nr'] + 1) . "'");
				SQLQuery($hDatabase, "INSERT INTO rechnungsnummer(nr,jahr,azID,betrag) VALUES ('" . $aQuery[0]['nr'] . "','" . $aQuery[0]['jahr'] . "','" . $_SESSION['akte'] . "','" . floatval($_POST['rnrbetrag']) . "')");
				Protokoll($hDatabase, "Rechnungsnummer " . $aQuery[0]['jahr'] . " - " . $aQuery[0]['nr'] . " zugewiesen.");
		}
		
		if (isset($_POST['pkhzuweisen'])) {
				SQLQuery($hDatabase, "INSERT INTO rechnungsnummer(nr,jahr,azID,betrag) VALUES ('0','0','" . $_SESSION['akte'] . "','" . floatval($_POST['pkhbetrag']) . "')");
				Protokoll($hDatabase, "BerH / PKH zugewiesen.");
		}
		
		
		if (isset($_POST['aendern'])) {
				if ($_POST['zeile'] != '') {
						if ($_POST['zeile'][0] == "b") {
								$iID = (int) substr($_POST['zeile'], 1);
								SQLQuery($hDatabase, "UPDATE rechnungsnummer SET betrag='" . floatval($_POST[$_POST['zeile']]) . "' WHERE id='" . $iID . "'");
						} else {
								$aParam['_error_']   = "Keine Auswahl getroffen !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		if (isset($_POST['zufuegen'])) {
				if (($_POST['grund'] != '') && ($_POST['betrag'] != '')) {
						SQLQuery($hDatabase, "INSERT INTO kosten(azID,datum,grund,betrag) VALUES ('" . $_SESSION['akte'] . "','" . date('U') . "','" . $_POST['grund'] . "','" . floatval($_POST['betrag']) . "')");
						Protokoll($hDatabase, "Kosten in Höhe von " . $_POST['betrag'] . " (EUR) für '" . $_POST['grund'] . "' erfasst.");
				} else {
						$aParam['_error_']   = "Bitte Betrag und Grund angeben !";
						$aParam['_display_'] = 'block';
				}
		}
		if (isset($_POST['del'])) {
				if ($_POST['zeile'] != '') {
						if ($_POST['zeile'][0] != "b") {
								SQLQuery($hDatabase, "DELETE FROM kosten WHERE nr='" . (int) $_POST['zeile'] . "'");
						} else {
								$aParam['_error_']   = "Keine Auswahl getroffen !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Keine Auswahl getroffen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aQuery  = SQLArrayQuery($hDatabase, "SELECT * FROM freieRNR");
		$aQuery2 = SQLArrayQuery($hDatabase, "SELECT * FROM rechnungsnummer WHERE azID='" . $_SESSION['akte'] . "'");
		$aQuery3 = SQLArrayQuery($hDatabase, "SELECT * FROM kosten WHERE azID='" . $_SESSION['akte'] . "'");
		CloseDB($hDatabase);
		
		$aParam['_nextrnr_'] = $aQuery[0]['jahr'] . " - " . $aQuery[0]['nr'];
		
		if (sizeof($aQuery2) != 0) {
				for ($t = 0; $t < sizeof($aQuery2); $t++) {
						if (($aQuery2[$t]['jahr'] == 0) && ($aQuery2[$t]['nr'] == 0)) {
								$aRnr[$t] = "Beratungshilfe/PKH";
						} else {
								$aRnr[$t] = $aQuery2[$t]['jahr'] . " - " . $aQuery2[$t]['nr'];
						}
						$aRnrBetrag[$t] = number_format($aQuery2[$t]['betrag'], 2, ".", "");
						$aRnrId[$t]     = $aQuery2[$t]['id'];
				}
				$aParam['_rnr_']       = $aRnr;
				$aParam['_rnrbetrag_'] = $aRnrBetrag;
				$aParam['_id_']        = $aRnrId;
		}
		
		$fGesamt = 0;
		
		if (sizeof($aQuery3) != 0) {
				for ($t = 0; $t < sizeof($aQuery3); $t++) {
						$aNr[$t]     = $aQuery3[$t]['nr'];
						$aGrund[$t]  = $aQuery3[$t]['grund'];
						$aBetrag[$t] = number_format($aQuery3[$t]['betrag'], 2, ".", ".");
						$fGesamt     = $fGesamt + $aQuery3[$t]['betrag'];
				}
				$aParam['_nr_']     = $aNr;
				$aParam['_grund_']  = $aGrund;
				$aParam['_betrag_'] = $aBetrag;
				$aParam['_gesamt_'] = number_format($fGesamt, 2, ".", ".");
		}
		
		ShowGui('kosten.html', $aParam);
}