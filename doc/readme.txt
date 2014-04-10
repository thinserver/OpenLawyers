Lizenz und Disclaimer

Copyright (c) 2009-2014 
LastCoderStanding <lastcoder@users.sourceforge.net>, 
Matthias Bock <mail@matthiasbock.net> 

Die Software unterliegt der GPLv3. Der Lizenztext liegt dem Paket bei.

Die Software ist ein Hobby-Projekt, das sich zwar im persönlichen
Einsatz bewährt hat - Fehler und deren Folgen können aber in einem
solchen Projekt nicht ausgeschlossen werden; es ist zu bezweifeln,
dass der BGH eine Anwaltshaftung durch Vertrauen auf diese Software
verneinen würde ! 

Was ist OpenLawyer's ?

OpenLawyer's ist eine sowohl für den privaten wie auch geschäftlichen
Bereich freie Aktenverwaltung, die der Struktur nach auf die
Bedürfnisse von Rechtsanwälten ausgerichtet ist. OpenLawyer's ist
technisch gesehen ein über den Web-Browser eines Rechners verwendbares
Front-End für eine SQL-basierte Datenbank, die mithilfe eines
PHP-Scriptes über einen WebServer angesprochen wird. Um den Nutzer
nicht zu große Installationsschwierigkeiten zu bereiten, nutzt die
aktuelle Version die z.T. in PHP (5.0) integrierte oder ggf. als
Extension zu ladende SQLite2-Bibliothek.

Sowohl PHP als auch SQLite (Lizenzbedingungen siehe Herstellerseiten)
sind frei verwendbar. WebServer gibt es ebenfalls als
Freeware/OpenSource.

Wozu gibt es OpenLawyer's ?

In fast jedem Softwarebereich gibt es freie oder
OpenSource-Software. Der Autor nutzt selbst ausschließlich OpenSource
und dafür gibts es eine Dankeschön an die Community mit diesem Werk.

Was kann OpenLawyer's ?

OpenLawyer's ist dem Minimalismus verschrieben. Dies bedeutet, dass
die Software nur das anbietet, was sich im Alltag für den Autor als
zwingend notwendig ergeben hat. Für die übrigen Bedürfnisse gibt es in
der Regel eine Vielzahl an Software, die aufgrund ihrer
Spezialisierung die gewünschten Aufgaben komfortabler lösen kann. 

Darüber hinaus stehen mit der Entwicklung des Internet reichhaltige
Datenbanken, z.B. mit Rechtsprechung oder Gerichtsverzeichnissen, zur
Verfügung.

Folgende, nicht abschließende Liste an Funktionen bietet OpenLawyer's: 
* beliebige Nutzerzahl (nur begrenzt durch den Server)
* beliebig große Anzahl an Akten (nur begrenzt durch den Server)
* vielfältige Konfigurationsmöglichkeiten für den Administrator bis
  zur äußeren Erscheinung (Skin) 
* Akten anlegen / archivieren / suchen
* Dokumentenverwaltung /-suche
* Aktenspezifische Beteiligtenzuordnung
* Posteingangs- und -ausgangsbuch
* Wiedervorlagen
* Zuordnung von fortlaufenden Rechnungsnummern und Auslagenkontrolle
* Formatvorlagenaufruf
* Erweiterbare Internet-Linkliste
* Statistikfunktionen

Dabei bedarf es lediglich der Einrichtung eines (Web)"Servers". Die
Arbeitsplatzrechner greifen mit einem Web-Browser auf die Software zu.

Und welche Nachteile gibt es ?

Aufgrund der Struktur als Web-Frontend gibt es gerade im Bereich der
Aktenvita und der Formatvorlagen Funktionseinschränkungen. So gibt es
bisher (aber in Planung) keine Möglichkeit, dass die aktenspezifischen
Daten wie Aktenzeichen, Bearbeiter, Gegner etc. bei Aufruf der
Formatvorlage automatisch eingefügt werden. 

Das Speichern von Dokumenten zur Aktenvita bedarf des Umweges über die
Upload-Funktion des Browsers, so dass bei Erstellung von Schriftsätzen
der Weg über ein lokales Verzeichnis gegangen werden muss. Seit
OpenLawyers 1.0 gibt es eine Kurzvermerkfunktion, um Vermerke
unmittelbar zur Akte zu speichern.

