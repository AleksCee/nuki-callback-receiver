#!/bin/bash

# Build-In Server starten
nohup php -d 'date.timezone=Europe/Berlin' -S localhost:9000 > phpd.log 2>&1 &
# PID merken
PHP_SERVER_PID=$!

sleep 1

# Tests
## 
curl -X POST -H 'Content-Type: application/json' \
--json '{"deviceType":0,"nukiId":1234567,"mode":2,"state":1,"stateName":"locked","batteryCritical":false,"batteryCharging":false,"batteryChargeState":90,"doorsensorState":2,"doorsensorStateName":"door closed"}' \
http://localhost:9000/

tail -n1 nuki.log

# Build-In Server stoppen
kill $PHP_SERVER_PID
