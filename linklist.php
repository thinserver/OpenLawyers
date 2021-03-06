<?php

// Link-Datenbank

function Linklist()
{
		global $sDatabase;
		$hDatabase           = OpenDB($sDatabase);
		$aParam['_error_']   = '';
		$aParam['_display_'] = 'none';
		
		// will jemand Eintr�ge l�schen ?
		
		if (isset($_POST['loeschen'])) {
				if (isset($_POST['eintraege'])) {
						foreach ($_POST['eintraege'] as $iSelected) {
								SQLQuery($hDatabase, "DELETE FROM linkliste WHERE nr='" . (int) $iSelected . "'");
						}
				} else {
						$aParam['_error_']   = "W�hlen Sie einen<br>Eintrag aus !";
						$aParam['_display_'] = 'block';
				}
		}
		
		// will jemand Link hinzuf�gen ?
		
		if (isset($_POST['hinzufuegen'])) {
				$sBezeichnung = $_POST['bezeichnung'];
				$sLink        = $_POST['link'];
				if ($sBezeichnung != "" && $sLink != "") {
						// Protokoll entfernen
						if (preg_match('/.*:\/\/*/i', $sLink, $aMatches) == 1) {
								if (($aMatches[0] != 'http://') && ($aMatches[0] != 'https://')) {
										$sLink = "http://" . preg_replace('/.*:\/\/*/i', '', $sLink);
								}
						} else {
								$sLink = "http://" . $sLink;
						}
						
						SQLQuery($hDatabase, "INSERT INTO linkliste (bezeichnung,ahref) VALUES ('" . $sBezeichnung . "','" . base64_encode($sLink) . "')");
				} else {
						$aParam['_error_']   = "Geben Sie eine URL und eine Bezeichnung an !";
						$aParam['_display_'] = 'block';
				}
		}
		
		$aLogs = SQLArrayQuery($hDatabase, "SELECT * FROM linkliste ORDER BY bezeichnung");
		CloseDB($hDatabase);
		
		if (sizeof($aLogs) != 0) {
				// gibt es �berhaupt Eintr�ge ?
				for ($t = 0; $t < sizeof($aLogs); $t++) {
						$aNr[$t]      = $aLogs[$t]['nr'];
						$aEintrag[$t] = $aLogs[$t]['bezeichnung'];
				}
				$aParam['_nr_']   = $aNr;
				$aParam['_link_'] = $aEintrag;
				if (sizeof($aNr) > 30) {
						$aParam['_max_'] = 30;
				} else {
						$aParam['_max_'] = sizeof($aNr);
				}
		} else {
				$aParam['_nr_']   = null;
				$aParam['_link_'] = 'Keine Eintr�ge vorhanden !';
				$aParam['_max_']  = 0;
		}
		
		ShowGui('linkliste.html', $aParam);
}

// Link aus Linkliste �ffnen

function Linkliste()
{
		global $sDatabase;
		global $sFvpath;
		
		$hDatabase               = OpenDB($sDatabase);
		$aParam['_url_']         = '';
		$aParam['_bezeichnung_'] = 'Keine Links gespeichert !';
		$aParam['_nr_']          = '';
		
		$aQuery = SQLArrayQuery($hDatabase, "SELECT * FROM linkliste ORDER BY bezeichnung");
		CloseDB($hDatabase);
		
		if (sizeof($aQuery) != 0) {
				for ($t = 0; $t < sizeof($aQuery); $t++) {
						$aUrl[$t]  = base64_decode($aQuery[$t]['ahref']);
						$aName[$t] = $aQuery[$t]['bezeichnung'];
						$aNr[$t]   = $aQuery[$t]['nr'];
				}
				$aParam['_url_']         = $aUrl;
				$aParam['_bezeichnung_'] = $aName;
				$aParam['_nr_']          = $aNr;
		}
		
		ShowGui('linkwahl.html', $aParam);
}