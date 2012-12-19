<?php

// Beteiligte: Art

function BetArt()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		// will jemand Einträge löschen ?
		
		if (isset($_POST['loeschen'])) {
				if (isset($_POST['eintraege'])) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT COUNT(*) FROM beteiligtenart");
						if ($aQuery[0]['COUNT(*)'] > sizeof($_POST['eintraege'])) {
								foreach ($_POST['eintraege'] as $iSelected) {
										$aQuery = secure_sqlite_array_query($hDatabase, "SELECT azID FROM beteiligte WHERE beteiligtenartID='" . (int) $iSelected . "' LIMIT 1");
										if (empty($aQuery)) {
												secure_sqlite_query($hDatabase, "DELETE FROM beteiligtenart WHERE id='" . (int) $iSelected . "'");
										} else {
												$aParam['_error_']   = "Beteiligter ist einer Akte zugeordnet !";
												$aParam['_display_'] = 'block';
										}
								}
						} else {
								$aParam['_error_']   = "Es dürfen nicht alle Beteiligtenarten gelöscht werden !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Wählen Sie zu löschende Beteiligte aus !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// will jemand Beteiligten hinzufügen ?
		
		if (isset($_POST['hinzufuegen'])) {
				$sGebiet = $_POST['betname'];
				if ($sGebiet != "") {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT arten FROM beteiligtenart WHERE arten='" . $sGebiet . "'");
						if (empty($aQuery)) {
								secure_sqlite_query($hDatabase, "INSERT INTO beteiligtenart (arten) VALUES ('" . $sGebiet . "')");
						} else {
								$aParam['_error_']   = "Beteiligter existiert bereits !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Bitte geben Sie eine Bezeichnung an !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aLogs = secure_sqlite_array_query($hDatabase, "SELECT * FROM beteiligtenart ORDER BY arten");
		secure_sqlite_close($hDatabase);
		
		if (!sizeof($aLogs) == 0) {
				// gibt es überhaupt Einträge ?
				for ($t = 0; $t < sizeof($aLogs); $t++) {
						$aNr[$t]      = $aLogs[$t]['id'];
						$aEintrag[$t] = $aLogs[$t]['arten'];
				}
				$aParam['_id_']     = $aNr;
				$aParam['_bettyp_'] = $aEintrag;
				if (sizeof($aNr) > 30) {
						$aParam['_max_'] = 30;
				} else {
						$aParam['_max_'] = sizeof($aNr);
				}
		} else {
				$aParam['_id_']     = null;
				$aParam['_bettyp_'] = 'Keine Einträge vorhanden !';
				$aParam['_max_']    = 1;
		}
		
		ShowGui('betart.html', $aParam);
}

// Beteiligte 

function Beteiligte()
{
		global $sDatabase;
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam = POSTerhalten($_POST);
		
		$aParam['_name_']         = '';
		$aParam['_adresse_']      = '';
		$aParam['_betstatus_']    = '';
		$aParam['_betadresse_']   = 'Nicht eingetragen';
		$aParam['_bettyp_']       = 'Keine Typen';
		$aParam['_betid_']        = 0;
		$aParam['_adrid_']        = 0;
		$aParam['_beteiligteid_'] = 0;
		
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		if (isset($_POST['del'])) {
				if ($_POST['zeile'] != '') {
						if ($_POST['zeile'][0] == "b") {
								$iID = (int) substr($_POST['zeile'], 1);
								secure_sqlite_query($hDatabase, "DELETE FROM beteiligte WHERE id='" . $iID . "'");
						}
						
						else {
								$aParam['_error_']   = "Bitte Beteiligten auswählen !";
								$aParam['_display_'] = 'block';
						}
				} else {
						$aParam['_error_']   = "Bitte Beteiligten auswählen !";
						$aParam['_display_'] = 'block';
				}
		}
		
		if (isset($_POST['add'])) {
				if (isset($_POST['betart']) && ($_POST['zeile'] != '')) {
						if ($_POST['zeile'][0] != "b") {
								// Kleine Interessenkollisionsprüfung
								// Im Kern wird geschaut, ob der hinzugefügte Beteiligte schon in anderer Beteiligungsart
								// mit einer anderen Akte verknüpft ist.
								
								$aKollision = secure_sqlite_array_query($hDatabase, "SELECT aktenzeichen.aznr AS aznr, aktenzeichen.azjahr AS azjahr, adressen.firma AS firma, adressen.name AS name FROM beteiligte, adressen, aktenzeichen WHERE beteiligte.adressenID=" . $_POST['zeile'] . " AND beteiligte.beteiligtenartID!=" . $_POST['betart'] . " AND beteiligte.azID!=" . $_SESSION['akte'] . " AND aktenzeichen.id=beteiligte.azID AND adressen.id=beteiligte.adressenID");
								if (sizeof($aKollision) != 0) {
										$aParam['_display_'] = 'block';
										$aParam['_error_']   = 'ACHTUNG !<br>Mögliche Interessenkollision !<br>Details im Aktenprotokoll vermerkt.';
										
										$sDetailInfo = "Mögliche Interessenkollision !\nBeteiligter -" . ($aKollision[0]['firma'] != "" ? " Firma: " . $aKollision[0]['firma'] : "") . ($aKollision[0]['name'] != "" ? " Name: " . $aKollision[0]['name'] : "") . " - ist mit folgenden Akten verknüpft:\n";
										for ($t = 0; $t < sizeof($aKollision); $t++) {
												$sDetailInfo = $sDetailInfo . "Aktenzeichen " . $aKollision[$t]['aznr'] . "-" . $aKollision[$t]['azjahr'] . "\n";
										}
										
										Protokoll($hDatabase, $sDetailInfo);
								}
								
								secure_sqlite_query($hDatabase, "INSERT INTO beteiligte(azID,beteiligtenartID,adressenID,ansprechpartner,telefon,aktenzeichen) VALUES('" . $_SESSION['akte'] . "','" . $_POST['betart'] . "','" . $_POST['zeile'] . "','" . $_POST['ansprechpanam'] . "','" . $_POST['ansprechpatel'] . "','" . $_POST['ansprechpazei'] . "')");
						}
						
						else {
								$aParam['_error_']   = "Bitte Adresse aus Suchergebnissen auswählen !";
								$aParam['_display_'] = 'block';
						}
				}
				
				else {
						$aParam['_error_']   = "Keine Adresse gewählt !";
						$aParam['_display_'] = 'block';
				}
		}
		
		if (isset($_POST['find'])) {
				if (($_POST['firma'] != '') || ($_POST['name'] != '') || ($_POST['vorname'])) {
						$aQuery = secure_sqlite_array_query($hDatabase, "SELECT * FROM adressen WHERE firma LIKE '%" . $_POST['firma'] . "%' AND name LIKE '%" . $_POST['name'] . "%' AND vorname LIKE '%" . $_POST['vorname'] . "%'");
						if (sizeof($aQuery) != 0) {
								for ($t = 0; $t < sizeof($aQuery); $t++) {
										$aAdrid[$t] = $aQuery[$t]['id'];
										
										$aName[$t] = (($aQuery[$t]['firma'] != "") ? $aQuery[$t]['firma'] . "<br/>" : "") . (($aQuery[$t]['vorname'] != "") ? $aQuery[$t]['vorname'] . " " : "") . $aQuery[$t]['name'];
										
										$aAdresse[$t] = (($aQuery[$t]['strasse1'] != "") ? $aQuery[$t]['strasse1'] . "<br/>" : "") . (($aQuery[$t]['strasse2'] != "") ? $aQuery[$t]['strasse2'] . "<br/>" : "") . (($aQuery[$t]['plz'] != "") ? $aQuery[$t]['plz'] : "") . " " . (($aQuery[$t]['ort'] != "") ? $aQuery[$t]['ort'] . "<br/><br/>" : "") . (($aQuery[$t]['telefon1'] != "") ? "Tel " . $aQuery[$t]['telefon1'] . "<br/>" : "") . (($aQuery[$t]['telefon2'] != "") ? "Tel " . $aQuery[$t]['telefon2'] . "<br/>" : "") . (($aQuery[$t]['fax'] != "") ? "Fax " . $aQuery[$t]['fax'] . "<br/>" : "") . (($aQuery[$t]['email'] != "") ? "<a href='mailto:" . $aQuery[$t]['email'] . "'>" . $aQuery[$t]['email'] . "</a>" : "");
								}
								$aParam['_name_']    = $aName;
								$aParam['_adresse_'] = $aAdresse;
								$aParam['_adrid_']   = $aAdrid;
						} else {
								$aParam['_error_']   = "Keine Adresse gefunden!";
								$aParam['_display_'] = 'block';
								
						}
				} else {
						$aParam['_error_']   = "Bitte Suchkriterien angeben !";
						$aParam['_display_'] = 'block';
				}
		}
		
		
		$aQuery  = secure_sqlite_array_query($hDatabase, "SELECT * FROM beteiligtenart");
		$aQuery2 = secure_sqlite_array_query($hDatabase, "SELECT adressen.*,beteiligtenart.arten,beteiligte.* FROM adressen,beteiligte,beteiligtenart WHERE adressen.id=beteiligte.adressenID AND beteiligte.azID='" . $_SESSION['akte'] . "' AND beteiligte.beteiligtenartID=beteiligtenart.id ORDER BY beteiligtenartID");
		secure_sqlite_close($hDatabase);
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aBetid[$t] = $aQuery[$t]['id'];
						$aArt[$t]   = $aQuery[$t]['arten'];
				}
				$aParam['_betid_']  = $aBetid;
				$aParam['_bettyp_'] = $aArt;
		}
		
		if (sizeof($aQuery2) != 0) {
				for ($t = 0; $t < sizeof($aQuery2); $t++) {
						$aId[$t]     = $aQuery2[$t]['beteiligte.id'];
						$aStatus[$t] = $aQuery2[$t]['beteiligtenart.arten'];
						
						//  $aBetadresse[$t]=$aQuery2[$t]['adressen.firma']."<br>".$aQuery2[$t]['adressen.vorname']." ".$aQuery2[$t]['adressen.name']."<br>".$aQuery2[$t]['adressen.strasse1']."<br>".$aQuery2[$t]['adressen.strasse2']."<br>".$aQuery2[$t]['adressen.plz']." ".$aQuery2[$t]['adressen.ort']."<br>Tel. ".$aQuery2[$t]['adressen.telefon1']."<br>Tel. ".$aQuery2[$t]['adressen.telefon2']."<br>Fax ".$aQuery2[$t]['adressen.fax']."<br>".$aQuery2[$t]['adressen.email'];
						//  $aBetadresse[$t]=$aBetadresse[$t]."Ansprechpartner<br>".$aQuery2[$t]['beteiligte.ansprechpartner']."<br>Tel. ".$aQuery2[$t]['beteiligte.telefon']."<br>AZ ".$aQuery2[$t]['beteiligte.aktenzeichen']."<br>";
						
						$aBetadresse[$t] = (($aQuery2[$t]['adressen.firma'] != "") ? $aQuery2[$t]['adressen.firma'] . "<br/>" : "") . (($aQuery2[$t]['adressen.vorname'] != "") ? $aQuery2[$t]['adressen.vorname'] . " " : "") . (($aQuery2[$t]['adressen.name'] != "") ? $aQuery2[$t]['adressen.name'] . "<br/>" : "<br/>") . (($aQuery2[$t]['adressen.strasse1'] != "") ? $aQuery2[$t]['adressen.strasse1'] . "<br/>" : "") . (($aQuery2[$t]['adressen.strasse2'] != "") ? $aQuery2[$t]['adressen.strasse2'] . "<br/>" : "") . (($aQuery2[$t]['adressen.plz'] != "") ? $aQuery2[$t]['adressen.plz'] : "") . " " . (($aQuery2[$t]['adressen.ort'] != "") ? $aQuery2[$t]['adressen.ort'] . "<br/>" : "<br/>") . (($aQuery2[$t]['adressen.telefon1'] != "") ? "Tel " . $aQuery2[$t]['adressen.telefon1'] . "<br/>" : "") . (($aQuery2[$t]['adressen.telefon2'] != "") ? "Tel " . $aQuery2[$t]['adressen.telefon2'] . "<br/>" : "") . (($aQuery2[$t]['adressen.fax'] != "") ? "Fax " . $aQuery2[$t]['adressen.fax'] . "<br/>" : "") . (($aQuery2[$t]['adressen.email'] != "") ? "<a href='mailto:" . $aQuery2[$t]['adressen.email'] . "'>" . $aQuery2[$t]['adressen.email'] . "</a><br/>" : "");
						$sTmpBetAdresse  = (($aQuery2[$t]['beteiligte.ansprechpartner'] != "") ? $aQuery2[$t]['beteiligte.ansprechpartner'] . "<br/>" : "") . (($aQuery2[$t]['beteiligte.telefon'] != "") ? "Kontakt " . $aQuery2[$t]['beteiligte.telefon'] . "<br/>" : "") . (($aQuery2[$t]['beteiligte.aktenzeichen'] != "") ? "Zeichen " . $aQuery2[$t]['beteiligte.aktenzeichen'] : "");
						if ($sTmpBetAdresse != "") {
								$aBetadresse[$t] = $aBetadresse[$t] . "<br/><b>Ansprechpartner</b><br/>" . $sTmpBetAdresse;
						}
				}
				
				$aParam['_beteiligteid_'] = $aId;
				$aParam['_betstatus_']    = $aStatus;
				$aParam['_betadresse_']   = $aBetadresse;
		}
		
		ShowGui('beteiligte.html', $aParam);
}
