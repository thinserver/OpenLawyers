<?PHP

// Defragmentiert die Datenbank. Wird in einer späteren Version im Administratorpanel
// als Funktion angeboten

$sDatabasepath='../db/';
$sDb='akten.opl';
$sDatabase=$sDatabasepath.$sDb;

function Error($Serrormsg)
{
	print("<html><head><script language='JavaScript'>function err() { alert('$Serrormsg'); }</script><body onload='err()'></body></html>");
}

echo "Defrag OpenLawyer's Database <br/>";

$hDatabase=@sqlite_open($sDatabase,0777,$sError);
if ($hDatabase==FALSE) 
{
	Error("SQLite Error: ".$sError." ! Call Administrator !");
	die;
} 

sqlite_exec($hDatabase,"VACUUM;");

sqlite_close($hDatabase);
echo "Done.<br/>";
?>


