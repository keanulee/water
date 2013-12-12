<?php
header('Content-type: text/plain');

$db = new SQLite3('db/data.db');
$msg = "";

if (!$db->exec('CREATE TABLE water_log (date INTEGER, time INTEGER, duration INTEGER);')) $msg .= $db->lastErrorMsg()."\n";

if (!$db->exec('CREATE TABLE settings (single_duration INTEGER, duration INTEGER, time INTEGER, sunday INTEGER, monday INTEGER, tuesday INTEGER, wednesday INTEGER, thursday INTEGER, friday INTEGER, saturday INTEGER, past_rain INTEGER, past_max_amount INTEGER, past_num_days INTEGER, present_rain INTEGER, future_rain INTEGER, future_num_days INTEGER);')) $msg .= $db->lastErrorMsg()."\n";
else $db->exec('INSERT INTO settings VALUES (30,20,300,0,1,0,0,1,0,0,0,20,5,1,1,1);');

if (!$db->exec('CREATE TABLE weather_log (date INTEGER PRIMARY KEY, precipMM REAL, temp_C INTEGER, weatherDesc TEXT, weatherCode INTEGER, weatherIconUrl TEXT);')) $msg .= $db->lastErrorMsg()."\n";

if (!$db->exec('CREATE TABLE current_condition (precipMM REAL, temp_C INTEGER, weatherDesc TEXT, weatherCode INTEGER, weatherIconUrl TEXT);')) $msg .= $db->lastErrorMsg()."\n";
else $db->exec('INSERT INTO current_condition VALUES (0, 0, "", 0, "");');

if (!$db->exec('CREATE TABLE device_status (status INTEGER);')) $msg .= $db->lastErrorMsg()."\n";
else $db->exec('INSERT INTO device_status VALUES (0);');

if ($msg != "") {
	echo $msg;
	echo "To reset database, delete db/data.db and rerun this script.\n";
}
else {
	echo "Done creating database.\n";
}

