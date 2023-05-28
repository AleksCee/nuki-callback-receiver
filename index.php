<?php
$file = './nuki.log'; # Logausgabe
$data = file_get_contents('php://input'); # Daten via RAW-Stream lesen so wie sie von der NUKI-Bridge kommen
if (!empty($data)){
	$json = json_decode($data, true); # Die daten kommen als JSON hier hier wird ein PHP-Objekt daraus erstellt.
	$json = array('time' => date('d.m.Y - H:i:s', time())) + $json; # Timestamp zu den Daten dazu faken

	if ($json['deviceType'] === 2 && $json['ringactionState']){ # ist der Type der Opener und wurde geklingelt 
		if (date('H') >= 7 && date('H') < 22){ # sind wir im Zeitfenster - hier 07:00 - 22:00 Uhr
			file_get_contents('http://nas.fritz.box:1880/ring'); # Ruf die URL in NodeRed auf.
            ## Hier ein Beispiel für einen Aufruf von https://github.com/jishi/node-sonos-http-api womit Sprache "Es hat an der Tür geklingelt" ausgeben wird.
            ## Dazu ist aber auch erfoderlich das Projekt node-sonos-http-api zu installieren, was hier nicht beschrieben wird.
			//file_get_contents('http://nas.fritz.box:5005/wohnzimmer/say/Es%20hat%20an%20der%20T%C3%BCr%20geklingelt/de');
			$json['triggered_action'] = 'Ring'; # zusätzlichen Flag setzten damit im Log zu erkennen ist, dass diese Aktionen getriggert wurde.
		}
	}

    # Prüfen ob in dem Event ein batteryCritical gesetzt wurde und wenn ja für welches Gerät
	if ($json['batteryCritical'] && !is_file($json['nukiId'].".status")){
		// {"time":"27.12.2019 - 06:31:08","deviceType":0,"nukiId":xxxxxxx,"mode":2,"state":4,"stateName":"locking","batteryCritical":false}
		switch($json['deviceType']){
			case 0:
				$schloss = 'Wohnungstür';
				break;
			case 2:
				$schloss = 'Haustür';
				break;
			default:
				$schloss = 'unbekannt';
		}
        # Mail senden und merken das die Mail für diese Gerät schon gesendet wurde. Wird hier an der nukiId festgemacht.
		$mailTo = 'emfänger@maildomain.tld'; # hier den Emfänger der Mail hinerlegen
		$mailFrom = 'absender@maildomain.tld'; # hier den Absender der Mail hinterlegen
		$mailSubject = '=?utf-8?B?'.base64_encode('Nuki-Lock Batterieinfo zu '. $schloss).'?=';
		$email = "Hallo!\n\nBei dem Gerät ". $schloss . " ist die Batterie aufgebraucht. Bitte wechseln.\n\nGruß, NAS";
		if(mail("$mailTo","$mailSubject","$email", "Mime-Version: 1.0\r\nContent-type: text/plain; charset=utf-8\r\nFrom:$mailFrom\r\n")){
			$json['mailSend'] = true;
			touch($json['nukiId'].".status");
		} else {
			$json['mailSend'] = false;
		}
	} elseif (!$json['batteryCritical'] && is_file($json['nukiId'].".status")){
		unlink($json['nukiId'].".status"); # hier wird geprüft ob der gemerkte Status wieder gelöscht werden kann, weil die Batterie getauscht wurde.
	}

    # Alles was hier bis hierher gesammelt habe, als JSON-String ins Log schreiben.
	file_put_contents($file, json_encode($json) ."\n", FILE_APPEND | LOCK_EX);
} else { # Ausgabe des Logs, wenn als Webseite aufgerufen.
	echo '<html><head><title>Nuki Logs</title></head><body>';
	echo implode('<br />', file($file));
	echo '</body></html>';
}
?>