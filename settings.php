<?php

// ------------------------------------ Startvariablen ---------------------------------------------------------------
// Verzeichnisse müssen aus Sicherheitgründen außerhalb des www-Root-Pfades liegen
// zugriff nur via PHP, nicht via WebServer

$sGuipath      = 'html/';
$sUsergui      = "user/";
$sAdmingui     = "admin/";
$sTmp          = "../tmp/";
$sDatabasepath = '../db/';
$sDatabase     = '../db/akten.opl';
$sDBName       = "akten.opl";
$sFvpath       = '../fv/';
$sAktenpath    = '../akten/';
$sLogpath      = '../logs/';

$aStatfiles = array(
		'erledigt.xml',
		'aktenbearbeiter.xml',
		'aktenbearbeitererledigt.xml',
		'fachgebiete.xml',
		'umsatzfachgebiete.xml',
		'umsatzakte.xml',
		'umsatzbearbeiter.xml',
		'arbeitbearbeiter.xml'
);

// Funktionen werden über ein Array aufgerufen, nicht über den direkten Namen, um Hacking vorzubeugen

$aAdminfunc = array(
		'NoFunction',
		'Benutzer',
		'Sicherheit',
		'LogFile',
		'AZfestlegen',
		'FvBearbeiten',
		'RGbearbeiten',
		'Statistik',
		'Logout',
		'Linklist',
		'WVTypen',
		'BetArt'
);
$aUserfunc  = array(
		'NoFunction',
		'OpenAkte',
		'CreateAkte',
		'Wiedervorlagen',
		'Adressen',
		'Termine',
		'Formatvorlagen',
		'Linkliste',
		'Logout',
		'CloseAkte',
		'AktenVita',
		'Beteiligte',
		'AktenWV',
		'AktenBogen',
		'Posteingang',
		'Postausgang',
		'Stammdaten',
		'Kosten',
		'ActAkte',
		'DokSuche',
		'Postbuch',
		'Kurzvermerk'
);
