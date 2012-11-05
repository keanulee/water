<?php
header('Content-type: text/plain');

include 'config.php';

date_default_timezone_set($timezone);

include 'app/device.php';
include 'app/scheduler.php';
include 'app/weather.php';

$device = new Device($ipaddress, $port);
$scheduler = new Scheduler();
$weather = new Weather($location,$api_key);

echo date("m/d H:i");
echo "\t";

$status = $device->getStatus();
echo $status[0];

//only print details and update weather every hour
if (date("i") < 5 || $_GET['update_weather']) {
	echo "\t";
	
	if ($scheduler->getNextRunTime()) {
		echo date("m/d H:i",$scheduler->getNextRunTime());
		echo " (";
		echo (int)(($scheduler->getNextRunTime() - time())/60);
		echo "m)\t";
	}
	else echo "No water scheduled \t";

	if ($weather->updateWeatherSource()) echo "Update OK\t";
	else echo "Unavailable\t";

	if ($scheduler->getSetting('past_rain')) {
		echo "1-";
		echo $weather->didRainAmount($scheduler->getSetting('past_num_days'), $scheduler->getSetting('past_max_amount'));
		echo "\t";
	}
	else echo "0-\t";

	if ($scheduler->getSetting('present_rain')) {
		echo "1-";
		echo $weather->isRaining();
		echo "\t";
	}
	else echo "0-\t";


	if ($scheduler->getSetting('future_rain')) {
		echo "1-";
		echo $weather->willRain($scheduler->getSetting('future_num_days'));
	}
	else echo "0-";
}

//Call runCycle if within 15 mins and weather contraints are met
if ($scheduler->getNextRunTime() && ($scheduler->getNextRunTime() - time()) <= (60*15)) {
	if (!$scheduler->getSetting('past_rain') || !$weather->didRainAmount($scheduler->getSetting('past_num_days'), $scheduler->getSetting('past_max_amount'))) {
		if (!$scheduler->getSetting('present_rain') || !$weather->isRaining()) {
			if (!$scheduler->getSetting('future_rain') || !$weather->willRain($scheduler->getSetting('future_num_days'))) {
				if ($device->isReady()) {
					echo "\trunCycle(";
					echo $scheduler->getSetting('duration');
					echo ",";
					echo (int)(($scheduler->getNextRunTime() - time())/60);
					echo ")";
					
					$device->runCycle($scheduler->getSetting('duration'), (int)(($scheduler->getNextRunTime() - time()) / 60));
				}
			}
		}
	}
}

echo "\n";

?>