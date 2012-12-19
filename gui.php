<?php

// Funktion füllt ein übergebenes HTML-File (GUI) und ersetzt Variablen durch Werte aus Hashtable, ggf. mehrfach !
// Übergeben wird der Name der GUI und eine Hashtable, wobei die Keys den "Variablen" der GUI entsprechen müssen
// Es wird mit 2 oder 3 Argumenten gearbeitet, wobei, sofern gesetzt, das dritte Argument lediglich dafür sorgt, dass "die" nicht ausgef wird um ggf. danach weitere Operationen durchzuführen

function ShowGui()
{
		global $sGuipath;
		
		if (func_num_args() < 2) {
				Error("Fehlende Parameter für ShowGui !");
				die;
		}
		
		$Sguifile   = func_get_arg(0);
		$Hvariables = func_get_arg(1);
		if (func_num_args() < 3) {
				$bDie = 1;
		} else {
				$bDie = 0;
		}
		
		$Sguifile = $sGuipath . (isset($_SESSION['guipath']) ? $_SESSION['guipath'] : "") . $Sguifile;
		if (!file_exists($Sguifile)) {
				Error("HTML-Datei " . $Sguifile . " nicht gefunden !");
				die;
		}
		$Adata = file($Sguifile);
		
		// für geschlossene Akten müssen bestimmte Buttons disabled werden. Bei geschlossenen Akten soll zwar weiterhin
		// in Aktenvita etc. nachgesehen werden können, Manipulationen durch Löschung oder Sonstiges sollen aber
		// ausgeschlossen sein. Daher werden kritische Funktionen zentral beim Status "deaktiviert" bereits beim Aufbau 
		// schlicht verhindert. Bei GUI Entwicklung darauf achten, dass diese Buttons isoliert in einer Zeile stehen, damit der Interpreter
		// nicht mehrfacheinsetzungen versucht.
		
		$Hvariables['_ButtonDisabled_'] = '';
		
		if (isset($_SESSION['aktenstatus'])) {
				if ($_SESSION['aktenstatus'] == 1) {
						$Hvariables['_ButtonDisabled_'] = 'disabled';
				}
				
		}
		
		for ($t = 0; $t < count($Adata); $t++) {
				if ($Hvariables != null) {
						if (preg_match_all('/_.[^_]*_/', $Adata[$t], $Afound)) {
								// Aufbau von $aFound: [0] ist ein Array mit gefundenen Einträgen -> im Stil "_TAG_"
								// feststellen, ob ein Eintrag _TAG_ von den übergebenen Variablen ein Array ist und das größte für den Schleifenzähler festhalten
								
								$iMaxIt = 0;
								for ($i = 0; $i < sizeof($Afound[0]); $i++) {
										// wegen POSTErhalten kann es in den GUI Files gerade bei Erstaufruf in Hvariables
										// nicht existente Paramter geben 
										
										if (isset($Hvariables[$Afound[0][$i]])) {
												if (is_array($Hvariables[$Afound[0][$i]])) {
														if (sizeof($Hvariables[$Afound[0][$i]]) > $iMaxIt) {
																$iMaxIt = sizeof($Hvariables[$Afound[0][$i]]);
														}
												}
										} else {
												// gefunden, aber nicht existent ? Dann sicherheitshalber anlegen
												
												$Hvariables[$Afound[0][$i]] = "";
										}
								}
								
								// sollen mehrere Einträge eingefügt werden (mehrere Zeilen mit gleichlautenden Variablen)?
								// dann wäre ein Array übergeben worden und $iMaxIt>0
								
								if ($iMaxIt > 0) {
										// Größe des größten Arrays entspricht Anzahl der Tabellenzeilen
										
										for ($iZeilen = 0; $iZeilen < $iMaxIt; $iZeilen++) {
												$sZeile = $Adata[$t];
												
												// Anzahl der (Tabellen)Spalten entspricht der Anzahl der mittels preg_match gefundenen Übereinstimmungen, also sizeof ($Afound[0]) 
												
												for ($iSpalten = 0; $iSpalten < sizeof($Afound[0]); $iSpalten++) {
														$aList = $Hvariables[$Afound[0][$iSpalten]];
														
														// ist es überhaupt ein Array, nicht zwingend, falls (nur) gleichlautende Konstanten gesetzt werden sollen
														
														if (is_array($aList)) {
																// falls Arrays übergeben wurden, die kleiner als das größte sind 
																// [was bei einer Tabelle möglichst nicht vorkommen sollte] sicherheitshalber abfangen
																
																if (!isset($aList[$iZeilen])) {
																		$sRep = '&nbsp;';
																} else {
																		$sRep = $aList[$iZeilen];
																}
														} else {
																$sRep = $aList;
														}
														
														$sZeile = preg_replace('/' . $Afound[0][$iSpalten] . '/', $sRep, $sZeile);
												}
												print($sZeile);
										}
								} else {
										// nur eine Zeile zu ändern - vielleicht mehrere Variablen ?
										$sZeile = $Adata[$t];
										for ($iSpalten = 0; $iSpalten < sizeof($Afound[0]); $iSpalten++) {
												$sList  = $Hvariables[$Afound[0][$iSpalten]];
												$sZeile = preg_replace('/' . $Afound[0][$iSpalten] . '/', $sList, $sZeile);
										}
										print($sZeile);
								}
								
						} else {
								// Nichts zum Ersetzen da ...
								print($Adata[$t]);
						}
				} else {
						print($Adata[$t]);
				}
				// da null geben, nichts machen außer Ausgabe
		}
		if ($bDie == 1) {
				die;
		}
}

// Einfache Errormeldung für Systemfehler

function Error($Serrormsg)
{
		print("<html><head><script language='JavaScript'>function err() { alert('$Serrormsg'); }</script><body onload='err()'></body></html>");
}
