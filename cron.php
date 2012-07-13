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

echo "\n";
echo date("m/d H:i");
echo "\t";

if ($scheduler->getNextRunTime()) {
	echo "Next run: ".date("m/d H:i",$scheduler->getNextRunTime())." (".(int)(($scheduler->getNextRunTime() - time())/60)." mins)\t";
}
else echo "Not scheduled to water\t";

//only update weather every hour between :00 and :09 inclusive
if (date("i") < 10 || $_GET['update_weather']) 
{
	$weather->updateWeatherSource();
	echo "Updated weather";
}

echo "\n\t\t";

if ($scheduler->getSetting('past_rain')) {
	echo "Past rain: 1-".$weather->didRainAmount($scheduler->getSetting('past_num_days'), $scheduler->getSetting('past_max_amount'));
}
else echo "Past rain:   ";
echo "\t";

if ($scheduler->getSetting('present_rain')) {
	echo "Present: 1-".$weather->isRaining();
}
else echo "Present:   ";
echo "\t";


if ($scheduler->getSetting('future_rain')) {
	echo "Future: 1-".$weather->willRain($scheduler->getSetting('future_num_days'));
}
else echo "Future:   ";
echo "\t";

echo "Status: ".$device->getStatus();
echo "\t";


//Call runCycle if within 15 mins and weather contraints are met
if ($scheduler->getNextRunTime() && ($scheduler->getNextRunTime() - time()) <= (60*15)) {
	if (!$scheduler->getSetting('past_rain') || !$weather->didRainAmount($scheduler->getSetting('past_num_days'), $scheduler->getSetting('past_max_amount'))) {
		if (!$scheduler->getSetting('present_rain') || !$weather->isRaining()) {
			if (!$scheduler->getSetting('future_rain') || !$weather->willRain($scheduler->getSetting('future_num_days'))) {
				if ($device->isReady()) {
					echo "Calling runCycle(".$scheduler->getSetting('duration');
					echo ",".(int)(($scheduler->getNextRunTime() - time())/60).")";
					$device->runCycle($scheduler->getSetting('duration'), (int)(($scheduler->getNextRunTime() - time()) / 60));
				}
			}
		}
	}
}

?>