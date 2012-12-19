<?php

// ------------------------------ Funktionen, um SQlite-Fehler abzufangen ---------------------------------------
// Kann auch als Wrapper für andere SQL-Datenbanken verwendet werden 
// ----------------------------------------------------------------------------------------------------------------------------------------

$SQLite = false;

function OpenDB($sDatabasefilename, &$error = null)
{
		global $SQLite;
		if ($SQLite) {
			$error   = null;
			$iHandle = @sqlite_open($sDatabasefilename, 0777, $sError);
			
			// Falls Errormeldung durchgereicht werden soll an zusätzlichen Parameter
			// zusätzlicher Parameter wird durch Referenz angesprochen
			
			if ($iHandle == false) {
					if (func_num_args() < 2) {
							Error("SQLite Error: " . $sError);
							die;
					} else {
							$error = $sError;
					}
			}
		} else {
			global $sMySQL;
			$link = mysql_connect('localhost', 'kanzleiserver', 'kanzlei');
			if (!$link) {
					Error('MySQL-Verbindung schlug fehl: ' . mysql_error());
					}
			$iHandle = mysql_select_db('OpenLawyers', $link);
			if (!$iHandle) {
					Error ('Kann die MySQL-Datenbank '.$db.' nicht benutzen : ' . mysql_error());
					}
			}
		return ($link);
}

function SQLQuery($hHandle, $sFunktion, &$error = null)
{
		global $SQLite;
		if ($SQLite) {
			$error     = null;
			$aErgebnis = @sqlite_query($hHandle, $sFunktion);
			if (sqlite_last_error($hHandle) != 0) {
					if (func_num_args() < 3) {
							Error("SQLite Error: " . sqlite_error_string(sqlite_last_error($hHandle)));
							CloseDB($hHandle);
							die;
					} else {
							$error = sqlite_error_string(sqlite_last_error($hHandle));
					}
			}
		} else {
			// Führe Abfrage aus
			$result = mysql_query($sFunktion, $hHandle);
			$aErgebnis = mysql_fetch_array($result, MYSQL_BOTH);
			mysql_free_result($result);
			}
		return ($aErgebnis);
}

function SQLArrayQuery($hHandle, $sFunktion)
{
		global $SQLite;
		if ($SQLite) {
			$aErgebnis = @sqlite_array_query($hHandle, $sFunktion);
			if (sqlite_last_error($hHandle) != 0) {
					Error("SQLite Error: " . sqlite_error_string(sqlite_last_error($hHandle)));
					CloseDB($hHandle);
					die;
			}
		} else {
			$aErgebnis = SQLQuery($hHandle, $sFunktion);
			}
		return ($aErgebnis);
}

function CloseDB($hHandle)
{
		global $SQLite;
		if ($SQLite) {
			$aErgebnis = @sqlite_close($hHandle);
			if ($aErgebnis != 0) {
					// SQLite_OK = 0
					Error("SQLite Error: Sqlite_close Error !");
					die;
			}
		} else {
			mysql_close($hHandle);
			}
		return ($aErgebnis);
}