Beteiligte einer Akte können ausschließlich aus dem zentral zu
pflegenden Adressbuch zugeordnet werden, um unnötige Doppeleinträge zu
vermeiden. Dadurch sind bei der Beteiligtenzuordnung ein paar Schritte
mehr, als vielleicht von anderer Software bekannt,nötig.

Da es sich um freie, unentgeltlich abgegebene Software handelt, hängt
die Unterstützung bei Schwierigkeiten und Problemen ganz von einer
Community und der Lust und Zeit des Autors ab.

Wie ist OpenLawyer's abgesichert ?

Jeder Nutzer muss vom Administrator mit Namen und Passwort eingetragen
werden. Darüber hinaus muss zwingend die IP-Adresse der zum Zugriff
zugelassenen Rechner des Intranets freigegeben werden (in Planung,
diese Beschränkung für HomeOffice@Internet aufzuheben). Bei
fehlerhaftem Login oder falscher IP-Adresse wird der Zugang gesperrt.

Das Backup der Daten ist durch eine einfache Kopieroperation möglich,
da lediglich die von OpenLawyer's angelegten Verzeichnisse auf ein
Sicherungsmedium kopiert werden müssen. Sämtliche Schriftsätze zu
einer Akte werden in separaten Verzeichnissen im Original abgelegt, so
dass selbst bei einem Totalausfall der Datenbank ein Zugriff auf die
Schriftsätze möglich bleibt. Die Datensicherheit der Software hängt im
Wesentlichen von einer ordnungsgemäßen Konfiguration des als Server
einzusetzenden Rechners ab ! Regelmäßige Backups verstehen sich von
selbst.

Installation

Eine Installation im herkömmlichen Sinne, d.h. über ein ausführbares Programm, findet nicht statt.
Unerfahrene Nutzer sollten fachmännische Hilfe in Anspruch nehmen oder
mit entsprechender Einarbeitungszeit rechnen!

Da OpenLawyer's aus HTML-Dateien und einem PHP-Script besteht, müssen
Sie zur Inbetriebnahme wie folgt vorgehen: 
* Wählen Sie einen Rechner im Intranet
* Installieren Sie PHP (ab 5.0) mit SQLite2-Unterstützung
* Installieren Sie einen WebServer mit PHP-Unterstützung (empfohlen: Lighttpd)
* Entpacken Sie das gedownloadete OpenLawyer's-Paket in einem frei
  gewählten Verzeichnis (für das stetige Anwachsen der Datenbank
  sollte eine große Festplatte gewählt werden!)
* Konfigurieren Sie den WebServer so, dass das Root-Verzeichnis auf das OpenLawyer's-
  Verzeichnis "/www/" verweist
* Rufen Sie über den WebBrowser die Adresse 127.0.0.1/olclient.php auf
* OpenLawyer's versucht nun, die notwendigen Verzeichnisse unterhalb
  des "/www/" Verzeichnisses für Akten, Datenbank etc. anzulegen
  (Beachten Sie die betriebssystemspezifische Rechteverwaltung zum
  Zugriff; nur der WebServer sollte Zugriff auf das
  OpenLawyer's-Verzeichnis haben)
* Nach erfolgreicher Initialisierung können Sie sich unter
  127.0.0.1/olclient.php als Administrator anmelden 
  Name: Administrator
  PW: sysop

Ändern Sie zuerst das Passwort des Administrators ! Konfigurieren Sie
sodann IP-Adressen und Benutzer. Die restlichen
Konfigurationsmöglichkeiten ergeben sich aus dem Menü.

Allgemeine Sicherheitshinweise

Durch die Verlagerung der Datenbank und der Aktenschriftsätze
unterhalb des Root- Verzeichnisses des WebServers soll verhindert
werden, dass unberechtigte Dritte darauf Zugriff erhalten.

Den gleichen Zweck verfolgt die White-List von zugriffberechtigten
IP-Adressen. Die Datensicherheit und die Abwehr unberechtigter
Zugriffe kann aber nur durch einen fachmännisch abgesicherten Rechner
gewährleistet werden!
