# nuki-callback-receiver

Dieses kleine Script ist dazu da, um die von der NUKI-Bridge gesendeten callbacks zum empfangen, protokolieren und ggf. andere Aktionen auszuführen.

Infos zur Einrichtung eines Callbacks auf der Bridge findest Du hier: https://developer.nuki.io/page/nuki-bridge-http-api-1-13/4/#heading--callback

## Was macht das Script

- Callback Aufruf empfangen
- Einen Zeitstempel in die Daten "schmuggeln", damit man im Log auch sehen kann, wann der Callback ausgelößt wurde.
- Zeitfensterprüfen (7-22 Uhr) und bei Klingelaktion in dem Zeitfenster andere Aktionen ausführen (erweiterbar).
- Im Fall, dass Opener oder Schloss den Status "batteryCritical" melden, eine Mail senden. Diesen Status merken, so das keine Dopplung der Mails passieren. Der Status wird zurückgesetzt, wenn "batteryCritical" wieder ok ist.
- Die empfangenen Callback-Daten in eine Log-Datei speichern. (Hier zusätzliches Shell-Script für ein "logrotate") mit Link auf das aktuelle Log.
- Bei Aufruf des Scripts über einen Browser wird das aktuelle Log angezeigt.

## Wie richte ich es ein?

- Auf einem Webserver mit PHP einen Ordner anlegen und die index.php dort ablegen.  
  Beispiel: http://192.168.1.77/nuki/index.php die IP ersetzen mit dem des Webservers/NAS-Webstation.
- Der Webserver benötigt hier Lese-/Schreibrechte
- eMail-Adresse im Script anpassen.
- Aktionen im Script anpassen.
- PHP muss so konfiguriert sein, dass [file_get_contents](https://www.php.net/manual/de/filesystem.configuration.php#ini.allow-url-fopen) URLs aufrufen kann, sonst ggf. durch curl-Aufrufe ersetzen.
  Um das Logrotate Script nutzen zu können einmal eine leere Logdatei anlegen:

```
touch nuki_$(date +%Y%m).log
ln -s nuki_$(date +%Y%m).log nuki.log
```

So erhält man einen Link nuki.log der auf die Log-Datei vom aktuellen Monat zeigt z.B.:

`nuki.log -> nuki_202305.log`
Damit kann dann das Script rotate.sh Täglich um 23:55 Uhr anfgerufen werden und sorgt am Monatsende dafür, dass eine neue Datei für den Monat angelegt wird. In dem Script wird auch der aktuelle Tag als Ausgabe geliefert, was cron dann als Mail Ausgabe weiterleitet. Das Script sollte mit root-Rechten gestartet werden, damit es in der Lage ist die Rechte wieder auf den WEB-User zu ändern (z.B. bei einer Synology). Hier sollte im Fall der Synology bei der Aufgabe über den Aufgabenplaner das Mail-Protokoll aktiviert werden, damit man auch die Ausgabe des Scripts per Mail bekommt.

- Callback auf der Bridge [einrichten](https://developer.nuki.io/page/nuki-bridge-http-api-1-13/4/#heading--callback-add), der auf dieser Ordner bzw. die index.php als Ziel zugreift. Anleitung wie von NUKI beschrieben unter [Callback](https://developer.nuki.io/page/nuki-bridge-http-api-1-13/4/#heading--callback-add). Hier eignet sich z.B. [Postman](https://www.postman.com/) für die Konfiguration der Callbacks auf der Bridge. Es geht aber auch mit dem Browser, die Beispiele in der NUKI-Anleitung sind da sehr gut - nur URL und TOKEN austauschen und es sollte gehen. Zur Sicherheit nach dem add auch mal mit list die URLs prüfen die man bereits hinterlegt hat. Es nur 2 Callback-URLs und nur ohne SSL (https).

Zum Test dann einfach mal Klingeln oder das Schloss auf/zu machen. Dann sollten im Log Einträge wie:

```
{"time":"28.05.2023 - 14:19:47","deviceType":2,"nukiId":xxxxxxx,"mode":2,"state":1,"stateName":"online","batteryCritical":false,"ringactionTimestamp":"2023-05-28T12:19:32+00:00","ringactionState":true,"IFTTT_action":"Ring"}

{"time":"28.05.2023 - 14:41:53","deviceType":0,"nukiId":xxxxxxx,"mode":2,"state":3,"stateName":"unlocked","batteryCritical":false,"batteryCharging":false,"batteryChargeState":90,"doorsensorState":2,"doorsensorStateName":"door closed"}

{"time":"28.05.2023 - 14:42:02","deviceType":0,"nukiId":xxxxxxx,"mode":2,"state":3,"stateName":"unlocked","batteryCritical":false,"batteryCharging":false,"batteryChargeState":90,"doorsensorState":4,"doorsensorStateName":"door state unknown"}

{"time":"28.05.2023 - 14:43:25","deviceType":0,"nukiId":xxxxxxx,"mode":2,"state":3,"stateName":"unlocked","batteryCritical":false,"batteryCharging":false,"batteryChargeState":90,"doorsensorState":2,"doorsensorStateName":"door closed"}

{"time":"28.05.2023 - 14:48:34","deviceType":0,"nukiId":xxxxxxx,"mode":2,"state":1,"stateName":"locked","batteryCritical":false,"batteryCharging":false,"batteryChargeState":90,"doorsensorState":2,"doorsensorStateName":"door closed"}
```

zu lesen sein. Hier muss man dann etwas ausprobieren wie man damit umgeht - in meinem Script sind 2 Beispiele. Eines reagiert auf die Statusänderung von "batteryCritical" und das andere auf "ringactionState"

Ein aktiviertes RingToOpen kann wie folgt erkannt werden "stateName":

```
{"time":"27.05.2023 - 20:26:05","deviceType":2,"nukiId":xxxxxxx,"mode":2,"state":3,"stateName":"rto active","batteryCritical":false,"ringactionTimestamp":"2023-05-27T13:42:11+00:00","ringactionState":false}
```

Man könnte hier also, wenn auf ein "rto active" ein ringactionState: true folgt, eine personalisiert Aktion ausführen, dass jemand der gerade in die Home-Zone kam, geklingelt hat. Ähnlich wie beim Batterie-Status einen Merker bei "rto active" setzen, bei anderen Status-Änderungen zu dem device-Type wieder löschen, und wenn bei gemerktem Status eine Ring-Aktion passiert, einen Auslöser starten.
