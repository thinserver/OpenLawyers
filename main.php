<?php

// error_reporting(E_ALL);
// ini_set("display_errors", TRUE);

// für SQlite - benötigt TMP Verzeichnis mit Zugriffsrechten

putenv("TMP=" . $sTmp);

session_start();

// kommt jemand ohne Session und ohne Post ? Dann erstmaliger Aufruf, Login !

if (empty($_SESSION) && empty($_POST)) {
		CheckIntegrity();
		$aParam['_display_'] = 'none';
		$aParam['_error_']   = '';
		ShowGui('login.html', $aParam);
}

// POST-Codes werden vorher von diversen Sonderzeichen befreit ...

if (!empty($_POST)) {
		$aPost = $_POST;
		unset($_POST);
		for ($t = 0; $t < sizeof($aPost); $t++) {
				$sKey   = key($aPost);
				$sValue = current($aPost);
				if (is_string($sValue)) {
						$sValue = rtrim($sValue);
						$sValue = ltrim($sValue);
						$sValue = MakeSafe($sValue);
				}
				
				$aPost[$sKey] = $sValue;
				next($aPost);
		}
		$_POST = $aPost;
}

// kommt jemand mit einer Session ? Wenn ohne, aber mit POST, kann es nur Loginanfrage sein

if (!isset($_SESSION["benutzer"])) {
		Login($_POST);
}

// eine Session ist gesetzt ...

// stimmt die Session-IP mit der aktuellen überein ? 

if (ip2long(getenv('REMOTE_ADDR')) != $_SESSION['ipadresse']) {
		unset($_SESSION);
		session_destroy();
		$aParam['_display_'] = 'none';
		$aParam['_error_']   = '';
		ShowGui('login.html', $aParam);
}

// Session abgelaufen (mehr als 24h her) ?

if ((date('U') - $_SESSION['time']) > 86400) {
		unset($_SESSION);
		session_destroy();
		$aParam['_display_'] = 'none';
		$aParam['_error_']   = '';
		ShowGui('login.html', $aParam);
}

// okay, alles in Ordnung .. $_POST-Codes = konkrete Funktion, $_GET-Codes Funktionsaufruf / GUI-Aufruf

if (empty($_GET) && empty($_POST)) {
		ShowGui($_SESSION['panel'], null);
}

// $_GET übersandt - dient nur zum Aufruf von Funktionen aus Menuspalte und für GUIs

if (!empty($_GET)) {
		if (isset($_GET['function'])) {
				$iWhichfunction = (int) $_GET['function'];
				if ($_SESSION['benutzer'] == 'Administrator') {
						Adminfuncs();
				} else {
						Userfuncs();
				}
		}
		
		if (isset($_GET['gui'])) {
				if ($_GET['gui'] == "status.html") {
						$aParam['_benutzer_'] = $_SESSION['benutzer'];
						ShowGui(MakeSafe($_GET['gui']), $aParam);
				} else {
						ShowGui(MakeSafe($_GET['gui']), null);
				}
		}
		
		header("HTTP/1.1 404 Not Found");
		ShowGui('404.html', null);
		die;
}

// okay, es kam POST Informationen -> jede GUI übermittelt Functionscode zur Auswertung ...

if (isset($_POST['function'])) {
		$iWhichfunction = (int) $_POST['function'];
} else {
		$iWhichfunction = 0;
}

if ($_SESSION['benutzer'] == 'Administrator') {
		Adminfuncs();
} else {
		Userfuncs();
}

die;
