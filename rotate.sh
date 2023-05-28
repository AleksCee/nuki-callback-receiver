#!/bin/bash
if test $(id -ru) -ne 0;then echo "Nur als root ausfuehren";exit;fi
cd $(dirname $0) # in das verzeichnis wechseln wo das Script gestartet wurde

# Den heutigen Tag aus dem Log raussuchen und ausgeben, so das Cron oder der Synology-Aufgabenplaner es per Mail sendet.
grep "$(date '+%d.%m.%Y')" ./nuki.log

# Wenn wir am letzten Tag im Monat sind, dann neue Log-Datei für den nächsten Monat anlegen und nuki.log drauf hin zeigen lassen.
if [ $(date +%d) -eq $(date +%d -d "-$(date +%d) days +1 month") ];then
	touch nuki_$(date +%Y%m -d "+1 day").log
	chown http nuki_$(date +%Y%m -d "+1 day").log
	rm nuki.log
	ln -s nuki_$(date +%Y%m -d "+1 day").log nuki.log
fi