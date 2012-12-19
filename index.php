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

// Speichert die mit POST übergebenen Daten, um sie beim Neuaufbau der Seite wieder einzutragen

// isset bei $_POST ist immer gegeben, sobald <form> abgesandt wird und <input> einen namen hat, unabhängig davon, was oder ob etwas eingetragen wurde
// AUSNAHME: bei select o.st isset nur gegeben, wenn etwas ausgewählt wurde aus der Liste ! Bei Button ebenfalls

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

// ------------------------------------ Funktionen für Sicherheit & Session ----------------------------------------------------------------

include('gui.php');

include('common.php');

include('recentchanges.php');

include('sql.php');

//// ------------------------------------ MAIN ----------------------------------------------- ////

include('ipblock.php');
include('ipfilter.php');
include('login.php');

// ---------------------------------------- SVG generation -------------------------------------------------------------

include('svg.php');

// ---------------------------------------- Admin functions -------------------------------------------------

include('install.php');

include('logs.php');

include('users.php');

// ------------------------------------ User functions --------------------------------------------------------------

include('inbox.php');

include('account.php');

include('bills.php');

include('search.php');

include('notes.php');

include('dates.php');

include('templates.php');

include('records.php');

include('lawtopics.php');

include('statistics.php');

include('linklist.php');

include('followups.php');

include('collaborators.php');

// ------------------------------------ Run! -------------------------------------------------------

include('selfcheck.php');

include('main.php');

?>
