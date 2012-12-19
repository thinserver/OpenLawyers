<?php

// ------------------------------ SQlite Fehler abfangen ! ---------------------------------------------------------
// Kann auch als Wrapper für andere SQL-Datenbanken verwendet werden 
// -----------------------------------------------------------------------------------------------------------------

function secure_sqlite_array_query($hHandle, $sFunktion)
{
		$aErgebnis = @sqlite_array_query($hHandle, $sFunktion);
		if (sqlite_last_error($hHandle) != 0) {
				Error("SQLite Error: " . sqlite_error_string(sqlite_last_error($hHandle)));
				secure_sqlite_close($hHandle);
				die;
		}
		return ($aErgebnis);
}

function secure_sqlite_query($hHandle, $sFunktion, &$error = null)
{
		$error     = null;
		$aErgebnis = @sqlite_query($hHandle, $sFunktion);
		if (sqlite_last_error($hHandle) != 0) {
				if (func_num_args() < 3) {
						Error("SQLite Error: " . sqlite_error_string(sqlite_last_error($hHandle)));
						secure_sqlite_close($hHandle);
						die;
				} else {
						$error = sqlite_error_string(sqlite_last_error($hHandle));
				}
		}
		return ($aErgebnis);
}

function secure_sqlite_open($sDatabasefilename, &$error = null)
{
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
		return ($iHandle);
}

function secure_sqlite_close($hHandle)
{
		$aErgebnis = @sqlite_close($hHandle);
		if ($aErgebnis != 0) {
				// SQLite_OK = 0
				Error("SQLite Error: Sqlite_close Error !");
				die;
		}
		return ($aErgebnis);
}
