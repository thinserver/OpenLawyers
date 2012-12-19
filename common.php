<?php

// Entschärft Benutzereingaben - nur A..Z, 0..9 erlaubt, um Codeinjection zu verhindern

function MakeSafe($eingabe)
{
		$sReplace = '';
		return preg_replace("/[\"\'<>\\\{\}\$#]/", $sReplace, $eingabe);
}

function NormAZ($aAZ)
{
		if (is_array($aAZ)) {
		}
}

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