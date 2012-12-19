<?php

// Statistiken 

function Statistik()
{
		global $sDatabase;
		global $aStatfiles;
		
		$hDatabase = secure_sqlite_open($sDatabase);
		
		$aParam['_error_']        = '';
		$aParam['_display_']      = 'none';
		$aParam['_akten_']        = '0';
		$aParam['_doks_']         = '0';
		$aParam['_umsatz_']       = '0';
		$aParam['_durchschnitt_'] = '0';
		
		$aParam['_erledigt_']                = 'null.xml';
		$aParam['_aktenbearbeiter_']         = 'null.xml';
		$aParam['_aktenbearbeitererledigt_'] = 'null.xml';
		$aParam['_fachgebiete_']             = 'null.xml';
		$aParam['_umsatzfachgebiete_']       = 'null.xml';
		$aParam['_umsatzakte_']              = 'null.xml';
		$aParam['_umsatzbearbeiter_']        = 'null.xml';
		$aParam['_arbeitbearbeiter_']        = 'null.xml';
		
		// Anzahl der Akten
		
		$aAktencount = secure_sqlite_array_query($hDatabase, "SELECT COUNT(*) FROM akten");
		if (sizeof($aAktencount) != 0) {
				$aParam['_akten_'] = $aAktencount[0]['COUNT(*)'];
		}
		
		// Anzahl der Dokumente
		
		$aDokcount = secure_sqlite_array_query($hDatabase, "SELECT COUNT(*) FROM aktenvita");
		if (sizeof($aDokcount) != 0) {
				$aParam['_doks_'] = $aDokcount[0]['COUNT(*)'];
		}
		
		// Honorarumsatz
		
		$aHonorar = secure_sqlite_array_query($hDatabase, "SELECT SUM(betrag) FROM rechnungsnummer");
		if (sizeof($aHonorar) != 0) {
				$aParam['_umsatz_'] = $aHonorar[0]['SUM(betrag)'];
		}
		
		// Umsatz je Akte gesamt
		
		$aDurchschnittumsatz = secure_sqlite_array_query($hDatabase, "SELECT ROUND(SUM(rechnungsnummer.betrag)/COUNT(akten.azID),2) AS durchschnitt FROM akten LEFT JOIN rechnungsnummer ON akten.azID=rechnungsnummer.azID WHERE akten.status=1");
		if (sizeof($aDurchschnittumsatz) != 0) {
				$aParam['_durchschnitt_'] = $aDurchschnittumsatz[0]['durchschnitt'];
		}
		
		if ($aParam['_akten_'] > 19) {
				// Erledigte und unerledigte Akten
				
				$aAktenerledigt   = secure_sqlite_array_query($hDatabase, "SELECT COUNT(status) FROM akten WHERE status=1");
				$iAktenunerledigt = $aAktencount[0]['COUNT(*)'] - $aAktenerledigt[0]['COUNT(status)'];
				
				Tortendiagramm(array(
						$aAktenerledigt[0]['COUNT(status)'],
						$iAktenunerledigt
				), array(
						'Abgeschlossen',
						'In Bearbeitung'
				), "erledigt.xml");
				$aParam['_erledigt_'] = $aStatfiles[0];
				
				// Akten je Bearbeiter
				
				unset($aValues);
				unset($aText);
				$aAktenbearbeiter = secure_sqlite_array_query($hDatabase, "SELECT COUNT(*) AS gesamt, users.username FROM akten,users WHERE users.id=akten.bearbeiterID GROUP BY akten.bearbeiterID ORDER BY gesamt");
				for ($t = 0; $t < sizeof($aAktenbearbeiter); $t++) {
						$aValues[$t] = $aAktenbearbeiter[$t]['gesamt'];
						$aText[$t]   = $aAktenbearbeiter[$t]['users.username'];
				}
				Tortendiagramm($aValues, $aText, "aktenbearbeiter.xml");
				$aParam['_aktenbearbeiter_'] = $aStatfiles[1];
				
				// Abschlussquote (erledigte Akten/gesamt) je Bearbeiter
				
				unset($aValues);
				unset($aText);
				$aAktenBearbeitererledigt = secure_sqlite_array_query($hDatabase, "SELECT COUNT(status) AS gesamt, users.username FROM akten,users WHERE status=1 AND akten.bearbeiterID=users.id GROUP BY akten.bearbeiterID ORDER BY gesamt");
				for ($t = 0; $t < sizeof($aAktenBearbeitererledigt); $t++) {
						$aValues[$t] = round(($aAktenBearbeitererledigt[$t]['gesamt'] / $aAktenbearbeiter[$t]['gesamt']) * 100);
						$aText[$t]   = $aAktenBearbeitererledigt[$t]['users.username'];
				}
				Tortendiagramm($aValues, $aText, "aktenbearbeitererledigt.xml");
				$aParam['_aktenbearbeitererledigt_'] = $aStatfiles[2];
				
				// Fachgebiete
				
				$aFachgebiete = secure_sqlite_array_query($hDatabase, "SELECT COUNT(akten.rechtsgebietID) AS summe, rechtsgebiete.bezeichnung FROM akten,rechtsgebiete WHERE akten.rechtsgebietID=rechtsgebiete.id GROUP BY akten.rechtsgebietID ORDER BY summe");
				unset($aValues);
				unset($aText);
				for ($t = 0; $t < sizeof($aFachgebiete); $t++) {
						$aValues[$t] = $aFachgebiete[$t]['summe'];
						$aText[$t]   = $aFachgebiete[$t]['rechtsgebiete.bezeichnung'];
				}
				Tortendiagramm($aValues, $aText, "fachgebiete.xml");
				$aParam['_fachgebiete_'] = $aStatfiles[3];
				
				// Umsatz je Fachgebiet
				
				$aUmsatzFach = secure_sqlite_array_query($hDatabase, "SELECT SUM(rechnungsnummer.betrag) AS summe, rechtsgebiete.bezeichnung FROM rechnungsnummer, akten, rechtsgebiete WHERE (rechnungsnummer.azID=akten.azID AND rechtsgebiete.id=akten.rechtsgebietID) GROUP BY rechtsgebiete.bezeichnung ORDER BY summe");
				unset($aValues);
				unset($aText);
				for ($t = 0; $t < sizeof($aUmsatzFach); $t++) {
						$aValues[$t] = $aUmsatzFach[$t]['summe'];
						$aText[$t]   = $aUmsatzFach[$t]['rechtsgebiete.bezeichnung'];
				}
				Tortendiagramm($aValues, $aText, "umsatzfachgebiete.xml");
				$aParam['_umsatzfachgebiete_'] = $aStatfiles[4];
				
				// Umsatz je Akte je Fachgebiet - berichtigt nur erledigte Vorgänge bzw. Vorgänge, bei denen schon Beträge eingegangen sind
				// ACHTUNG: sobald mehrere Rechnungsnummern einer Akte zugewiesen sind, ist das Ergebnis FALSCH, da mehrere Ergebniszeilen trotz einer Akte entstehen -> COUNT z alle Zeilen, sodass Betrag durch x*Akte berechnet wird. COUNT(DISTINCT ..) kann dies verhindern, wird aber nicht von SQLITE <3 unterstützt
				
				$aUmsatzAkteFach = secure_sqlite_array_query($hDatabase, "SELECT ROUND(SUM(rechnungsnummer.betrag)/COUNT(akten.azID),2) AS durchschnitt, rechtsgebiete.bezeichnung FROM akten LEFT JOIN rechnungsnummer ON akten.azID=rechnungsnummer.azID LEFT JOIN rechtsgebiete ON akten.rechtsgebietID=rechtsgebiete.id WHERE (rechnungsnummer.betrag>0 OR akten.status=1) GROUP BY rechtsgebiete.bezeichnung ORDER BY durchschnitt");
				
				unset($aValues);
				unset($aText);
				for ($t = 0; $t < sizeof($aUmsatzAkteFach); $t++) {
						$aValues[$t] = $aUmsatzAkteFach[$t]['durchschnitt'];
						$aText[$t]   = $aUmsatzAkteFach[$t]['rechtsgebiete.bezeichnung'];
				}
				Tortendiagramm($aValues, $aText, "umsatzakte.xml");
				$aParam['_umsatzakte_'] = $aStatfiles[5];
				
				// Umsatz je Bearbeiter
				
				$aUmsatzBearbeiter = secure_sqlite_array_query($hDatabase, "SELECT SUM(rechnungsnummer.betrag) AS summe, users.username FROM rechnungsnummer, akten, users WHERE (akten.bearbeiterID=users.id AND rechnungsnummer.azID=akten.azID) GROUP BY users.username ORDER BY summe");
				unset($aValues);
				unset($aText);
				for ($t = 0; $t < sizeof($aUmsatzBearbeiter); $t++) {
						$aValues[$t] = $aUmsatzBearbeiter[$t]['summe'];
						$aText[$t]   = $aUmsatzBearbeiter[$t]['users.username'];
				}
				Tortendiagramm($aValues, $aText, "umsatzbearbeiter.xml");
				$aParam['_umsatzbearbeiter_'] = $aStatfiles[6];
				
				// ACHTUNG: die folgende Verteilung ist wissenschaftlich nicht untermauert, sondern lediglich eine Idee des Autors.
				
				// Arbeitsaufwand je Bearbeiter - Der Schriftwechsel ist wichtiger Indikator für den Arbeitsaufwand, daher hat die Aktenvita je Akte mit 58 % den größten Stellenwert.
				// Posteingang, Postausgang sowie die Wiedervorlagen sind einzelne Kriterien, die auch den Arbeitsaufwand widerspiegeln. 
				// Da WVen aber oftmals einfach nur geschoben werden, viele Briefe nicht im Postausgang registriert werden (Bsp. Fax) und Antworten auch nicht
				// steuerbar sind, bekommen diese Indizien nur jeweils 14% an der Statistik
				
				
				// COUNT()=0 Problem - sobald WVen oder Postin etc finen User nicht vorliegt, sind die Arrays unterschiedlich gros me fen User auch die 0 ausgegeben werden - Lsg. Count(*) ?
				
				$aArbeitWV        = secure_sqlite_array_query($hDatabase, "SELECT COUNT(wiedervorlagen.bearbeiterID) AS wv, users.username AS bearbeiter FROM wiedervorlagen, users WHERE users.id=wiedervorlagen.bearbeiterID GROUP BY users.username ORDER BY users.username");
				$aArbeitAktenvita = secure_sqlite_array_query($hDatabase, "SELECT COUNT(aktenvita.nr) AS vita, users.username AS bearbeiter FROM akten,aktenvita,users WHERE aktenvita.azID=akten.azID AND akten.bearbeiterID=users.id GROUP BY users.username ORDER BY users.username");
				$aArbeitPostin    = secure_sqlite_array_query($hDatabase, "SELECT COUNT(posteingang.nr) AS postin, users.username AS bearbeiter FROM akten,posteingang,users WHERE akten.azID=posteingang.azID AND akten.bearbeiterID=users.id GROUP BY users.username ORDER BY users.username");
				$aArbeitPostout   = secure_sqlite_array_query($hDatabase, "SELECT COUNT(postausgang.nr) AS postout, users.username AS bearbeiter FROM akten,postausgang,users WHERE akten.azID=postausgang.azID AND akten.bearbeiterID=users.id GROUP BY users.username ORDER BY users.username");
				
				unset($aValues);
				unset($aText);
				for ($t = 0; $t < sizeof($aArbeitWV); $t++) {
						$aValues[$t] = round(($aArbeitWV[$t]['wv'] * 0.14) + ($aArbeitAktenvita[$t]['vita'] * 0.58) + ($aArbeitPostin[$t]['postin'] * 0.14) + ($aArbeitPostout[$t]['postout'] * 0.14), 2);
						$aText[$t]   = $aArbeitWV[$t]['bearbeiter'];
				}
				Tortendiagramm($aValues, $aText, "arbeitbearbeiter.xml");
				$aParam['_arbeitbearbeiter_'] = $aStatfiles[7];
		} else {
				$aParam['_error_']   = 'Für eine statistische Auswertung sind min. 20 Akten notwendig !';
				$aParam['_display_'] = 'block';
		}
		
		secure_sqlite_close($hDatabase);
		
		ShowGui('statistik.html', $aParam);
}

