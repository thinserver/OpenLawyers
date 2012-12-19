<?php
//
// OpenLawyer's
// Software License: GNU Affero-GPL v3
//
// Installations- und Benutzungshinweise siehe Datei readme
//

// HINWEIS: DATEIBERECHTIGUNGEN
// Für Verzeichnis und Dateioperationen werden im Skript die Rechte 777 und 666 verwendet. 
// durch umask, die regelmäßig auf 22 steht, sollten die üblich zu verwendenden Rechte 755 und 644 vorliegen
// Für manuelle Backups und manuelle Updates ist immer darauf zu achten, dass der Webserver Besitzer ist, sonst gibt es
// bei diesen Rechten Probleme; andernfalls kann eine umask 0 diese überwinden

include('settings.php');

// isset bei $_POST ist immer gegeben, sobald <form> abgesandt wird und <input> einen namen hat, unabhängig davon, was oder ob etwas eingetragen wurde
// AUSNAHME: bei select o.st isset nur gegeben, wenn etwas ausgewählt wurde aus der Liste ! Bei Button ebenfalls

// ------------------------------------ Funktionen für Sicherheit & Session ----------------------------------------------------------------

include('gui.php');

// Entschärft Benutzereingaben - nur A..Z, 0..9 erlaubt, um Codeinjection zu verhindern

function MakeSafe($eingabe)
{
		$sReplace = '';
		return preg_replace("/[\"\'<>\\\{\}\$#]/", $sReplace, $eingabe);
}

// prüft, ob IP-Adresse der Notation entspricht

function CheckIP($ipadr)
{
		$state = 1;
		$count = 0;
		$told  = 0;
		$ipadr = $ipadr . ".";
		for ($t = 0; $t < strlen($ipadr); $t++) {
				if ($ipadr[$t] == ".") {
						$count++;
						$nums = substr($ipadr, $told, $t - $told);
						if ((strlen($nums) > 3) || (strlen($nums) == 0)) {
								$state = 0;
								break;
						}
						// regulärer Ausdruck - suche, ob etwas außer 0-9 vorhanden ist
						if (preg_match("/[^0-9]/", $nums)) {
								$state = 0;
								break;
						}
						$told = $t + 1;
				}
		}
		if ($count != 4) {
				$state = 0;
		}
		return $state;
}

// macht aus der IP eine genormte ...

function NormIP($sIpadr)
{
		$aZ      = array(
				'00',
				'0',
				''
		);
		$sIppart = '';
		$sNewIP  = '';
		for ($t = 0; $t < strlen($sIpadr); $t++) {
				if (($sIpadr[$t]) == '.') {
						$sIppart = $aZ[strlen($sIppart) - 1] . $sIppart . '.';
						$sNewIP  = $sNewIP . $sIppart;
						$sIppart = '';
				} else {
						$sIppart = $sIppart . $sIpadr[$t];
				}
		}
		$sNewIP = $sNewIP . $aZ[strlen($sIppart) - 1] . $sIppart;
		return $sNewIP;
}

include('ipblock.php');

function NormAZ($aAZ)
{
		if (is_array($aAZ)) {
		}
}

// Speichert die mit POST übergebenen Daten, um sie beim Neuaufbau der Seite wieder einzutragen

function POSTerhalten($aPostValues)
{
		if (!empty($aPostValues)) {
				unset($aPOSTCopy);
				foreach ($aPostValues as $sPOSTKey => $sPOSTValue) {
						// spezielle Notation für zu ersetzende GUI Variablen - in Großbuchstaben um Verwechslungen zu verhindern
						$sNewKey             = "_" . strtoupper($sPOSTKey) . "_";
						$aPOSTCopy[$sNewKey] = $sPOSTValue;
				}
				return $aPOSTCopy;
		} else
				return NULL;
}

include('recentchanges.php');

include('sql.php');

// ------------------------------------ Hauptfunktionen des Programmes -----------------------------------------------

include('login.php');

// ---------------------------------------- SVG Kreation -------------------------------------------------------------

include('svg.php');

// ---------------------------------------- Administrator-Funktionen -------------------------------------------------

include('install.php');

include('logs.php');

include('ipfilter.php');

include('users.php');

include('templates.php');

include('records.php');

include('lawtopics.php');

include('statistics.php');

include('linklist.php');

include('followups.php');

include('collaborators.php');

// ------------------------------------ User-Funktionen --------------------------------------------------------------

include('inbox.php');

include('account.php');

include('bills.php');

include('search.php');

include('notes.php');

include('dates.php');

// ------------------------------------ Steuerzentrale Funktionenaufruf ------------------------------------------

function Adminfuncs()
{
		global $aAdminfunc;
		global $iWhichfunction;
		if (($iWhichfunction < 0) || ($iWhichfunction > (sizeof($aAdminfunc) - 1))) {
				NoFunction();
		}
		// Undefinierter Funktionenaufruf
		call_user_func($aAdminfunc[$iWhichfunction]);
}

function Userfuncs()
{
		global $aUserfunc;
		global $iWhichfunction;
		if (($iWhichfunction < 0) || ($iWhichfunction > (sizeof($aUserfunc) - 1))) {
				NoFunction();
		}
		// Undefinierter Funktionenaufruf
		call_user_func($aUserfunc[$iWhichfunction]);
}

function NoFunction()
{
		Error('Undefinierter Zustand !');
		die;
}

include('selfcheck.php');

// ------------------------------------ Hier beginnt die main() -------------------------------------------------------

include('main.php');

?>
